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

namespace core_ltix\local\ltiservice;

use stdclass;
use SimpleXMLElement;
use Exception;
use coding_exception;
use core_ltix\constants;
use core_ltix\helper;
use core_ltix\oauth_helper;
use core_ltix\local\lticore\models\resource_link;

/**
 * This class exposes functions for LTI 1.3 Service Plugin Management.
 *
 * @package    core_ltix
 * @copyright  2023 Ismael Texidor-Rodriguez (Turnitin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_helper {
    public static function get_contexts($json) {

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

    public static function get_response_xml($codemajor, $description, $messageref, $messagetype) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><imsx_POXEnvelopeResponse />');
        $xml->addAttribute('xmlns', 'http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0');

        $headerinfo = $xml->addChild('imsx_POXHeader')->addChild('imsx_POXResponseHeaderInfo');

        $headerinfo->addChild('imsx_version', 'V1.0');
        $headerinfo->addChild('imsx_messageIdentifier', (string)mt_rand());

        $statusinfo = $headerinfo->addChild('imsx_statusInfo');
        $statusinfo->addchild('imsx_codeMajor', $codemajor);
        $statusinfo->addChild('imsx_severity', 'status');
        $statusinfo->addChild('imsx_description', $description);
        $statusinfo->addChild('imsx_messageRefIdentifier', $messageref);
        $incomingtype = str_replace('Response', 'Request', $messagetype);
        $statusinfo->addChild('imsx_operationRefIdentifier', $incomingtype);

        $xml->addChild('imsx_POXBody')->addChild($messagetype);

        return $xml;
    }

    public static function parse_message_id($xml) {
        if (empty($xml->imsx_POXHeader)) {
            return '';
        }

        $node = $xml->imsx_POXHeader->imsx_POXRequestHeaderInfo->imsx_messageIdentifier;
        $messageid = (string)$node;

        return $messageid;
    }

    public static function parse_grade_replace_message($xml) {
        $node = $xml->imsx_POXBody->replaceResultRequest->resultRecord->sourcedGUID->sourcedId;
        $resultjson = json_decode((string)$node);
        if ( is_null($resultjson) ) {
            throw new Exception('Invalid sourcedId in result message');
        }
        $node = $xml->imsx_POXBody->replaceResultRequest->resultRecord->result->resultScore->textString;

        $score = (string) $node;
        if ( ! is_numeric($score) ) {
            throw new Exception('Score must be numeric');
        }
        $grade = floatval($score);
        if ( $grade < 0.0 || $grade > 1.0 ) {
            throw new Exception('Score not between 0.0 and 1.0');
        }

        $parsed = new stdClass();
        $parsed->gradeval = $grade;

        $parsed->instanceid = $resultjson->data->instanceid;
        $parsed->userid = $resultjson->data->userid;
        $parsed->launchid = $resultjson->data->launchid;
        $parsed->typeid = $resultjson->data->typeid;
        $parsed->sourcedidhash = $resultjson->hash;

        $parsed->messageid = self::parse_message_id($xml);

        return $parsed;
    }

    public static function parse_grade_read_message($xml) {
        $node = $xml->imsx_POXBody->readResultRequest->resultRecord->sourcedGUID->sourcedId;
        $resultjson = json_decode((string)$node);
        if ( is_null($resultjson) ) {
            throw new Exception('Invalid sourcedId in result message');
        }

        $parsed = new stdClass();
        $parsed->instanceid = $resultjson->data->instanceid;
        $parsed->userid = $resultjson->data->userid;
        $parsed->launchid = $resultjson->data->launchid;
        $parsed->typeid = $resultjson->data->typeid;
        $parsed->sourcedidhash = $resultjson->hash;

        $parsed->messageid = self::parse_message_id($xml);

        return $parsed;
    }

    public static function parse_grade_delete_message($xml) {
        $node = $xml->imsx_POXBody->deleteResultRequest->resultRecord->sourcedGUID->sourcedId;
        $resultjson = json_decode((string)$node);
        if ( is_null($resultjson) ) {
            throw new Exception('Invalid sourcedId in result message');
        }

        $parsed = new stdClass();
        $parsed->instanceid = $resultjson->data->instanceid;
        $parsed->userid = $resultjson->data->userid;
        $parsed->launchid = $resultjson->data->launchid;
        $parsed->typeid = $resultjson->data->typeid;
        $parsed->sourcedidhash = $resultjson->hash;

        $parsed->messageid = self::parse_message_id($xml);

        return $parsed;
    }

    /**
     * Check whether the tool accept grades.
     *
     * @param resource_link $resourcelink The resource link instance associated to the tool
     * @return bool Whether the tool accepts grades or not
     */
    public static function accepts_grades(resource_link $resourcelink): bool {
        global $DB;

        $context = \context::instance_by_id($resourcelink->get('contextid'));
        // Verify the context level of the provided resource link.
        // At present, only resource links placed within course modules are gradable, though this may change in the future.
        // If the link is not in a module context, do not proceed with updating the grade.
        if ($context->contextlevel !== CONTEXT_MODULE) {
            throw new Exception('The provided resource link is not associated to a course module.');
        }

        $ltitype = $DB->get_record('lti_types', ['id' => $resourcelink->get('typeid')]);

        if (empty($ltitype->toolproxyid)) {
            $ltitypeid = $ltitype ? $ltitype->id : 0;
            $typeconfig = helper::get_type_config($ltitypeid);

            $typeacceptgrades = $typeconfig['acceptgrades'] ?? \core_ltix\constants::LTI_SETTING_DELEGATE;

            $acceptsgrades = $resourcelink->get('gradable') && ($typeacceptgrades == constants::LTI_SETTING_ALWAYS ||
                $typeacceptgrades == constants::LTI_SETTING_DELEGATE);
        } else {
            $enabledcapabilities = explode("\n", $ltitype->enabledcapability);
            $acceptsgrades = in_array('Result.autocreate', $enabledcapabilities) ||
                in_array('BasicOutcome.url', $enabledcapabilities);
        }

        return $acceptsgrades;
    }

    /**
     * Set the passed user ID to the session user.
     *
     * @param int $userid
     */
    public static function set_session_user($userid) {
        global $DB;

        if ($user = $DB->get_record('user', array('id' => $userid))) {
            \core\session\manager::set_user($user);
        }
    }

    /**
     * Update grade.
     *
     * @param resource_link $resourcelink The resource link instance
     * @param int $userid The user ID
     * @param string $launchid The unique launchid identifier that is stored as a session variable
     * @param float $gradeval The grade value
     * @return bool Whether the grade was successfully updated or not
     */
    public static function update_grade(resource_link $resourcelink, int $userid, string $launchid, float $gradeval): bool {
        global $CFG, $DB;
        require_once($CFG->libdir . '/gradelib.php');

        $context = \context::instance_by_id($resourcelink->get('contextid'));
        // Obtain the course module.
        $cm = get_fast_modinfo($context->get_course_context()->instanceid)->get_cm($context->instanceid);

        // Obtain the module instance.
        $moduleinstance = $DB->get_record($cm->modname, ['id' => $cm->instance]);

        $params = [
            'itemname' => $moduleinstance->name
        ];

        $gradeval = $gradeval * floatval($moduleinstance->grade);

        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = $gradeval;

        $status = grade_update(
            \core_ltix\constants::LTI_SOURCE,
            $moduleinstance->course,
            \core_ltix\constants::LTI_ITEM_TYPE,
            \core_ltix\constants::LTI_ITEM_MODULE,
            $moduleinstance->id,
            0,
            $grade,
            $params
        );

        $record = $DB->get_record(
            'lti_submission',
            [
                'ltiresourcelinkid' => $resourcelink->get('id'),
                'userid' => $userid,
                'launchid' => $launchid
            ],
            'id'
        );

        if ($record) {
            $id = $record->id;
        } else {
            $id = null;
        }

        if (!empty($id)) {
            $DB->update_record(
                'lti_submission',
                [
                    'id' => $id,
                    'dateupdated' => time(),
                    'gradepercent' => $gradeval,
                    'state' => 2
                ]
            );
        } else {
            $DB->insert_record(
                'lti_submission',
                [
                    'ltiresourcelinkid' => $resourcelink->get('id'),
                    'userid' => $userid,
                    'datesubmitted' => time(),
                    'dateupdated' => time(),
                    'gradepercent' => $gradeval,
                    'originalgrade' => $gradeval,
                    'launchid' => $launchid,
                    'state' => 1
                ]
            );
        }

        return $status == GRADE_UPDATE_OK;
    }

    /**
     * Read grade.
     *
     * @param resource_link $resourcelink The resource link instance
     * @param int $userid The user ID
     * @return float|null The grade if set, otherwise null
     */
    public static function read_grade(resource_link $resourcelink, int $userid): ?float {
        global $CFG, $DB;
        require_once($CFG->libdir . '/gradelib.php');

        $context = \context::instance_by_id($resourcelink->get('contextid'));
        // Obtain the course module.
        $cm = get_fast_modinfo($context->get_course_context()->instanceid)->get_cm($context->instanceid);

        // Obtain the module instance.
        $moduleinstance = $DB->get_record($cm->modname, ['id' => $cm->instance]);

        $grades = grade_get_grades(
            $moduleinstance->course,
            \core_ltix\constants::LTI_ITEM_TYPE,
            \core_ltix\constants::LTI_ITEM_MODULE,
            $moduleinstance->id,
            $userid
        );

        $ltigrade = floatval($moduleinstance->grade);

        if (!empty($ltigrade) && isset($grades) && isset($grades->items[0]) && is_array($grades->items[0]->grades)) {
            foreach ($grades->items[0]->grades as $agrade) {
                $grade = $agrade->grade;
                if (isset($grade)) {
                    return $grade / $ltigrade;
                }
            }
        }
        return null;
    }

    /**
     * Delete grade.
     *
     * @param resource_link $resourcelink The resource link instance
     * @param int $userid The user ID
     * @return bool Whether the grade was successfully deleted or not
     */
    public static function delete_grade(resource_link $resourcelink, int $userid): bool {
        global $CFG, $DB;
        require_once($CFG->libdir . '/gradelib.php');

        $context = \context::instance_by_id($resourcelink->get('contextid'));
        // Obtain the course module.
        $cm = get_fast_modinfo($context->get_course_context()->instanceid)->get_cm($context->instanceid);

        // Obtain the module instance.
        $moduleinstance = $DB->get_record($cm->modname, ['id' => $cm->instance]);

        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = null;

        $status = grade_update(
            \core_ltix\constants::LTI_SOURCE,
            $moduleinstance->course,
            \core_ltix\constants::LTI_ITEM_TYPE,
            \core_ltix\constants::LTI_ITEM_MODULE,
            $moduleinstance->id,
            0,
            $grade
        );

        return $status == GRADE_UPDATE_OK;
    }

    public static function verify_message($key, $sharedsecrets, $body, $headers = null) {
        foreach ($sharedsecrets as $secret) {
            $signaturefailed = false;

            try {
                // TODO: Switch to core oauthlib once implemented - MDL-30149.
                oauth_helper::handle_oauth_body_post($key, $secret, $body, $headers);
            } catch (Exception $e) {
                debugging('LTI message verification failed: '.$e->getMessage());
                $signaturefailed = true;
            }

            if (!$signaturefailed) {
                return $secret; // Return the secret used to sign the message).
            }
        }

        return false;
    }

    /**
     * Validate source ID from external request
     *
     * @param resource_link $resourcelink The resource link instance
     * @param object $parsed
     * @throws Exception
     */
    public static function verify_sourcedid(resource_link $resourcelink, object $parsed) {
        $sourceid = \core_ltix\helper::build_sourcedid(
            $parsed->instanceid,
            $parsed->userid,
            $resourcelink->get('servicesalt'),
            $parsed->typeid,
            $parsed->launchid
        );

        if ($sourceid->hash != $parsed->sourcedidhash) {
            throw new Exception('SourcedId hash not valid');
        }
    }

    /**
     * Extend the LTI services through the ltixsource plugins
     *
     * @param stdClass $data LTI request data
     * @return bool
     * @throws coding_exception
     */
    public static function extend_lti_services($data) {
        $plugins = get_plugin_list_with_function('ltixsource', $data->messagetype);
        if (!empty($plugins)) {
            // There can only be one.
            if (count($plugins) > 1) {
                throw new coding_exception('More than one ltixsource plugin handler found');
            }
            $data->xml = new SimpleXMLElement($data->body);
            $callback = current($plugins);
            call_user_func($callback, $data);

            return true;
        }
        return false;
    }

    /**
     * Initializes an instance of the named service
     *
     * @param string $servicename Name of service
     *
     * @return bool|\core_ltix\local\ltiservice\service_base Service
     */
    public static function get_service_by_name($servicename) {
        $service = false;
        $classname = "\\ltixservice_{$servicename}\\local\\service\\{$servicename}";
        if (class_exists($classname)) {
            $service = new $classname();
        }

        return $service;

    }
}
