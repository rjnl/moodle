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
 * Utility code for LTI service handling.
 *
 * @package mod_lti
 * @copyright  Copyright (c) 2011 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Chris Scribner
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/lti/locallib.php');
require_once($CFG->dirroot.'/ltix/constants.php');

use core_ltix\local\ltiservice\service_helper;


/**
 * Lti get response xml
 *
 * @deprecated since Moodle 4.4
 * @param [type] $codemajor
 * @param [type] $description
 * @param [type] $messageref
 * @param [type] $messagetype
 * @return void
 */
function lti_get_response_xml($codemajor, $description, $messageref, $messagetype) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\local\ltiservice\service_helper::get_response_xml() instead.',
        DEBUG_DEVELOPER);

    return service_helper::get_response_xml($codemajor, $description, $messageref, $messagetype);
}

/**
 * @deprecated since Moodle 4.4
 */
function lti_parse_message_id($xml) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\local\ltiservice\service_helper::parse_message_id() instead.',
        DEBUG_DEVELOPER);

    return service_helper::parse_message_id($xml);
}

/**
 * @deprecated since Moodle 4.4
 */
function lti_parse_grade_replace_message($xml) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\local\ltiservice\service_helper::parse_grade_replace_message() instead.',
        DEBUG_DEVELOPER);

    return service_helper::parse_grade_replace_message($xml);
}

/**
 * @deprecated since Moodle 4.4
 */
function lti_parse_grade_read_message($xml) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\local\ltiservice\service_helper::parse_grade_read_message() instead.',
        DEBUG_DEVELOPER);

    return service_helper::parse_grade_read_message($xml);
}

/**
 * @deprecated since Moodle 4.4
 */
function lti_parse_grade_delete_message($xml) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\local\ltiservice\service_helper::parse_grade_delete_message() instead.',
        DEBUG_DEVELOPER);

    return service_helper::parse_grade_delete_message($xml);
}

/**
 * @deprecated since Moodle 4.4
 */
function lti_accepts_grades($ltiinstance) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\local\ltiservice\service_helper::accepts_grades() instead.',
        DEBUG_DEVELOPER);

    return service_helper::accepts_grades($ltiinstance);
}

/**
 * Set the passed user ID to the session user.
 *
 * @deprecated since Moodle 4.4
 * @param int $userid
 */
function lti_set_session_user($userid) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\local\ltiservice\service_helper::set_session_user() instead.',
        DEBUG_DEVELOPER);

    return service_helper::set_session_user($userid);
}

/**
 * @deprecated since Moodle 4.4
 */
function lti_update_grade($ltiinstance, $userid, $launchid, $gradeval) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\local\ltiservice\service_helper::update_grade() instead.',
        DEBUG_DEVELOPER);

    return service_helper::update_grade($ltiinstance, $userid, $launchid, $gradeval);
}

/**
 * @deprecated since Moodle 4.4
 */
function lti_read_grade($ltiinstance, $userid) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\local\ltiservice\service_helper::read_grade() instead.',
        DEBUG_DEVELOPER);

    return service_helper::read_grade($ltiinstance, $userid);
}

/**
 * @deprecated since Moodle 4.4
 */
function lti_delete_grade($ltiinstance, $userid) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\local\ltiservice\service_helper::delete_grade() instead.',
        DEBUG_DEVELOPER);

    return service_helper::delete_grade($ltiinstance, $userid);
}

/**
 * @deprecated since Moodle 4.4
 */
function lti_verify_message($key, $sharedsecrets, $body, $headers = null) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\local\ltiservice\service_helper::verify_message() instead.',
        DEBUG_DEVELOPER);

    return service_helper::verify_message($key, $sharedsecrets, $body, $headers);
}

/**
 * Validate source ID from external request
 *
 * @deprecated since Moodle 4.4
 * @param object $ltiinstance
 * @param object $parsed
 * @throws Exception
 */
function lti_verify_sourcedid($ltiinstance, $parsed) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\local\ltiservice\service_helper::verify_sourcedid() instead.',
        DEBUG_DEVELOPER);

    service_helper::verify_sourcedid($ltiinstance, $parsed);
}

/**
 * Extend the LTI services through the ltisource plugins
 *
 * deprecated since Moodle 4.4
 * @param stdClass $data LTI request data
 * @return bool
 * @throws coding_exception
 */
function lti_extend_lti_services($data) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\local\ltiservice\service_helper::extend_lti_services() instead.',
        DEBUG_DEVELOPER);

    service_helper::extend_lti_services($data);
}
