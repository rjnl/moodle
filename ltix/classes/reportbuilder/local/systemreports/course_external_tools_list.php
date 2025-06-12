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

namespace core_ltix\reportbuilder\local\systemreports;

defined('MOODLE_INTERNAL') || die();

use core_ltix\local\placement\placement_status;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\report\column;
use core_ltix\reportbuilder\local\entities\tool_types;
use core_reportbuilder\system_report;


/**
 * Course external tools list system report class implementation.
 *
 * @package    core_ltix
 * @copyright  2023 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_external_tools_list extends system_report {

    /** @var \stdClass the course to constrain the report to. */
    protected \stdClass $course;

    /** @var int the usage count for the tool represented in a row, and set by row_callback(). */
    protected int $perrowtoolusage = 0;

    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        global $DB;

        $this->course = get_course($this->get_context()->instanceid);

        // Our main entity, it contains all the column definitions that we need.
        $entitymain = new tool_types();
        $entitymainalias = $entitymain->get_table_alias('lti_types');

        $this->set_main_table('lti_types', $entitymainalias);
        $this->add_entity($entitymain);

        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns($entitymain);
        $this->add_filters();
        $this->add_actions();

        // We need id and course in the actions column, without entity prefixes, so add these here.
        // We also need access to the tool usage count in a few places (the usage column as well as the actions column).
        $ti = database::generate_param_name(); // Tool instance param.
        $this->add_base_fields("{$entitymainalias}.id, {$entitymainalias}.course, ".
            "(SELECT COUNT($ti.id)
                FROM {lti} $ti
                WHERE $ti.typeid = {$entitymainalias}.id) AS toolusage");

        // Join the types_categories table, to include only tools available to the current course's category.
        $cattablealias = database::generate_alias();
        $joinsql = "LEFT JOIN {lti_types_categories} {$cattablealias}
                           ON ({$cattablealias}.typeid = {$entitymainalias}.id)";
        $this->add_join($joinsql);

        // Scope the report to the course context and include only those tools available to the category.
        $paramprefix = database::generate_param_name();
        $coursevisibleparam = database::generate_param_name();
        $categoryparam = database::generate_param_name();
        $toolstateparam = database::generate_param_name();
        [$insql, $params] = $DB->get_in_or_equal([get_site()->id, $this->course->id], SQL_PARAMS_NAMED, "{$paramprefix}_");
        $wheresql = "{$entitymainalias}.course {$insql} ".
            "AND {$entitymainalias}.coursevisible NOT IN (:{$coursevisibleparam}) ".
            "AND ({$cattablealias}.id IS NULL OR {$cattablealias}.categoryid = :{$categoryparam}) ".
            "AND {$entitymainalias}.state = :{$toolstateparam}";
        $params = array_merge(
            $params,
            [
                $coursevisibleparam => \core_ltix\constants::LTI_COURSEVISIBLE_NO,
                $categoryparam => $this->course->category,
                $toolstateparam => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED
            ]
        );
        $this->add_base_condition_sql($wheresql, $params);

        $this->set_downloadable(false, get_string('courseexternaltools', 'core_ltix'));
        $this->set_default_per_page(10);
        $this->set_default_no_results_notice(null);
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('moodle/ltix:viewcoursetools', $this->get_context());
    }

    public function row_callback(\stdClass $row): void {
        $this->perrowtoolusage = $row->toolusage;
    }

    /**
     * Adds the columns we want to display in the report.
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     * @param tool_types $tooltypesentity
     * @return void
     */
    protected function add_columns(tool_types $tooltypesentity): void {
        $entitymainalias = $tooltypesentity->get_table_alias('lti_types');

        $columns = [
            'tool_types:name',
            'tool_types:description',
        ];

        $this->add_columns_from_entities($columns);

        // Add a column to show all placement types for each tool.
        $this->add_column(new column(
            'activeplacement',
            new \lang_string('activeplacement', 'core_ltix'),
            $tooltypesentity->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(false)
            ->add_field("{$entitymainalias}.id")
            ->add_callback(function($toolid) {
                global $DB;

                $coursecontext = \core\context\course::instance($this->course->id);

                $sql = "SELECT pt.type
                        FROM {lti_types} t
                        JOIN {lti_placement} p ON t.id = p.toolid
                        JOIN {lti_placement_type} pt ON p.placementtypeid = pt.id
                        LEFT JOIN {lti_placement_config} pc ON p.id = pc.placementid AND pc.name = :placementconfigname
                        LEFT JOIN {lti_placement_status} ps ON p.id = ps.placementid AND ps.contextid = :contextid
                        WHERE t.id = :toolid
                          AND t.course = :courseid
                          AND (
                            ps.status = :placementenabledstatus
                            OR (ps.status IS NULL AND pc.value = :placementconfigvalue)
                          )";

                $placements = $DB->get_records_sql($sql, [
                    'toolid' => $toolid,
                    'courseid' => $this->course->id,
                    'contextid' => $coursecontext->id,
                    'placementenabledstatus' => placement_status::ENABLED->value,
                    'placementconfigname' => 'default_usage',
                    'placementconfigvalue' => 'enabled',
                ]);

                if (empty($placements)) {
                    return '';
                }

                $placementnames = [];
                foreach ($placements as $placement) {
                    $placementnames[] = get_string($placement->type, 'core_ltix');
                }

                return implode(', ', $placementnames);
            });

        // Tool usage column using a custom SQL subquery (defined in initialise method) to count tool instances within the course.
        // TODO: This should be replaced with proper column aggregation once that's added to system_report instances in MDL-76392.
        $this->add_column(new column(
            'usage',
            new \lang_string('usage', 'core_ltix'),
            $tooltypesentity->get_entity_name()
        ))
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_field("{$entitymainalias}.id")
            ->add_callback(fn() => $this->perrowtoolusage);

        // Attempt to create a dummy actions column, working around the limitations of the official actions feature.
        $this->add_column(new column(
            'actions', new \lang_string('actions'),
            $tooltypesentity->get_entity_name()
        ))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(false)
            ->add_fields("{$entitymainalias}.id, {$entitymainalias}.course, {$entitymainalias}.name")
            ->add_callback(function($field, $row) {
                global $OUTPUT;

                // Lock actions when the user can't add course tools.
                if (!has_capability('moodle/ltix:addcoursetool', \context_course::instance($this->course->id))) {
                    return \html_writer::div(
                        \html_writer::div(
                            $OUTPUT->pix_icon('t/locked', get_string('courseexternaltoolsnoeditpermissions', 'core_ltix')
                        ), 'tool-action-icon-container'), 'd-flex justify-content-end'
                    );
                }

                // Build and display an action menu.
                $menu = new \action_menu();
                $menu->set_menu_trigger($OUTPUT->pix_icon('i/moremenu', get_string('actions', 'core')),
                    'btn btn-icon d-flex'); // TODO check 'actions' lang string with UX.

                $menu->add(new \action_menu_link(
                    new \moodle_url('#'),
                    null,
                    get_string('manageplacements', 'core_ltix'),
                    null,
                    [
                        'data-action' => 'manage-placements',
                        'data-toolid' => $row->id,
                        'data-courseid' => $this->course->id,
                    ]
                ));

                if (get_site()->id != $row->course) {
                    $divider = new \core\output\action_menu\filler();
                    $divider->primary = false;
                    $menu->add($divider);

                    $menu->add(new \action_menu_link(
                        new \moodle_url('/ltix/coursetooledit.php', ['course' => $row->course, 'typeid' => $row->id]),
                        null,
                        get_string('edit', 'core'),
                        null
                    ));

                    $menu->add(new \action_menu_link(
                        new \moodle_url('#'),
                        null,
                        get_string('delete', 'core'),
                        null,
                        [
                            'data-action' => 'course-tool-delete',
                            'data-course-tool-id' => $row->id,
                            'data-course-tool-name' => $row->name,
                            'data-course-tool-usage' => $this->perrowtoolusage,
                            'class' => 'text-danger',
                        ],
                    ));
                }

                return $OUTPUT->render($menu);
            });

        // Default sorting.
        $this->set_initial_sort_column('tool_types:name', SORT_ASC);
    }

    /**
     * Add any actions for this report.
     *
     * @return void
     */
    protected function add_actions(): void {
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {

        $this->add_filters_from_entities([]);
    }
}
