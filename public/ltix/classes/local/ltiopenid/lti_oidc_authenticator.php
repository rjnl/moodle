<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace core_ltix\local\ltiopenid;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\context\collection\substitution_context;
use core_ltix\local\lticore\message\lti_message;
use core_ltix\local\lticore\message\payload\lti_1px_payload_converter;
use core_ltix\local\lticore\message\substitution\factory\variable_substitutor_factory;
use core_ltix\local\lticore\repository\tool_registration_repository;
use core_ltix\local\lticore\token\lti_token;

/**
 * Class validating LTI 1.3 3rd party login requests, providing the lti message that can be posted to the tool's redirect URI.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lti_oidc_authenticator {

    /**
     * Constructor.
     *
     * @param lti_user_authenticator_interface $userauthenticator user authenticator which performs user auth.
     * @param tool_registration_repository $registrationrepository a repository used to fetch the tool registration.
     * @param lti_1px_payload_converter $payloadconverter a converter instance to turn legacy payload data into 1p3 JWT claims.
     * @param variable_substitutor_factory $substitutorfactory factory to get a parameter substitutor instance.
     * @param array $jwks array representation of the JWKS JSON, used to decode the JWT in the auth request.
     */
    public function __construct(
        protected lti_user_authenticator_interface $userauthenticator,
        protected tool_registration_repository     $registrationrepository,
        protected lti_1px_payload_converter        $payloadconverter,
        protected variable_substitutor_factory     $substitutorfactory,
        protected array                            $jwks // TODO should be a key object that contains jwks + private key info.
    ) {
    }

    /**
     * Validate the OIDC login and return the lti_message instance to post to the tool.
     *
     * @param array $authrequestdata the auth request data.
     * @return lti_message the lti message instance which can then be posted to the tool's launch redirect endpoint.
     */
    public function authenticate(array $authrequestdata): lti_message {

        // Retrieve the JWT from the lti_message_hint and parse into an lti_token.
        try {
            $launchjwt = $authrequestdata['lti_message_hint'] ?? '';
            $launchtoken = lti_token::from_jwt_with_keyset($launchjwt, $this->jwks);
        } catch (\Exception $e) {
            throw new lti_exception(
                errorcode: 'Error decoding lti_message_hint JWT: '.$launchjwt,
                previous: $e
            );
        }

        $toolregistrationid = $launchtoken->get_claim('tool_registration_id');
        $toolconfig = $this->registrationrepository->get_by_id($toolregistrationid);
        if ($toolconfig === null) {
            throw new lti_exception('Cannot find registration id: '.$toolregistrationid);
        }

        $this->validate_auth_request($authrequestdata, $toolconfig);

        // Will throw if the user is not auth'd correctly, or if the login hint doesn't match the user, etc.
        $ltiuser = $this->userauthenticator->authenticate($toolconfig, $authrequestdata['login_hint']);

        // Perform final substitution of custom claim properties using the data present in the auth'd user.
        // User data isn't present at launch initiation time, so this final substitution of claims needs to be done here,
        // in case any custom param property uses a user-centric substitution param.
        $launchtoken = $this->resolve_substitution(
            $launchtoken,
            $ltiuser
        );

        // Format + add the new user claims to the token.
        $launchtoken = $this->format_user_claims($launchtoken, $ltiuser);

        // Nonce is opaque to the platform and must be returned as it was sent in the auth request.
        $launchtoken->add_claim('nonce', $authrequestdata['nonce']);

        // Obtain the private key for signing the JWT.
        $privatekey = jwks_helper::get_private_key();

        return new lti_message(
            $authrequestdata['redirect_uri'],
            [
                ...(isset($authrequestdata['state']) ? ['state' => $authrequestdata['state']] : []),
                'id_token' => $launchtoken->to_jwt(
                    privatekey: $privatekey['key'], // TODO once passed in, use the key object to sign.
                    kid: $privatekey['kid']
                )
            ]
        );
    }

    /**
     * Format the user claims as JWT claims and add them to the launch token.
     *
     * @param lti_token $launchtoken the token to add to.
     * @param lti_user $ltiuser the LTI authenticated user.
     * @return lti_token the updated launch token.
     */
    private function format_user_claims(lti_token $launchtoken, lti_user $ltiuser): lti_token {

        foreach ($this->payloadconverter->params_to_claims($ltiuser->get_unformatted_userdata()) as $name => $value) {
            if (!is_null($value)) {
                $launchtoken->add_claim($name, $value);
            }
        }
        return $launchtoken;
    }

    /**
     * Perform substitution of custom params which may include user-centric substitution variables.
     *
     * @param lti_token $launchtoken the launch token in which the custom claims reside.
     * @param lti_user $ltiuser the LTI authenticated user.
     * @return lti_token the updated launch token.
     */
    private function resolve_substitution(lti_token $launchtoken, lti_user $ltiuser): lti_token {

        $customparams = $launchtoken->get_claim(\core_ltix\constants::LTI_JWT_CLAIM_PREFIX.'/claim/custom') ?? [];
        $substitutionhandler = $this->substitutorfactory->get_for_oidc_auth();
        $substitutedcustomparams = $substitutionhandler->substitute(
            $customparams,
            substitution_context::for_auth($ltiuser->get_unformatted_userdata())
        );
        $launchtoken->add_claim(\core_ltix\constants::LTI_JWT_CLAIM_PREFIX.'/claim/custom', $substitutedcustomparams);
        return $launchtoken;
    }

    /**
     * Validation of the authentication request.
     *
     * @param array $requestdata the request payload data.
     * @param \stdClass $toolconfig the tool registration
     * @return void
     * @throws lti_exception in the case of any validation errors.
     */
    private function validate_auth_request(array $requestdata, \stdClass $toolconfig): void {
        // Validation of the following, per the spec (https://www.imsglobal.org/spec/security/v1p1#step-2-authentication-request):
        // - scope - must be 'openid'
        // - response_type - must be 'id_token'
        // - response_mode - must be 'form_post'
        // - prompt - must be 'none'
        // - nonce - is required
        // - client_id must match the client id associated with the given registration.
        // - NO: login_hint - must match user who started the launch - this is done in the user_authenticator.
        // - redirect_uri - must match valid, stored redirect URI (from tool config).

        if (($scope = $requestdata['scope'] ?? '') !== 'openid') {
            throw new lti_exception("Invalid scope. scope: $scope. Must be 'openid'.");
        }
        if (($responsetype = $requestdata['response_type'] ?? '') !== 'id_token') {
            throw new lti_exception("Invalid response_type. response_type: $responsetype. Must be 'id_token'.");
        }
        if (($responsemode = $requestdata['response_mode'] ?? '') !== 'form_post') {
            throw new lti_exception("Invalid response_mode. response_mode: $responsemode. Must be 'form_post'.");
        }
        if (($prompt = $requestdata['prompt'] ?? '') !== 'none') {
            throw new lti_exception("Invalid prompt. prompt: $prompt. Must be 'none'.");
        }
        if (empty($requestdata['nonce'])) {
            throw new lti_exception("Invalid nonce. nonce is a required field and cannot be empty.");
        }

        $clientid = $requestdata['client_id'] ?? '';
        if (empty($clientid) || $toolconfig->tool->clientid !== $clientid) {
            throw new lti_exception("Invalid client_id. client_id: $clientid. Does not match the tool registration value.");
        }

        $redirecturi = $requestdata['redirect_uri'] ?? '';
        $uris = array_map('trim', explode("\n", $toolconfig->config->redirectionuris));
        if (!in_array($redirecturi, $uris)) {
            throw new lti_exception("Invalid redirect_uri. redirect_uri: $redirecturi. "
                ."Must match a redirect URI on the tool registration.");
        }
    }
}
