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

namespace core_ltix\form;

use context_course;
use context;
use moodle_url;

/**
 * Manage placements form
 *
 * @package    core_ltix
 * @copyright  2025 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_placements_form extends \core_form\dynamic_form {

    /**
     * Return form context
     */
    protected function get_context_for_dynamic_submission(): context {
        $courseid = $this->optional_param('courseid', null, PARAM_INT);
        return context_course::instance($courseid);
    }

    /**
     * Check if current user can access to this form, otherwise throw exception
     */
    protected function check_access_for_dynamic_submission(): void {
        $courseid = $this->optional_param('courseid', null, PARAM_INT);

        require_capability('moodle/ltix:addcoursetool', context_course::instance($courseid));
    }

    /**
     * Load in existing data as form defaults
     */
    public function set_data_for_dynamic_submission(): void {
        return;
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     */
    public function process_dynamic_submission() {
        global $DB;

        $data = $this->get_data();

        $courseid = $data->courseid;
        $toolid = $data->toolid;
        $contextid = $data->contextid;

        // Get config for placement types only.
        $placementconfig = array_filter(
            get_object_vars($data),
            fn($val, $key) => str_starts_with($key, 'placementtype_'),
            ARRAY_FILTER_USE_BOTH
        );

        // Get placement ids for this tool.
        $toolplacementids = $DB->get_records_menu('lti_placement', ['toolid' => $toolid], 'id ASC', 'id, placementtypeid');

        foreach ($toolplacementids as $placementid => $placementtypeid) {
            if (isset($placementconfig['placementtype_' . $placementtypeid])) {
                $status = $placementconfig['placementtype_' . $placementtypeid];

                try {
                    // Attempt to insert first.
                    $DB->insert_record('lti_placement_status',
                        ['contextid' => $contextid, 'placementid' => $placementid, 'status' => $status]);
                } catch (\dml_exception $e) {
                    // Existing record, so update instead.
                    $DB->set_field('lti_placement_status', 'status', $status,
                        ['contextid' => $contextid, 'placementid' => $placementid]);
                }
            }
        }
    }

    /**
     * Define the form elements.
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $courseid = $this->optional_param('courseid', null, PARAM_INT);
        $toolid = $this->optional_param('toolid', null, PARAM_INT);

        // Get registered placement types.
        $registeredplacementtypes = $DB->get_records_menu('lti_placement_type', null, 'id ASC', 'id,type');

        $sql = <<<EOF
            SELECT lps.id, lps.placementid, p.placementtypeid, lps.status
            FROM {lti_placement_status} lps
            JOIN {lti_placement} p ON (p.id = lps.placementid)
            JOIN {lti_placement_type} pt ON (pt.id = p.placementtypeid)
            WHERE p.toolid = :toolid AND lps.contextid = :contextid
        EOF;

        $params = [
            'toolid' => $toolid,
            'contextid' => context_course::instance($courseid)->id,
        ];

        $records = $DB->get_records_sql($sql, $params);

        // Lookup array for quick status access.
        $statusbyplacementtype = [];
        foreach ($records as $record) {
            $statusbyplacementtype[$record->placementtypeid] = $record->status;
        }

        foreach ($registeredplacementtypes as $id => $type) {
            // Add a checkbox for each registered placement type.
            $mform->addElement(
                'advcheckbox',
                'placementtype_' . $id,
                get_string($type, 'core_ltix'),
                get_string($type.'_description', 'core_ltix')
            );

            $status = \core_ltix\local\placement\placement_status::DISABLED->value;
            if (isset($statusbyplacementtype[$id])) {
                $status = $statusbyplacementtype[$id];
            }

            $mform->setDefault('placementtype_' . $id, $status);
            $mform->setType('placementtype_' . $id, PARAM_BOOL);
        }

        $mform->addElement('hidden', 'toolid', $this->optional_param('toolid', null, PARAM_INT));
        $mform->setType('toolid', PARAM_INT);

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'contextid', context_course::instance($courseid)->id);
        $mform->setType('contextid', PARAM_INT);
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/ltix/coursetools.php');
    }
}
