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
 * Handle the return from the Tool Provider after registering a tool proxy.
 *
 * @package    core_ltix
 * @copyright  2015 Ryan Wyllie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');

$status = optional_param('status', '', PARAM_TEXT);
$msg = optional_param('lti_msg', '', PARAM_TEXT);
$err = optional_param('lti_errormsg', '', PARAM_TEXT);
$id = optional_param('id', 0, PARAM_INT);

// No guest autologin.
require_sesskey();
require_login(0, false);

$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

$pageurl = new moodle_url('/ltix/externalregistrationreturn.php');
$PAGE->set_context($systemcontext);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('maintenance');
$output = $PAGE->get_renderer('core_ltix');
echo $output->header();

// Check status and lti_errormsg.
if ($status !== 'success' && empty($err)) {
    // We have a failed status and an empty lti_errormsg. Check if we can use lti_msg.
    if (!empty($msg)) {
        // The lti_msg attribute is set, use this as the error message.
        $err = $msg;
    } else {
        // Otherwise, use our generic error message.
        $err = get_string('failedtocreatetooltype', 'core_ltix');
    }
}
$params = array('message' => s($msg), 'error' => s($err), 'id' => $id, 'status' => s($status));

$page = new \core_ltix\output\external_registration_return_page();
echo $output->render($page);

$PAGE->requires->js_call_amd('core_ltix/external_registration_return', 'init', $params);
echo $output->footer();
