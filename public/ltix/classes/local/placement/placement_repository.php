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

    /**
     * Get a list of tools having an enabled placement of the given type in the given context.
     *
     * Omits placements for tools not available to the context (e.g. excludes placements for hidden tools).
     *
     * @param string $placementtype the placement type string
     * @param int $courseid the course id
     * @return array the list of placements
     * @throws coding_exception if the placement type is invalid.
     */
    public static function get_tools_with_enabled_placement_in_course(string $placementtype, int $courseid): array {
        global $DB, $SITE;

        if (!placements_manager::is_valid_placement_type($placementtype)) {
            throw new coding_exception("Invalid placement type.");
        }

        $coursecontext =  \core\context\course::instance($courseid);
        $coursecategory = $DB->get_field('course', 'category', ['id' => $courseid]);

        [$visiblesql, $visibleparams] = $DB->get_in_or_equal(
            [\core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED],
            SQL_PARAMS_NAMED
        );

        $sql = <<<EOF
            SELECT t.*
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
            ] + $visibleparams;

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Override coursevisible for a given tool on course level.
     *
     * @param int $toolid the LTI tool id
     * @param int $courseid the course id
     * @return array the list of placements and their statuses.
     */
    public static function get_placement_status_for_tool(int $toolid, int $courseid): array {
        global $DB;

        $sql = <<<EOF
            SELECT
                pt.id AS placementtypeid,
                pt.type,
                pt.component,
                CASE
                    WHEN ps.status IS NOT NULL THEN ps.status
                    WHEN pc.value = :placementconfigvalue THEN 1
                    ELSE 0
                END AS status
            FROM {lti_placement_type} pt
            JOIN {lti_placement} p ON p.placementtypeid = pt.id
            LEFT JOIN {lti_placement_status} ps ON ps.placementid = p.id AND ps.contextid = :contextid
            LEFT JOIN {lti_placement_config} pc ON p.id = pc.placementid AND pc.name = :placementconfigname
            WHERE p.toolid = :toolid
            GROUP BY pt.id, pt.type, ps.status, pc.value
            ORDER BY pt.id ASC
        EOF;

        $params = [
            'toolid' => $toolid,
            'contextid' => \core\context\course::instance($courseid)->id,
            'placementconfigname' => 'default_usage',
            'placementconfigvalue' => 'enabled',
        ];

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get placement configuration for a specific tool and placement type.
     *
     * @param int $toolid The tool ID to get placement config for
     * @param string $placementtype The placement type string
     * @return stdClass Object containing placement configuration
     */
    public static function get_placement_config_by_placement_type(int $toolid, string $placementtype): \stdClass {
        global $DB;

        if (!placements_manager::is_valid_placement_type_string($placementtype)) {
            throw new coding_exception("Invalid placement type. Should be of the form 'component:placementtypename'.");
        }

        $sql = "SELECT c.name, c.value
                  FROM {lti_placement_config} c
                  JOIN {lti_placement} p ON p.id = c.placementid
                  JOIN {lti_placement_type} pt ON pt.id = p.placementtypeid
                 WHERE p.toolid = :toolid AND pt.type = :placementtype";

        $params = [
            'toolid' => $toolid,
            'placementtype' => $placementtype,
        ];

        $configrecords = $DB->get_records_sql($sql, $params);

        $configs = new \stdClass();
        foreach ($configrecords as $record) {
            $configs->{$record->name} = $record->value;
        }

        return $configs;
    }

    /**
     * Load the placement configuration from the database.
     *
     * @param int $toolid The tool id.
     * @return object The placement configuration.
     */
    public static function load_placement_config(int $toolid): object {
        global $DB;

        $config = new \stdClass();

        // Get the placement types for this tool.
        $toolplacements = $DB->get_records_menu('lti_placement', ['toolid' => $toolid], 'id ASC', 'id,placementtypeid');

        $config->toolplacements = array_values($toolplacements);

        // Get the placement configs for this tool.
        $configrecords = $DB->get_records_list('lti_placement_config', 'placementid', array_keys($toolplacements));

        foreach ($configrecords as $record) {
            // Suffix to append to the config element names so that they match the form element names.
            $elementsuffix = '_placementconfig' . $toolplacements[$record->placementid];

            $config->{$record->name . $elementsuffix} = $record->value;
        }

        return $config;
    }

    /**
     * Save the placement configuration for a tool type.
     *
     * @param object $type The tool type object.
     * @param object $config The placement configuration.
     * @return void
     */
    public static function update_placement_config(object $type, object $config): void {
        global $DB;

        // Update placement type config.
        $placementtypeids = $config->toolplacements;

        $registeredplacementtypes = $DB->get_records('lti_placement_type');

        foreach ($placementtypeids as $pid) {
            // Get the placement type record.
            $placementtype = $registeredplacementtypes[$pid];
            $placementdata = [
                'toolid' => $type->id,
                'placementtypeid' => $placementtype->id,
            ];

            // Check if the record already exists.
            $existingrecord = $DB->get_record('lti_placement', $placementdata);

            // Use the existing placement ID if found; otherwise, insert a new record and return its ID.
            $placementid = $existingrecord ? $existingrecord->id : $DB->insert_record('lti_placement', $placementdata);

            // Now save the placement config for this placement.
            // Suffix used for the config element names in $config.
            $elementsuffix = "_placementconfig{$placementtype->id}";

            // Get config for this placement type from $config.
            $placementconfig = array_filter(
                get_object_vars($config),
                fn($val, $key) => str_ends_with($key, $elementsuffix),
                ARRAY_FILTER_USE_BOTH
            );

            // Placements should have the default_usage config set. If not set, set it to 'enabled' (e.g., for course tool).
            if (!isset($placementconfig["default_usage{$elementsuffix}"])) {
                $placementconfig["default_usage{$elementsuffix}"] = 'enabled';
            }

            // Save the config values.
            foreach ($placementconfig as $name => $value) {
                $configrow = (object) [
                    'placementid' => $placementid,
                    'name' => str_replace($elementsuffix, '', $name),
                    'value' => $value,
                ];

                self::insert_or_update_placement_config($configrow);
            }
        }

        // Remove any placement config records that are not in the current list.
        $idstoremove = array_diff(array_keys($registeredplacementtypes), $placementtypeids);

        if (!empty($idstoremove)) {
            self::delete_tool_placements($type->id, $idstoremove);
        }
    }

    /**
     * Insert or update a placement config record.
     *
     * @param object $record Placement config record.
     * @return void
     */
    public static function insert_or_update_placement_config(object $record): void {
        global $DB;

        // Check if the record already exists.
        $existingrecord = $DB->get_record('lti_placement_config', [
            'placementid' => $record->placementid,
            'name' => $record->name,
        ]);

        // Update the existing placement config if found; otherwise, insert a new record.
        if ($existingrecord) {
            $record->id = $existingrecord->id;
            $DB->update_record('lti_placement_config', $record);
        } else {
            $DB->insert_record('lti_placement_config', $record);
        }
    }

    /**
     * Removes tool placements and related config.
     *
     * @param int $toolid The tool ID.
     * @param array $placementtypeids (optional) Array of placement type IDs used to specify only specific tool placements
     *                                to delete. If not specified, all tool placements will be deleted.
     * @return void
     */
    public static function delete_tool_placements(int $toolid, array $placementtypeids = []): void {
        global $DB;

        $sqlparams = ['toolid' => $toolid];
        $wheresql = "toolid = :toolid";

        // Add placement type filter if placement type IDs have been provided.
        if (!empty($placementtypeids)) {
            [$insql, $inparams] = $DB->get_in_or_equal($placementtypeids, SQL_PARAMS_NAMED);
            $wheresql .= " AND placementtypeid $insql";
            $sqlparams += $inparams;
        }

        // Build subquery used for both placement config and placement status deletions.
        $subquery = "placementid IN (SELECT id FROM {lti_placement} WHERE $wheresql)";

        // Delete related placement configs.
        $DB->delete_records_select('lti_placement_config', $subquery, $sqlparams);

        // Delete related placement statuses.
        $DB->delete_records_select('lti_placement_status', $subquery, $sqlparams);

        // Delete the placements.
        $DB->delete_records_select('lti_placement', $wheresql, $sqlparams);
    }
}
