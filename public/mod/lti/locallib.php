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
//
// This file is part of BasicLTI4Moodle
//
// BasicLTI4Moodle is an IMS BasicLTI (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. BasicLTI is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS BasicLTI
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support BasicLTI. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// BasicLTI4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// SimpleLTI consumer for Moodle is an implementation of the early specification of LTI
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// BasicLTI4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu.

/**
 * This file contains the library of functions and constants for the lti module
 *
 * @package mod_lti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @copyright  2015 Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// TODO: Switch to core oauthlib once implemented - MDL-30149.
use mod_lti\helper;
use moodle\ltix as lti;

global $CFG;
require_once($CFG->dirroot.'/ltix/OAuth.php');
require_once($CFG->libdir.'/weblib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/ltix/TrivialStore.php');
require_once($CFG->dirroot . '/ltix/constants.php');

/**
 * Return the mapping for standard message types to JWT message_type claim.
 *
 * @deprecated since Moodle 4.4
 * @return array
 */
function lti_get_jwt_message_type_mapping() {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\oauth_helper::get_jwt_message_type_mapping() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\oauth_helper::get_jwt_message_type_mapping();
}

/**
 * Return the mapping for standard message parameters to JWT claim.
 *
 * @deprecated since Moodle 4.4
 * @return array
 */
function lti_get_jwt_claim_mapping() {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\oauth_helper::get_jwt_claim_mapping() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\oauth_helper::get_jwt_claim_mapping();
}

/**
 * Return the type of the instance, using domain matching if no explicit type is set.
 *
 * @param  object $instance the external tool activity settings
 * @return object|null
 * @since  Moodle 3.9
 */
function lti_get_instance_type(object $instance): ?object {
    if (empty($instance->typeid)) {
        if (!$tool = lti_get_tool_by_url_match($instance->toolurl, $instance->course)) {
            $tool = lti_get_tool_by_url_match($instance->securetoolurl,  $instance->course);
        }
        return $tool;
    }
    return lti_get_type($instance->typeid);
}

/**
 * Return the launch data required for opening the external tool.
 *
 * @param  stdClass $instance the external tool activity settings
 * @param  string $nonce  the nonce value to use (applies to LTI 1.3 only)
 * @return array the endpoint URL and parameters (including the signature)
 * @since  Moodle 3.0
 */
function lti_get_launch_data($instance, $nonce = '', $messagetype = 'basic-lti-launch-request', $foruserid = 0) {
    global $PAGE, $USER;
    $messagetype = $messagetype ? $messagetype : 'basic-lti-launch-request';
    $tool = lti_get_instance_type($instance);
    if ($tool) {
        $typeid = $tool->id;
        $ltiversion = $tool->ltiversion;
    } else {
        $typeid = null;
        $ltiversion = LTI_VERSION_1;
    }

    if ($typeid) {
        $typeconfig = lti_get_type_config($typeid);
    } else {
        // There is no admin configuration for this tool. Use configuration in the lti instance record plus some defaults.
        $typeconfig = (array)$instance;

        $typeconfig['sendname'] = $instance->instructorchoicesendname;
        $typeconfig['sendemailaddr'] = $instance->instructorchoicesendemailaddr;
        $typeconfig['customparameters'] = $instance->instructorcustomparameters;
        $typeconfig['acceptgrades'] = $instance->instructorchoiceacceptgrades;
        $typeconfig['allowroster'] = $instance->instructorchoiceallowroster;
        $typeconfig['forcessl'] = '0';
    }

    if (isset($tool->toolproxyid)) {
        $toolproxy = lti_get_tool_proxy($tool->toolproxyid);
        $key = $toolproxy->guid;
        $secret = $toolproxy->secret;
    } else {
        $toolproxy = null;
        if (!empty($instance->resourcekey)) {
            $key = $instance->resourcekey;
        } else if ($ltiversion === LTI_VERSION_1P3) {
            $key = $tool->clientid;
        } else if (!empty($typeconfig['resourcekey'])) {
            $key = $typeconfig['resourcekey'];
        } else {
            $key = '';
        }
        if (!empty($instance->password)) {
            $secret = $instance->password;
        } else if (!empty($typeconfig['password'])) {
            $secret = $typeconfig['password'];
        } else {
            $secret = '';
        }
    }

    $endpoint = !empty($instance->toolurl) ? $instance->toolurl : $typeconfig['toolurl'];
    $endpoint = trim($endpoint);

    // If the current request is using SSL and a secure tool URL is specified, use it.
    if (lti_request_is_using_ssl() && !empty($instance->securetoolurl)) {
        $endpoint = trim($instance->securetoolurl);
    }

    // If SSL is forced, use the secure tool url if specified. Otherwise, make sure https is on the normal launch URL.
    if (isset($typeconfig['forcessl']) && ($typeconfig['forcessl'] == '1')) {
        if (!empty($instance->securetoolurl)) {
            $endpoint = trim($instance->securetoolurl);
        }

        if ($endpoint !== '') {
            $endpoint = lti_ensure_url_is_https($endpoint);
        }
    } else if ($endpoint !== '' && !strstr($endpoint, '://')) {
        $endpoint = 'http://' . $endpoint;
    }

    $orgid = lti_get_organizationid($typeconfig);

    $course = $PAGE->course;
    $islti2 = isset($tool->toolproxyid);
    $allparams = lti_build_request($instance, $typeconfig, $course, $typeid, $islti2, $messagetype, $foruserid);
    if ($islti2) {
        $requestparams = lti_build_request_lti2($tool, $allparams);
    } else {
        $requestparams = $allparams;
    }
    $requestparams = array_merge($requestparams, lti_build_standard_message($instance, $orgid, $ltiversion, $messagetype));
    $customstr = '';
    if (isset($typeconfig['customparameters'])) {
        $customstr = $typeconfig['customparameters'];
    }
    $services = lti_get_services();
    foreach ($services as $service) {
        [$endpoint, $customstr] = $service->override_endpoint($messagetype,
            $endpoint, $customstr, $instance->course, $instance);
    }
    $requestparams = array_merge($requestparams, lti_build_custom_parameters($toolproxy, $tool, $instance, $allparams, $customstr,
        $instance->instructorcustomparameters, $islti2));

    $launchcontainer = lti_get_launch_container($instance, $typeconfig);
    $returnurlparams = array('course' => $course->id,
        'launch_container' => $launchcontainer,
        'instanceid' => $instance->id,
        'sesskey' => sesskey());

    // Add the return URL. We send the launch container along to help us avoid frames-within-frames when the user returns.
    $url = new \moodle_url('/mod/lti/return.php', $returnurlparams);
    $returnurl = $url->out(false);

    if (isset($typeconfig['forcessl']) && ($typeconfig['forcessl'] == '1')) {
        $returnurl = lti_ensure_url_is_https($returnurl);
    }

    $target = '';
    switch($launchcontainer) {
        case LTI_LAUNCH_CONTAINER_EMBED:
        case LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS:
            $target = 'iframe';
            break;
        case LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW:
            $target = 'frame';
            break;
        case LTI_LAUNCH_CONTAINER_WINDOW:
            $target = 'window';
            break;
    }
    if (!empty($target)) {
        $requestparams['launch_presentation_document_target'] = $target;
    }

    $requestparams['launch_presentation_return_url'] = $returnurl;

    // Add the parameters configured by the LTI services.
    if ($typeid && !$islti2) {
        $services = lti_get_services();
        foreach ($services as $service) {
            $serviceparameters = $service->get_launch_parameters('basic-lti-launch-request',
                    $course->id, $USER->id , $typeid, $instance->id);
            foreach ($serviceparameters as $paramkey => $paramvalue) {
                $requestparams['custom_' . $paramkey] = lti_parse_custom_parameter($toolproxy, $tool, $requestparams, $paramvalue,
                    $islti2);
            }
        }
    }

    // Allow request params to be updated by sub-plugins.
    $plugins = core_component::get_plugin_list('ltisource');
    foreach (array_keys($plugins) as $plugin) {
        $pluginparams = component_callback('ltisource_'.$plugin, 'before_launch',
            array($instance, $endpoint, $requestparams), array());

        if (!empty($pluginparams) && is_array($pluginparams)) {
            $requestparams = array_merge($requestparams, $pluginparams);
        }
    }

    if ((!empty($key) && !empty($secret)) || ($ltiversion === LTI_VERSION_1P3)) {
        if ($ltiversion !== LTI_VERSION_1P3) {
            $parms = lti_sign_parameters($requestparams, $endpoint, 'POST', $key, $secret);
        } else {
            $parms = lti_sign_jwt($requestparams, $endpoint, $key, $typeid, $nonce);
        }

        $endpointurl = new \moodle_url($endpoint);
        $endpointparams = $endpointurl->params();

        // Strip querystring params in endpoint url from $parms to avoid duplication.
        if (!empty($endpointparams) && !empty($parms)) {
            foreach (array_keys($endpointparams) as $paramname) {
                if (isset($parms[$paramname])) {
                    unset($parms[$paramname]);
                }
            }
        }

    } else {
        // If no key and secret, do the launch unsigned.
        $returnurlparams['unsigned'] = '1';
        $parms = $requestparams;
    }

    return array($endpoint, $parms);
}

/**
 * Launch an external tool activity.
 *
 * @param stdClass $instance the external tool activity settings
 * @param int $foruserid for user param, optional
 * @return string The HTML code containing the javascript code for the launch
 */
function lti_launch_tool($instance, $foruserid=0) {

    list($endpoint, $parms) = lti_get_launch_data($instance, '', '', $foruserid);
    $debuglaunch = ( $instance->debuglaunch == 1 );

    $content = lti_post_launch_html($parms, $endpoint, $debuglaunch);

    echo $content;
}

/**
 * Prepares an LTI registration request message
 *
 * @param object $toolproxy  Tool Proxy instance object
 */
function lti_register($toolproxy) {
    $endpoint = $toolproxy->regurl;

    // Change the status to pending.
    $toolproxy->state = LTI_TOOL_PROXY_STATE_PENDING;
    lti_update_tool_proxy($toolproxy);

    $requestparams = lti_build_registration_request($toolproxy);

    $content = lti_post_launch_html($requestparams, $endpoint, false);

    echo $content;
}


/**
 * Gets the parameters for the regirstration request
 *
 * @param object $toolproxy Tool Proxy instance object
 * @return array Registration request parameters
 */
function lti_build_registration_request($toolproxy) {
    $key = $toolproxy->guid;
    $secret = $toolproxy->secret;

    $requestparams = array();
    $requestparams['lti_message_type'] = 'ToolProxyRegistrationRequest';
    $requestparams['lti_version'] = 'LTI-2p0';
    $requestparams['reg_key'] = $key;
    $requestparams['reg_password'] = $secret;
    $requestparams['reg_url'] = $toolproxy->regurl;

    // Add the profile URL.
    $profileservice = lti_get_service_by_name('profile');
    $profileservice->set_tool_proxy($toolproxy);
    $requestparams['tc_profile_url'] = $profileservice->parse_value('$ToolConsumerProfile.url');

    // Add the return URL.
    $returnurlparams = array('id' => $toolproxy->id, 'sesskey' => sesskey());
    $url = new \moodle_url('/mod/lti/externalregistrationreturn.php', $returnurlparams);
    $returnurl = $url->out(false);

    $requestparams['launch_presentation_return_url'] = $returnurl;

    return $requestparams;
}


/** get Organization ID using default if no value provided
 *
 * @deprecated since Moodle 4.4
 * @param object $typeconfig
 * @return string
 */
function lti_get_organizationid($typeconfig) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::get_organizationid() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\types_helper::get_organizationid($typeconfig);
}

/**
 * Build source ID
 *
 * @param int $instanceid
 * @param int $userid
 * @param string $servicesalt
 * @param null|int $typeid
 * @param null|int $launchid
 * @return stdClass
 */
function lti_build_sourcedid($instanceid, $userid, $servicesalt, $typeid = null, $launchid = null) {
    $data = new \stdClass();

    $data->instanceid = $instanceid;
    $data->userid = $userid;
    $data->typeid = $typeid;
    if (!empty($launchid)) {
        $data->launchid = $launchid;
    } else {
        $data->launchid = mt_rand();
    }

    $json = json_encode($data);

    $hash = hash('sha256', $json . $servicesalt, false);

    $container = new \stdClass();
    $container->data = $data;
    $container->hash = $hash;

    return $container;
}

/**
 * This function builds the request that must be sent to the tool producer
 *
 * @param object    $instance       Basic LTI instance object
 * @param array     $typeconfig     Basic LTI tool configuration
 * @param object    $course         Course object
 * @param int|null  $typeid         Basic LTI tool ID
 * @param boolean   $islti2         True if an LTI 2 tool is being launched
 * @param string    $messagetype    LTI Message Type for this launch
 * @param int       $foruserid      User targeted by this launch
 *
 * @return array                    Request details
 */
function lti_build_request($instance, $typeconfig, $course, $typeid = null, $islti2 = false,
    $messagetype = 'basic-lti-launch-request', $foruserid = 0) {
    global $USER, $CFG;

    if (empty($instance->cmid)) {
        $instance->cmid = 0;
    }

    $role = lti_get_ims_role($USER, $instance->cmid, $instance->course, $islti2);

    $requestparams = array(
        'user_id' => $USER->id,
        'lis_person_sourcedid' => $USER->idnumber,
        'roles' => $role,
        'context_id' => $course->id,
        'context_label' => trim(html_to_text($course->shortname, 0)),
        'context_title' => trim(html_to_text($course->fullname, 0)),
    );
    if ($foruserid) {
        $requestparams['for_user_id'] = $foruserid;
    }
    if ($messagetype) {
        $requestparams['lti_message_type'] = $messagetype;
    }
    if (!empty($instance->name)) {
        $requestparams['resource_link_title'] = trim(html_to_text($instance->name, 0));
    }
    if (!empty($instance->cmid)) {
        $intro = format_module_intro('lti', $instance, $instance->cmid);
        $intro = trim(html_to_text($intro, 0, false));

        // This may look weird, but this is required for new lines
        // so we generate the same OAuth signature as the tool provider.
        $intro = str_replace("\n", "\r\n", $intro);
        $requestparams['resource_link_description'] = $intro;
    }
    if (!empty($instance->id)) {
        $requestparams['resource_link_id'] = $instance->id;
    }
    if (!empty($instance->resource_link_id)) {
        $requestparams['resource_link_id'] = $instance->resource_link_id;
    }
    if ($course->format == 'site') {
        $requestparams['context_type'] = 'Group';
    } else {
        $requestparams['context_type'] = 'CourseSection';
        $requestparams['lis_course_section_sourcedid'] = $course->idnumber;
    }

    if (!empty($instance->id) && !empty($instance->servicesalt) && ($islti2 ||
            $typeconfig['acceptgrades'] == LTI_SETTING_ALWAYS ||
            ($typeconfig['acceptgrades'] == LTI_SETTING_DELEGATE && $instance->instructorchoiceacceptgrades == LTI_SETTING_ALWAYS))
    ) {
        $placementsecret = $instance->servicesalt;
        $sourcedid = json_encode(lti_build_sourcedid($instance->id, $USER->id, $placementsecret, $typeid));
        $requestparams['lis_result_sourcedid'] = $sourcedid;

        // Add outcome service URL.
        $serviceurl = new \moodle_url('/mod/lti/service.php');
        $serviceurl = $serviceurl->out();

        $forcessl = false;
        if (!empty($CFG->mod_lti_forcessl)) {
            $forcessl = true;
        }

        if ((isset($typeconfig['forcessl']) && ($typeconfig['forcessl'] == '1')) or $forcessl) {
            $serviceurl = lti_ensure_url_is_https($serviceurl);
        }

        $requestparams['lis_outcome_service_url'] = $serviceurl;
    }

    // Send user's name and email data if appropriate.
    if ($islti2 || $typeconfig['sendname'] == LTI_SETTING_ALWAYS ||
        ($typeconfig['sendname'] == LTI_SETTING_DELEGATE && isset($instance->instructorchoicesendname)
            && $instance->instructorchoicesendname == LTI_SETTING_ALWAYS)
    ) {
        $requestparams['lis_person_name_given'] = $USER->firstname;
        $requestparams['lis_person_name_family'] = $USER->lastname;
        $requestparams['lis_person_name_full'] = fullname($USER);
        $requestparams['ext_user_username'] = $USER->username;
    }

    if ($islti2 || $typeconfig['sendemailaddr'] == LTI_SETTING_ALWAYS ||
        ($typeconfig['sendemailaddr'] == LTI_SETTING_DELEGATE && isset($instance->instructorchoicesendemailaddr)
            && $instance->instructorchoicesendemailaddr == LTI_SETTING_ALWAYS)
    ) {
        $requestparams['lis_person_contact_email_primary'] = $USER->email;
    }

    return $requestparams;
}

/**
 * This function builds the request that must be sent to an LTI 2 tool provider
 *
 * @deprecated since Moodle 4.4
 * @param object    $tool           Basic LTI tool object
 * @param array     $params         Custom launch parameters
 *
 * @return array                    Request details
 */
function lti_build_request_lti2($tool, $params) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::build_request_lti2() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::build_request_lti2($tool, $params);
}

/**
 * This function builds the standard parameters for an LTI 1 or 2 request that must be sent to the tool producer
 *
 * @param stdClass  $instance       Basic LTI instance object
 * @param string    $orgid          Organisation ID
 * @param boolean   $islti2         True if an LTI 2 tool is being launched
 * @param string    $messagetype    The request message type. Defaults to basic-lti-launch-request if empty.
 *
 * @return array                    Request details
 * @deprecated since Moodle 3.7 MDL-62599 - please do not use this function any more.
 * @see lti_build_standard_message()
 */
function lti_build_standard_request($instance, $orgid, $islti2, $messagetype = 'basic-lti-launch-request') {
    if (!$islti2) {
        $ltiversion = LTI_VERSION_1;
    } else {
        $ltiversion = LTI_VERSION_2;
    }
    return lti_build_standard_message($instance, $orgid, $ltiversion, $messagetype);
}

/**
 * This function builds the standard parameters for an LTI message that must be sent to the tool producer
 *
 * @param stdClass  $instance       Basic LTI instance object
 * @param string    $orgid          Organisation ID
 * @param boolean   $ltiversion     LTI version to be used for tool messages
 * @param string    $messagetype    The request message type. Defaults to basic-lti-launch-request if empty.
 *
 * @return array                    Message parameters
 */
function lti_build_standard_message($instance, $orgid, $ltiversion, $messagetype = 'basic-lti-launch-request') {
    global $CFG;

    $requestparams = array();

    if ($instance) {
        $requestparams['resource_link_id'] = $instance->id;
        if (property_exists($instance, 'resource_link_id') and !empty($instance->resource_link_id)) {
            $requestparams['resource_link_id'] = $instance->resource_link_id;
        }
    }

    $requestparams['launch_presentation_locale'] = current_language();

    // Make sure we let the tool know what LMS they are being called from.
    $requestparams['ext_lms'] = 'moodle-2';
    $requestparams['tool_consumer_info_product_family_code'] = 'moodle';
    $requestparams['tool_consumer_info_version'] = strval($CFG->version);

    // Add oauth_callback to be compliant with the 1.0A spec.
    $requestparams['oauth_callback'] = 'about:blank';

    $requestparams['lti_version'] = $ltiversion;
    $requestparams['lti_message_type'] = $messagetype;

    if ($orgid) {
        $requestparams["tool_consumer_instance_guid"] = $orgid;
    }
    if (!empty($CFG->mod_lti_institution_name)) {
        $requestparams['tool_consumer_instance_name'] = trim(html_to_text($CFG->mod_lti_institution_name, 0));
    } else {
        $requestparams['tool_consumer_instance_name'] = get_site()->shortname;
    }
    $requestparams['tool_consumer_instance_description'] = trim(html_to_text(get_site()->fullname, 0));

    return $requestparams;
}

/**
 * This function builds the custom parameters
 *
 * @param object    $toolproxy      Tool proxy instance object
 * @param object    $tool           Tool instance object
 * @param object    $instance       Tool placement instance object
 * @param array     $params         LTI launch parameters
 * @param string    $customstr      Custom parameters defined for tool
 * @param string    $instructorcustomstr      Custom parameters defined for this placement
 * @param boolean   $islti2         True if an LTI 2 tool is being launched
 *
 * @return array                    Custom parameters
 */
function lti_build_custom_parameters($toolproxy, $tool, $instance, $params, $customstr, $instructorcustomstr, $islti2) {

    // Concatenate the custom parameters from the administrator and the instructor
    // Instructor parameters are only taken into consideration if the administrator
    // has given permission.
    $custom = array();
    if ($customstr) {
        $custom = lti_split_custom_parameters($toolproxy, $tool, $params, $customstr, $islti2);
    }
    if ($instructorcustomstr) {
        $custom = array_merge(lti_split_custom_parameters($toolproxy, $tool, $params,
            $instructorcustomstr, $islti2), $custom);
    }
    if ($islti2) {
        $custom = array_merge(lti_split_custom_parameters($toolproxy, $tool, $params,
            $tool->parameter, true), $custom);
        $settings = lti_get_tool_settings($tool->toolproxyid);
        $custom = array_merge($custom, lti_get_custom_parameters($toolproxy, $tool, $params, $settings));
        if (!empty($instance->course)) {
            $settings = lti_get_tool_settings($tool->toolproxyid, $instance->course);
            $custom = array_merge($custom, lti_get_custom_parameters($toolproxy, $tool, $params, $settings));
            if (!empty($instance->id)) {
                $settings = lti_get_tool_settings($tool->toolproxyid, $instance->course, $instance->id);
                $custom = array_merge($custom, lti_get_custom_parameters($toolproxy, $tool, $params, $settings));
            }
        }
    }

    return $custom;
}

/**
 * Builds a standard LTI Content-Item selection request.
 *
 * @param int $id The tool type ID.
 * @param stdClass $course The course object.
 * @param moodle_url $returnurl The return URL in the tool consumer (TC) that the tool provider (TP)
 *                              will use to return the Content-Item message.
 * @param string $title The tool's title, if available.
 * @param string $text The text to display to represent the content item. This value may be a long description of the content item.
 * @param array $mediatypes Array of MIME types types supported by the TC. If empty, the TC will support ltilink by default.
 * @param array $presentationtargets Array of ways in which the selected content item(s) can be requested to be opened
 *                                   (via the presentationDocumentTarget element for a returned content item).
 *                                   If empty, "frame", "iframe", and "window" will be supported by default.
 * @param bool $autocreate Indicates whether any content items returned by the TP would be automatically persisted without
 * @param bool $multiple Indicates whether the user should be permitted to select more than one item. False by default.
 *                         any option for the user to cancel the operation. False by default.
 * @param bool $unsigned Indicates whether the TC is willing to accept an unsigned return message, or not.
 *                       A signed message should always be required when the content item is being created automatically in the
 *                       TC without further interaction from the user. False by default.
 * @param bool $canconfirm Flag for can_confirm parameter. False by default.
 * @param bool $copyadvice Indicates whether the TC is able and willing to make a local copy of a content item. False by default.
 * @param string $nonce
 * @return stdClass The object containing the signed request parameters and the URL to the TP's Content-Item selection interface.
 * @throws moodle_exception When the LTI tool type does not exist.`
 * @throws coding_exception For invalid media type and presentation target parameters.
 */
function lti_build_content_item_selection_request($id, $course, moodle_url $returnurl, $title = '', $text = '', $mediatypes = [],
                                                  $presentationtargets = [], $autocreate = false, $multiple = true,
                                                  $unsigned = false, $canconfirm = false, $copyadvice = false, $nonce = '') {
    global $USER;

    $tool = lti_get_type($id);
    // Validate parameters.
    if (!$tool) {
        throw new moodle_exception('errortooltypenotfound', 'core_ltix');
    }
    if (!is_array($mediatypes)) {
        throw new coding_exception('The list of accepted media types should be in an array');
    }
    if (!is_array($presentationtargets)) {
        throw new coding_exception('The list of accepted presentation targets should be in an array');
    }

    // Check title. If empty, use the tool's name.
    if (empty($title)) {
        $title = $tool->name;
    }

    $typeconfig = lti_get_type_config($id);
    $key = '';
    $secret = '';
    $islti2 = false;
    $islti13 = false;
    if (isset($tool->toolproxyid)) {
        $islti2 = true;
        $toolproxy = lti_get_tool_proxy($tool->toolproxyid);
        $key = $toolproxy->guid;
        $secret = $toolproxy->secret;
    } else {
        $islti13 = $tool->ltiversion === LTI_VERSION_1P3;
        $toolproxy = null;
        if ($islti13 && !empty($tool->clientid)) {
            $key = $tool->clientid;
        } else if (!$islti13 && !empty($typeconfig['resourcekey'])) {
            $key = $typeconfig['resourcekey'];
        }
        if (!empty($typeconfig['password'])) {
            $secret = $typeconfig['password'];
        }
    }
    $tool->enabledcapability = '';
    if (!empty($typeconfig['enabledcapability_ContentItemSelectionRequest'])) {
        $tool->enabledcapability = $typeconfig['enabledcapability_ContentItemSelectionRequest'];
    }

    $tool->parameter = '';
    if (!empty($typeconfig['parameter_ContentItemSelectionRequest'])) {
        $tool->parameter = $typeconfig['parameter_ContentItemSelectionRequest'];
    }

    // Set the tool URL.
    if (!empty($typeconfig['toolurl_ContentItemSelectionRequest'])) {
        $toolurl = new moodle_url($typeconfig['toolurl_ContentItemSelectionRequest']);
    } else {
        $toolurl = new moodle_url($typeconfig['toolurl']);
    }

    // Check if SSL is forced.
    if (!empty($typeconfig['forcessl'])) {
        // Make sure the tool URL is set to https.
        if (strtolower($toolurl->get_scheme()) === 'http') {
            $toolurl->set_scheme('https');
        }
        // Make sure the return URL is set to https.
        if (strtolower($returnurl->get_scheme()) === 'http') {
            $returnurl->set_scheme('https');
        }
    }
    $toolurlout = $toolurl->out(false);

    // Get base request parameters.
    $instance = new stdClass();
    $instance->course = $course->id;
    $requestparams = lti_build_request($instance, $typeconfig, $course, $id, $islti2);

    // Get LTI2-specific request parameters and merge to the request parameters if applicable.
    if ($islti2) {
        $lti2params = lti_build_request_lti2($tool, $requestparams);
        $requestparams = array_merge($requestparams, $lti2params);
    }

    // Get standard request parameters and merge to the request parameters.
    $orgid = lti_get_organizationid($typeconfig);
    $standardparams = lti_build_standard_message(null, $orgid, $tool->ltiversion, 'ContentItemSelectionRequest');
    $requestparams = array_merge($requestparams, $standardparams);

    // Get custom request parameters and merge to the request parameters.
    $customstr = '';
    if (!empty($typeconfig['customparameters'])) {
        $customstr = $typeconfig['customparameters'];
    }
    $customparams = lti_build_custom_parameters($toolproxy, $tool, $instance, $requestparams, $customstr, '', $islti2);
    $requestparams = array_merge($requestparams, $customparams);

    // Add the parameters configured by the LTI services.
    if ($id && !$islti2) {
        $services = lti_get_services();
        foreach ($services as $service) {
            $serviceparameters = $service->get_launch_parameters('ContentItemSelectionRequest',
                $course->id, $USER->id , $id);
            foreach ($serviceparameters as $paramkey => $paramvalue) {
                $requestparams['custom_' . $paramkey] = lti_parse_custom_parameter($toolproxy, $tool, $requestparams, $paramvalue,
                    $islti2);
            }
        }
    }

    // Allow request params to be updated by sub-plugins.
    $plugins = core_component::get_plugin_list('ltisource');
    foreach (array_keys($plugins) as $plugin) {
        $pluginparams = component_callback('ltisource_' . $plugin, 'before_launch', [$instance, $toolurlout, $requestparams], []);

        if (!empty($pluginparams) && is_array($pluginparams)) {
            $requestparams = array_merge($requestparams, $pluginparams);
        }
    }

    if (!$islti13) {
        // Media types. Set to ltilink by default if empty.
        if (empty($mediatypes)) {
            $mediatypes = [
                'application/vnd.ims.lti.v1.ltilink',
            ];
        }
        $requestparams['accept_media_types'] = implode(',', $mediatypes);
    } else {
        // Only LTI links are currently supported.
        $requestparams['accept_types'] = 'ltiResourceLink';
    }

    // Presentation targets. Supports frame, iframe, window by default if empty.
    if (empty($presentationtargets)) {
        $presentationtargets = [
            'frame',
            'iframe',
            'window',
        ];
    }
    $requestparams['accept_presentation_document_targets'] = implode(',', $presentationtargets);

    // Other request parameters.
    $requestparams['accept_copy_advice'] = $copyadvice === true ? 'true' : 'false';
    $requestparams['accept_multiple'] = $multiple === true ? 'true' : 'false';
    $requestparams['accept_unsigned'] = $unsigned === true ? 'true' : 'false';
    $requestparams['auto_create'] = $autocreate === true ? 'true' : 'false';
    $requestparams['can_confirm'] = $canconfirm === true ? 'true' : 'false';
    $requestparams['content_item_return_url'] = $returnurl->out(false);
    $requestparams['title'] = $title;
    $requestparams['text'] = $text;
    if (!$islti13) {
        $signedparams = lti_sign_parameters($requestparams, $toolurlout, 'POST', $key, $secret);
    } else {
        $signedparams = lti_sign_jwt($requestparams, $toolurlout, $key, $id, $nonce);
    }
    $toolurlparams = $toolurl->params();

    // Strip querystring params in endpoint url from $signedparams to avoid duplication.
    if (!empty($toolurlparams) && !empty($signedparams)) {
        foreach (array_keys($toolurlparams) as $paramname) {
            if (isset($signedparams[$paramname])) {
                unset($signedparams[$paramname]);
            }
        }
    }

    // Check for params that should not be passed. Unset if they are set.
    $unwantedparams = [
        'resource_link_id',
        'resource_link_title',
        'resource_link_description',
        'launch_presentation_return_url',
        'lis_result_sourcedid',
    ];
    foreach ($unwantedparams as $param) {
        if (isset($signedparams[$param])) {
            unset($signedparams[$param]);
        }
    }

    // Prepare result object.
    $result = new stdClass();
    $result->params = $signedparams;
    $result->url = $toolurlout;

    return $result;
}

/**
 * Verifies the OAuth signature of an incoming message.
 *
 * @deprecated since Moodle 4.4
 * @param int $typeid The tool type ID.
 * @param string $consumerkey The consumer key.
 * @return stdClass Tool type
 * @throws moodle_exception
 * @throws lti\OAuthException
 */
function lti_verify_oauth_signature($typeid, $consumerkey) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::verify_oauth_signature() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\oauth_helper::verify_oauth_signature($typeid, $consumerkey);
}

/**
 * Verifies the JWT signature using a JWK keyset.
 *
 * @deprecated since Moodle 4.4
 * @param string $jwtparam JWT parameter value.
 * @param string $keyseturl The tool keyseturl.
 * @param string $clientid The tool client id.
 *
 * @return object The JWT's payload as a PHP object
 * @throws moodle_exception
 * @throws UnexpectedValueException     Provided JWT was invalid
 * @throws SignatureInvalidException    Provided JWT was invalid because the signature verification failed
 * @throws BeforeValidException         Provided JWT is trying to be used before it's eligible as defined by 'nbf'
 * @throws BeforeValidException         Provided JWT is trying to be used before it's been created as defined by 'iat'
 * @throws ExpiredException             Provided JWT has since expired, as defined by the 'exp' claim
 */
function lti_verify_with_keyset($jwtparam, $keyseturl, $clientid) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\oauth_helper::verify_with_keyset() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\oauth_helper::verify_with_keyset($jwtparam, $keyseturl, $clientid);
}

/**
 * Verifies the JWT signature of an incoming message.
 *
 * @deprecated since Moodle 4.4
 * @param int $typeid The tool type ID.
 * @param string $consumerkey The consumer key.
 * @param string $jwtparam JWT parameter value
 *
 * @return stdClass Tool type
 * @throws moodle_exception
 * @throws UnexpectedValueException     Provided JWT was invalid
 * @throws SignatureInvalidException    Provided JWT was invalid because the signature verification failed
 * @throws BeforeValidException         Provided JWT is trying to be used before it's eligible as defined by 'nbf'
 * @throws BeforeValidException         Provided JWT is trying to be used before it's been created as defined by 'iat'
 * @throws ExpiredException             Provided JWT has since expired, as defined by the 'exp' claim
 */
function lti_verify_jwt_signature($typeid, $consumerkey, $jwtparam) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\oauth_helper::verify_jwt_signature() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\oauth_helper::verify_jwt_signature($typeid, $consumerkey, $jwtparam);
}

/**
 * Converts an array of custom parameters to a new line separated string.
 *
 * @param object $params list of params to concatenate
 *
 * @return string
 */
function params_to_string(object $params) {
    $customparameters = [];
    foreach ($params as $key => $value) {
        $customparameters[] = "{$key}={$value}";
    }
    return implode("\n", $customparameters);
}

/**
 * Converts LTI 1.1 Content Item for LTI Link to Form data.
 *
 * @deprecated since Moodle 4.4
 * @param object $tool Tool for which the item is created for.
 * @param object $typeconfig The tool configuration.
 * @param object $item Item populated from JSON to be converted to Form form
 *
 * @return stdClass Form config for the item
 */
function content_item_to_form(object $tool, object $typeconfig, object $item): stdClass {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::content_item_to_form() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::content_item_to_form($tool, $typeconfig, $item);
}

/**
 * Processes the tool provider's response to the ContentItemSelectionRequest and builds the configuration data from the
 * selected content item. This configuration data can be then used when adding a tool into the course.
 *
 * @deprecated since Moodle 4.4
 * @param int $typeid The tool type ID.
 * @param string $messagetype The value for the lti_message_type parameter.
 * @param string $ltiversion The value for the lti_version parameter.
 * @param string $consumerkey The consumer key.
 * @param string $contentitemsjson The JSON string for the content_items parameter.
 * @return stdClass The array of module information objects.
 * @throws moodle_exception
 * @throws lti\OAuthException
 */
function lti_tool_configuration_from_content_item($typeid, $messagetype, $ltiversion, $consumerkey, $contentitemsjson) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::tool_configuration_from_content_item() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::tool_configuration_from_content_item($typeid, $messagetype, $ltiversion, $consumerkey, $contentitemsjson);
}

/**
 * Converts the new Deep-Linking format for Content-Items to the old format.
 *
 * @deprecated since Moodle 4.4
 * @param string $param JSON string representing new Deep-Linking format
 * @return string  JSON representation of content-items
 */
function lti_convert_content_items($param) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::convert_content_items() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::convert_content_items($param);
}

function lti_get_tool_table($tools, $id) {
    global $OUTPUT;
    $html = '';

    $typename = get_string('typename', 'lti');
    $baseurl = get_string('baseurl', 'lti');
    $action = get_string('action', 'lti');
    $createdon = get_string('createdon', 'lti');

    if (!empty($tools)) {
        $html .= "
        <div id=\"{$id}_tools_container\" style=\"margin-top:.5em;margin-bottom:.5em\">
            <table id=\"{$id}_tools\">
                <thead>
                    <tr>
                        <th>$typename</th>
                        <th>$baseurl</th>
                        <th>$createdon</th>
                        <th>$action</th>
                    </tr>
                </thead>
        ";

        foreach ($tools as $type) {
            $date = userdate($type->timecreated, get_string('strftimedatefullshort', 'core_langconfig'));
            $accept = get_string('accept', 'lti');
            $update = get_string('update', 'lti');
            $delete = get_string('delete', 'lti');

            if (empty($type->toolproxyid)) {
                $baseurl = new \moodle_url('/mod/lti/typessettings.php', array(
                        'action' => 'accept',
                        'id' => $type->id,
                        'sesskey' => sesskey(),
                        'tab' => $id
                    ));
                $ref = $type->baseurl;
            } else {
                $baseurl = new \moodle_url('/mod/lti/toolssettings.php', array(
                        'action' => 'accept',
                        'id' => $type->id,
                        'sesskey' => sesskey(),
                        'tab' => $id
                    ));
                $ref = $type->tpname;
            }

            $accepthtml = $OUTPUT->action_icon($baseurl,
                    new \pix_icon('t/check', $accept, '', array('class' => 'iconsmall')), null,
                    array('title' => $accept, 'class' => 'editing_accept'));

            $deleteaction = 'delete';

            if ($type->state == LTI_TOOL_STATE_CONFIGURED) {
                $accepthtml = '';
            }

            if ($type->state != LTI_TOOL_STATE_REJECTED) {
                $deleteaction = 'reject';
                $delete = get_string('reject', 'lti');
            }

            $updateurl = clone($baseurl);
            $updateurl->param('action', 'update');
            $updatehtml = $OUTPUT->action_icon($updateurl,
                    new \pix_icon('t/edit', $update, '', array('class' => 'iconsmall')), null,
                    array('title' => $update, 'class' => 'editing_update'));

            if (($type->state != LTI_TOOL_STATE_REJECTED) || empty($type->toolproxyid)) {
                $deleteurl = clone($baseurl);
                $deleteurl->param('action', $deleteaction);
                $deletehtml = $OUTPUT->action_icon($deleteurl,
                        new \pix_icon('t/delete', $delete, '', array('class' => 'iconsmall')), null,
                        array('title' => $delete, 'class' => 'editing_delete'));
            } else {
                $deletehtml = '';
            }
            $html .= "
            <tr>
                <td>
                    {$type->name}
                </td>
                <td>
                    {$ref}
                </td>
                <td>
                    {$date}
                </td>
                <td align=\"center\">
                    {$accepthtml}{$updatehtml}{$deletehtml}
                </td>
            </tr>
            ";
        }
        $html .= '</table></div>';
    } else {
        $html .= get_string('no_' . $id, 'lti');
    }

    return $html;
}

/**
 * This function builds the tab for a category of tool proxies
 *
 * @param object    $toolproxies    Tool proxy instance objects
 * @param string    $id             Category ID
 *
 * @return string                   HTML for tab
 */
function lti_get_tool_proxy_table($toolproxies, $id) {
    global $OUTPUT;

    if (!empty($toolproxies)) {
        $typename = get_string('typename', 'lti');
        $url = get_string('registrationurl', 'lti');
        $action = get_string('action', 'lti');
        $createdon = get_string('createdon', 'lti');

        $html = <<< EOD
        <div id="{$id}_tool_proxies_container" style="margin-top: 0.5em; margin-bottom: 0.5em">
            <table id="{$id}_tool_proxies">
                <thead>
                    <tr>
                        <th>{$typename}</th>
                        <th>{$url}</th>
                        <th>{$createdon}</th>
                        <th>{$action}</th>
                    </tr>
                </thead>
EOD;
        foreach ($toolproxies as $toolproxy) {
            $date = userdate($toolproxy->timecreated, get_string('strftimedatefullshort', 'core_langconfig'));
            $accept = get_string('register', 'lti');
            $update = get_string('update', 'lti');
            $delete = get_string('delete', 'lti');

            $baseurl = new \moodle_url('/mod/lti/registersettings.php', array(
                    'action' => 'accept',
                    'id' => $toolproxy->id,
                    'sesskey' => sesskey(),
                    'tab' => $id
                ));

            $registerurl = new \moodle_url('/mod/lti/register.php', array(
                    'id' => $toolproxy->id,
                    'sesskey' => sesskey(),
                    'tab' => 'tool_proxy'
                ));

            $accepthtml = $OUTPUT->action_icon($registerurl,
                    new \pix_icon('t/check', $accept, '', array('class' => 'iconsmall')), null,
                    array('title' => $accept, 'class' => 'editing_accept'));

            $deleteaction = 'delete';

            if ($toolproxy->state != LTI_TOOL_PROXY_STATE_CONFIGURED) {
                $accepthtml = '';
            }

            if (($toolproxy->state == LTI_TOOL_PROXY_STATE_CONFIGURED) || ($toolproxy->state == LTI_TOOL_PROXY_STATE_PENDING)) {
                $delete = get_string('cancel', 'lti');
            }

            $updateurl = clone($baseurl);
            $updateurl->param('action', 'update');
            $updatehtml = $OUTPUT->action_icon($updateurl,
                    new \pix_icon('t/edit', $update, '', array('class' => 'iconsmall')), null,
                    array('title' => $update, 'class' => 'editing_update'));

            $deleteurl = clone($baseurl);
            $deleteurl->param('action', $deleteaction);
            $deletehtml = $OUTPUT->action_icon($deleteurl,
                    new \pix_icon('t/delete', $delete, '', array('class' => 'iconsmall')), null,
                    array('title' => $delete, 'class' => 'editing_delete'));
            $html .= <<< EOD
            <tr>
                <td>
                    {$toolproxy->name}
                </td>
                <td>
                    {$toolproxy->regurl}
                </td>
                <td>
                    {$date}
                </td>
                <td align="center">
                    {$accepthtml}{$updatehtml}{$deletehtml}
                </td>
            </tr>
EOD;
        }
        $html .= '</table></div>';
    } else {
        $html = get_string('no_' . $id, 'lti');
    }

    return $html;
}

/**
 * Extracts the enabled capabilities into an array, including those implicitly declared in a parameter
 *
 * @deprecated since Moodle 4.4
 * @param object $tool  Tool instance object
 *
 * @return array List of enabled capabilities
 */
function lti_get_enabled_capabilities($tool) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_enabled_capabilities() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_enabled_capabilities($tool);
}

/**
 * Splits the custom parameters
 *
 * @deprecated since Moodle 4.4
 * @param string    $customstr      String containing the parameters
 *
 * @return array of custom parameters
 */
function lti_split_parameters($customstr) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::split_parameters() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::split_parameters($customstr);
}

/**
 * Splits the custom parameters field to the various parameters
 *
 * @deprecated since Moodle 4.4
 * @param object    $toolproxy      Tool proxy instance object
 * @param object    $tool           Tool instance object
 * @param array     $params         LTI launch parameters
 * @param string    $customstr      String containing the parameters
 * @param boolean   $islti2         True if an LTI 2 tool is being launched
 *
 * @return array of custom parameters
 */
function lti_split_custom_parameters($toolproxy, $tool, $params, $customstr, $islti2 = false) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::split_custom_parameters() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::split_custom_parameters($toolproxy, $tool, $params, $customstr, $islti2);
}

/**
 * Adds the custom parameters to an array
 *
 * @deprecated since Moodle 4.4
 * @param object    $toolproxy      Tool proxy instance object
 * @param object    $tool           Tool instance object
 * @param array     $params         LTI launch parameters
 * @param array     $parameters     Array containing the parameters
 *
 * @return array    Array of custom parameters
 */
function lti_get_custom_parameters($toolproxy, $tool, $params, $parameters) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_custom_parameters() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_custom_parameters($toolproxy, $tool, $params, $parameters);
}

/**
 * Parse a custom parameter to replace any substitution variables
 *
 * @deprecated since Moodle 4.4
 * @param object    $toolproxy      Tool proxy instance object
 * @param object    $tool           Tool instance object
 * @param array     $params         LTI launch parameters
 * @param string    $value          Custom parameter value
 * @param boolean   $islti2         True if an LTI 2 tool is being launched
 *
 * @return string Parsed value of custom parameter
 */
function lti_parse_custom_parameter($toolproxy, $tool, $params, $value, $islti2) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::parse_custom_parameter() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::parse_custom_parameter($toolproxy, $tool, $params, $value, $islti2);
}

/**
 * Calculates the value of a custom parameter that has not been specified earlier
 *
 * @deprecated since Moodle 4.4
 * @param string    $value          Custom parameter value
 *
 * @return string Calculated value of custom parameter
 */
function lti_calculate_custom_parameter($value) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::calculate_custom_parameter() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::calculate_custom_parameter($value);
}

/**
 * Build the history chain for this course using the course originalcourseid.
 *
 * @deprecated since Moodle 4.4
 * @param object $course course for which the history is returned.
 *
 * @return array ids of the source course in ancestry order, immediate parent 1st.
 */
function get_course_history($course) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_course_history() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_course_history($course);
}

/**
 * Used for building the names of the different custom parameters
 *
 * @deprecated since Moodle 4.4
 * @param string $key   Parameter name
 * @param bool $tolower Do we want to convert the key into lower case?
 * @return string       Processed name
 */
function lti_map_keyname($key, $tolower = true) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::map_keyname() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::map_keyname($key, $tolower);
}

/**
 * Gets the IMS role string for the specified user and LTI course module.
 *
 * @param mixed    $user      User object or user id
 * @param int      $cmid      The course module id of the LTI activity
 * @param int      $courseid  The course id of the LTI activity
 * @param boolean  $islti2    True if an LTI 2 tool is being launched
 *
 * @return string A role string suitable for passing with an LTI launch
 */
function lti_get_ims_role($user, $cmid, $courseid, $islti2) {
    $roles = array();

    if (empty($cmid)) {
        // If no cmid is passed, check if the user is a teacher in the course
        // This allows other modules to programmatically "fake" a launch without
        // a real LTI instance.
        $context = context_course::instance($courseid);

        if (has_capability('moodle/course:manageactivities', $context, $user)) {
            array_push($roles, 'Instructor');
        } else {
            array_push($roles, 'Learner');
        }
    } else {
        $context = context_module::instance($cmid);

        if (has_capability('mod/lti:manage', $context)) {
            array_push($roles, 'Instructor');
        } else {
            array_push($roles, 'Learner');
        }
    }

    if (!is_role_switched($courseid) && (is_siteadmin($user)) || has_capability('mod/lti:admin', $context)) {
        // Make sure admins do not have the Learner role, then set admin role.
        $roles = array_diff($roles, array('Learner'));
        if (!$islti2) {
            array_push($roles, 'urn:lti:sysrole:ims/lis/Administrator', 'urn:lti:instrole:ims/lis/Administrator');
        } else {
            array_push($roles, 'http://purl.imsglobal.org/vocab/lis/v2/person#Administrator');
        }
    }

    return join(',', $roles);
}

/**
 * Returns configuration details for the tool
 *
 * @deprecated since Moodle 4.4
 * @param int $typeid   Basic LTI tool typeid
 *
 * @return array        Tool Configuration
 */
function lti_get_type_config($typeid) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::get_type_config() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\types_helper::get_type_config($typeid);
}

/**
 * Get tools by url
 *
 * @deprecated since Moodle 4.4
 * @param $url
 * @param $state
 * @param $courseid
 * @return array
 */
function lti_get_tools_by_url($url, $state, $courseid = null) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_tools_by_url() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_tools_by_url($url, $state, $courseid);
}

/**
 * Get tools by domain
 *
 * @deprecated since Moodle 4.4
 * @param $domain
 * @param $state
 * @param $courseid
 * @return array
 */
function lti_get_tools_by_domain($domain, $state = null, $courseid = null) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_tools_by_domain() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_tools_by_domain($domain, $state, $courseid);
}

/**
 * Returns all basicLTI tools configured by the administrator
 *
 * @deprecated since Moodle 4.4
 * @param int $course
 *
 * @return array
 */
function lti_filter_get_types($course) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::filter_get_types() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\types_helper::filter_get_types($course);
}

/**
 * Given an array of tools, filter them based on their state
 *
 * @deprecated since Moodle 4.4
 * @param array $tools An array of lti_types records
 * @param int $state One of the LTI_TOOL_STATE_* constants
 * @return array
 */
function lti_filter_tool_types(array $tools, $state) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::filter_tool_types() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::filter_tool_types($tools, $state);
}

/**
 * Returns all lti types visible in this course
 *
 * @deprecated since Moodle 4.3
 * @param int $courseid The id of the course to retieve types for
 * @param array $coursevisible options for 'coursevisible' field,
 *        default [LTI_COURSEVISIBLE_PRECONFIGURED, LTI_COURSEVISIBLE_ACTIVITYCHOOSER]
 * @return stdClass[] All the lti types visible in the given course
 */
function lti_get_lti_types_by_course($courseid, $coursevisible = null) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \mod_lti\local\types_helper::get_lti_types_by_course() instead.',
        DEBUG_DEVELOPER);

    global $USER;
    return \mod_lti\local\types_helper::get_lti_types_by_course($courseid, $USER->id, $coursevisible ?? []);
}

/**
 * Returns tool types for lti add instance and edit page
 *
 * @return array Array of lti types
 */
function lti_get_types_for_add_instance() {
    global $COURSE, $USER;

    // Always return the 'manual' type option, despite manual config being deprecated, so that we have it for legacy instances.
    $types = [(object) ['name' => get_string('automatic', 'lti'), 'course' => 0, 'toolproxyid' => null]];

    $preconfiguredtypes = \mod_lti\local\types_helper::get_lti_types_by_course($COURSE->id, $USER->id);
    foreach ($preconfiguredtypes as $type) {
        $types[$type->id] = $type;
    }

    return $types;
}

/**
 * Returns a list of configured types in the given course
 *
 * @param int $courseid The id of the course to retieve types for
 * @param int $sectionreturn section to return to for forming the URLs
 * @return array Array of lti types. Each element is object with properties: name, title, icon, help, helplink, link
 */
function lti_get_configured_types($courseid, $sectionreturn = 0) {
    global $OUTPUT, $USER;
    $types = [];
    $preconfiguredtypes = \mod_lti\local\types_helper::get_lti_types_by_course($courseid, $USER->id,
        [LTI_COURSEVISIBLE_ACTIVITYCHOOSER]);

    foreach ($preconfiguredtypes as $ltitype) {
        $type           = new stdClass();
        $type->id       = $ltitype->id;
        $type->modclass = MOD_CLASS_ACTIVITY;
        $type->name     = 'lti_type_' . $ltitype->id;
        // Clean the name. We don't want tags here.
        $type->title    = clean_param($ltitype->name, PARAM_NOTAGS);
        $trimmeddescription = trim($ltitype->description ?? '');
        if ($trimmeddescription != '') {
            // Clean the description. We don't want tags here.
            $type->help     = clean_param($trimmeddescription, PARAM_NOTAGS);
            $type->helplink = get_string('modulename_shortcut_link', 'lti');
        }

        $iconurl = get_tool_type_icon_url($ltitype);
        $iconclass = '';
        if ($iconurl !== $OUTPUT->image_url('monologo', 'lti')->out()) {
            // Do not filter the icon if it is not the default LTI activity icon.
            $iconclass = 'nofilter';
        }
        $type->icon = html_writer::empty_tag('img', ['src' => $iconurl, 'alt' => '', 'class' => "icon $iconclass"]);

        $params = [
            'add' => 'lti',
            'return' => 0,
            'course' => $courseid,
            'typeid' => $ltitype->id,
        ];
        if (!is_null($sectionreturn)) {
            $params['sr'] = $sectionreturn;
        }
        $type->link = new moodle_url('/course/modedit.php', $params);
        $types[] = $type;
    }
    return $types;
}

/***
 * Get LTI domain from URL
 *
 * @deprecated since Moodle 4.4
 * @param $url
 * @return mixed|void
 */
function lti_get_domain_from_url($url) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_domain_from_url() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_domain_from_url($url);
}

/**
 * Get tool by url match
 *
 * @deprecated since Moodle 4.4
 * @param $url
 * @param $courseid
 * @param $state
 * @return mixed|null
 */
function lti_get_tool_by_url_match($url, $courseid = null, $state = LTI_TOOL_STATE_CONFIGURED) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_tool_by_url_match() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_tool_by_url_match($url, $courseid, $state);
}

/**
 * Get URL by thumbprint
 *
 * @deprecated since Moodle 4.4
 * @param $url
 * @return string
 */
function lti_get_url_thumbprint($url) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_url_thumbprint() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_url_thumbprint($url);
}

/**
 * Get best tool by URL
 *
 * @deprecated since Moodle 4.4
 * @param $url
 * @param $tools
 * @param $courseid
 * @return mixed|null
 */
function lti_get_best_tool_by_url($url, $tools, $courseid = null) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_best_tool_by_url() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_best_tool_by_url($url, $tools, $courseid);
}

function lti_get_shared_secrets_by_key($key) {
    global $DB;

    // Look up the shared secret for the specified key in both the types_config table (for configured tools)
    // And in the lti resource table for ad-hoc tools.
    $lti13 = LTI_VERSION_1P3;
    $query = "SELECT " . $DB->sql_compare_text('t2.value', 256) . " AS value
                FROM {lti_types_config} t1
                JOIN {lti_types_config} t2 ON t1.typeid = t2.typeid
                JOIN {lti_types} type ON t2.typeid = type.id
              WHERE t1.name = 'resourcekey'
                AND " . $DB->sql_compare_text('t1.value', 256) . " = :key1
                AND t2.name = 'password'
                AND type.state = :configured1
                AND type.ltiversion <> :ltiversion
               UNION
              SELECT tp.secret AS value
                FROM {lti_tool_proxies} tp
                JOIN {lti_types} t ON tp.id = t.toolproxyid
              WHERE tp.guid = :key2
                AND t.state = :configured2
               UNION
              SELECT password AS value
               FROM {lti}
              WHERE resourcekey = :key3";

    $sharedsecrets = $DB->get_records_sql($query, array('configured1' => LTI_TOOL_STATE_CONFIGURED, 'ltiversion' => $lti13,
        'configured2' => LTI_TOOL_STATE_CONFIGURED, 'key1' => $key, 'key2' => $key, 'key3' => $key));

    $values = array_map(function($item) {
        return $item->value;
    }, $sharedsecrets);

    // There should really only be one shared secret per key. But, we can't prevent
    // more than one getting entered. For instance, if the same key is used for two tool providers.
    return $values;
}

/**
 * Delete a Basic LTI configuration
 *
 * @deprecated since Moodle 4.4
 * @param int $id   Configuration id
 */
function lti_delete_type($id) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::delete_type() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\types_helper::delete_type($id);
}

/**
 * Set type state
 *
 * @deprecated since Moodle 4.4
 * @param $id
 * @param $state
 */
function lti_set_state_for_type($id, $state) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::set_state_for_type() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\types_helper::set_state_for_type($id, $state);
}

/**
 * Transforms a basic LTI object to an array
 *
 * @param object $ltiobject    Basic LTI object
 *
 * @return array Basic LTI configuration details
 */
function lti_get_config($ltiobject) {
    $typeconfig = (array)$ltiobject;
    $additionalconfig = lti_get_type_config($ltiobject->typeid);
    $typeconfig = array_merge($typeconfig, $additionalconfig);
    return $typeconfig;
}

/**
 *
 * Generates some of the tool configuration based on the instance details
 *
 * @param int $id
 *
 * @return object configuration
 *
 */
function lti_get_type_config_from_instance($id) {
    global $DB;

    $instance = $DB->get_record('lti', array('id' => $id));
    $config = lti_get_config($instance);

    $type = new \stdClass();
    $type->lti_fix = $id;
    if (isset($config['toolurl'])) {
        $type->lti_toolurl = $config['toolurl'];
    }
    if (isset($config['instructorchoicesendname'])) {
        $type->lti_sendname = $config['instructorchoicesendname'];
    }
    if (isset($config['instructorchoicesendemailaddr'])) {
        $type->lti_sendemailaddr = $config['instructorchoicesendemailaddr'];
    }
    if (isset($config['instructorchoiceacceptgrades'])) {
        $type->lti_acceptgrades = $config['instructorchoiceacceptgrades'];
    }
    if (isset($config['instructorchoiceallowroster'])) {
        $type->lti_allowroster = $config['instructorchoiceallowroster'];
    }

    if (isset($config['instructorcustomparameters'])) {
        $type->lti_allowsetting = $config['instructorcustomparameters'];
    }
    return $type;
}

/**
 * Generates some of the tool configuration based on the admin configuration details
 *
 * @deprecated since Moodle 4.4
 * @param int $id
 *
 * @return stdClass Configuration details
 */
function lti_get_type_type_config($id) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::get_type_type_config() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\types_helper::get_type_type_config($id);
}

/**
 * Prepare type config for save
 *
 * @deprecated since Moodle 4.4
 * @param $type
 * @param $config
 */
function lti_prepare_type_for_save($type, $config) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::prepare_type_for_save() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\types_helper::prepare_type_for_save($type, $config);
}

/**
 * Update type
 *
 * @deprecated since Moodle 4.4
 * @param $type
 * @param $config
 */
function lti_update_type($type, $config) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::update_type() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\types_helper::update_type($type, $config);
}

/**
 * Add LTI Type course category.
 *
 * @deprecated since Moodle 4.4
 * @param int $typeid
 * @param string $lticoursecategories Comma separated list of course categories.
 * @return void
 */
function lti_type_add_categories(int $typeid, string $lticoursecategories = ''): void {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::type_add_categories() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\types_helper::type_add_categories($typeid, $lticoursecategories);
}

/**
 * Add LTI type
 *
 * @deprecated since Moodle 4.4
 * @param $type
 * @param $config
 * @return bool|int
 */
function lti_add_type($type, $config) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::add_type() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\types_helper::add_type($type, $config);
}

/**
 * Given an array of tool proxies, filter them based on their state
 *
 * @deprecated since Moodle 4.4
 * @param array $toolproxies An array of lti_tool_proxies records
 * @param int $state One of the LTI_TOOL_PROXY_STATE_* constants
 *
 * @return array
 */
function lti_filter_tool_proxy_types(array $toolproxies, $state) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::filter_tool_proxy_types() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::filter_tool_proxy_types($toolproxies, $state);
}

/**
 * Get the tool proxy instance given its GUID
 *
 * @deprecated since Moodle 4.4
 * @param string  $toolproxyguid   Tool proxy GUID value
 *
 * @return object
 */
function lti_get_tool_proxy_from_guid($toolproxyguid) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_tool_proxy_from_guid() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_tool_proxy_from_guid($toolproxyguid);
}

/**
 * Get the tool proxy instance given its registration URL
 *
 * @deprecated since Moodle 4.4
 * @param string $regurl Tool proxy registration URL
 *
 * @return array The record of the tool proxy with this url
 */
function lti_get_tool_proxies_from_registration_url($regurl) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_tool_proxies_from_registration_url() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_tool_proxies_from_registration_url($regurl);
}

/**
 * Generates some of the tool proxy configuration based on the admin configuration details
 *
 * @deprecated since Moodle 4.4
 * @param int $id
 *
 * @return mixed Tool Proxy details
 */
function lti_get_tool_proxy($id) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_tool_proxy() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_tool_proxy($id);
}

/**
 * Returns lti tool proxies.
 *
 * @deprecated since Moodle 4.4
 * @param bool $orphanedonly Only retrieves tool proxies that have no type associated with them
 * @return array of basicLTI types
 */
function lti_get_tool_proxies($orphanedonly) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_tool_proxies() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_tool_proxies($orphanedonly);
}

/**
 * Generates some of the tool proxy configuration based on the admin configuration details
 *
 * @deprecated since Moodle 4.4
 * @param int $id
 *
 * @return mixed  Tool Proxy details
 */
function lti_get_tool_proxy_config($id) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_tool_proxy_config() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_tool_proxy_config($id);
}

/**
 * Update the database with a tool proxy instance
 *
 * @deprecated since Moodle 4.4
 * @param object   $config    Tool proxy definition
 *
 * @return int  Record id number
 */
function lti_add_tool_proxy($config) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::add_tool_proxy() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::add_tool_proxy($config);
}

/**
 * Updates a tool proxy in the database
 *
 * @deprecated since Moodle 4.4
 * @param object  $toolproxy   Tool proxy
 *
 * @return int    Record id number
 */
function lti_update_tool_proxy($toolproxy) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::update_tool_proxy() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::update_tool_proxy($toolproxy);
}

/**
 * Delete a Tool Proxy
 *
 * @deprecated since Moodle 4.4
 * @param int $id   Tool Proxy id
 */
function lti_delete_tool_proxy($id) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::delete_tool_proxy() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\tool_helper::delete_tool_proxy($id);
}

/**
 * Get both LTI tool proxies and tool types.
 *
 * If limit and offset are not zero, a subset of the tools will be returned. Tool proxies will be counted before tool
 * types.
 * For example: If 10 tool proxies and 10 tool types exist, and the limit is set to 15, then 10 proxies and 5 types
 * will be returned.
 *
 * @deprecated since Moodle 4.4
 * @param int $limit Maximum number of tools returned.
 * @param int $offset Do not return tools before offset index.
 * @param bool $orphanedonly If true, only return orphaned proxies.
 * @param int $toolproxyid If not 0, only return tool types that have this tool proxy id.
 * @return array list(proxies[], types[]) List containing array of tool proxies and array of tool types.
 */
function lti_get_lti_types_and_proxies(int $limit = 0, int $offset = 0, bool $orphanedonly = false, int $toolproxyid = 0): array {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::get_lti_types_and_proxies() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\types_helper::get_lti_types_and_proxies($limit, $offset, $orphanedonly, $toolproxyid);
}

/**
 * Get the total number of LTI tool types and tool proxies.
 *
 * @deprecated since Moodle 4.4
 * @param bool $orphanedonly If true, only count orphaned proxies.
 * @param int $toolproxyid If not 0, only count tool types that have this tool proxy id.
 * @return int Count of tools.
 */
function lti_get_lti_types_and_proxies_count(bool $orphanedonly = false, int $toolproxyid = 0): int {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::get_lti_types_and_proxies_count() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\types_helper::get_lti_types_and_proxies_count($orphanedonly, $toolproxyid);
}

/**
 * Add a tool configuration in the database
 *
 * @deprecated since Moodle 4.4
 * @param object $config   Tool configuration
 *
 * @return int Record id number
 */
function lti_add_config($config) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::add_config() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\types_helper::add_config($config);
}

/**
 * Updates a tool configuration in the database
 *
 * @deprecated since Moodle 4.4
 * @param object  $config   Tool configuration
 *
 * @return mixed Record id number
 */
function lti_update_config($config) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::update_config() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\types_helper::update_config($config);
}

/**
 * Gets the tool settings
 *
 * @deprecated since Moodle 4.4
 * @param int  $toolproxyid   Id of tool proxy record (or tool ID if negative)
 * @param int  $courseid      Id of course (null if system settings)
 * @param int  $instanceid    Id of course module (null if system or context settings)
 *
 * @return array  Array settings
 */
function lti_get_tool_settings($toolproxyid, $courseid = null, $instanceid = null) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_tool_settings() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_tool_settings($toolproxyid, $courseid, $instanceid);
}

/**
 * Sets the tool settings (
 *
 * @param array  $settings      Array of settings
 * @param int    $toolproxyid   Id of tool proxy record (or tool ID if negative)
 * @param int    $courseid      Id of course (null if system settings)
 * @param int    $instanceid    Id of course module (null if system or context settings)
 */
function lti_set_tool_settings($settings, $toolproxyid, $courseid = null, $instanceid = null) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::set_tool_settings() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\tool_helper::set_tool_settings($settings, $toolproxyid, $courseid, $instanceid);
}

/**
 * Signs the petition to launch the external tool using OAuth
 *
 * @deprecated since Moodle 4.4
 * @param array  $oldparms     Parameters to be passed for signing
 * @param string $endpoint     url of the external tool
 * @param string $method       Method for sending the parameters (e.g. POST)
 * @param string $oauthconsumerkey
 * @param string $oauthconsumersecret
 * @return array|null
 */
function lti_sign_parameters($oldparms, $endpoint, $method, $oauthconsumerkey, $oauthconsumersecret) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\oauth_helper::sign_parameters() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\oauth_helper::sign_parameters($oldparms, $endpoint, $method, $oauthconsumerkey, $oauthconsumersecret);
}

/**
 * Converts the message paramters to their equivalent JWT claim and signs the payload to launch the external tool using JWT
 *
 * @deprecated since Moodle 4.4
 * @param array  $parms        Parameters to be passed for signing
 * @param string $endpoint     url of the external tool
 * @param string $oauthconsumerkey
 * @param string $typeid       ID of LTI tool type
 * @param string $nonce        Nonce value to use
 * @return array|null
 */
function lti_sign_jwt($parms, $endpoint, $oauthconsumerkey, $typeid = 0, $nonce = '') {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\oauth_helper::sign_jwt() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\oauth_helper::sign_jwt($parms, $endpoint, $oauthconsumerkey, $typeid, $nonce);
}

/**
 * Verfies the JWT and converts its claims to their equivalent message parameter.
 *
 * @deprecated since Moodle 4.4
 * @param int    $typeid
 * @param string $jwtparam   JWT parameter
 *
 * @return array  message parameters
 * @throws moodle_exception
 */
function lti_convert_from_jwt($typeid, $jwtparam) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\oauth_helper::convert_from_jwt() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\oauth_helper::convert_from_jwt($typeid, $jwtparam);
}

/**
 * Posts the launch petition HTML
 *
 * @param array $newparms   Signed parameters
 * @param string $endpoint  URL of the external tool
 * @param bool $debug       Debug (true/false)
 * @return string
 */
function lti_post_launch_html($newparms, $endpoint, $debug=false) {
    $r = "<form action=\"" . $endpoint .
        "\" name=\"ltiLaunchForm\" id=\"ltiLaunchForm\" method=\"post\" encType=\"application/x-www-form-urlencoded\">\n";

    // Contruct html for the launch parameters.
    foreach ($newparms as $key => $value) {
        $key = htmlspecialchars($key, ENT_COMPAT);
        $value = htmlspecialchars($value, ENT_COMPAT);
        if ( $key == "ext_submit" ) {
            $r .= "<input type=\"submit\"";
        } else {
            $r .= "<input type=\"hidden\" name=\"{$key}\"";
        }
        $r .= " value=\"";
        $r .= $value;
        $r .= "\"/>\n";
    }

    if ( $debug ) {
        $r .= "<script language=\"javascript\"> \n";
        $r .= "  //<![CDATA[ \n";
        $r .= "function basicltiDebugToggle() {\n";
        $r .= "    var ele = document.getElementById(\"basicltiDebug\");\n";
        $r .= "    if (ele.style.display == \"block\") {\n";
        $r .= "        ele.style.display = \"none\";\n";
        $r .= "    }\n";
        $r .= "    else {\n";
        $r .= "        ele.style.display = \"block\";\n";
        $r .= "    }\n";
        $r .= "} \n";
        $r .= "  //]]> \n";
        $r .= "</script>\n";
        $r .= "<a id=\"displayText\" href=\"javascript:basicltiDebugToggle();\">";
        $r .= get_string("toggle_debug_data", "lti")."</a>\n";
        $r .= "<div id=\"basicltiDebug\" style=\"display:none\">\n";
        $r .= "<b>".get_string("basiclti_endpoint", "lti")."</b><br/>\n";
        $r .= $endpoint . "<br/>\n&nbsp;<br/>\n";
        $r .= "<b>".get_string("basiclti_parameters", "lti")."</b><br/>\n";
        foreach ($newparms as $key => $value) {
            $key = htmlspecialchars($key, ENT_COMPAT);
            $value = htmlspecialchars($value, ENT_COMPAT);
            $r .= "$key = $value<br/>\n";
        }
        $r .= "&nbsp;<br/>\n";
        $r .= "</div>\n";
    }
    $r .= "</form>\n";

    // Auto-submit the form if endpoint is set.
    if ($endpoint !== '' && !$debug) {
        $r .= " <script type=\"text/javascript\"> \n" .
            "  //<![CDATA[ \n" .
            "    document.ltiLaunchForm.submit(); \n" .
            "  //]]> \n" .
            " </script> \n";
    }
    return $r;
}

/**
 * Generate the form for initiating a login request for an LTI 1.3 message
 *
 * @param int            $courseid  Course ID
 * @param int            $cmid        LTI instance ID
 * @param stdClass|null  $instance  LTI instance
 * @param stdClass       $config    Tool type configuration
 * @param string         $messagetype   LTI message type
 * @param string         $title     Title of content item
 * @param string         $text      Description of content item
 * @param int            $foruserid Id of the user targeted by the launch
 * @return string
 */
function lti_initiate_login($courseid, $cmid, $instance, $config, $messagetype = 'basic-lti-launch-request',
        $title = '', $text = '', $foruserid = 0) {
    global $SESSION;

    $params = lti_build_login_request($courseid, $cmid, $instance, $config, $messagetype, $foruserid, $title, $text);

    $r = "<form action=\"" . $config->lti_initiatelogin .
        "\" name=\"ltiInitiateLoginForm\" id=\"ltiInitiateLoginForm\" method=\"post\" " .
        "encType=\"application/x-www-form-urlencoded\">\n";

    foreach ($params as $key => $value) {
        $key = htmlspecialchars($key, ENT_COMPAT);
        $value = htmlspecialchars($value, ENT_COMPAT);
        $r .= "  <input type=\"hidden\" name=\"{$key}\" value=\"{$value}\"/>\n";
    }
    $r .= "</form>\n";

    $r .= "<script type=\"text/javascript\">\n" .
        "//<![CDATA[\n" .
        "document.ltiInitiateLoginForm.submit();\n" .
        "//]]>\n" .
        "</script>\n";

    return $r;
}

/**
 * Prepares an LTI 1.3 login request
 *
 * @param int            $courseid  Course ID
 * @param int            $cmid        Course Module instance ID
 * @param stdClass|null  $instance  LTI instance
 * @param stdClass       $config    Tool type configuration
 * @param string         $messagetype   LTI message type
 * @param int            $foruserid Id of the user targeted by the launch
 * @param string         $title     Title of content item
 * @param string         $text      Description of content item
 * @return array Login request parameters
 */
function lti_build_login_request($courseid, $cmid, $instance, $config, $messagetype, $foruserid=0, $title = '', $text = '') {
    global $USER, $CFG, $SESSION;
    $ltihint = [];
    if (!empty($instance)) {
        $endpoint = !empty($instance->toolurl) ? $instance->toolurl : $config->lti_toolurl;
        $launchid = 'ltilaunch'.$instance->id.'_'.rand();
        $ltihint['cmid'] = $cmid;
        $SESSION->$launchid = "{$courseid},{$config->typeid},{$cmid},{$messagetype},{$foruserid},,";
    } else {
        $endpoint = $config->lti_toolurl;
        if (($messagetype === 'ContentItemSelectionRequest') && !empty($config->lti_toolurl_ContentItemSelectionRequest)) {
            $endpoint = $config->lti_toolurl_ContentItemSelectionRequest;
        }
        $launchid = "ltilaunch_$messagetype".rand();
        $SESSION->$launchid =
            "{$courseid},{$config->typeid},,{$messagetype},{$foruserid}," . base64_encode($title) . ',' . base64_encode($text);
    }
    $endpoint = trim($endpoint);
    $services = lti_get_services();
    foreach ($services as $service) {
        [$endpoint] = $service->override_endpoint($messagetype ?? 'basic-lti-launch-request', $endpoint, '', $courseid, $instance);
    }

    $ltihint['launchid'] = $launchid;
    // If SSL is forced make sure https is on the normal launch URL.
    if (isset($config->lti_forcessl) && ($config->lti_forcessl == '1')) {
        $endpoint = lti_ensure_url_is_https($endpoint);
    } else if (!strstr($endpoint, '://')) {
        $endpoint = 'http://' . $endpoint;
    }

    $params = array();
    $params['iss'] = $CFG->wwwroot;
    $params['target_link_uri'] = $endpoint;
    $params['login_hint'] = $USER->id;
    $params['lti_message_hint'] = json_encode($ltihint);
    $params['client_id'] = $config->lti_clientid;
    $params['lti_deployment_id'] = $config->typeid;
    return $params;
}

/**
 * Get type record by id
 *
 * @deprecated since Moodle 4.4
 * @param $typeid
 * @return false|mixed|stdClass
 */
function lti_get_type($typeid) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::get_type() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\types_helper::get_type($typeid);
}

function lti_get_launch_container($lti, $toolconfig) {
    if (empty($lti->launchcontainer)) {
        $lti->launchcontainer = LTI_LAUNCH_CONTAINER_DEFAULT;
    }

    if ($lti->launchcontainer == LTI_LAUNCH_CONTAINER_DEFAULT) {
        if (isset($toolconfig['launchcontainer'])) {
            $launchcontainer = $toolconfig['launchcontainer'];
        }
    } else {
        $launchcontainer = $lti->launchcontainer;
    }

    if (empty($launchcontainer) || $launchcontainer == LTI_LAUNCH_CONTAINER_DEFAULT) {
        $launchcontainer = LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS;
    }

    $devicetype = core_useragent::get_device_type();

    // Scrolling within the object element doesn't work on iOS or Android
    // Opening the popup window also had some issues in testing
    // For mobile devices, always take up the entire screen to ensure the best experience.
    if ($devicetype === core_useragent::DEVICETYPE_MOBILE || $devicetype === core_useragent::DEVICETYPE_TABLET ) {
        $launchcontainer = LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW;
    }

    return $launchcontainer;
}

/**
 * @deprecated since Moodle 4.4
 * @return bool
 */
function lti_request_is_using_ssl() {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::request_is_using_ssl() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::request_is_using_ssl();
}

/**
 * Ensure URL is https
 *
 * @deprecated since Moodle 4.4
 * @param $url
 * @return mixed|string
 */
function lti_ensure_url_is_https($url) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::ensure_url_is_https() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::ensure_url_is_https($url);
}

/**
 * Determines if we should try to log the request
 *
 * @param string $rawbody
 * @return bool
 */
function lti_should_log_request($rawbody) {
    global $CFG;

    if (empty($CFG->mod_lti_log_users)) {
        return false;
    }

    $logusers = explode(',', $CFG->mod_lti_log_users);
    if (empty($logusers)) {
        return false;
    }

    try {
        $xml = new \SimpleXMLElement($rawbody);
        $ns  = $xml->getNamespaces();
        $ns  = array_shift($ns);
        $xml->registerXPathNamespace('lti', $ns);
        $requestuserid = '';
        if ($node = $xml->xpath('//lti:userId')) {
            $node = $node[0];
            $requestuserid = clean_param((string) $node, PARAM_INT);
        } else if ($node = $xml->xpath('//lti:sourcedId')) {
            $node = $node[0];
            $resultjson = json_decode((string) $node);
            $requestuserid = clean_param($resultjson->data->userid, PARAM_INT);
        }
    } catch (Exception $e) {
        return false;
    }

    if (empty($requestuserid) or !in_array($requestuserid, $logusers)) {
        return false;
    }

    return true;
}

/**
 * Logs the request to a file in temp dir.
 *
 * @param string $rawbody
 */
function lti_log_request($rawbody) {
    if ($tempdir = make_temp_directory('mod_lti', false)) {
        if ($tempfile = tempnam($tempdir, 'mod_lti_request'.date('YmdHis'))) {
            $content  = "Request Headers:\n";
            foreach (moodle\ltix\OAuthUtil::get_headers() as $header => $value) {
                $content .= "$header: $value\n";
            }
            $content .= "Request Body:\n";
            $content .= $rawbody;

            file_put_contents($tempfile, $content);
            chmod($tempfile, 0644);
        }
    }
}

/**
 * Log an LTI response.
 *
 * @param string $responsexml The response XML
 * @param Exception $e If there was an exception, pass that too
 */
function lti_log_response($responsexml, $e = null) {
    if ($tempdir = make_temp_directory('mod_lti', false)) {
        if ($tempfile = tempnam($tempdir, 'mod_lti_response'.date('YmdHis'))) {
            $content = '';
            if ($e instanceof Exception) {
                $info = get_exception_info($e);

                $content .= "Exception:\n";
                $content .= "Message: $info->message\n";
                $content .= "Debug info: $info->debuginfo\n";
                $content .= "Backtrace:\n";
                $content .= format_backtrace($info->backtrace, true);
                $content .= "\n";
            }
            $content .= "Response XML:\n";
            $content .= $responsexml;

            file_put_contents($tempfile, $content);
            chmod($tempfile, 0644);
        }
    }
}

/**
 * Fetches LTI type configuration for an LTI instance
 *
 * @param stdClass $instance
 * @return array Can be empty if no type is found
 */
function lti_get_type_config_by_instance($instance) {
    $typeid = null;
    if (empty($instance->typeid)) {
        $tool = lti_get_tool_by_url_match($instance->toolurl, $instance->course);
        if ($tool) {
            $typeid = $tool->id;
        }
    } else {
        $typeid = $instance->typeid;
    }
    if (!empty($typeid)) {
        return lti_get_type_config($typeid);
    }
    return array();
}

/**
 * Enforce type config settings onto the LTI instance
 *
 * @param stdClass $instance
 * @param array $typeconfig
 */
function lti_force_type_config_settings($instance, array $typeconfig) {
    $forced = array(
        'instructorchoicesendname'      => 'sendname',
        'instructorchoicesendemailaddr' => 'sendemailaddr',
        'instructorchoiceacceptgrades'  => 'acceptgrades',
    );

    foreach ($forced as $instanceparam => $typeconfigparam) {
        if (array_key_exists($typeconfigparam, $typeconfig) && $typeconfig[$typeconfigparam] != LTI_SETTING_DELEGATE) {
            $instance->$instanceparam = $typeconfig[$typeconfigparam];
        }
    }
}

/**
 * Initializes an array with the capabilities supported by the LTI module
 *
 * @deprecated since Moodle 4.4
 * @return array List of capability names (without a dollar sign prefix)
 */
function lti_get_capabilities() {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_capabilities() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_capabilities();
}

/**
 * Initializes an array with the services supported by the LTI module
 *
 * @deprecated since Moodle 4.4
 * @return array List of services
 */
function lti_get_services() {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_services() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_services();
}

/**
 * Initializes an instance of the named service
 *
 * @param string $servicename Name of service
 *
 * @return bool|\mod_lti\local\ltiservice\service_base Service
 */
function lti_get_service_by_name($servicename) {

    $service = false;
    $classname = "\\ltiservice_{$servicename}\\local\\service\\{$servicename}";
    if (class_exists($classname)) {
        $service = new $classname();
    }

    return $service;

}

/**
 * Finds a service by id
 *
 * @param \mod_lti\local\ltiservice\service_base[] $services Array of services
 * @param string $resourceid  ID of resource
 *
 * @return mod_lti\local\ltiservice\service_base Service
 */
function lti_get_service_by_resource_id($services, $resourceid) {

    $service = false;
    foreach ($services as $aservice) {
        foreach ($aservice->get_resources() as $resource) {
            if ($resource->get_id() === $resourceid) {
                $service = $aservice;
                break 2;
            }
        }
    }

    return $service;

}

/**
 * Initializes an array with the scopes for services supported by the LTI module
 * and authorized for this particular tool instance.
 *
 * @param object $type  LTI tool type
 * @param array  $typeconfig  LTI tool type configuration
 *
 * @return array List of scopes
 */
function lti_get_permitted_service_scopes($type, $typeconfig) {

    $services = lti_get_services();
    $scopes = array();
    foreach ($services as $service) {
        $service->set_type($type);
        $service->set_typeconfig($typeconfig);
        $servicescopes = $service->get_permitted_scopes();
        if (!empty($servicescopes)) {
            $scopes = array_merge($scopes, $servicescopes);
        }
    }

    return $scopes;
}

/**
 * Extracts the named contexts from a tool proxy
 *
 * @param object $json
 *
 * @return array Contexts
 */
function lti_get_contexts($json) {

    $contexts = array();
    if (isset($json->{'@context'})) {
        foreach ($json->{'@context'} as $context) {
            if (is_object($context)) {
                $contexts = array_merge(get_object_vars($context), $contexts);
            }
        }
    }

    return $contexts;

}

/**
 * Converts an ID to a fully-qualified ID
 *
 * @param array $contexts
 * @param string $id
 *
 * @return string Fully-qualified ID
 */
function lti_get_fqid($contexts, $id) {

    $parts = explode(':', $id, 2);
    if (count($parts) > 1) {
        if (array_key_exists($parts[0], $contexts)) {
            $id = $contexts[$parts[0]] . $parts[1];
        }
    }

    return $id;

}

/**
 * Returns the icon for the given tool type
 *
 * @param stdClass $type The tool type
 *
 * @return string The url to the tool type's corresponding icon
 */
function get_tool_type_icon_url(stdClass $type) {
    global $OUTPUT;

    $iconurl = $type->secureicon;

    if (empty($iconurl)) {
        $iconurl = $type->icon;
    }

    if (empty($iconurl)) {
        $iconurl = $OUTPUT->image_url('monologo', 'lti')->out();
    }

    return $iconurl;
}

/**
 * Returns the edit url for the given tool type
 *
 * @param stdClass $type The tool type
 *
 * @return string The url to edit the tool type
 */
function get_tool_type_edit_url(stdClass $type) {
    $url = new moodle_url('/mod/lti/typessettings.php',
                          array('action' => 'update', 'id' => $type->id, 'sesskey' => sesskey(), 'returnto' => 'toolconfigure'));
    return $url->out();
}

/**
 * Returns the edit url for the given tool proxy.
 *
 * @param stdClass $proxy The tool proxy
 *
 * @return string The url to edit the tool type
 */
function get_tool_proxy_edit_url(stdClass $proxy) {
    $url = new moodle_url('/mod/lti/registersettings.php',
                          array('action' => 'update', 'id' => $proxy->id, 'sesskey' => sesskey(), 'returnto' => 'toolconfigure'));
    return $url->out();
}

/**
 * Returns the course url for the given tool type
 *
 * @param stdClass $type The tool type
 *
 * @return string The url to the course of the tool type, void if it is a site wide type
 */
function get_tool_type_course_url(stdClass $type) {
    if ($type->course != 1) {
        $url = new moodle_url('/course/view.php', array('id' => $type->course));
        return $url->out();
    }
    return null;
}

/**
 * Returns the icon and edit urls for the tool type and the course url if it is a course type.
 *
 * @param stdClass $type The tool type
 *
 * @return array The urls of the tool type
 */
function get_tool_type_urls(stdClass $type) {
    $courseurl = get_tool_type_course_url($type);

    $urls = array(
        'icon' => get_tool_type_icon_url($type),
        'edit' => get_tool_type_edit_url($type),
    );

    if ($courseurl) {
        $urls['course'] = $courseurl;
    }

    $url = new moodle_url('/mod/lti/certs.php');
    $urls['publickeyset'] = $url->out();
    $url = new moodle_url('/mod/lti/token.php');
    $urls['accesstoken'] = $url->out();
    $url = new moodle_url('/mod/lti/auth.php');
    $urls['authrequest'] = $url->out();

    return $urls;
}

/**
 * Returns the icon and edit urls for the tool proxy.
 *
 * @param stdClass $proxy The tool proxy
 *
 * @return array The urls of the tool proxy
 */
function get_tool_proxy_urls(stdClass $proxy) {
    global $OUTPUT;

    $urls = array(
        'icon' => $OUTPUT->image_url('monologo', 'lti')->out(),
        'edit' => get_tool_proxy_edit_url($proxy),
    );

    return $urls;
}

/**
 * Returns information on the current state of the tool type
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $type The tool type
 *
 * @return array An array with a text description of the state, and boolean for whether it is in each state:
 * pending, configured, rejected, unknown
 */
function get_tool_type_state_info(stdClass $type) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::get_tool_type_state_info() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\types_helper::get_tool_type_state_info($type);
}

/**
 * Returns information on the configuration of the tool type
 *
 * @param stdClass $type The tool type
 *
 * @return array An array with configuration details
 */
function get_tool_type_config($type) {
    global $CFG;
    $platformid = $CFG->wwwroot;
    $clientid = $type->clientid;
    $deploymentid = $type->id;
    $publickeyseturl = new moodle_url('/mod/lti/certs.php');
    $publickeyseturl = $publickeyseturl->out();

    $accesstokenurl = new moodle_url('/mod/lti/token.php');
    $accesstokenurl = $accesstokenurl->out();

    $authrequesturl = new moodle_url('/mod/lti/auth.php');
    $authrequesturl = $authrequesturl->out();

    return array(
        'platformid' => $platformid,
        'clientid' => $clientid,
        'deploymentid' => $deploymentid,
        'publickeyseturl' => $publickeyseturl,
        'accesstokenurl' => $accesstokenurl,
        'authrequesturl' => $authrequesturl
    );
}

/**
 * Returns a summary of each LTI capability this tool type requires in plain language
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $type The tool type
 *
 * @return array An array of text descriptions of each of the capabilities this tool type requires
 */
function get_tool_type_capability_groups($type) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::get_tool_type_capability_groups() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\types_helper::get_tool_type_capability_groups($type);
}


/**
 * Returns the ids of each instance of this tool type
 *
 * @param stdClass $type The tool type
 *
 * @return array An array of ids of the instances of this tool type
 */
function get_tool_type_instance_ids($type) {
    global $DB;

    return array_keys($DB->get_fieldset_select('lti', 'id', 'typeid = ?', array($type->id)));
}

/**
 * Serialises this tool type
 *
 * @param stdClass $type The tool type
 *
 * @return array An array of values representing this type
 */
function serialise_tool_type(stdClass $type) {
    global $CFG;

    $capabilitygroups = get_tool_type_capability_groups($type);
    $instanceids = get_tool_type_instance_ids($type);
    // Clean the name. We don't want tags here.
    $name = clean_param($type->name, PARAM_NOTAGS);
    if (!empty($type->description)) {
        // Clean the description. We don't want tags here.
        $description = clean_param($type->description, PARAM_NOTAGS);
    } else {
        $description = get_string('editdescription', 'mod_lti');
    }
    return array(
        'id' => $type->id,
        'name' => $name,
        'description' => $description,
        'urls' => get_tool_type_urls($type),
        'state' => get_tool_type_state_info($type),
        'platformid' => $CFG->wwwroot,
        'clientid' => $type->clientid,
        'deploymentid' => $type->id,
        'hascapabilitygroups' => !empty($capabilitygroups),
        'capabilitygroups' => $capabilitygroups,
        // Course ID of 1 means it's not linked to a course.
        'courseid' => $type->course == 1 ? 0 : $type->course,
        'instanceids' => $instanceids,
        'instancecount' => count($instanceids)
    );
}

/**
 * Loads the cartridge information into the tool type, if the launch url is for a cartridge file
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $type The tool type object to be filled in
 * @since Moodle 3.1
 */
function lti_load_type_if_cartridge($type) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::load_type_if_cartridge() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\types_helper::load_type_if_cartridge($type);
}

/**
 * Loads the cartridge information into the new tool, if the launch url is for a cartridge file
 *
 * @param stdClass $lti The tools config
 * @since Moodle 3.1
 */
function lti_load_tool_if_cartridge($lti) {
    if (!empty($lti->toolurl) && lti_is_cartridge($lti->toolurl)) {
        lti_load_tool_from_cartridge($lti->toolurl, $lti);
    }
}

/**
 * Determines if the given url is for a IMS basic cartridge
 *
 * @deprecated since Moodle 4.4
 * @param  string $url The url to be checked
 * @return True if the url is for a cartridge
 * @since Moodle 3.1
 */
function lti_is_cartridge($url) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::is_cartridge() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::is_cartridge($url);
}

/**
 * Allows you to load settings for an external tool type from an IMS cartridge.
 *
 * @deprecated since Moodle 4.4
 * @param  string   $url     The URL to the cartridge
 * @param  stdClass $type    The tool type object to be filled in
 * @throws moodle_exception if the cartridge could not be loaded correctly
 * @since Moodle 3.1
 */
function lti_load_type_from_cartridge($url, $type) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::load_type_from_cartridge() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\types_helper::load_type_from_cartridge($url, $type);
}

/**
 * Allows you to load in the configuration for an external tool from an IMS cartridge.
 *
 * @param  string   $url    The URL to the cartridge
 * @param  stdClass $lti    LTI object
 * @throws moodle_exception if the cartridge could not be loaded correctly
 * @since Moodle 3.1
 */
function lti_load_tool_from_cartridge($url, $lti) {
    $toolinfo = lti_load_cartridge($url,
        array(
            "title" => "name",
            "launch_url" => "toolurl",
            "secure_launch_url" => "securetoolurl",
            "description" => "intro",
            "icon" => "icon",
            "secure_icon" => "secureicon"
        ),
        array(
            "icon_url" => "extension_icon",
            "secure_icon_url" => "extension_secureicon"
        )
    );
    // If an activity name exists, unset the cartridge name so we don't override it.
    if (isset($lti->name)) {
        unset($toolinfo['name']);
    }

    // Always prefer cartridge core icons first, then, if none are found, look at the extension icons.
    if (empty($toolinfo['icon']) && !empty($toolinfo['extension_icon'])) {
        $toolinfo['icon'] = $toolinfo['extension_icon'];
    }
    unset($toolinfo['extension_icon']);

    if (empty($toolinfo['secureicon']) && !empty($toolinfo['extension_secureicon'])) {
        $toolinfo['secureicon'] = $toolinfo['extension_secureicon'];
    }
    unset($toolinfo['extension_secureicon']);

    foreach ($toolinfo as $property => $value) {
        $lti->$property = $value;
    }
}

/**
 * Search for a tag within an XML DOMDocument
 *
 * @deprecated since Moodle 4.4
 * @param  string $url The url of the cartridge to be loaded
 * @param  array  $map The map of tags to keys in the return array
 * @param  array  $propertiesmap The map of properties to keys in the return array
 * @return array An associative array with the given keys and their values from the cartridge
 * @throws moodle_exception if the cartridge could not be loaded correctly
 * @since Moodle 3.1
 */
function lti_load_cartridge($url, $map, $propertiesmap = array()) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::load_cartridge() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::load_cartridge($url, $map, $propertiesmap);
}

/**
 * Search for a tag within an XML DOMDocument
 *
 * @deprecated since Moodle 4.4
 * @param  stdClass $tagname The name of the tag to search for
 * @param  XPath    $xpath   The XML to find the tag in
 * @param  XPath    $attribute The attribute to search for (if we should search for a child node with the given
 * value for the name attribute
 * @since Moodle 3.1
 */
function get_tag($tagname, $xpath, $attribute = null) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\tool_helper::get_tag() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\tool_helper::get_tag($tagname, $xpath, $attribute);
}

/**
 * Create a new access token.
 *
 * @deprecated since Moodle 4.4
 * @param int $typeid Tool type ID
 * @param string[] $scopes Scopes permitted for new token
 *
 * @return stdClass Access token
 */
function lti_new_access_token($typeid, $scopes) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\types_helper::new_access_token() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\types_helper::new_access_token($typeid, $scopes);
}
