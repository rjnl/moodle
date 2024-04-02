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
 * External function to create a tool type.
 *
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_tool_type extends external_api {

    /**
     * Parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'cartridgeurl' => new external_value(PARAM_URL, 'URL to cartridge to load tool information', VALUE_DEFAULT, ''),
                'key' => new external_value(PARAM_TEXT, 'Consumer key', VALUE_DEFAULT, ''),
                'secret' => new external_value(PARAM_TEXT, 'Shared secret', VALUE_DEFAULT, ''),
            )
        );
    }

    /**
     * Creates a tool type.
     *
     * @param string $cartridgeurl Url of the xml cartridge representing the LTI tool
     * @param string $key The consumer key to identify this consumer
     * @param string $secret The secret
     * @return array created tool type
     * @throws \moodle_exception If the tool type could not be created
     */
    public static function execute(string $cartridgeurl, string $key = '', string $secret = ''): array {
        $params = self::validate_parameters(self::execute_parameters(),
            array(
                'cartridgeurl' => $cartridgeurl,
                'key' => $key,
                'secret' => $secret
            ));
        $cartridgeurl = $params['cartridgeurl'];
        $key = $params['key'];
        $secret = $params['secret'];

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $id = null;

        if (!empty($cartridgeurl)) {
            $type = new \stdClass();
            $data = new \stdClass();
            $type->state = LTI_TOOL_STATE_CONFIGURED;
            $data->lti_coursevisible = 1;
            $data->lti_sendname = LTI_SETTING_DELEGATE;
            $data->lti_sendemailaddr = LTI_SETTING_DELEGATE;
            $data->lti_acceptgrades = LTI_SETTING_DELEGATE;
            $data->lti_forcessl = 0;

            if (!empty($key)) {
                $data->lti_resourcekey = $key;
            }

            if (!empty($secret)) {
                $data->lti_password = $secret;
            }

            helper::load_type_from_cartridge($cartridgeurl, $data);
            if (empty($data->lti_toolurl)) {
                throw new \moodle_exception('unabletocreatetooltype', 'core_ltix');
            } else {
                $id = helper::add_type($type, $data);
            }
        }

        if (!empty($id)) {
            $type = helper::get_type($id);
            return helper::serialise_tool_type($type);
        } else {
            throw new \moodle_exception('unabletocreatetooltype', 'core_ltix');
        }
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return structs::tool_type_return_structure();
    }
}
