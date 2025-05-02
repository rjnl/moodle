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

global $CFG;
require_once($CFG->dirroot.'/ltix/OAuth.php');
require_once($CFG->libdir.'/weblib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/ltix/TrivialStore.php');

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
 * @deprecated since Moodle 4.4
 * @return object|null
 * @since  Moodle 3.9
 */
function lti_get_instance_type(object $instance): ?object {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_instance_type() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_instance_type($instance);
}

/**
 * Return the launch data required for opening the external tool.
 *
 * @param  stdClass $instance the external tool activity settings
 * @param  string $nonce  the nonce value to use (applies to LTI 1.3 only)
 * @deprecated since Moodle 4.4
 * @return array the endpoint URL and parameters (including the signature)
 * @since  Moodle 3.0
 */
function lti_get_launch_data($instance, $nonce = '', $messagetype = 'basic-lti-launch-request', $foruserid = 0) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_launch_data() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_launch_data($instance, $nonce, $messagetype, $foruserid);
}

/**
 * Launch an external tool activity.
 *
 * @param stdClass $instance the external tool activity settings
 * @param int $foruserid for user param, optional
 * @deprecated since Moodle 4.4
 * @return string The HTML code containing the javascript code for the launch
 */
function lti_launch_tool($instance, $foruserid=0) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::launch_tool() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::launch_tool($instance, $foruserid);
}

/**
 * Prepares an LTI registration request message
 *
 * @deprecated since Moodle 4.4
 * @param object $toolproxy  Tool Proxy instance object
 */
function lti_register($toolproxy) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::register() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::register($toolproxy);
}


/**
 * Gets the parameters for the regirstration request
 *
 * @param object $toolproxy Tool Proxy instance object
* @deprecated since Moodle 4.4
 * @return array Registration request parameters
 */
function lti_build_registration_request($toolproxy) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::build_registration_request() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::build_registration_request($toolproxy);
}


/** get Organization ID using default if no value provided
 *
 * @deprecated since Moodle 4.4
 * @param object $typeconfig
 * @return string
 */
function lti_get_organizationid($typeconfig) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_organizationid() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_organizationid($typeconfig);
}

/**
 * Build source ID
 *
 * @param int $instanceid
 * @param int $userid
 * @param string $servicesalt
 * @param null|int $typeid
 * @param null|int $launchid
 * @deprecated since Moodle 4.4
 * @return stdClass
 */
function lti_build_sourcedid($instanceid, $userid, $servicesalt, $typeid = null, $launchid = null) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::build_sourcedid() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::build_sourcedid($instanceid, $userid, $servicesalt, $typeid, $launchid);
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
 * @deprecated since Moodle 4.4
 * @return array                    Request details
 */
function lti_build_request($instance, $typeconfig, $course, $typeid = null, $islti2 = false,
    $messagetype = 'basic-lti-launch-request', $foruserid = 0) {

    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::build_request() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::build_request($instance, $typeconfig, $course, $typeid, $islti2,
        $messagetype, $foruserid);
}

/**
 * This function builds the request that must be sent to an LTI 2 tool provider
 *
 * @param object    $tool           Basic LTI tool object
 * @param array     $params         Custom launch parameters
 *
 * @deprecated since Moodle 4.4
 * @return array                    Request details
 */
function lti_build_request_lti2($tool, $params) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::build_request_lti2() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::build_request_lti2($tool, $params);
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
        $ltiversion = \core_ltix\constants::LTI_VERSION_1;
    } else {
        $ltiversion = \core_ltix\constants::LTI_VERSION_2;
    }
    return \core_ltix\helper::build_standard_message($instance, $orgid, $ltiversion, $messagetype);
}

/**
 * This function builds the standard parameters for an LTI message that must be sent to the tool producer
 *
 * @param stdClass  $instance       Basic LTI instance object
 * @param string    $orgid          Organisation ID
 * @param boolean   $ltiversion     LTI version to be used for tool messages
 * @param string    $messagetype    The request message type. Defaults to basic-lti-launch-request if empty.
 *
 * @deprecated since Moodle 4.4
 * @return array                    Message parameters
 */
function lti_build_standard_message($instance, $orgid, $ltiversion, $messagetype = 'basic-lti-launch-request') {
     debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::build_standard_message() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::build_standard_message($instance, $orgid, $ltiversion, $messagetype);
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
 * @deprecated since Moodle 4.4
 * @return array                    Custom parameters
 */
function lti_build_custom_parameters($toolproxy, $tool, $instance, $params, $customstr, $instructorcustomstr, $islti2) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::build_custom_parameters() instead.',
    DEBUG_DEVELOPER);

    return \core_ltix\helper::build_custom_parameters($toolproxy, $tool, $instance, $params, $customstr,
        $instructorcustomstr, $islti2);

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
 * @param string $launchid The unique launchid identifier that is stored as a session variable.
 * @deprecated since Moodle 4.4
 * @return stdClass The object containing the signed request parameters and the URL to the TP's Content-Item selection interface.
 * @throws moodle_exception When the LTI tool type does not exist.`
 * @throws coding_exception For invalid media type and presentation target parameters.
 */
function lti_build_content_item_selection_request($id, $course, moodle_url $returnurl, $title = '', $text = '', $mediatypes = [],
        $presentationtargets = [], $autocreate = false, $multiple = true, $unsigned = false, $canconfirm = false,
        $copyadvice = false, $nonce = '', $launchid = '') {

    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::build_content_item_selection_request() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::build_content_item_selection_request($id, $course, $returnurl, $launchid, $title, $text,
        $mediatypes, $presentationtargets, $autocreate, $multiple, $unsigned, $canconfirm, $copyadvice, $nonce);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::verify_oauth_signature() instead.',
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
 * @deprecated since Moodle 4.4
 * @return string
 */
function params_to_string(object $params) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::params_to_string() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::params_to_string($params);
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
    global $OUTPUT;

    debugging(__FUNCTION__ . '() is deprecated with no direct replacement. This logic has been moved to the
        custom formatter \mod_lti\lti\placement\contentitemformatter\form\content_item_to_form_formatter.',
        DEBUG_DEVELOPER);

    $config = new stdClass();
    $config->name = '';
    if (isset($item->title)) {
        $config->name = $item->title;
    }
    if (empty($config->name)) {
        $config->name = $tool->name;
    }
    if (isset($item->text)) {
        $config->introeditor = [
            'text' => $item->text,
            'format' => FORMAT_PLAIN
        ];
    } else {
        $config->introeditor = [
            'text' => '',
            'format' => FORMAT_PLAIN
        ];
    }
    if (isset($item->icon->{'@id'})) {
        $iconurl = new moodle_url($item->icon->{'@id'});
        // Assign item's icon URL to secureicon or icon depending on its scheme.
        if (strtolower($iconurl->get_scheme()) === 'https') {
            $config->secureicon = $iconurl->out(false);
        } else {
            $config->icon = $iconurl->out(false);
        }
    }
    if (isset($item->url)) {
        $url = new moodle_url($item->url);
        $config->toolurl = $url->out(false);
        $config->typeid = 0;
    } else {
        $config->typeid = $tool->id;
    }
    $config->instructorchoiceacceptgrades = \core_ltix\constants::LTI_SETTING_NEVER;
    $islti2 = $tool->ltiversion === \core_ltix\constants::LTI_VERSION_2;
    if (!$islti2 && isset($typeconfig->lti_acceptgrades)) {
        $acceptgrades = $typeconfig->lti_acceptgrades;
        if ($acceptgrades == \core_ltix\constants::LTI_SETTING_ALWAYS) {
            // We create a line item regardless if the definition contains one or not.
            $config->instructorchoiceacceptgrades = \core_ltix\constants::LTI_SETTING_ALWAYS;
            $config->grade_modgrade_point = 100;
        }
        if ($acceptgrades == \core_ltix\constants::LTI_SETTING_DELEGATE || $acceptgrades == \core_ltix\constants::LTI_SETTING_ALWAYS) {
            if (isset($item->lineItem)) {
                $lineitem = $item->lineItem;
                $config->instructorchoiceacceptgrades = \core_ltix\constants::LTI_SETTING_ALWAYS;
                $maxscore = 100;
                if (isset($lineitem->scoreConstraints)) {
                    $sc = $lineitem->scoreConstraints;
                    if (isset($sc->totalMaximum)) {
                        $maxscore = $sc->totalMaximum;
                    } else if (isset($sc->normalMaximum)) {
                        $maxscore = $sc->normalMaximum;
                    }
                }
                $config->grade_modgrade_point = $maxscore;
                $config->lineitemresourceid = '';
                $config->lineitemtag = '';
                $config->lineitemsubreviewurl = '';
                $config->lineitemsubreviewparams = '';
                if (isset($lineitem->assignedActivity) && isset($lineitem->assignedActivity->activityId)) {
                    $config->lineitemresourceid = $lineitem->assignedActivity->activityId?:'';
                }
                if (isset($lineitem->tag)) {
                    $config->lineitemtag = $lineitem->tag?:'';
                }
                if (isset($lineitem->submissionReview)) {
                    $subreview = $lineitem->submissionReview;
                    $config->lineitemsubreviewurl = 'DEFAULT';
                    if (!empty($subreview->url)) {
                        $config->lineitemsubreviewurl = $subreview->url;
                    }
                    if (isset($subreview->custom)) {
                        $config->lineitemsubreviewparams = params_to_string($subreview->custom);
                    }
                }
            }
        }
    }
    $config->instructorchoicesendname = \core_ltix\constants::LTI_SETTING_NEVER;
    $config->instructorchoicesendemailaddr = \core_ltix\constants::LTI_SETTING_NEVER;

    // Since 4.3, the launch container is dictated by the value set in tool configuration and isn't controllable by content items.
    $config->launchcontainer = \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT;

    if (isset($item->custom)) {
        $config->instructorcustomparameters = params_to_string($item->custom);
    }

    // Pass an indicator to the relevant form field.
    $config->selectcontentindicator = $OUTPUT->pix_icon('i/valid', get_string('yes')) . get_string('contentselected', 'mod_lti');

    return $config;
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
    debugging(__FUNCTION__ . '() is deprecated. Please use ' .
        '\mod_lti\lti\placement\contentitemformatter\form\content_item_to_form_formatter instead.', DEBUG_DEVELOPER);

    $tool = \core_ltix\helper::get_type($typeid);
    // Validate parameters.
    if (!$tool) {
        throw new moodle_exception('errortooltypenotfound', 'core_ltix');
    }
    // Check lti_message_type. Show debugging if it's not set to ContentItemSelection.
    // No need to throw exceptions for now since lti_message_type does not seem to be used in this processing at the moment.
    if ($messagetype !== 'ContentItemSelection') {
        debugging("lti_message_type is invalid: {$messagetype}. It should be set to 'ContentItemSelection'.",
            DEBUG_DEVELOPER);
    }
    // Check LTI versions from our side and the response's side. Show debugging if they don't match.
    // No need to throw exceptions for now since LTI version does not seem to be used in this processing at the moment.
    $expectedversion = $tool->ltiversion;
    if ($ltiversion !== $expectedversion) {
        debugging("lti_version from response does not match the tool's configuration. Tool: {$expectedversion}," .
            " Response: {$ltiversion}", DEBUG_DEVELOPER);
    }

    $items = json_decode($contentitemsjson);
    if (empty($items)) {
        throw new moodle_exception('errorinvaliddata', 'core_ltix', '', $contentitemsjson);
    }
    if (!isset($items->{'@graph'}) || !is_array($items->{'@graph'})) {
        throw new moodle_exception('errorinvalidresponseformat', 'core_ltix');
    }

    $config = null;
    $items = $items->{'@graph'};
    if (!empty($items)) {
        $contentitemformatter = new \mod_lti\lti\placement\contentitemformatter\form\content_item_to_form_formatter();
        $config = $contentitemformatter->format($items, $tool);
    }

    return $config;
}

/**
 * Converts the new Deep-Linking format for Content-Items to the old format.
 *
 * @deprecated since Moodle 4.4
 * @param string $param JSON string representing new Deep-Linking format
 * @return string  JSON representation of content-items
 */
function lti_convert_content_items($param) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::convert_content_items() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::convert_content_items($param);
}

/**
 * Get tool table function.
 *
 * @deprecated since Moodle 4.4
 * @return void
 */
function lti_get_tool_table($tools, $id) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_table() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_table($tools, $id);
}

/**
 * This function builds the tab for a category of tool proxies
 *
 * @param object    $toolproxies    Tool proxy instance objects
 * @param string    $id             Category ID
 * @deprecated since Moodle 4.4
 * @return string                   HTML for tab
 */
function lti_get_tool_proxy_table($toolproxies, $id) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_proxy_table() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_proxy_table($toolproxies, $id);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_enabled_capabilities() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_enabled_capabilities($tool);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::split_parameters() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::split_parameters($customstr);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::split_custom_parameters() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::split_custom_parameters($toolproxy, $tool, $params, $customstr, $islti2);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_custom_parameters() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_custom_parameters($toolproxy, $tool, $params, $parameters);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::parse_custom_parameter() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::parse_custom_parameter($toolproxy, $tool, $params, $value, $islti2);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::calculate_custom_parameter() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::calculate_custom_parameter($value);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_course_history() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_course_history($course);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::map_keyname() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::map_keyname($key, $tolower);
}

/**
 * Gets the IMS role string for the specified user and LTI course module.
 *
 * @deprecated since Moodle 4.4
 * @param mixed    $user      User object or user id
 * @param int      $cmid      The course module id of the LTI activity
 * @param int      $courseid  The course id of the LTI activity
 * @param boolean  $islti2    True if an LTI 2 tool is being launched
 *
 * @return string A role string suitable for passing with an LTI launch
 */
function lti_get_ims_role($user, $cmid, $courseid, $islti2) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_ims_role() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_ims_role($user, $cmid, $courseid, $islti2);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_type_config() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_type_config($typeid);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tools_by_url() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tools_by_url($url, $state, $courseid);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tools_by_domain() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tools_by_domain($domain, $state, $courseid);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::filter_get_types() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::filter_get_types($course);
}

/**
 * Given an array of tools, filter them based on their state
 *
 * @deprecated since Moodle 4.4
 * @param array $tools An array of lti_types records
 * @param int $state One of the \core_ltix\constants::LTI_TOOL_STATE_* constants
 * @return array
 */
function lti_filter_tool_types(array $tools, $state) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::filter_tool_types() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::filter_tool_types($tools, $state);
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

    $context = \core\context\course::instance($courseid);

    if (!has_capability('mod/lti:addpreconfiguredinstance', $context, $USER->id)) {
        return [];
    }

    $preconfiguredtypes = \core_ltix\helper::get_tools_with_enabled_placement_in_course('mod_lti:activityplacement', $courseid);

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

        $iconurl = \core_ltix\helper::get_tool_type_icon_url($ltitype);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_domain_from_url() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_domain_from_url($url);
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
function lti_get_tool_by_url_match($url, $courseid = null, $state = \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_by_url_match() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_by_url_match($url, $courseid, $state);
}

/**
 * Get URL by thumbprint
 *
 * @deprecated since Moodle 4.4
 * @param $url
 * @return string
 */
function lti_get_url_thumbprint($url) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_url_thumbprint() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_url_thumbprint($url);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_best_tool_by_url() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_best_tool_by_url($url, $tools, $courseid);
}

/**
 * Get shared secrets by key
 *
 * @deprecated since Moodle 4.4
 * @param string $key
 * @return void
 */
function lti_get_shared_secrets_by_key($key) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_shared_secrets_by_key() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_shared_secrets_by_key($key);
}

/**
 * Delete a Basic LTI configuration
 *
 * @deprecated since Moodle 4.4
 * @param int $id   Configuration id
 */
function lti_delete_type($id) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::delete_type() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\helper::delete_type($id);
}

/**
 * Set type state
 *
 * @deprecated since Moodle 4.4
 * @param $id
 * @param $state
 */
function lti_set_state_for_type($id, $state) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::set_state_for_type() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\helper::set_state_for_type($id, $state);
}

/**
 * Transforms a basic LTI object to an array
 *
 * @deprecated since Moodle 4.4
 * @param object $ltiobject    Basic LTI object
 *
 * @return array Basic LTI configuration details
 */
function lti_get_config($ltiobject) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_config() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_config($ltiobject);
}

/**
 *
 * Generates some of the tool configuration based on the instance details
 *
 * @deprecated since Moodle 4.4
 * @param int $id
 *
 * @return object configuration
 *
 */
function lti_get_type_config_from_instance($id) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_type_config_from_instance() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_type_config_from_instance($id);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_type_type_config() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_type_type_config($id);
}

/**
 * Prepare type config for save
 *
 * @deprecated since Moodle 4.4
 * @param $type
 * @param $config
 */
function lti_prepare_type_for_save($type, $config) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::prepare_type_for_save() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\helper::prepare_type_for_save($type, $config);
}

/**
 * Update type
 *
 * @deprecated since Moodle 4.4
 * @param $type
 * @param $config
 */
function lti_update_type($type, $config) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::update_type() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\helper::update_type($type, $config);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::type_add_categories() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\helper::type_add_categories($typeid, $lticoursecategories);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::add_type() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::add_type($type, $config);
}

/**
 * Given an array of tool proxies, filter them based on their state
 *
 * @deprecated since Moodle 4.4
 * @param array $toolproxies An array of lti_tool_proxies records
 * @param int $state One of the \core_ltix\constants::LTI_TOOL_PROXY_STATE_* constants
 *
 * @return array
 */
function lti_filter_tool_proxy_types(array $toolproxies, $state) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::filter_tool_proxy_types() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::filter_tool_proxy_types($toolproxies, $state);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_proxy_from_guid() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_proxy_from_guid($toolproxyguid);
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
    debugging(__FUNCTION__ . '() is deprecated. ' .
        'Please use \core_ltix\helper::get_tool_proxies_from_registration_url() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_proxies_from_registration_url($regurl);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_proxy() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_proxy($id);
}

/**
 * Returns lti tool proxies.
 *
 * @deprecated since Moodle 4.4
 * @param bool $orphanedonly Only retrieves tool proxies that have no type associated with them
 * @return array of basicLTI types
 */
function lti_get_tool_proxies($orphanedonly) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_proxies() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_proxies($orphanedonly);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_proxy_config() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_proxy_config($id);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::add_tool_proxy() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::add_tool_proxy($config);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::update_tool_proxy() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::update_tool_proxy($toolproxy);
}

/**
 * Delete a Tool Proxy
 *
 * @deprecated since Moodle 4.4
 * @param int $id   Tool Proxy id
 */
function lti_delete_tool_proxy($id) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::delete_tool_proxy() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\helper::delete_tool_proxy($id);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_lti_types_and_proxies() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_lti_types_and_proxies($limit, $offset, $orphanedonly, $toolproxyid);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_lti_types_and_proxies_count() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_lti_types_and_proxies_count($orphanedonly, $toolproxyid);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::add_config() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::add_config($config);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::update_config() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::update_config($config);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_settings() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_settings($toolproxyid, $courseid, $instanceid);
}

/**
 * Sets the tool settings (
 *
 * @deprecated since Moodle 4.4
 *  @param array  $settings      Array of settings
 * @param int    $toolproxyid   Id of tool proxy record (or tool ID if negative)
 * @param int    $courseid      Id of course (null if system settings)
 * @param int    $instanceid    Id of course module (null if system or context settings)
 */
function lti_set_tool_settings($settings, $toolproxyid, $courseid = null, $instanceid = null) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::set_tool_settings() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\helper::set_tool_settings($settings, $toolproxyid, $courseid, $instanceid);
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
 * @deprecated since Moodle 4.4
 * @param array $newparms   Signed parameters
 * @param string $endpoint  URL of the external tool
 * @param bool $debug       Debug (true/false)
 * @return string
 */
function lti_post_launch_html($newparms, $endpoint, $debug=false) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::post_launch_html() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\helper::post_launch_html($newparms, $endpoint, $debug);
}

/**
 * Generate the form for initiating a login request for an LTI 1.3 message
 *
 * @deprecated since Moodle 4.4
 * @param int            $courseid  Course ID
 * @param int            $cmid        LTI instance ID
 * @param stdClass|null  $instance  LTI instance
 * @param stdClass       $config    Tool type configuration
 * @param string         $messagetype   LTI message type
 * @param string         $title     Title of content item
 * @param string         $text      Description of content item
 * @param int            $foruserid Id of the user targeted by the launch
 * @param string         $placementtype The placement type string.
 * @return string
 */
function lti_initiate_login($courseid, $cmid, $instance, $config, $messagetype = 'basic-lti-launch-request',
        $title = '', $text = '', $foruserid = 0, $placementtype = '') {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::initiate_login() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::initiate_login($courseid, $cmid, $instance, $config, $messagetype, $placementtype, $title,
        $text, $foruserid);
}

/**
 * Prepares an LTI 1.3 login request
 *
 * @deprecated since Moodle 4.4
 * @param int            $courseid  Course ID
 * @param int            $cmid        Course Module instance ID
 * @param stdClass|null  $instance  LTI instance
 * @param stdClass       $config    Tool type configuration
 * @param string         $messagetype   LTI message type
 * @param int            $foruserid Id of the user targeted by the launch
 * @param string         $title     Title of content item
 * @param string         $text      Description of content item
 * @param string         $placementtype The placement type string

 * @return array Login request parameters
 */
function lti_build_login_request($courseid, $cmid, $instance, $config, $messagetype, $foruserid = 0, $title = '', $text = '',
        $placementtype = '') {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::build_login_request() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::build_login_request($courseid, $cmid, $instance, $config, $messagetype, $placementtype,
        $foruserid, $title, $text);
}

/**
 * Get type record by id
 *
 * @deprecated since Moodle 4.4
 * @param $typeid
 * @return false|mixed|stdClass
 */
function lti_get_type($typeid) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_type() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_type($typeid);
}

/**
 * @deprecated since Moodle 4.4
 * @return int
 */
function lti_get_launch_container($lti, $toolconfig) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_launch_container() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_launch_container($lti, $toolconfig);
}

/**
 * @deprecated since Moodle 4.4
 * @return bool
 */
function lti_request_is_using_ssl() {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::request_is_using_ssl() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::request_is_using_ssl();
}

/**
 * Ensure URL is https
 *
 * @deprecated since Moodle 4.4
 * @param $url
 * @return mixed|string
 */
function lti_ensure_url_is_https($url) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::ensure_url_is_https() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::ensure_url_is_https($url);
}

/**
 * Determines if we should try to log the request
 *
 * @deprecated since Moodle 4.4
 * @param string $rawbody
 * @return bool
 */
function lti_should_log_request($rawbody) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::should_log_request() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::should_log_request($rawbody);
}

/**
 * Logs the request to a file in temp dir.
 *
 * @deprecated since Moodle 4.4
 * @param string $rawbody
 */
function lti_log_request($rawbody) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::log_request() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::log_request($rawbody);
}

/**
 * Log an LTI response.
 *
 * @deprecated since Moodle 4.4
 * @param string $responsexml The response XML
 * @param Exception $e If there was an exception, pass that too
 */
function lti_log_response($responsexml, $e = null) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::log_response() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::log_response($responsexml, $e);
}

/**
 * Fetches LTI type configuration for an LTI instance
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $instance
 * @return array Can be empty if no type is found
 */
function lti_get_type_config_by_instance($instance) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_type_config_by_instance() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_type_config_by_instance($instance);
}

/**
 * Enforce type config settings onto the LTI instance
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $instance
 * @param array $typeconfig
 */
function lti_force_type_config_settings($instance, array $typeconfig) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::force_type_config_settings() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::force_type_config_settings($instance, $typeconfig);
}

/**
 * Initializes an array with the capabilities supported by the LTI module
 *
 * @deprecated since Moodle 4.4
 * @return array List of capability names (without a dollar sign prefix)
 */
function lti_get_capabilities() {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_capabilities() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_capabilities();
}

/**
 * Initializes an array with the services supported by the LTI module
 *
 * @deprecated since Moodle 4.4
 * @return array List of services
 */
function lti_get_services() {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_services() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_services();
}

/**
 * Initializes an instance of the named service
 *
 * @deprecated since Moodle 4.4
 * @param string $servicename Name of service
 *
 * @return bool|\core_ltix\local\ltiservice\service_base Service
 */
function lti_get_service_by_name($servicename) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\local\ltiservice\service_helper::get_service_by_name() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\local\ltiservice\service_helper::get_service_by_name($servicename);
}

/**
 * Finds a service by id
 *
 * @deprecated since Moodle 4.4
 * @param \core_ltix\local\ltiservice\service_base[] $services Array of services
 * @param string $resourceid  ID of resource
 *
 * @return core_ltix\local\ltiservice\service_base Service
 */
function lti_get_service_by_resource_id($services, $resourceid) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_service_by_resource_id() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_service_by_resource_id($services, $resourceid);
}

/**
 * Initializes an array with the scopes for services supported by the LTI module
 * and authorized for this particular tool instance.
 *
 * @deprecated since Moodle 4.4
 * @param object $type  LTI tool type
 * @param array  $typeconfig  LTI tool type configuration
 *
 * @return array List of scopes
 */
function lti_get_permitted_service_scopes($type, $typeconfig) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_permitted_service_scopes() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_permitted_service_scopes($type, $typeconfig);
}

/**
 * Extracts the named contexts from a tool proxy
 *
 * @deprecated since Moodle 4.4
 * @param object $json
 *
 * @return array Contexts
 */
function lti_get_contexts($json) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_contexts() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_contexts($json);
}

/**
 * Converts an ID to a fully-qualified ID
 *
 * @deprecated since Moodle 4.4
 * @param array $contexts
 * @param string $id
 *
 * @return string Fully-qualified ID
 */
function lti_get_fqid($contexts, $id) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_fqid() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_fqid($contexts, $id);
}

/**
 * Returns the icon for the given tool type
 *
 * @param stdClass $type The tool type
 *
 * @return string The url to the tool type's corresponding icon
 */
function get_tool_type_icon_url(stdClass $type) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_type_icon_url() instead.',
        DEBUG_DEVELOPER);
    
    return \core_ltix\helper::get_tool_type_icon_url($type);
}

/**
 * Returns the edit url for the given tool type
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $type The tool type
 *
 * @return string The url to edit the tool type
 */
function get_tool_type_edit_url(stdClass $type) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_type_edit_url() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_type_edit_url($type);
}

/**
 * Returns the edit url for the given tool proxy.
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $proxy The tool proxy
 *
 * @return string The url to edit the tool type
 */
function get_tool_proxy_edit_url(stdClass $proxy) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_proxy_edit_url() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_proxy_edit_url($proxy);
}

/**
 * Returns the course url for the given tool type
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $type The tool type
 *
 * @return string The url to the course of the tool type, void if it is a site wide type
 */
function get_tool_type_course_url(stdClass $type) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_type_course_url() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_type_course_url($type);
}

/**
 * Returns the icon and edit urls for the tool type and the course url if it is a course type.
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $type The tool type
 *
 * @return array The urls of the tool type
 */
function get_tool_type_urls(stdClass $type) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_type_urls() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_type_urls($type);
}

/**
 * Returns the icon and edit urls for the tool proxy.
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $proxy The tool proxy
 *
 * @return array The urls of the tool proxy
 */
function get_tool_proxy_urls(stdClass $proxy) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_proxy_urls() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_proxy_urls($proxy);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_type_state_info() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_type_state_info($type);
}

/**
 * Returns information on the configuration of the tool type
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $type The tool type
 *
 * @return array An array with configuration details
 */
function get_tool_type_config($type) {
    debugging(__FUNCTION__ . '() is deprecated, please do not use it any more', DEBUG_DEVELOPER);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_type_capability_groups() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_type_capability_groups($type);
}


/**
 * Returns the ids of each instance of this tool type
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $type The tool type
 *
 * @return array An array of ids of the instances of this tool type
 */
function get_tool_type_instance_ids($type) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tool_type_instance_ids() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tool_type_instance_ids($type);
}

/**
 * Serialises this tool type
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $type The tool type
 *
 * @return array An array of values representing this type
 */
function serialise_tool_type(stdClass $type) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::serialise_tool_type() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::serialise_tool_type($type);
}

/**
 * Loads the cartridge information into the tool type, if the launch url is for a cartridge file
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $type The tool type object to be filled in
 * @since Moodle 3.1
 */
function lti_load_type_if_cartridge($type) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::load_type_if_cartridge() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\helper::load_type_if_cartridge($type);
}

/**
 * Loads the cartridge information into the new tool, if the launch url is for a cartridge file
 *
 * @deprecated since Moodle 4.4
 * @param stdClass $lti The tools config
 * @since Moodle 3.1
 */
function lti_load_tool_if_cartridge($lti) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::load_tool_if_cartridge() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::load_tool_if_cartridge($lti);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::is_cartridge() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::is_cartridge($url);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::load_type_from_cartridge() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\helper::load_type_from_cartridge($url, $type);
}

/**
 * Allows you to load in the configuration for an external tool from an IMS cartridge.
 *
 * @deprecated since Moodle 4.4
 * @param  string   $url    The URL to the cartridge
 * @param  stdClass $lti    LTI object
 * @throws moodle_exception if the cartridge could not be loaded correctly
 * @since Moodle 3.1
 */
function lti_load_tool_from_cartridge($url, $lti) {
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::load_tool_from_cartridge() instead.',
        DEBUG_DEVELOPER);

    \core_ltix\helper::load_tool_from_cartridge($url, $lti);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::load_cartridge() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::load_cartridge($url, $map, $propertiesmap);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::get_tag() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::get_tag($tagname, $xpath, $attribute);
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
    debugging(__FUNCTION__ . '() is deprecated. Please use \core_ltix\helper::new_access_token() instead.',
        DEBUG_DEVELOPER);

    return \core_ltix\helper::new_access_token($typeid, $scopes);
}


/**
 * Wrapper for function libxml_disable_entity_loader() deprecated in PHP 8
 *
 * Method was deprecated in PHP 8 and it shows deprecation message. However it is still
 * required in the previous versions on PHP. While Moodle supports both PHP 7 and 8 we need to keep it.
 * @see https://php.watch/versions/8.0/libxml_disable_entity_loader-deprecation
 *
 * @param bool $value
 * @return bool
 *
 * @deprecated since Moodle 4.3
 */
function lti_libxml_disable_entity_loader(bool $value): bool {
    debugging(__FUNCTION__ . '() is deprecated, please do not use it any more', DEBUG_DEVELOPER);
    return true;
}
