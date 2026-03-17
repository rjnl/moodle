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

namespace core_ltix\local\lticore\message\launchrequest\builder\v2p0;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\lti_message;
use core_ltix\oauth_helper;

/**
 * Base class handling the creation of launch request messages for all LTI 2p0 messages.
 *
 * Subclass to create request builders for specific message types.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class v2p0_launch_request_builder {

    /**
     * Build the message.
     *
     * @param \stdClass $toolconfig the tool configuration.
     * @param string $messagetype the string message type, in 2p0 terms.
     * @param string $launchurl the URL to launch
     * @param string $userid the user id
     * @param array $roles any LTI roles the user should have
     * @param array $extraparams any optional parameters, which will vary depending on the message type being implemented.
     * @return lti_message the built message.
     */
    final protected function build(
        \stdClass $toolconfig,
        string $messagetype,
        string $launchurl,
        string $userid = '',
        array $roles = [],
        array $extraparams = []
    ): lti_message {
        if (empty($toolconfig->toolproxy)) {
            throw new lti_exception('Error: Tool is missing Tool Proxy. Tool Proxy required for LTI-2p0 launches');
        }

        $params = $this->get_required_launch_params($messagetype);
        $params = array_merge($params, (!empty($roles) ? ['roles' => implode(',', $roles)]: []));
        $params = array_merge($params, (!empty($userid) ? ['user_id' => $userid]: []));

        // Required params trump extra params.
        $params = array_merge($extraparams, $params);

        // Filter parameters depending on the tool's enabled LTI capabilities.
        $params = $this->enforce_tool_capabilities($toolconfig, $params);

        // Include oauth_callback before signing so it is covered by the signature (1.0A compliance).
        $params['oauth_callback'] = 'about:blank';
        $params = oauth_helper::sign_parameters(
            $params,
            $launchurl,
            'POST',
            $toolconfig->toolproxy->guid,
            $toolconfig->toolproxy->secret
        );

        return new lti_message($launchurl, $params);
    }

    /**
     * Filter the request params, excluding those which are controlled by capabilities currently not enabled on the tool.
     *
     * Any params which aren't controlled by capabilities will remain.
     *
     * @param \stdClass $toolconfig the tool configuration.
     * @param array $params the launch params to filter.
     * @return array the filtered launch params.
     */
    final protected function enforce_tool_capabilities(\stdClass $toolconfig, array $params): array {
        $allcapabilities = \core_ltix\helper::get_capabilities();
        $enabledcapabilities = !empty($toolconfig->tool->enabledcapability)
            ? explode("\n", $toolconfig->tool->enabledcapability): [];
        // E.g. of $params: ['context_title' => 'some value'].
        // E.g. of $allcaps: ['Context.title' => 'context_title'].
        // E.g. of $enabledcaps: ['Context.title', 'Context.id'].
        return array_filter($params, function ($paramval, $paramkey) use ($allcapabilities, $enabledcapabilities) {
            // Note: the same value may appear for MULTIPLE keys in the array.
            // Enabled capabilities are checked for all of them.
            $matchedcapabilitykeys = array_keys($allcapabilities, $paramkey);
            return empty($matchedcapabilitykeys) || array_intersect($matchedcapabilitykeys, $enabledcapabilities);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Get those launch params which are required for any LTI 2p0 message.
     *
     * @param string $messagetype
     * @return array the array of required params.
     */
    final protected function get_required_launch_params(string $messagetype): array {
        return [
            'lti_version' => lti_version::LTI_VERSION_2->value,
            'lti_message_type' => $messagetype,
        ];
    }
}
