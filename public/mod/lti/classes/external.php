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
 * External tool module external API
 *
 * @package    mod_lti
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

use core_course\external\helper_for_get_mods_by_courses;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use core_external\util;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/lti/lib.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');

/**
 * External tool module external functions
 *
 * @package    mod_lti
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class mod_lti_external extends external_api {

    /**
     * Returns structure be used for returning a tool type from a web service.
     *
     * @deprecated since Moodle 4.4
     *  @return external_function_parameters
     * @since Moodle 3.1
     */
    private static function tool_type_return_structure() {
        return \core_ltix\external::tool_type_return_structure();
    }

    /**
     * Returns description of a tool proxy
     *
     * @deprecated since Moodle 4.4
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    private static function tool_proxy_return_structure() {
        return \core_ltix\external::tool_proxy_return_structure();
    }

    /**
     * Returns description of method parameters
     *
     * @deprecated since Moodle 4.4
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_tool_proxies_parameters() {
        return \core_ltix\external::get_tool_proxies_parameters();
    }

    /**
     * Returns the tool types.
     *
     * @deprecated since Moodle 4.4
     * @param bool $orphanedonly Retrieve only tool proxies that do not have a corresponding tool type
     * @return array of tool types
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function get_tool_proxies($orphanedonly) {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\external:get_tool_proxies instead.',
                  DEBUG_DEVELOPER);
        return \core_ltix\external::get_tool_proxies($orphanedonly);
    }

    /**
     * Mark the function as deprecated.
     * @return bool
     */
    public static function get_tool_proxies_is_deprecated() {
        return true;
    }

    /**
     * Returns description of method result value.
     *
     * @deprecated since Moodle 4.4
     * @return \core_external\external_description
     * @since Moodle 3.1
     */
    public static function get_tool_proxies_returns() {
        return new external_multiple_structure(
            self::tool_proxy_return_structure()
        );
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function get_tool_launch_data_parameters() {
        return new external_function_parameters(
            array(
                'toolid' => new external_value(PARAM_INT, 'external tool instance id')
            )
        );
    }

    /**
     * Return the launch data for a given external tool.
     *
     * @param int $toolid the external tool instance id
     * @return array of warnings and launch data
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function get_tool_launch_data($toolid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/lti/lib.php');

        $params = self::validate_parameters(self::get_tool_launch_data_parameters(),
                                            array(
                                                'toolid' => $toolid
                                            ));
        $warnings = array();

        // Request and permission validation.
        $lti = $DB->get_record('lti', array('id' => $params['toolid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($lti, 'lti');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/lti:view', $context);

        $lti->cmid = $cm->id;
        list($endpoint, $parms) = \core_ltix\helper::get_launch_data($lti);

        $parameters = array();
        foreach ($parms as $name => $value) {
            $parameters[] = array(
                'name' => $name,
                'value' => $value
            );
        }

        $result = array();
        $result['endpoint'] = $endpoint;
        $result['parameters'] = $parameters;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return \core_external\external_description
     * @since Moodle 3.0
     */
    public static function get_tool_launch_data_returns() {
        return new external_single_structure(
            array(
                'endpoint' => new external_value(PARAM_RAW, 'Endpoint URL'), // Using PARAM_RAW as is defined in the module.
                'parameters' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_NOTAGS, 'Parameter name'),
                            'value' => new external_value(PARAM_RAW, 'Parameter value')
                        )
                    )
                ),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Describes the parameters for get_ltis_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function get_ltis_by_courses_parameters() {
        return new external_function_parameters (
            array(
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'course id'), 'Array of course ids', VALUE_DEFAULT, array()
                ),
            )
        );
    }

    /**
     * Returns a list of external tools in a provided list of courses,
     * if no list is provided all external tools that the user can view will be returned.
     *
     * @param array $courseids the course ids
     * @return array the lti details
     * @since Moodle 3.0
     */
    public static function get_ltis_by_courses($courseids = array()) {
        global $CFG;

        $returnedltis = array();
        $warnings = array();

        $params = self::validate_parameters(self::get_ltis_by_courses_parameters(), array('courseids' => $courseids));

        $mycourses = array();
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = util::validate_courses($params['courseids'], $mycourses);

            // Get the ltis in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $ltis = get_all_instances_in_courses("lti", $courses);

            foreach ($ltis as $lti) {

                $context = context_module::instance($lti->coursemodule);

                // Entry to return.
                $module = helper_for_get_mods_by_courses::standard_coursemodule_element_values(
                        $lti, 'mod_lti', 'moodle/course:manageactivities', 'mod/lti:view');

                $viewablefields = [];
                if (has_capability('mod/lti:view', $context)) {
                    $viewablefields = array('launchcontainer', 'showtitlelaunch', 'showdescriptionlaunch', 'icon', 'secureicon');
                }

                // Check additional permissions for returning optional private settings.
                if (has_capability('moodle/course:manageactivities', $context)) {
                    $additionalfields = array('timecreated', 'timemodified', 'typeid', 'toolurl', 'securetoolurl',
                        'instructorchoicesendname', 'instructorchoicesendemailaddr', 'instructorchoiceallowroster',
                        'instructorchoiceallowsetting', 'instructorcustomparameters', 'instructorchoiceacceptgrades', 'grade',
                        'resourcekey', 'password', 'debuglaunch', 'servicesalt');
                    $viewablefields = array_merge($viewablefields, $additionalfields);
                }

                foreach ($viewablefields as $field) {
                    $module[$field] = $lti->{$field};
                }

                $returnedltis[] = $module;
            }
        }

        $result = array();
        $result['ltis'] = $returnedltis;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the get_ltis_by_courses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.0
     */
    public static function get_ltis_by_courses_returns() {

        return new external_single_structure(
            array(
                'ltis' => new external_multiple_structure(
                    new external_single_structure(array_merge(
                        helper_for_get_mods_by_courses::standard_coursemodule_elements_returns(true),
                        [
                            'timecreated' => new external_value(PARAM_INT, 'Time of creation', VALUE_OPTIONAL),
                            'timemodified' => new external_value(PARAM_INT, 'Time of last modification', VALUE_OPTIONAL),
                            'typeid' => new external_value(PARAM_INT, 'Type id', VALUE_OPTIONAL),
                            'toolurl' => new external_value(PARAM_URL, 'Tool url', VALUE_OPTIONAL),
                            'securetoolurl' => new external_value(PARAM_RAW, 'Secure tool url', VALUE_OPTIONAL),
                            'instructorchoicesendname' => new external_value(PARAM_TEXT, 'Instructor choice send name',
                                                                               VALUE_OPTIONAL),
                            'instructorchoicesendemailaddr' => new external_value(PARAM_INT, 'instructor choice send mail address',
                                                                                    VALUE_OPTIONAL),
                            'instructorchoiceallowroster' => new external_value(PARAM_INT, 'Instructor choice allow roster',
                                                                                VALUE_OPTIONAL),
                            'instructorchoiceallowsetting' => new external_value(PARAM_INT, 'Instructor choice allow setting',
                                                                                 VALUE_OPTIONAL),
                            'instructorcustomparameters' => new external_value(PARAM_RAW, 'instructor custom parameters',
                                                                                VALUE_OPTIONAL),
                            'instructorchoiceacceptgrades' => new external_value(PARAM_INT, 'instructor choice accept grades',
                                                                                    VALUE_OPTIONAL),
                            'grade' => new external_value(PARAM_INT, 'Enable grades', VALUE_OPTIONAL),
                            'launchcontainer' => new external_value(PARAM_INT, 'Launch container mode', VALUE_OPTIONAL),
                            'resourcekey' => new external_value(PARAM_RAW, 'Resource key', VALUE_OPTIONAL),
                            'password' => new external_value(PARAM_RAW, 'Shared secret', VALUE_OPTIONAL),
                            'debuglaunch' => new external_value(PARAM_INT, 'Debug launch', VALUE_OPTIONAL),
                            'showtitlelaunch' => new external_value(PARAM_INT, 'Show title launch', VALUE_OPTIONAL),
                            'showdescriptionlaunch' => new external_value(PARAM_INT, 'Show description launch', VALUE_OPTIONAL),
                            'servicesalt' => new external_value(PARAM_RAW, 'Service salt', VALUE_OPTIONAL),
                            'icon' => new external_value(PARAM_URL, 'Alternative icon URL', VALUE_OPTIONAL),
                            'secureicon' => new external_value(PARAM_URL, 'Secure icon URL', VALUE_OPTIONAL),
                        ]
                    ), 'Tool')
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function view_lti_parameters() {
        return new external_function_parameters(
            array(
                'ltiid' => new external_value(PARAM_INT, 'lti instance id')
            )
        );
    }

    /**
     * Trigger the course module viewed event and update the module completion status.
     *
     * @param int $ltiid the lti instance id
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function view_lti($ltiid) {
        global $DB;

        $params = self::validate_parameters(self::view_lti_parameters(),
                                            array(
                                                'ltiid' => $ltiid
                                            ));
        $warnings = array();

        // Request and permission validation.
        $lti = $DB->get_record('lti', array('id' => $params['ltiid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($lti, 'lti');

        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/lti:view', $context);

        // Trigger course_module_viewed event and completion.
        lti_view($lti, $course, $cm, $context);

        $result = array();
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return \core_external\external_description
     * @since Moodle 3.0
     */
    public static function view_lti_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @deprecated since Moodle 4.4
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function create_tool_proxy_parameters() {
        return \core_ltix\external::create_tool_proxy_parameters();
    }

    /**
     * Creates a new tool proxy
     *
     * @deprecated since Moodle 4.4
     * @param string $name Tool proxy name
     * @param string $registrationurl Registration url
     * @param string[] $capabilityoffered List of capabilities this tool proxy should be offered
     * @param string[] $serviceoffered List of services this tool proxy should be offered
     * @return object The new tool proxy
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function create_tool_proxy($name, $registrationurl, $capabilityoffered, $serviceoffered) {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\external:create_tool_proxy instead.',
                  DEBUG_DEVELOPER);
        return \core_ltix\external::create_tool_proxy($name, $registrationurl, $capabilityoffered, $serviceoffered);
    }

    /**
     * Mark the function as deprecated.
     * @return bool
     */
    public static function create_tool_proxy_is_deprecated() {
        return true;
    }

    /**
     * Returns description of method result value
     *
     * @deprecated since Moodle 4.4
     * @return \core_external\external_description
     * @since Moodle 3.1
     */
    public static function create_tool_proxy_returns() {
        return self::tool_proxy_return_structure();
    }

    /**
     * Returns description of method parameters
     *
     * @deprecated since Moodle 4.4
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function delete_tool_proxy_parameters() {
        return \core_ltix\external::delete_tool_proxy_parameters();
    }

    /**
     * Trigger the course module viewed event and update the module completion status.
     *
     * @deprecated since Moodle 4.4
     * @param int $id the lti instance id
     * @return object The tool proxy
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function delete_tool_proxy($id) {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\external:delete_tool_proxy instead.',
                  DEBUG_DEVELOPER);
        return \core_ltix\external::delete_tool_proxy($id);
    }

    /**
     * Mark the function as deprecated.
     * @return bool
     */
    public static function delete_tool_proxy_is_deprecated() {
        return true;
    }

    /**
     * Returns description of method result value
     *
     * @deprecated since Moodle 4.4
     * @return \core_external\external_description
     * @since Moodle 3.1
     */
    public static function delete_tool_proxy_returns() {
        return self::tool_proxy_return_structure();
    }

    /**
     * Returns description of method parameters
     *
     * @deprecated since Moodle 4.4
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function get_tool_proxy_registration_request_parameters() {
        return \core_ltix\external::get_tool_proxy_registration_request_parameters();
    }

    /**
     * Returns the registration request for a tool proxy.
     *
     * @deprecated since Moodle 4.4
     * @param int $id the lti instance id
     * @return array of registration parameters
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function get_tool_proxy_registration_request($id) {
        debugging(__FUNCTION__ . '() is deprecated. Please use 
                   \core_ltix\external:get_tool_proxy_registration_request instead.', DEBUG_DEVELOPER);
        return \core_ltix\external::get_tool_proxy_registration_request($id);
    }

    /**
     * Mark the function as deprecated.
     * @return bool
     */
    public static function get_tool_proxy_registration_request_is_deprecated() {
        return true;
    }

    /**
     * Returns description of method result value
     *
     * @return \core_external\external_description
     * @since Moodle 3.1
     */
    public static function get_tool_proxy_registration_request_returns() {
        return \core_ltix\external::get_tool_proxy_registration_request_returns();
    }

    /**
     * Returns description of method parameters
     *
     * @deprecated since Moodle 4.4
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_tool_types_parameters() {
        return \core_ltix\external::get_tool_types_parameters();
    }

    /**
     * Returns the tool types.
     *
     * @deprecated since Moodle 4.4
     * @param int $toolproxyid The tool proxy id
     * @return array of tool types
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function get_tool_types($toolproxyid) {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\external:get_tool_types instead.',
                  DEBUG_DEVELOPER);
        return \core_ltix\external::get_tool_types($toolproxyid);
    }

    /**
     * Mark the function as deprecated.
     * @return bool
     */
    public static function get_tool_types_is_deprecated() {
        return true;
    }

    /**
     * Returns description of method result value
     *
     * @deprecated since Moodle 4.4
     * @return \core_external\external_description
     * @since Moodle 3.1
     */
    public static function get_tool_types_returns() {
        return new external_multiple_structure(
            self::tool_type_return_structure()
        );
    }

    /**
     * Returns description of method parameters
     *
     * @deprecated since Moodle 4.4
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function create_tool_type_parameters() {
        return \core_ltix\external::create_tool_type_parameters();
    }

    /**
     * Creates a tool type.
     *
     * @deprecated since Moodle 4.4
     * @param string $cartridgeurl Url of the xml cartridge representing the LTI tool
     * @param string $key The consumer key to identify this consumer
     * @param string $secret The secret
     * @return array created tool type
     * @since Moodle 3.1
     * @throws moodle_exception If the tool type could not be created
     */
    public static function create_tool_type($cartridgeurl, $key, $secret) {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\external:create_tool_type instead.',
            DEBUG_DEVELOPER);
        return \core_ltix\external::create_tool_type($cartridgeurl, $key, $secret);
    }

    /**
     * Mark the function as deprecated.
     * @return bool
     */
    public static function create_tool_type_is_deprecated() {
        return true;
    }

    /**
     * Returns description of method result value
     *
     * @deprecated since Moodle 4.4
     * @return \core_external\external_description
     * @since Moodle 3.1
     */
    public static function create_tool_type_returns() {
        return self::tool_type_return_structure();
    }

    /**
     * Returns description of method parameters
     *
     * @deprecated since Moodle 4.4
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function update_tool_type_parameters() {
        return \core_ltix\external::update_tool_type_parameters();
    }

    /**
     * Update a tool type.
     *
     * @deprecated since Moodle 4.4
     * @param int $id The id of the tool type to update
     * @param string $name The name of the tool type
     * @param string $description The name of the tool type
     * @param int $state The state of the tool type
     * @return array updated tool type
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function update_tool_type($id, $name, $description, $state) {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\external:update_tool_type instead.',
            DEBUG_DEVELOPER);
        return \core_ltix\external::update_tool_type($id, $name, $description, $state);
    }

    /**
     * Mark the function as deprecated.
     * @return bool
     */
    public static function update_tool_type_is_deprecated() {
        return true;
    }

    /**
     * Returns description of method result value
     * 
     * @deprecated since Moodle 4.4
     * @return \core_external\external_description
     * @since Moodle 3.1
     */
    public static function update_tool_type_returns() {
        return self::tool_type_return_structure();
    }

    /**
     * Returns description of method parameters
     *
     * @deprecated since Moodle 4.4
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function delete_tool_type_parameters() {
        return \core_ltix\external::delete_tool_type_parameters();
    }

    /**
     * Delete a tool type.
     *
     * @deprecated since Moodle 4.4
     * @param int $id The id of the tool type to be deleted
     * @return array deleted tool type
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function delete_tool_type($id) {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\external:delete_tool_type instead.',
            DEBUG_DEVELOPER);
        return \core_ltix\external::delete_tool_type($id);
    }

    /**
     * Mark the function as deprecated.
     * @return bool
     */
    public static function delete_tool_type_is_deprecated() {
        return true;
    }

    /**
     * Returns description of method result value
     *
     * @deprecated since Moodle 4.4
     * @return \core_external\external_description
     * @since Moodle 3.1
     */
    public static function delete_tool_type_returns() {
        return \core_ltix\external::delete_tool_type_returns();
    }

    /**
     * Returns description of method parameters
     *
     * @deprecated since Moodle 4.4
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function is_cartridge_parameters() {
        return \core_ltix\external::is_cartridge_parameters();
    }

    /**
     * Determine if the url to a tool is for a cartridge.
     *
     * @deprecated since Moodle 4.4
     * @param string $url Url that may or may not be an xml cartridge
     * @return bool True if the url is for a cartridge.
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function is_cartridge($url) {
        debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\external:is_cartridge instead.',
            DEBUG_DEVELOPER);
        return \core_ltix\external::is_cartridge($url);
    }

    /**
     * Mark the function as deprecated.
     * @return bool
     */
    public static function is_cartridge_is_deprecated() {
        return true;
    }

    /**
     * Returns description of method result value
     *
     * @deprecated since Moodle 4.4
     * @return \core_external\external_description
     * @since Moodle 3.1
     */
    public static function is_cartridge_returns() {
        return \core_ltix\external::is_cartridge_returns();
    }
}
