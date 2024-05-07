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
 * Page allowing instructors to configure course-level tools.
 *
 * @package    core_ltix
 * @copyright  2023 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;

require_once('../config.php');

$courseid = required_param('course', PARAM_INT);
$typeid = optional_param('typeid', null, PARAM_INT);

// Permissions etc.
require_login($courseid, false);
require_capability('moodle/ltix:addcoursetool', context_course::instance($courseid));
if (!empty($typeid)) {
    $type = \core_ltix\helper::get_type_type_config($typeid);
    if ($type->course != $courseid || $type->course == get_site()->id) {
        throw new moodle_exception('You do not have permissions to edit this tool type.');
    }
} else {
    $type = (object) ['lti_clientid' => null];
}

// Page setup.
$url = new moodle_url('/ltix/coursetooledit.php', ['courseid' => $courseid]);
$pageheading = !empty($typeid) ? get_string('courseexternaltooledit', 'core_ltix', $type->lti_typename) :
    get_string('courseexternaltooladd', 'core_ltix');

$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($pageheading);
$PAGE->set_secondary_active_tab('coursetools');
$PAGE->add_body_class('limitedwidth');

$form = new \core_ltix\form\edit_types($url, (object)array('id' => $typeid, 'clientid' => $type->lti_clientid, 'iscoursetool' => true));
if ($form->is_cancelled()) {

    redirect(new moodle_url('/ltix/coursetools.php', ['id' => $courseid]));
} else if ($data = $form->get_data()) {

    require_sesskey();

    if (!empty($data->typeid)) {
        $type = (object) ['id' => $data->typeid];
        \core_ltix\helper::load_type_if_cartridge($data);
        \core_ltix\helper::update_type($type, $data);
        $redirecturl = new moodle_url('/ltix/coursetools.php', ['id' => $courseid]);
        $notice = get_string('courseexternaltooleditsuccess', 'core_ltix');
    } else {
        $type = (object) ['state' => LTI_TOOL_STATE_CONFIGURED, 'course' => $data->course];
        \core_ltix\helper::load_type_if_cartridge($data);
        \core_ltix\helper::add_type($type, $data);
        $redirecturl = new moodle_url('/ltix/coursetools.php', ['id' => $courseid]);
        $notice = get_string('courseexternaltooladdsuccess', 'core_ltix', $type->name);
    }

    redirect($redirecturl, $notice, 0, notification::NOTIFY_SUCCESS);
}

// Display the form.
echo $OUTPUT->header();
echo $OUTPUT->heading($pageheading);

if (!empty($typeid)) {
    $form->set_data($type);
}
$form->display();

echo $OUTPUT->footer();
