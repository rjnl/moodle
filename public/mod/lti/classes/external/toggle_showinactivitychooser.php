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

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_ltix\constants;
use core_ltix\local\placement\placement_status;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/lti/locallib.php');

/**
 * External function to toggle showinactivitychooser setting.
 *
 * @package    mod_lti
 * @copyright  2023 Ilya Tregubov <ilya.a.tregubov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toggle_showinactivitychooser extends external_api {

    /**
     * Get parameter definition.
     *
     * @deprecated since Moodle 5.1
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'tooltypeid' => new external_value(PARAM_INT, 'Tool type ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'showinactivitychooser' => new external_value(PARAM_BOOL, 'Show in activity chooser'),
        ]);
    }

    /**
     * Toggles showinactivitychooser setting.
     *
     * @deprecated since Moodle 5.1
     * @param int $tooltypeid the id of the course external tool type.
     * @param int $courseid the id of the course we are in.
     * @param bool $showinactivitychooser Show in activity chooser setting.
     * @return bool true or false
     */
    #[\core\attribute\deprecated(null, since: '5.1', reason: 'This method should not be used', mdl: 'MDL-85927')]
    public static function execute(int $tooltypeid, int $courseid, bool $showinactivitychooser): bool {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);

        // If the tool is configured with the placement 'mod_lti:activitychooser', and the user has permission,
        // change the value of the placement status and return true.
        // Otherwise, if it's a tool that isn't available to the current course category, throw.
        // Otherwise, return false.
        global $DB, $SITE;
        $coursecontext = context_course::instance($courseid);
        require_capability('moodle/ltix:addcoursetool', $coursecontext);

        $coursecategory = $DB->get_field('course', 'category', ['id' => $courseid]);
        $sql = <<<EOF
            SELECT p.id, tc.categoryid
              FROM {lti_types} t
              JOIN {lti_placement} p ON t.id = p.toolid
              JOIN {lti_placement_type} pt ON p.placementtypeid = pt.id AND pt.type = :placementtype
         LEFT JOIN {lti_types_categories} tc ON t.id = tc.typeid
             WHERE t.id = :toolid
               AND t.state = :active
               AND t.course IN (:courseid, :siteid)
               AND t.coursevisible = :coursevisible
        EOF;

        $params = [
            'placementtype' => 'mod_lti:activityplacement',
            'toolid' => $tooltypeid,
            'active' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
            'courseid' => $courseid,
            'siteid' => $SITE->id,
            'coursevisible' => constants::LTI_COURSEVISIBLE_PRECONFIGURED,
        ];

        $placement = $DB->get_records_sql($sql, $params);

        if (empty($placement)) {
            return false;
        }
        $placement = array_pop($placement);

        // If the tool is restricted to a different category, throw.
        if (!is_null($placement->categoryid) && $placement->categoryid != $coursecategory) {
            throw new \moodle_exception('You are not allowed to change this setting for this tool.');
        }

        $status = $DB->get_record('lti_placement_status', ['placementid' => $placement->id, 'contextid' => $coursecontext->id]);
        if (!$status) {
            $DB->insert_record('lti_placement_status', [
                'placementid' => $placement->id,
                'contextid' => $coursecontext->id,
                'status' => $showinactivitychooser ? placement_status::ENABLED->value : placement_status::DISABLED->value,
            ]);
        } else {
            $DB->update_record('lti_placement_status', [
                'id' => $status->id,
                'status' => $showinactivitychooser ? placement_status::ENABLED->value : placement_status::DISABLED->value,
            ]);
        }
        return true;
    }

    /**
     * Get service returns definition.
     *
     * @deprecated since Moodle 5.1
     * @return external_value
     */
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
