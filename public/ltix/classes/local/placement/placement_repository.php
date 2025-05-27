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

namespace core_ltix\local\placement;

use core\exception\coding_exception;

/**
 * Placement repository class.
 *
 * This class abstracts the data access layer, encapsulating the logic for retrieving and manipulating placement-related
 * data through dedicated methods.
 *
 * @package    core_ltix
 * @copyright  2025 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class placement_repository {

    /**
     * Whether a placement of a given type is enabled in a particular tool in a given course.
     *
     * @param string $placementtype The placement type string. e.g. 'mod_lti:activityplacement'.
     * @param int $toolid The tool ID.
     * @param int $courseid The course ID.
     * @return bool Whether the placement of a given type is enabled for the tool in the course.
     * @throws coding_exception If the provided placement type is not valid.
     * @throws \dml_exception
     */
    public static function is_placement_enabled_for_tool_in_course(string $placementtype, int $toolid, int $courseid): bool {
        global $DB, $SITE;

        if (!placements_manager::is_valid_placement_type($placementtype)) {
            throw new coding_exception("Invalid placement type.");
        }

        $coursecontext = \core\context\course::instance($courseid);
        $coursecategory = $DB->get_field('course', 'category', ['id' => $courseid]);

        [$visiblesql, $visibleparams] = $DB->get_in_or_equal(
            [\core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED],
            SQL_PARAMS_NAMED
        );

        $sql = <<<EOF
            SELECT p.id
            FROM {lti_types} t
            JOIN {lti_placement} p ON t.id = p.toolid
            JOIN {lti_placement_type} pt ON p.placementtypeid = pt.id AND pt.type = :placementtype
            LEFT JOIN {lti_placement_config} pc ON p.id = pc.placementid AND pc.name = :placementconfigname
            LEFT JOIN {lti_placement_status} ps ON p.id = ps.placementid AND ps.contextid = :contextid
            LEFT JOIN {lti_types_categories} tc ON t.id = tc.typeid
            WHERE t.state = :active
                AND t.course IN (:courseid, :siteid)
                AND (tc.id IS NULL OR tc.categoryid = :categoryid)
                AND t.coursevisible $visiblesql
                AND (
                    ps.status = :placementenabledstatus
                    OR (ps.status IS NULL AND pc.value = :placementconfigvalue)
                )
                AND p.toolid = :toolid
        EOF;

        $params = [
                'placementtype' => $placementtype,
                'contextid' => $coursecontext->id,
                'active' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                'siteid' => $SITE->id,
                'courseid' => $courseid,
                'categoryid' => $coursecategory,
                'placementenabledstatus' => placement_status::ENABLED->value,
                'placementconfigname' => 'default_usage',
                'placementconfigvalue' => 'enabled',
                'toolid' => $toolid,
            ] + $visibleparams;

        return $DB->record_exists_sql($sql, $params);
    }
}
