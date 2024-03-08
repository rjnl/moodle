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

namespace mod_lti\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;
use core_ltix\external\structs;

/**
 * External function for fetching all tool types and proxies.
 *
 * @package    mod_lti
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_tool_types_and_proxies extends external_api {

    /**
     * Get parameter definition for get_tool_types_and_proxies().
     *
     * @deprecated since Moodle 4.4
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'toolproxyid' => new external_value(
                PARAM_INT,
                'Tool proxy id',
                VALUE_DEFAULT,
                0
            ),
            'orphanedonly' => new external_value(
                PARAM_BOOL,
                'Orphaned tool types only',
                VALUE_DEFAULT,
                0
            ),
            'limit' => new external_value(
                PARAM_INT,
                'How many tool types displayed per page',
                VALUE_DEFAULT,
                60,
                NULL_NOT_ALLOWED
            ),
            'offset' => new external_value(
                PARAM_INT,
                'Current offset of tool elements',
                VALUE_DEFAULT,
                0,
                NULL_NOT_ALLOWED
            ),
        ]);
    }

    /**
     * Get data for all tool types and tool proxies.
     *
     * @deprecated since Moodle 4.4
     * @param int $toolproxyid The tool proxy id
     * @param bool $orphanedonly Whether to get orphaned proxies only.
     * @param int $limit How many elements to return if using pagination.
     * @param int $offset Which chunk of elements to return is using pagination.
     * @return array
     */
    public static function execute($toolproxyid, $orphanedonly, $limit, $offset): array {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\external\get_tool_types_and_proxies instead.',
                  DEBUG_DEVELOPER);
        return \core_ltix\external\get_tool_types_and_proxies::execute($toolproxyid, $orphanedonly, $limit, $offset);
    }

    /**
     * Get return definition for get_tool_types_and_proxies.
     *
     * @deprecated since Moodle 4.4
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'types' => new external_multiple_structure(structs::tool_type_return_structure()),
            'proxies' => new external_multiple_structure(structs::tool_proxy_return_structure()),
            'limit' => new external_value(PARAM_INT, 'Limit of how many tool types to show', VALUE_OPTIONAL),
            'offset' => new external_value(PARAM_INT, 'Offset of tool types', VALUE_OPTIONAL),
        ]);
    }

   /**
     * Mark the function as deprecated.
     * @return bool
     */
    public static function execute_is_deprecated() {
        return true;
    }
}
