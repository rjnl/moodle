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
use core_ltix\helper;
use core_ltix\local\placement\placement_status;

/**
 * Manage placements form
 *
 * @package    core_ltix
 * @copyright  2025 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_placements_form extends \core_form\dynamic_form {

    #[\Override]
    protected function get_context_for_dynamic_submission(): context {
        $courseid = $this->optional_param('courseid', null, PARAM_INT);
        return context_course::instance($courseid);
    }

    #[\Override]
    protected function check_access_for_dynamic_submission(): void {
        $courseid = $this->optional_param('courseid', null, PARAM_INT);

        require_capability('moodle/ltix:addcoursetool', context_course::instance($courseid));
    }

    #[\Override]
    public function set_data_for_dynamic_submission(): void {
        return;
    }

    #[\Override]
    public function process_dynamic_submission(): void {
        global $DB;

        $data = $this->get_data();

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
            if (!isset($placementconfig['placementtype_' . $placementtypeid])) {
                continue; // No config for this placement type, so skip.
            }

            $status = $placementconfig['placementtype_' . $placementtypeid];

            // Check if there is an existing status record.
            $statusrecord = $DB->get_record('lti_placement_status', ['contextid' => $contextid, 'placementid' => $placementid]);

            if ($statusrecord) {
                // Record exists, update it.
                $DB->set_field('lti_placement_status', 'status', $status,
                    ['contextid' => $contextid, 'placementid' => $placementid]);
            } else {
                // No existing record, insert new.
                $DB->insert_record('lti_placement_status',
                    ['contextid' => $contextid, 'placementid' => $placementid, 'status' => $status]);
            }
        }
    }

    #[\Override]
    public function definition(): void {
        $mform = $this->_form;

        $courseid = $this->optional_param('courseid', null, PARAM_INT);
        $toolid = $this->optional_param('toolid', null, PARAM_INT);

        $statusrecords = helper::get_placement_status_for_tool($toolid, $courseid);

        foreach ($statusrecords as $record) {
            $id = $record->placementtypeid;
            $type = $record->type;
            $status = $record->status ?? placement_status::DISABLED->value;
            $placementypecomponent = $record->component;

            // Add a checkbox for each registered placement type.
            $mform->addElement(
                'advcheckbox',
                'placementtype_' . $id,
                get_string($type, $placementypecomponent),
                get_string($type . '_description', $placementypecomponent)
            );

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

    #[\Override]
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/ltix/coursetools.php');
    }
}
