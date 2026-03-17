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

namespace core_ltix\local\lticore\message\launchrequest\builder\v1p1;

use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\lti_message;
use core_ltix\oauth_helper;

/**
 * Base class handling the creation of launch request messages for all LTI 1p1 messages.
 *
 * Subclass to create request builders for specific message types.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class v1p1_launch_request_builder {

    /**
     * Build a launch message.
     *
     * @param \stdClass $toolconfig the tool configuration.
     * @param string $messagetype the string message type, in 1p1 terms.
     * @param string $launchurl the URL to launch
     * @param array $roles any LTI roles the user should have
     * @param array $extraparams any optional parameters, which will vary depending on the message type being implemented.
     * @return lti_message
     */
    final protected function build(
        \stdClass $toolconfig,
        string $messagetype,
        string $launchurl,
        array $roles = [],
        array $extraparams = []
    ): lti_message {

        $params = $this->get_required_launch_params($messagetype);
        $params = array_merge($params, (!empty($roles) ? ['roles' => implode(',', $roles)]: []));

        // Required params trump extra params.
        $params = array_merge($extraparams, $params);

        [$consumerkey, $secret] = $this->get_signing_keys($toolconfig);
        if (!empty($consumerkey) && !empty($secret)) {
            // Include oauth_callback before signing so it is covered by the signature (1.0A compliance).
            $params['oauth_callback'] = 'about:blank';
            $params = oauth_helper::sign_parameters($params, $launchurl, 'POST', $consumerkey, $secret);
        }

        return new lti_message($launchurl, $params);
    }

    /**
     * Get the signing keys from tool config.
     *
     * @param \stdClass $toolconfig
     * @return array the array of [key, secret].
     */
    final protected function get_signing_keys(\stdClass $toolconfig): array {
        $key = !empty($toolconfig->config->resourcekey) ? $toolconfig->config->resourcekey : '';
        $secret = !empty($toolconfig->config->password) ? $toolconfig->config->password : '';

        return [$key, $secret];
    }

    /**
     * Get those launch params which are required for any LTI 1p1 message.
     *
     * @param string $messagetype
     * @return array the array of required params.
     */
    final protected function get_required_launch_params(string $messagetype): array {
        return [
            'lti_version' => lti_version::LTI_VERSION_1->value,
            'lti_message_type' => $messagetype,
        ];
    }
}
