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

use core_ltix\local\ltiservice\service_helper;


/**
 * Lti get response xml
 *
 * @deprecated since Moodle 5.1
 * @param [type] $codemajor
 * @param [type] $description
 * @param [type] $messageref
 * @param [type] $messagetype
 * @return void
 */
#[\core\attribute\deprecated(
    since: '5.1',
    reason: 'Use \core_ltix\local\ltiservice\service_helper::get_response_xml() instead',
    mdl: 'MDL-79518'
)]
function lti_get_response_xml($codemajor, $description, $messageref, $messagetype) {
    \core\deprecation::emit_deprecation_if_present(__FUNCTION__);
    return service_helper::get_response_xml($codemajor, $description, $messageref, $messagetype);
}

/**
 * @deprecated since Moodle 5.1
 */
#[\core\attribute\deprecated(
    since: '5.1',
    reason: 'Use \core_ltix\local\ltiservice\service_helper::parse_message_id() instead',
    mdl: 'MDL-79518'
)]
function lti_parse_message_id($xml) {
    \core\deprecation::emit_deprecation_if_present(__FUNCTION__);
    return service_helper::parse_message_id($xml);
}

/**
 * @deprecated since Moodle 5.1
 */
#[\core\attribute\deprecated(
    since: '5.1',
    reason: 'Use \core_ltix\local\ltiservice\service_helper::parse_grade_replace_message() instead',
    mdl: 'MDL-79518'
)]
function lti_parse_grade_replace_message($xml) {
    \core\deprecation::emit_deprecation_if_present(__FUNCTION__);
    return service_helper::parse_grade_replace_message($xml);
}

/**
 * @deprecated since Moodle 5.1
 */
#[\core\attribute\deprecated(
    since: '5.1',
    reason: 'Use \core_ltix\local\ltiservice\service_helper::parse_grade_read_message() instead',
    mdl: 'MDL-79518'
)]
function lti_parse_grade_read_message($xml) {
    \core\deprecation::emit_deprecation_if_present(__FUNCTION__);
    return service_helper::parse_grade_read_message($xml);
}

/**
 * @deprecated since Moodle 5.1
 */
#[\core\attribute\deprecated(
    since: '5.1',
    reason: 'Use \core_ltix\local\ltiservice\service_helper::parse_grade_delete_message() instead',
    mdl: 'MDL-79518'
)]
function lti_parse_grade_delete_message($xml) {
    \core\deprecation::emit_deprecation_if_present(__FUNCTION__);
    return service_helper::parse_grade_delete_message($xml);
}

/**
 * @deprecated since Moodle 5.1
 */
#[\core\attribute\deprecated(
    since: '5.1',
    reason: 'Use \core_ltix\local\ltiservice\service_helper::accepts_grades() instead',
    mdl: 'MDL-79518'
)]
function lti_accepts_grades($ltiinstance) {
    \core\deprecation::emit_deprecation_if_present(__FUNCTION__);
    return service_helper::accepts_grades($ltiinstance);
}

/**
 * Set the passed user ID to the session user.
 *
 * @deprecated since Moodle 5.1
 * @param int $userid
 */
#[\core\attribute\deprecated(
    since: '5.1',
    reason: 'Use \core_ltix\local\ltiservice\service_helper::set_session_user() instead',
    mdl: 'MDL-79518'
)]
function lti_set_session_user($userid) {
    \core\deprecation::emit_deprecation_if_present(__FUNCTION__);
    return service_helper::set_session_user($userid);
}

/**
 * @deprecated since Moodle 5.1
 */
#[\core\attribute\deprecated(
    since: '5.1',
    reason: 'Use \core_ltix\local\ltiservice\service_helper::update_grade() instead',
    mdl: 'MDL-79518'
)]
function lti_update_grade($ltiinstance, $userid, $launchid, $gradeval) {
    \core\deprecation::emit_deprecation_if_present(__FUNCTION__);
    return service_helper::update_grade($ltiinstance, $userid, $launchid, $gradeval);
}

/**
 * @deprecated since Moodle 5.1
 */
#[\core\attribute\deprecated(
    since: '5.1',
    reason: 'Use \core_ltix\local\ltiservice\service_helper::read_grade() instead',
    mdl: 'MDL-79518'
)]
function lti_read_grade($ltiinstance, $userid) {
    \core\deprecation::emit_deprecation_if_present(__FUNCTION__);
    return service_helper::read_grade($ltiinstance, $userid);
}

/**
 * @deprecated since Moodle 5.1
 */
#[\core\attribute\deprecated(
    since: '5.1',
    reason: 'Use \core_ltix\local\ltiservice\service_helper::delete_grade() instead',
    mdl: 'MDL-79518'
)]
function lti_delete_grade($ltiinstance, $userid) {
    \core\deprecation::emit_deprecation_if_present(__FUNCTION__);
    return service_helper::delete_grade($ltiinstance, $userid);
}

/**
 * @deprecated since Moodle 5.1
 */
#[\core\attribute\deprecated(
    since: '5.1',
    reason: 'Use \core_ltix\local\ltiservice\service_helper::verify_message() instead',
    mdl: 'MDL-79518'
)]
function lti_verify_message($key, $sharedsecrets, $body, $headers = null) {
    \core\deprecation::emit_deprecation_if_present(__FUNCTION__);
    return service_helper::verify_message($key, $sharedsecrets, $body, $headers);
}

/**
 * Validate source ID from external request
 *
 * @deprecated since Moodle 5.1
 * @param object $ltiinstance
 * @param object $parsed
 * @throws Exception
 */
#[\core\attribute\deprecated(
    since: '5.1',
    reason: 'Use \core_ltix\local\ltiservice\service_helper::verify_sourcedid() instead',
    mdl: 'MDL-79518'
)]
function lti_verify_sourcedid($ltiinstance, $parsed) {
    \core\deprecation::emit_deprecation_if_present(__FUNCTION__);
    service_helper::verify_sourcedid($ltiinstance, $parsed);
}

/**
 * Extend the LTI services through the ltisource plugins
 *
 * @deprecated since Moodle 5.1
 * @param stdClass $data LTI request data
 * @return bool
 * @throws coding_exception
 */
#[\core\attribute\deprecated(
    since: '5.1',
    reason: 'Use \core_ltix\local\ltiservice\service_helper::extend_lti_services() instead',
    mdl: 'MDL-79518'
)]
function lti_extend_lti_services($data) {
    \core\deprecation::emit_deprecation_if_present(__FUNCTION__);
    service_helper::extend_lti_services($data);
}
