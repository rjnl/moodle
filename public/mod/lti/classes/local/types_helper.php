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

namespace mod_lti\local;

use core\context\course;

/**
 * Helper class specifically dealing with LTI types (preconfigured tools).
 *
 * @package    mod_lti
 * @copyright  2023 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class types_helper {

    /**
     * Returns all LTI tool types (preconfigured tools) visible in the given course and for the given user.
     *
     * This list will contain both site level tools and course-level tools.
     *
     * @param int $courseid the id of the course.
     * @param int $userid the id of the user.
     * @param array $coursevisible options for 'coursevisible' field, which will default to
     *        [LTI_COURSEVISIBLE_PRECONFIGURED, LTI_COURSEVISIBLE_ACTIVITYCHOOSER] if omitted.
     * @return \stdClass[] the array of tool type objects.
     */
    public static function get_lti_types_by_course(int $courseid, int $userid, array $coursevisible = []): array {
        if (!has_capability('mod/lti:addpreconfiguredinstance', course::instance($courseid), $userid)) {
            return [];
        }

        return \core_ltix\types_helper::get_lti_types_by_course($courseid, $userid, $coursevisible);
    }

    /**
     * Override coursevisible for a given tool on course level.
     *
     * @deprecated since Moodle 4.4
     * @param int $tooltypeid Type ID
     * @param int $courseid Course ID
     * @param \core\context\course $context Course context
     * @param bool $showinactivitychooser Show or not show in activity chooser
     * @return bool True if the coursevisible was changed, false otherwise.
     */
    public static function override_type_showinactivitychooser(int $tooltypeid, int $courseid, \core\context\course $context, bool $showinactivitychooser): bool {
        debugging(__FUNCTION__ . '() is deprecated. ' .
            'Please use \core_ltix\types_helper::override_type_showinactivitychooser() instead.',
            DEBUG_DEVELOPER);

        return \core_ltix\types_helper::override_type_showinactivitychooser($tooltypeid, $courseid, $context,
            $showinactivitychooser);
    }

}
