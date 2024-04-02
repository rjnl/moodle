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
 * External function to delete a tool type.
 *
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_tool_type extends external_api {

    /**
     * Parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'Tool type id'),
            )
        );
    }

    /**
     * Delete a tool type.
     *
     * @param int $id The id of the tool type to be deleted
     * @return array containing the deleted tool type id
     */
    public static function execute(int $id): array {
        $params = self::validate_parameters(self::execute_parameters(),
            array(
                'id' => $id,
            ));
        $id = $params['id'];

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $type = helper::get_type($id);

        if (!empty($type)) {
            helper::delete_type($id);

            // If this is the last type for this proxy then remove the proxy
            // as well so that it isn't orphaned.
            $types = helper::get_lti_types_from_proxy_id($type->toolproxyid);
            if (empty($types)) {
                helper::delete_tool_proxy($type->toolproxyid);
            }
        }

        return array('id' => $id);
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Tool type id'),
            )
        );
    }
}
