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

/**
 * An event observer.
 *
 * @package    assignfeedback_editpdf
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace assignfeedback_editpdf\event;

/**
 * An event observer.
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Listen to events and queue the submission for processing.
     * @param \mod_assign\event\submission_created $event
     */
    public static function submission_created(\mod_assign\event\submission_created $event) {
        self::queue_conversion($event);
    }

    /**
     * Listen to events and queue the submission for processing.
     * @param \mod_assign\event\submission_updated $event
     */
    public static function submission_updated(\mod_assign\event\submission_updated $event) {
        self::queue_conversion($event);
    }

    /**
     * Listen to events and delete the data generated from a submission when it is removed.
     *
     * @param \mod_assign\event\submission_removed $event
     */
    public static function submission_removed(\mod_assign\event\submission_removed $event) {
        global $DB;

        // Clean up even if the plugin is now disabled: removal does not update timemodified, so
        // re-enabling the plugin would show the removed pages again. Nothing was generated without files.
        if (!$DB->record_exists('files', ['contextid' => $event->contextid, 'component' => 'assignfeedback_editpdf'])) {
            return;
        }

        $assign = $event->get_assign();

        if (!empty($event->relateduserid)) {
            $users = [$event->relateduserid];
        } else {
            // Team submission, clean up every member. Find them from their grades: get_submission_group_members()
            // skips suspended members unless the current user can see them.
            $params = [
                'assignment' => $assign->get_instance()->id,
                'attemptnumber' => $event->other['submissionattempt'],
            ];
            $join = '';
            if (!empty($event->other['groupid'])) {
                $join = 'JOIN {groups_members} gm ON gm.userid = g.userid AND gm.groupid = :groupid';
                $params['groupid'] = $event->other['groupid'];
            }
            $sql = "SELECT g.id, g.userid
                      FROM {assign_grades} g
                           $join
                     WHERE g.assignment = :assignment
                       AND g.attemptnumber = :attemptnumber";
            $users = [];
            foreach ($DB->get_records_sql($sql, $params) as $grade) {
                // Members of several groups submit in the default group.
                $group = $assign->get_submission_group($grade->userid);
                if (($group ? $group->id : 0) == $event->other['groupid']) {
                    $users[] = $grade->userid;
                }
            }
        }

        foreach ($users as $userid) {
            \assignfeedback_editpdf\document_services::delete_submission_files_for_attempt(
                $assign,
                $userid,
                $event->other['submissionattempt'],
                $event->other['submissionid'],
            );
        }
    }

    /**
     * Queue the submission for processing.
     * @param \mod_assign\event\base $event The submission created/updated event.
     */
    protected static function queue_conversion($event) {
        $assign = $event->get_assign();
        $plugin = $assign->get_feedback_plugin_by_type('editpdf');

        if (!$plugin->is_visible() || !$plugin->is_enabled()) {
            // The plugin is not enabled on this assignment instance, so nothing should be queued.
            return;
        }

        $data = [
            'submissionid' => $event->other['submissionid'],
            'submissionattempt' => $event->other['submissionattempt'],
        ];
        $task = new \assignfeedback_editpdf\task\convert_submission;
        $task->set_custom_data($data);
        \core\task\manager::queue_adhoc_task($task, true);
    }
}
