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

namespace core_ltix\local\lticore\message\launchrequest\builder\v1p3;

use core_ltix\constants;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\lti_message;
use core_ltix\local\lticore\token\lti_token;
use core_ltix\local\ltiopenid\jwks_helper;

/**
 * Base class supporting creation of the initiate launch request for LTI 1p3 messages.
 *
 * Subclass this to create specific message type builders.
 *
 * {@link https://www.imsglobal.org/spec/security/v1p1#step-1-third-party-initiated-login.}
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class v1p3_launch_request_builder {

    /**
     * Build a launch message.
     *
     * Note: For the value of $toolconfig, callers currently MUST pass in config as returned by the class:
     * {@see \core_ltix\local\lticore\repository\tool_registration_repository::class)}.
     *
     * @param \stdClass $toolconfig the tool configuration.
     * @param string $messagetype the string message type.
     * @param string $issuer the issuer
     * @param string $targetlinkuri
     * @param string $loginhint
     * @param array $roles any LTI roles the user should have
     * @param array $extraclaims any optional parameters, which will vary depending on the message type being implemented.
     * @return lti_message
     */
    final protected function build(
        \stdClass $toolconfig,
        string $messagetype,
        string $issuer,
        string $targetlinkuri,
        string $loginhint,
        array $roles = [],
        array $extraclaims = []
    ): lti_message {
        // Standard claims trump extra claims.
        $claims = array_merge(
            $extraclaims,
            $this->generate_standard_claims($toolconfig, $messagetype, $issuer),
        );
        $ltitoken = new lti_token($claims);

        // Roles could differ depending on the placement, so must be left to the calling code and are therefore passed in.
        $ltitoken->add_claim(constants::LTI_JWT_CLAIM_PREFIX.'/claim/roles', $roles);

        // Note: Single deployment model means the $tool->id IS the lti_deployment_id.
        $params = [
            'iss' => $issuer,
            'target_link_uri' => $targetlinkuri,
            'login_hint' => $loginhint,
            'lti_message_hint' => $ltitoken->to_jwt(
                privatekey: jwks_helper::get_private_key()['key'],
                kid: jwks_helper::get_private_key()['kid']
            ),
            'client_id' => $toolconfig->tool->clientid,
            'lti_deployment_id' => strval($toolconfig->tool->id),
        ];

        return new lti_message($toolconfig->config->initiatelogin, $params);
    }

    /**
     * Get those claims used in all lti messages and which are required.
     *
     * Must be claims that are generic and used in all lti messages. Note: this includes things like version, deployment_id and
     * others which, despite not being listed as applicable to all message types in the core spec, in practice, behave as such.
     *
     * @param \stdClass $toolconfig the tool configuration.
     * @param string $messagetype the string message type.
     * @param string $issuer the issuer.
     * @return array the array of standard claims.
     */
    final protected function generate_standard_claims(
        \stdClass $toolconfig,
        string $messagetype,
        string $issuer,
    ): array {

        $prefix = constants::LTI_JWT_CLAIM_PREFIX;
        return [
            'tool_registration_id' => $toolconfig->tool->id, // Note: This is a Moodle-specific claim.
            'iss' => $issuer,
            'aud' => $toolconfig->tool->clientid,
            "$prefix/claim/message_type" => $messagetype, // https://www.imsglobal.org/spec/lti/v1p3#message-type-and-schemas.
            "$prefix/claim/deployment_id" => strval($toolconfig->tool->id), // Used in every message.
            "$prefix/claim/version" => lti_version::LTI_VERSION_1P3,
            "nonce" => bin2hex(random_string(10)), // Uniqueness of the message hint payload from request to request.
        ];
    }
}
