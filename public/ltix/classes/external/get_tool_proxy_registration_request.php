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

namespace core_ltix\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core_ltix\helper;

/**
 * External function to get a tool proxy registration request.
 *
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_tool_proxy_registration_request extends external_api {

    /**
     * Parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'Tool proxy id'),
            )
        );
    }

    /**
     * Returns the registration request for a tool proxy.
     *
     * @param int $id the lti tool proxy id
     * @return array of registration parameters
     */
    public static function execute(int $id): array {
        $params = self::validate_parameters(get_tool_proxy_registration_request::execute_parameters(),
            array(
                'id' => $id,
            ));
        $id = $params['id'];

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $toolproxy = helper::get_tool_proxy($id);
        return helper::build_registration_request($toolproxy);
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            array(
                'lti_message_type' => new external_value(PARAM_ALPHANUMEXT, 'LTI message type'),
                'lti_version' => new external_value(PARAM_ALPHANUMEXT, 'LTI version'),
                'reg_key' => new external_value(PARAM_TEXT, 'Tool proxy registration key'),
                'reg_password' => new external_value(PARAM_TEXT, 'Tool proxy registration password'),
                'reg_url' => new external_value(PARAM_TEXT, 'Tool proxy registration url'),
                'tc_profile_url' => new external_value(PARAM_URL, 'Tool consumers profile URL'),
                'launch_presentation_return_url' => new external_value(PARAM_URL, 'URL to redirect on registration completion'),
            )
        );
    }
}
