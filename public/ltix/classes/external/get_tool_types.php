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
use core_external\external_multiple_structure;
use core_external\external_value;
use core_ltix\helper;

/**
 * External function to get tool types.
 *
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_tool_types extends external_api {

    /**
     * Parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'toolproxyid' => new external_value(PARAM_INT, 'Tool proxy id', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Returns tool types.
     *
     * @param int $toolproxyid The tool proxy id
     * @return array of tool types
     */
    public static function execute(int $toolproxyid = 0): array {
        $params = self::validate_parameters(self::execute_parameters(),
            array(
                'toolproxyid' => $toolproxyid
            ));
        $toolproxyid = $params['toolproxyid'];

        $context = \context_system::instance();

        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        if (!empty($toolproxyid)) {
            $types = helper::get_lti_types_from_proxy_id($toolproxyid);
        } else {
            $types = helper::get_lti_types();
        }

        return array_map("\core_ltix\helper::serialise_tool_type", array_values($types));
    }

    /**
     * Return definition.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            structs::tool_type_return_structure()
        );
    }
}
