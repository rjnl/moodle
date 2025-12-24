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

namespace core_ltix\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

/**
 * Privacy Subsystem for core_ltix implementing null_provider.
 *
 * @package    core_ltix
 * @author     Alex Morris <alex.morris@catalyst.net.nz>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // core_ltix stores user data.
    \core_privacy\local\metadata\provider,

    // The core_ltix subsystem provides data to other components.
    \core_privacy\local\request\subsystem\plugin_provider,

    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider,

    // The core_ltix subsystem may have data that belongs to this user.
    \core_privacy\local\request\subsystem\provider,

    \core_privacy\local\request\shared_userlist_provider
{

    /**
     * Returns information about the user data stored in this component.
     *
     * @param collection $collection A list of information about this component
     * @return collection The collection object filled out with information about this component.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link(
            'lti_provider',
            [
                'userid' => 'privacy:metadata:userid',
                'username' => 'privacy:metadata:username',
                'useridnumber' => 'privacy:metadata:useridnumber',
                'firstname' => 'privacy:metadata:firstname',
                'lastname' => 'privacy:metadata:lastname',
                'fullname' => 'privacy:metadata:fullname',
                'email' => 'privacy:metadata:email',
                'role' => 'privacy:metadata:role',
                'courseid' => 'privacy:metadata:courseid',
                'courseidnumber' => 'privacy:metadata:courseidnumber',
                'courseshortname' => 'privacy:metadata:courseshortname',
                'coursefullname' => 'privacy:metadata:coursefullname',
            ],
            'privacy:metadata:externalpurpose'
        );

        $collection->add_database_table(
            'lti_submission',
            [
                'userid' => 'privacy:metadata:lti_submission:userid',
                'datesubmitted' => 'privacy:metadata:lti_submission:datesubmitted',
                'dateupdated' => 'privacy:metadata:lti_submission:dateupdated',
                'gradepercent' => 'privacy:metadata:lti_submission:gradepercent',
                'originalgrade' => 'privacy:metadata:lti_submission:originalgrade',
            ],
            'privacy:metadata:lti_submission'
        );

        $collection->add_database_table(
            'lti_tool_proxies',
            [
                'name' => 'privacy:metadata:lti_tool_proxies:name',
                'createdby' => 'privacy:metadata:createdby',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'privacy:metadata:timemodified',
            ],
            'privacy:metadata:lti_tool_proxies'
        );
        $collection->add_database_table(
            'lti_types',
            [
                'name' => 'privacy:metadata:lti_types:name',
                'createdby' => 'privacy:metadata:createdby',
                'timecreated' => 'privacy:metadata:timecreated',
                'timemodified' => 'privacy:metadata:timemodified',
            ],
            'privacy:metadata:lti_types'
        );
        return $collection;
    }

    /**
     * Gets all of the users in a specified context.
     *
     * @param userlist $userlist List of users and context to check.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            // Fetch all LTI tool proxies.
            $sql = "SELECT ltp.createdby AS userid
                      FROM {lti_tool_proxies} ltp";
            $userlist->add_from_sql('userid', $sql, []);
        }

        if ($context->contextlevel == CONTEXT_COURSE) {
            // Fetch all LTI types.
            // Apart from course tools, this also fetches site tools, as they are currently created in the
            // Front Page (course ID = 1), which belongs to the course context.
            $sql = "SELECT lt.createdby AS userid
                      FROM {context} c
                      JOIN {course} course
                        ON c.contextlevel = :contextlevel
                       AND c.instanceid = course.id
                      JOIN {lti_types} lt
                        ON lt.course = course.id
                      WHERE c.id = :contextid";

            $params = [
                'contextlevel' => CONTEXT_COURSE,
                'contextid' => $context->id,
            ];
            $userlist->add_from_sql('userid', $sql, $params);
        }
    }

    /**
     * Gets a list of users in the LTI submission table using the requested context.
     *
     * This is a helper method used by the respective privacy providers of components to which the core_ltix subsystem
     * provides data. It enables them to return the users that have lti_submission data in their own context, which
     * core_ltix has no direct knowledge of.
     *
     * @param userlist $userlist List of users and context to check.
     * @return void
     */
    public static function get_lti_submission_users_in_context_from_sql(userlist $userlist): void {

        $sql = "SELECT ltisub.userid
                  FROM {lti_submission} ltisub
                  JOIN {lti_resource_link} ltirl
                    ON ltirl.id = ltisub.ltiresourcelinkid
                   AND ltirl.contextid = :contextid";

        $params = [
            'contextid' => $userlist->get_context()->id,
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int $userid The user to search.
     * @return  contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Fetch all LTI types.
        // Apart from course tools, this also handles site tools, as they are currently created in the
        // Front Page (course ID = 1), which belongs to the course context.
        $sql = "SELECT c.id
                 FROM {context} c
                 JOIN {course} course
                   ON c.contextlevel = :contextlevel
                  AND c.instanceid = course.id
                 JOIN {lti_types} ltit
                   ON ltit.course = course.id
                WHERE ltit.createdby = :userid";
        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid
        ];
        $contextlist->add_from_sql($sql, $params);

        // The LTI tool proxies sit in the system context.
        $contextlist->add_system_context();
        return $contextlist;
    }

    /**
     * Get SQL to retrieve all LTI submissions where the user has been involved.
     *
     * This is a helper method used by the respective privacy providers of components to which the core_ltix subsystem
     * provides data. It enables them to return the lti_submission data associated with a given user in their own
     * context, which core_ltix has no knowledge of.
     *
     * @param int $userid The user to search
     * @return array join/where/params SQL parts to include in queries
     */
    public static function get_lti_submission_user_join_sql(int $userid): array {

        $join = "INNER JOIN {lti_resource_link} ltirl
                    ON ltirl.contextid = c.id
                 INNER JOIN {lti_submission} ltisub
                    ON ltisub.ltiresourcelinkid = ltirl.id";

        $where = "WHERE ltisub.userid = :userid";

        return [
            'join' => $join,
            'where' => $where,
            'params' => ['userid' => $userid],
        ];
    }

    /**
     * Extracts and exports all of the user data for the provided contexts.
     *
     * @param approved_contextlist $contextlist The list of contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        self::export_user_data_lti_types($contextlist);
        self::export_user_data_lti_tool_proxies($contextlist);
    }

    /**
     * Export personal data for the given approved_contextlist related to LTI types.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     * @return void
     */
    protected static function export_user_data_lti_types(approved_contextlist $contextlist): void {
        global $DB;

        // Filter out any contexts that are not related to courses.
        // Apart from course tools, this also handles site tools, as they are currently created in the
        // Front Page (course ID = 1), which belongs to the course context.
        $courseids = array_reduce($contextlist->get_contexts(), function($carry, $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $carry[] = $context->instanceid;
            }
            return $carry;
        }, []);

        if (empty($courseids)) {
            return;
        }

        $user = $contextlist->get_user();

        list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $params = array_merge($inparams, ['userid' => $user->id]);
        $ltitypes = $DB->get_recordset_select('lti_types', "course $insql AND createdby = :userid", $params, 'timecreated ASC');
        self::recordset_loop_and_export($ltitypes, 'course', [], function($carry, $record) {
            $context = \context_course::instance($record->course);
            $options = ['context' => $context];
            $carry[] = [
                'name' => format_string($record->name, true, $options),
                'createdby' => transform::user($record->createdby),
                'timecreated' => transform::datetime($record->timecreated),
                'timemodified' => transform::datetime($record->timemodified)
            ];
            return $carry;
        }, function($courseid, $data) {
            $context = \context_course::instance($courseid);
            $finaldata = (object) ['lti_types' => $data];
            writer::with_context($context)->export_data([], $finaldata);
        });
    }

    /**
     * Export personal data for the given approved_contextlist related to LTI tool proxies.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     * @return void
     */
    protected static function export_user_data_lti_tool_proxies(approved_contextlist $contextlist): void {
        global $DB;

        // Filter out any contexts that are not related to system context.
        $systemcontexts = array_filter($contextlist->get_contexts(), function($context) {
            return $context->contextlevel == CONTEXT_SYSTEM;
        });

        if (empty($systemcontexts)) {
            return;
        }

        $user = $contextlist->get_user();

        $systemcontext = \context_system::instance();

        $data = [];
        $ltiproxies = $DB->get_recordset('lti_tool_proxies', ['createdby' => $user->id], 'timecreated ASC');
        foreach ($ltiproxies as $ltiproxy) {
            $data[] = [
                'name' => format_string($ltiproxy->name, true, ['context' => $systemcontext]),
                'createdby' => transform::user($ltiproxy->createdby),
                'timecreated' => transform::datetime($ltiproxy->timecreated),
                'timemodified' => transform::datetime($ltiproxy->timemodified)
            ];
        }
        $ltiproxies->close();

        $finaldata = (object) ['lti_tool_proxies' => $data];
        writer::with_context($systemcontext)->export_data([], $finaldata);
    }

    /**
     * Export personal data for the given user related to LTI submissions in particular contexts.
     *
     * This is a helper method used by the respective privacy providers of components to which the core_ltix subsystem
     * provides data. It enables them to export the lti_submission data associated with a given user in their own
     * contexts, which core_ltix has no knowledge of.
     *
     * @param \stdClass $user The user object
     * @param array $contextids The array of context IDs
     * @return void
     */
    public static function export_user_data_lti_submissions(\stdClass $user, array $contextids): void {
        global $DB;

        if (empty($contextids)) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
        // Get all the LTI resource links associated with the above contexts.
        $resourcelinkidstocontextids = $DB->get_records_sql_menu(
            "SELECT rl.id, rl.contextid
                   FROM {lti_resource_link} rl
                  WHERE rl.contextid $insql",
            $inparams
        );
        $recordset = $DB->get_recordset_sql(
            "SELECT s.ltiresourcelinkid, s.datesubmitted, s.dateupdated, s.gradepercent, s.originalgrade, rl.contextid
                   FROM {lti_submission} s
                   JOIN {lti_resource_link} rl ON rl.id = s.ltiresourcelinkid
                  WHERE rl.contextid $insql AND s.userid = :userid",
            array_merge($inparams, ['userid' => $user->id])
        );
        \core_ltix\privacy\provider::recordset_loop_and_export($recordset, 'ltiresourcelinkid', [], function($carry, $record) {
            $carry[] = [
                'gradepercent' => $record->gradepercent,
                'originalgrade' => $record->originalgrade,
                'datesubmitted' => transform::datetime($record->datesubmitted),
                'dateupdated' => transform::datetime($record->dateupdated)
            ];
            return $carry;
        }, function($ltiresourcelinkid, $data) use ($user, $resourcelinkidstocontextids) {
            $contextid = $resourcelinkidstocontextids[$ltiresourcelinkid];
            $context = \context::instance_by_id($contextid);
            $contextdata = helper::get_context_data($context, $user);
            $finaldata = (object) array_merge((array) $contextdata, ['submissions' => $data]);
            helper::export_context_files($context, $user);
            writer::with_context($context)->export_data([], $finaldata);
        });
    }

    /**
     * Deletes LTI data for all users in a given context.
     *
     * @param \context $context The context to delete all data for.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $DB->delete_records('lti_tool_proxies');
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            // Apart from course tools, this also handles site tools, as they are currently created in the
            // Front Page (course ID = 1), which belongs to the course context.
            $DB->delete_records('lti_types', ['course' => $context->instanceid]);
        }
    }

    /**
     * Deletes LTI data for a given user in all provided contexts.
     *
     * This function just updates the User ID to 0 instead of deleting the LTI instances
     * because the instances may be used by other people.
     *
     * @param approved_contextlist $contextlist List of contexts to delete the user from.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                $DB->set_field('lti_tool_proxies', 'createdby', 0, ['createdby' => $userid]);
            } else if ($context->contextlevel == CONTEXT_COURSE) {
                // Apart from course tools, this also handles site tools, as they are currently created in the
                // Front Page (course ID = 1), which belongs to the course context.
                $DB->set_field('lti_types', 'createdby', 0, ['course' => $context->instanceid, 'createdby' => $userid]);
            }
        }
    }

    /**
     * Deletes LTI data for a given list of users and their contexts.
     *
     * This function just updates the User ID to 0 instead of the deleting the LTI instances
     * because the instances may be used by other people.
     *
     * @param approved_userlist $userlist The list of contexts and users to delete the user from.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        [$usersinsql, $usersinparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $DB->set_field_select('lti_tool_proxies', 'createdby', 0, "createdby $usersinsql", $usersinparams);
        } else if ($context->contextlevel == CONTEXT_COURSE) {
            // Apart from course tools, this also handles site tools, as they are currently created in the
            // Front Page (course ID = 1), which belongs to the course context.
            $DB->set_field_select(
                'lti_types',
                'createdby',
                0,
                "course = :courseid AND createdby $usersinsql",
                array_merge($usersinparams, ['courseid' => $context->instanceid])
            );
        }
    }

    /**
     * Deletes the data from the LTI submission table.
     *
     * This is a helper method used by the respective privacy providers of components to which the core_ltix subsystem
     * provides data. It enables them to delete the lti_submission data associated with a given user in their own
     * context, which core_ltix has no knowledge of.
     *
     * @param \context $context The context object
     * @param array|null $userids User ID or array of IDs to delete the data for.
     * @return void
     */
    public static function delete_lti_submission_data(\context $context, ?array $userids = null): void {
        global $DB;

        $params = ['contextid' => $context->id];
        $sql = "ltiresourcelinkid IN (SELECT id FROM {lti_resource_link} WHERE contextid = :contextid)";

        if (!empty($userids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $sql .= " AND userid {$insql}";
            $params = array_merge($params, $inparams);
        }

        $DB->delete_records_select('lti_submission', $sql, $params);
    }

    /**
     * Loop and export from a recordset.
     *
     * @param \moodle_recordset $recordset The recordset.
     * @param string $splitkey The record key to determine when to export.
     * @param mixed $initial The initial data to reduce from.
     * @param callable $reducer The function to return the dataset, receives current dataset, and the current record.
     * @param callable $export The function to export the dataset, receives the last value from $splitkey and the dataset.
     * @return void
     */
    public static function recordset_loop_and_export(
            \moodle_recordset $recordset,
            string $splitkey,
            $initial,
            callable $reducer,
            callable $export
    ) {
        $data = $initial;
        $lastid = null;

        foreach ($recordset as $record) {
            if ($lastid && $record->{$splitkey} != $lastid) {
                $export($lastid, $data);
                $data = $initial;
            }
            $data = $reducer($data, $record);
            $lastid = $record->{$splitkey};
        }
        $recordset->close();

        if (!empty($lastid)) {
            $export($lastid, $data);
        }
    }
}
