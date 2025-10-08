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
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/lti/locallib.php');

/**
 * External function to delete a course tool type.
 *
 * @package    mod_lti
 * @copyright  2023 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\deprecated(
    reason: 'Use \core_ltix\external\delete_course_tool_type instead',
    since: '5.1',
    mdl: 'MDL-79518',
)]
class delete_course_tool_type extends external_api {

    /**
     * Get parameter definition.
     *
     * @deprecated since Moodle 5.1
     * @return external_function_parameters
     */
    #[\core\attribute\deprecated(
        reason: 'Use \core_ltix\external\delete_course_tool_type::execute_parameters() instead',
        since: '5.1',
        mdl: 'MDL-79518',
    )]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tooltypeid' => new external_value(PARAM_INT, 'Tool type ID'),
        ]);
    }

    /**
     * Delete a course tool type.
     *
     * @deprecated since Moodle 5.1
     * @param int $tooltypeid the id of the course external tool type.
     * @return bool true
     * @throws \invalid_parameter_exception if the provided id refers to a site level tool which cannot be deleted.
     */
    #[\core\attribute\deprecated(
        reason: 'Use \core_ltix\external\delete_course_tool_type::execute() instead',
        since: '5.1',
        mdl: 'MDL-79518',
    )]
    public static function execute(int $tooltypeid): bool {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);
        return \core_ltix\external\delete_course_tool_type::execute($tooltypeid);
    }

    /**
     * Get service returns definition.
     *
     * @deprecated since Moodle 5.1
     * @return external_value
     */
    #[\core\attribute\deprecated(
        reason: 'Use \core_ltix\external\delete_course_tool_type::execute_returns() instead',
        since: '5.1',
        mdl: 'MDL-79518',
    )]
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'Success');
    }

    /**
     * Mark the function as deprecated.
     * @return bool
     */
    public static function execute_is_deprecated() {
        return true;
    }
}
