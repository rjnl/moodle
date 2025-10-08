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
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/lti/locallib.php');

/**
 * External function for fetching the count of all tool types and proxies.
 *
 * @package    mod_lti
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\deprecated(
    reason: 'Use \core_ltix\external\get_tool_types_and_proxies_count instead',
    since: '5.1',
    mdl: 'MDL-79518',
)]
class get_tool_types_and_proxies_count extends external_api {

    /**
     * Get parameter definition for get_tool_types_and_proxies_count().
     *
     * @deprecated since Moodle 5.1
     * @return external_function_parameters
     */
    #[\core\attribute\deprecated(
        reason: 'Use \core_ltix\external\get_tool_types_and_proxies_count::execute_parameters() instead',
        since: '5.1',
        mdl: 'MDL-79518',
    )]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'toolproxyid' => new external_value(PARAM_INT, 'Tool proxy id', VALUE_DEFAULT, 0),
                'orphanedonly' => new external_value(PARAM_BOOL, 'Orphaned tool types only', VALUE_DEFAULT, 0),
            ]
        );
    }

    /**
     * Get count of every tool type and tool proxy.
     *
     * @deprecated since Moodle 5.1
     * @param int $toolproxyid The tool proxy id
     * @param bool $orphanedonly Whether to get orphaned proxies only.
     * @return array
     */
    #[\core\attribute\deprecated(
        reason: 'Use \core_ltix\external\get_tool_types_and_proxies_count::execute() instead',
        since: '5.1',
        mdl: 'MDL-79518',
    )]
    public static function execute($toolproxyid, $orphanedonly): array {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);
        return \core_ltix\external\get_tool_types_and_proxies_count::execute($toolproxyid, $orphanedonly);
    }

    /**
     * Get return definition for get_tool_types_and_proxies_count.
     *
     * @deprecated since Moodle 5.1
     * @return external_single_structure
     */
    #[\core\attribute\deprecated(
        reason: 'Use \core_ltix\external\get_tool_types_and_proxies_count::execute_returns() instead',
        since: '5.1',
        mdl: 'MDL-79518',
    )]
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'count' => new external_value(PARAM_INT, 'Total number of tool types and proxies', VALUE_REQUIRED),
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
