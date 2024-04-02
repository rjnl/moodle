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

namespace core_ltix;

use core_component;
use core_text;
use curl;
use DateTime;
use DateTimeZone;
use DOMDocument;
use DOMXPath;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Helper class specifically dealing with LTI tools.
 *
 * @package    core_ltix
 * @author     Alex Morris <alex.morris@catalyst.net.nz>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_helper {

    public static function get_tools_by_domain($domain, $state = null, $courseid = null) {
        global $DB, $SITE;

        $statefilter = '';
        $coursefilter = '';

        if ($state) {
            $statefilter = 'AND t.state = :state';
        }

        if ($courseid && $courseid != $SITE->id) {
            $coursefilter = 'OR t.course = :courseid';
        }

        $coursecategory = $DB->get_field('course', 'category', ['id' => $courseid]);
        $query = "SELECT t.*
                FROM {lti_types} t
           LEFT JOIN {lti_types_categories} tc on t.id = tc.typeid
               WHERE t.tooldomain = :tooldomain
                 AND (t.course = :siteid $coursefilter)
                 $statefilter
                 AND (tc.id IS NULL OR tc.categoryid = :categoryid)";

        return $DB->get_records_sql($query, [
            'courseid' => $courseid,
            'siteid' => $SITE->id,
            'tooldomain' => $domain,
            'state' => $state,
            'categoryid' => $coursecategory
        ]);
    }

    public static function get_domain_from_url($url) {
        $matches = array();

        if (preg_match(LTI_URL_DOMAIN_REGEX, $url ?? '', $matches)) {
            return $matches[1];
        }
    }

    public static function get_tools_by_url($url, $state, $courseid = null) {
        $domain = self::get_domain_from_url($url);

        return self::get_tools_by_domain($domain, $state, $courseid);
    }

    public static function get_tool_by_url_match($url, $courseid = null, $state = LTI_TOOL_STATE_CONFIGURED) {
        $possibletools = self::get_tools_by_url($url, $state, $courseid);

        return self::get_best_tool_by_url($url, $possibletools, $courseid);
    }

    public static function get_best_tool_by_url($url, $tools, $courseid = null) {
        if (count($tools) === 0) {
            return null;
        }

        $urllower = self::get_url_thumbprint($url);

        foreach ($tools as $tool) {
            $tool->_matchscore = 0;

            $toolbaseurllower = self::get_url_thumbprint($tool->baseurl);

            if ($urllower === $toolbaseurllower) {
                // 100 points for exact thumbprint match.
                $tool->_matchscore += 100;
            } else if (substr($urllower, 0, strlen($toolbaseurllower)) === $toolbaseurllower) {
                // 50 points if tool thumbprint starts with the base URL thumbprint.
                $tool->_matchscore += 50;
            }

            // Prefer course tools over site tools.
            if (!empty($courseid)) {
                // Minus 10 points for not matching the course id (global tools).
                if ($tool->course != $courseid) {
                    $tool->_matchscore -= 10;
                }
            }
        }

        $bestmatch = array_reduce($tools, function($value, $tool) {
            if ($tool->_matchscore > $value->_matchscore) {
                return $tool;
            } else {
                return $value;
            }

        }, (object) array('_matchscore' => -1));

        // None of the tools are suitable for this URL.
        if ($bestmatch->_matchscore <= 0) {
            return null;
        }

        return $bestmatch;
    }

    public static function get_url_thumbprint($url) {
        // Parse URL requires a schema otherwise everything goes into 'path'.  Fixed 5.4.7 or later.
        if (preg_match('/https?:\/\//', $url) !== 1) {
            $url = 'http://' . $url;
        }
        $urlparts = parse_url(strtolower($url));
        if (!isset($urlparts['path'])) {
            $urlparts['path'] = '';
        }

        if (!isset($urlparts['query'])) {
            $urlparts['query'] = '';
        }

        if (!isset($urlparts['host'])) {
            $urlparts['host'] = '';
        }

        if (substr($urlparts['host'], 0, 4) === 'www.') {
            $urlparts['host'] = substr($urlparts['host'], 4);
        }

        $urllower = $urlparts['host'] . '/' . $urlparts['path'];

        if ($urlparts['query'] != '') {
            $urllower .= '?' . $urlparts['query'];
        }

        return $urllower;
    }

    /**
     * Update the database with a tool proxy instance
     *
     * @param object $config Tool proxy definition
     *
     * @return int  Record id number
     */
    public static function add_tool_proxy($config) {
        global $USER, $DB;

        $toolproxy = new \stdClass();
        if (isset($config->lti_registrationname)) {
            $toolproxy->name = trim($config->lti_registrationname);
        }
        if (isset($config->lti_registrationurl)) {
            $toolproxy->regurl = trim($config->lti_registrationurl);
        }
        if (isset($config->lti_capabilities)) {
            $toolproxy->capabilityoffered = implode("\n", $config->lti_capabilities);
        } else {
            $toolproxy->capabilityoffered = implode("\n", array_keys(self::get_capabilities()));
        }
        if (isset($config->lti_services)) {
            $toolproxy->serviceoffered = implode("\n", $config->lti_services);
        } else {
            $func = function($s) {
                return $s->get_id();
            };
            $servicenames = array_map($func, self::get_services());
            $toolproxy->serviceoffered = implode("\n", $servicenames);
        }
        if (isset($config->toolproxyid) && !empty($config->toolproxyid)) {
            $toolproxy->id = $config->toolproxyid;
            if (!isset($toolproxy->state) || ($toolproxy->state != LTI_TOOL_PROXY_STATE_ACCEPTED)) {
                $toolproxy->state = LTI_TOOL_PROXY_STATE_CONFIGURED;
                $toolproxy->guid = random_string();
                $toolproxy->secret = random_string();
            }
            $id = self::update_tool_proxy($toolproxy);
        } else {
            $toolproxy->state = LTI_TOOL_PROXY_STATE_CONFIGURED;
            $toolproxy->timemodified = time();
            $toolproxy->timecreated = $toolproxy->timemodified;
            if (!isset($toolproxy->createdby)) {
                $toolproxy->createdby = $USER->id;
            }
            $toolproxy->guid = random_string();
            $toolproxy->secret = random_string();
            $id = $DB->insert_record('lti_tool_proxies', $toolproxy);
        }

        return $id;
    }

    /**
     * Initializes an array with the services supported by the LTI module
     *
     * @return array List of services
     */
    public static function get_services() {
        $services = array();
        $definedservices = core_component::get_plugin_list('ltiservice');
        foreach ($definedservices as $name => $location) {
            $classname = "\\ltiservice_{$name}\\local\\service\\{$name}";
            $services[] = new $classname();
        }

        return $services;
    }

    /**
     * Updates a tool proxy in the database
     *
     * @param object $toolproxy Tool proxy
     *
     * @return int    Record id number
     */
    public static function update_tool_proxy($toolproxy) {
        global $DB;

        $toolproxy->timemodified = time();
        $id = $DB->update_record('lti_tool_proxies', $toolproxy);

        return $id;
    }

    /**
     * Delete a Tool Proxy
     *
     * @param int $id   Tool Proxy id
     */
    public static function delete_tool_proxy($id) {
        global $DB;
        $DB->delete_records('lti_tool_settings', array('toolproxyid' => $id));
        $tools = $DB->get_records('lti_types', array('toolproxyid' => $id));
        foreach ($tools as $tool) {
            types_helper::delete_type($tool->id);
        }
        $DB->delete_records('lti_tool_proxies', array('id' => $id));
    }

    /**
     * Get SQL to query DB for LTI tool proxy records.
     *
     * @param bool $orphanedonly If true, return SQL to get orphaned proxies only.
     * @param bool $count If true, return SQL to get the count of the records instead of the records themselves.
     * @return string SQL.
     */
    public static function get_tool_proxy_sql(bool $orphanedonly = false, bool $count = false): string {
        if ($count) {
            $select = "SELECT count(*) as type_count";
            $sort = "";
        } else {
            // We only want the fields from lti_tool_proxies table. Must define every column to be compatible with mysqli.
            $select = "SELECT ltp.id, ltp.name, ltp.regurl, ltp.state, ltp.guid, ltp.secret, ltp.vendorcode,
                              ltp.capabilityoffered, ltp.serviceoffered, ltp.toolproxy, ltp.createdby,
                              ltp.timecreated, ltp.timemodified";
            $sort = " ORDER BY ltp.name ASC, ltp.state DESC, ltp.timemodified DESC";
        }
        $from = " FROM {lti_tool_proxies} ltp";
        if ($orphanedonly) {
            $join = " LEFT JOIN {lti_types} lt ON ltp.id = lt.toolproxyid";
            $where = " WHERE lt.toolproxyid IS null";
        } else {
            $join = "";
            $where = "";
        }

        return $select . $from . $join . $where . $sort;
    }

    /**
     * This function builds the request that must be sent to an LTI 2 tool provider
     *
     * @param object    $tool           Basic LTI tool object
     * @param array     $params         Custom launch parameters
     *
     * @return array                    Request details
     */
    public static function build_request_lti2($tool, $params) {

        $requestparams = array();

        $capabilities = self::get_capabilities();
        $enabledcapabilities = explode("\n", $tool->enabledcapability);
        foreach ($enabledcapabilities as $capability) {
            if (array_key_exists($capability, $capabilities)) {
                $val = $capabilities[$capability];
                if ($val && (substr($val, 0, 1) != '$')) {
                    if (isset($params[$val])) {
                        $requestparams[$capabilities[$capability]] = $params[$capabilities[$capability]];
                    }
                }
            }
        }

        return $requestparams;

    }

    /**
     * Processes the tool provider's response to the ContentItemSelectionRequest and builds the configuration data from the
     * selected content item. This configuration data can be then used when adding a tool into the course.
     *
     * @param int $typeid The tool type ID.
     * @param string $messagetype The value for the lti_message_type parameter.
     * @param string $ltiversion The value for the lti_version parameter.
     * @param string $consumerkey The consumer key.
     * @param string $contentitemsjson The JSON string for the content_items parameter.
     * @return stdClass The array of module information objects.
     * @throws moodle_exception
     * @throws moodle\mod\lti\OAuthException
     */
    public static function tool_configuration_from_content_item($typeid, $messagetype, $ltiversion, $consumerkey, $contentitemsjson) {
        $tool = types_helper::get_type($typeid);
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
        $islti2 = ($expectedversion === LTI_VERSION_2);
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
            $typeconfig = types_helper::get_type_type_config($tool->id);
            if (count($items) == 1) {
                $config = self::content_item_to_form($tool, $typeconfig, $items[0]);
            } else {
                $multiple = [];
                foreach ($items as $item) {
                    $multiple[] = self::content_item_to_form($tool, $typeconfig, $item);
                }
                $config = new stdClass();
                $config->multiple = $multiple;
            }
        }
        return $config;
    }

    /**
     * Converts LTI 1.1 Content Item for LTI Link to Form data.
     *
     * @param object $tool Tool for which the item is created for.
     * @param object $typeconfig The tool configuration.
     * @param object $item Item populated from JSON to be converted to Form form
     *
     * @return stdClass Form config for the item
     */
    public static function content_item_to_form(object $tool, object $typeconfig, object $item) : stdClass {
        global $OUTPUT;

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
        $config->instructorchoiceacceptgrades = LTI_SETTING_NEVER;
        $islti2 = $tool->ltiversion === LTI_VERSION_2;
        if (!$islti2 && isset($typeconfig->lti_acceptgrades)) {
            $acceptgrades = $typeconfig->lti_acceptgrades;
            if ($acceptgrades == LTI_SETTING_ALWAYS) {
                // We create a line item regardless if the definition contains one or not.
                $config->instructorchoiceacceptgrades = LTI_SETTING_ALWAYS;
                $config->grade_modgrade_point = 100;
            }
            if ($acceptgrades == LTI_SETTING_DELEGATE || $acceptgrades == LTI_SETTING_ALWAYS) {
                if (isset($item->lineItem)) {
                    $lineitem = $item->lineItem;
                    $config->instructorchoiceacceptgrades = LTI_SETTING_ALWAYS;
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
        $config->instructorchoicesendname = LTI_SETTING_NEVER;
        $config->instructorchoicesendemailaddr = LTI_SETTING_NEVER;

        // Since 4.3, the launch container is dictated by the value set in tool configuration and isn't controllable by content items.
        $config->launchcontainer = LTI_LAUNCH_CONTAINER_DEFAULT;

        if (isset($item->custom)) {
            $config->instructorcustomparameters = params_to_string($item->custom);
        }

        // Pass an indicator to the relevant form field.
        $config->selectcontentindicator = $OUTPUT->pix_icon('i/valid', get_string('yes')) . get_string('contentselected', 'ltix');

        return $config;
    }

    /**
     * Converts the new Deep-Linking format for Content-Items to the old format.
     *
     * @param string $param JSON string representing new Deep-Linking format
     * @return string  JSON representation of content-items
     */
    public static function convert_content_items($param) {
        $items = array();
        $json = json_decode($param);
        if (!empty($json) && is_array($json)) {
            foreach ($json as $item) {
                if (isset($item->type)) {
                    $newitem = clone $item;
                    switch ($item->type) {
                        case 'ltiResourceLink':
                            $newitem->{'@type'} = 'LtiLinkItem';
                            $newitem->mediaType = 'application\/vnd.ims.lti.v1.ltilink';
                            break;
                        case 'link':
                        case 'rich':
                            $newitem->{'@type'} = 'ContentItem';
                            $newitem->mediaType = 'text/html';
                            break;
                        case 'file':
                            $newitem->{'@type'} = 'FileItem';
                            break;
                    }
                    unset($newitem->type);
                    if (isset($item->html)) {
                        $newitem->text = $item->html;
                        unset($newitem->html);
                    }
                    if (isset($item->iframe)) {
                        // DeepLinking allows multiple options to be declared as supported.
                        // We favor iframe over new window if both are specified.
                        $newitem->placementAdvice = new stdClass();
                        $newitem->placementAdvice->presentationDocumentTarget = 'iframe';
                        if (isset($item->iframe->width)) {
                            $newitem->placementAdvice->displayWidth = $item->iframe->width;
                        }
                        if (isset($item->iframe->height)) {
                            $newitem->placementAdvice->displayHeight = $item->iframe->height;
                        }
                        unset($newitem->iframe);
                        unset($newitem->window);
                    } else if (isset($item->window)) {
                        $newitem->placementAdvice = new stdClass();
                        $newitem->placementAdvice->presentationDocumentTarget = 'window';
                        if (isset($item->window->targetName)) {
                            $newitem->placementAdvice->windowTarget = $item->window->targetName;
                        }
                        if (isset($item->window->width)) {
                            $newitem->placementAdvice->displayWidth = $item->window->width;
                        }
                        if (isset($item->window->height)) {
                            $newitem->placementAdvice->displayHeight = $item->window->height;
                        }
                        unset($newitem->window);
                    } else if (isset($item->presentation)) {
                        // This may have been part of an early draft but is not in the final spec
                        // so keeping it around for now in case it's actually been used.
                        $newitem->placementAdvice = new stdClass();
                        if (isset($item->presentation->documentTarget)) {
                            $newitem->placementAdvice->presentationDocumentTarget = $item->presentation->documentTarget;
                        }
                        if (isset($item->presentation->windowTarget)) {
                            $newitem->placementAdvice->windowTarget = $item->presentation->windowTarget;
                        }
                        if (isset($item->presentation->width)) {
                            $newitem->placementAdvice->dislayWidth = $item->presentation->width;
                        }
                        if (isset($item->presentation->height)) {
                            $newitem->placementAdvice->dislayHeight = $item->presentation->height;
                        }
                        unset($newitem->presentation);
                    }
                    if (isset($item->icon) && isset($item->icon->url)) {
                        $newitem->icon->{'@id'} = $item->icon->url;
                        unset($newitem->icon->url);
                    }
                    if (isset($item->thumbnail) && isset($item->thumbnail->url)) {
                        $newitem->thumbnail->{'@id'} = $item->thumbnail->url;
                        unset($newitem->thumbnail->url);
                    }
                    if (isset($item->lineItem)) {
                        unset($newitem->lineItem);
                        $newitem->lineItem = new stdClass();
                        $newitem->lineItem->{'@type'} = 'LineItem';
                        $newitem->lineItem->reportingMethod = 'http://purl.imsglobal.org/ctx/lis/v2p1/Result#totalScore';
                        if (isset($item->lineItem->label)) {
                            $newitem->lineItem->label = $item->lineItem->label;
                        }
                        if (isset($item->lineItem->resourceId)) {
                            $newitem->lineItem->assignedActivity = new stdClass();
                            $newitem->lineItem->assignedActivity->activityId = $item->lineItem->resourceId;
                        }
                        if (isset($item->lineItem->tag)) {
                            $newitem->lineItem->tag = $item->lineItem->tag;
                        }
                        if (isset($item->lineItem->scoreMaximum)) {
                            $newitem->lineItem->scoreConstraints = new stdClass();
                            $newitem->lineItem->scoreConstraints->{'@type'} = 'NumericLimits';
                            $newitem->lineItem->scoreConstraints->totalMaximum = $item->lineItem->scoreMaximum;
                        }
                        if (isset($item->lineItem->submissionReview)) {
                            $newitem->lineItem->submissionReview = $item->lineItem->submissionReview;
                        }
                    }
                    $items[] = $newitem;
                }
            }
        }

        $newitems = new stdClass();
        $newitems->{'@context'} = 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem';
        $newitems->{'@graph'} = $items;

        return json_encode($newitems);
    }

    /**
     * Extracts the enabled capabilities into an array, including those implicitly declared in a parameter
     *
     * @param object $tool  Tool instance object
     *
     * @return array List of enabled capabilities
     */
    public static function get_enabled_capabilities($tool) {
        if (!isset($tool)) {
            return array();
        }
        if (!empty($tool->enabledcapability)) {
            $enabledcapabilities = explode("\n", $tool->enabledcapability);
        } else {
            $enabledcapabilities = array();
        }
        if (!empty($tool->parameter)) {
            $paramstr = str_replace("\r\n", "\n", $tool->parameter);
            $paramstr = str_replace("\n\r", "\n", $paramstr);
            $paramstr = str_replace("\r", "\n", $paramstr);
            $params = explode("\n", $paramstr);
            foreach ($params as $param) {
                $pos = strpos($param, '=');
                if (($pos === false) || ($pos < 1)) {
                    continue;
                }
                $value = trim(core_text::substr($param, $pos + 1, strlen($param)));
                if (substr($value, 0, 1) == '$') {
                    $value = substr($value, 1);
                    if (!in_array($value, $enabledcapabilities)) {
                        $enabledcapabilities[] = $value;
                    }
                }
            }
        }
        return $enabledcapabilities;
    }

    /**
     * Splits the custom parameters
     *
     * @param string    $customstr      String containing the parameters
     *
     * @return array of custom parameters
     */
    public static function split_parameters($customstr) {
        $customstr = str_replace("\r\n", "\n", $customstr);
        $customstr = str_replace("\n\r", "\n", $customstr);
        $customstr = str_replace("\r", "\n", $customstr);
        $lines = explode("\n", $customstr);  // Or should this split on "/[\n;]/"?
        $retval = array();
        foreach ($lines as $line) {
            $pos = strpos($line, '=');
            if ( $pos === false || $pos < 1 ) {
                continue;
            }
            $key = trim(core_text::substr($line, 0, $pos));
            $val = trim(core_text::substr($line, $pos + 1, strlen($line)));
            $retval[$key] = $val;
        }
        return $retval;
    }

    /**
     * Splits the custom parameters field to the various parameters
     *
     * @param object    $toolproxy      Tool proxy instance object
     * @param object    $tool           Tool instance object
     * @param array     $params         LTI launch parameters
     * @param string    $customstr      String containing the parameters
     * @param boolean   $islti2         True if an LTI 2 tool is being launched
     *
     * @return array of custom parameters
     */
    public static function split_custom_parameters($toolproxy, $tool, $params, $customstr, $islti2 = false) {
        $splitted = self::split_parameters($customstr);
        $retval = array();
        foreach ($splitted as $key => $val) {
            $val = self::parse_custom_parameter($toolproxy, $tool, $params, $val, $islti2);
            $key2 = self::map_keyname($key);
            $retval['custom_'.$key2] = $val;
            if (($islti2 || ($tool->ltiversion === LTI_VERSION_1P3)) && ($key != $key2)) {
                $retval['custom_'.$key] = $val;
            }
        }
        return $retval;
    }

    /**
     * Adds the custom parameters to an array
     *
     * @param object    $toolproxy      Tool proxy instance object
     * @param object    $tool           Tool instance object
     * @param array     $params         LTI launch parameters
     * @param array     $parameters     Array containing the parameters
     *
     * @return array    Array of custom parameters
     */
    public static function get_custom_parameters($toolproxy, $tool, $params, $parameters) {
        $retval = array();
        foreach ($parameters as $key => $val) {
            $key2 = self::map_keyname($key);
            $val = self::parse_custom_parameter($toolproxy, $tool, $params, $val, true);
            $retval['custom_'.$key2] = $val;
            if ($key != $key2) {
                $retval['custom_'.$key] = $val;
            }
        }
        return $retval;
    }

    /**
     * Parse a custom parameter to replace any substitution variables
     *
     * @param object    $toolproxy      Tool proxy instance object
     * @param object    $tool           Tool instance object
     * @param array     $params         LTI launch parameters
     * @param string    $value          Custom parameter value
     * @param boolean   $islti2         True if an LTI 2 tool is being launched
     *
     * @return string Parsed value of custom parameter
     */
    public static function parse_custom_parameter($toolproxy, $tool, $params, $value, $islti2) {
        // This is required as {${$valarr[0]}->{$valarr[1]}}" may be using the USER or COURSE var.
        global $USER, $COURSE;

        if ($value) {
            if (substr($value, 0, 1) == '\\') {
                $value = substr($value, 1);
            } else if (substr($value, 0, 1) == '$') {
                $value1 = substr($value, 1);
                $enabledcapabilities = self::get_enabled_capabilities($tool);
                if (!$islti2 || in_array($value1, $enabledcapabilities)) {
                    $capabilities = self::get_capabilities();
                    if (array_key_exists($value1, $capabilities)) {
                        $val = $capabilities[$value1];
                        if ($val) {
                            if (substr($val, 0, 1) != '$') {
                                $value = $params[$val];
                            } else {
                                $valarr = explode('->', substr($val, 1), 2);
                                $value = "{${$valarr[0]}->{$valarr[1]}}";
                                $value = str_replace('<br />' , ' ', $value);
                                $value = str_replace('<br>' , ' ', $value);
                                $value = format_string($value);
                            }
                        } else {
                            $value = self::calculate_custom_parameter($value1);
                        }
                    } else {
                        $val = $value;
                        $services = self::get_services();
                        foreach ($services as $service) {
                            $service->set_tool_proxy($toolproxy);
                            $service->set_type($tool);
                            $value = $service->parse_value($val);
                            if ($val != $value) {
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $value;
    }

    /**
     * Calculates the value of a custom parameter that has not been specified earlier
     *
     * @param string    $value          Custom parameter value
     *
     * @return string Calculated value of custom parameter
     */
    public static function calculate_custom_parameter($value) {
        global $USER, $COURSE;

        switch ($value) {
            case 'Moodle.Person.userGroupIds':
                return implode(",", groups_get_user_groups($COURSE->id, $USER->id)[0]);
            case 'Context.id.history':
                return implode(",", self::get_course_history($COURSE));
            case 'CourseSection.timeFrame.begin':
                if (empty($COURSE->startdate)) {
                    return "";
                }
                $dt = new DateTime("@$COURSE->startdate", new DateTimeZone('UTC'));
                return $dt->format(DateTime::ATOM);
            case 'CourseSection.timeFrame.end':
                if (empty($COURSE->enddate)) {
                    return "";
                }
                $dt = new DateTime("@$COURSE->enddate", new DateTimeZone('UTC'));
                return $dt->format(DateTime::ATOM);
        }
        return null;
    }

    /**
     * Build the history chain for this course using the course originalcourseid.
     *
     * @param object $course course for which the history is returned.
     *
     * @return array ids of the source course in ancestry order, immediate parent 1st.
     */
    public static function get_course_history($course) {
        global $DB;
        $history = [];
        $parentid = $course->originalcourseid;
        while (!empty($parentid) && !in_array($parentid, $history)) {
            $history[] = $parentid;
            $parentid = $DB->get_field('course', 'originalcourseid', array('id' => $parentid));
        }
        return $history;
    }

    /**
     * Used for building the names of the different custom parameters
     *
     * @param string $key   Parameter name
     * @param bool $tolower Do we want to convert the key into lower case?
     * @return string       Processed name
     */
    public static function map_keyname($key, $tolower = true) {
        if ($tolower) {
            $newkey = '';
            $key = core_text::strtolower(trim($key));
            foreach (str_split($key) as $ch) {
                if ( ($ch >= 'a' && $ch <= 'z') || ($ch >= '0' && $ch <= '9') ) {
                    $newkey .= $ch;
                } else {
                    $newkey .= '_';
                }
            }
        } else {
            $newkey = $key;
        }
        return $newkey;
    }

    /**
     * Given an array of tools, filter them based on their state
     *
     * @param array $tools An array of lti_types records
     * @param int $state One of the LTI_TOOL_STATE_* constants
     * @return array
     */
    public static function filter_tool_types(array $tools, $state) {
        $return = array();
        foreach ($tools as $key => $tool) {
            if ($tool->state == $state) {
                $return[$key] = $tool;
            }
        }
        return $return;
    }

    /**
     * Given an array of tool proxies, filter them based on their state
     *
     * @param array $toolproxies An array of lti_tool_proxies records
     * @param int $state One of the LTI_TOOL_PROXY_STATE_* constants
     *
     * @return array
     */
    public static function filter_tool_proxy_types(array $toolproxies, $state) {
        $return = array();
        foreach ($toolproxies as $key => $toolproxy) {
            if ($toolproxy->state == $state) {
                $return[$key] = $toolproxy;
            }
        }
        return $return;
    }

    /**
     * Get the tool proxy instance given its GUID
     *
     * @param string  $toolproxyguid   Tool proxy GUID value
     *
     * @return object
     */
    public static function get_tool_proxy_from_guid($toolproxyguid) {
        global $DB;

        $toolproxy = $DB->get_record('lti_tool_proxies', array('guid' => $toolproxyguid));

        return $toolproxy;
    }

    /**
     * Get the tool proxy instance given its registration URL
     *
     * @param string $regurl Tool proxy registration URL
     *
     * @return array The record of the tool proxy with this url
     */
    public static function get_tool_proxies_from_registration_url($regurl) {
        global $DB;

        return $DB->get_records_sql(
            'SELECT * FROM {lti_tool_proxies}
        WHERE '.$DB->sql_compare_text('regurl', 256).' = :regurl',
            array('regurl' => $regurl)
        );
    }

    /**
     * Generates some of the tool proxy configuration based on the admin configuration details
     *
     * @param int $id
     *
     * @return mixed Tool Proxy details
     */
    public static function get_tool_proxy($id) {
        global $DB;

        $toolproxy = $DB->get_record('lti_tool_proxies', array('id' => $id));
        return $toolproxy;
    }

    /**
     * Returns lti tool proxies.
     *
     * @param bool $orphanedonly Only retrieves tool proxies that have no type associated with them
     * @return array of basicLTI types
     */
    public static function get_tool_proxies($orphanedonly) {
        global $DB;

        if ($orphanedonly) {
            $usedproxyids = array_values($DB->get_fieldset_select('lti_types', 'toolproxyid', 'toolproxyid IS NOT NULL'));
            $proxies = $DB->get_records('lti_tool_proxies', null, 'state DESC, timemodified DESC');
            foreach ($proxies as $key => $value) {
                if (in_array($value->id, $usedproxyids)) {
                    unset($proxies[$key]);
                }
            }
            return $proxies;
        } else {
            return $DB->get_records('lti_tool_proxies', null, 'state DESC, timemodified DESC');
        }
    }

    /**
     * Generates some of the tool proxy configuration based on the admin configuration details
     *
     * @param int $id
     *
     * @return mixed  Tool Proxy details
     */
    public static function get_tool_proxy_config($id) {
        $toolproxy = self::get_tool_proxy($id);

        $tp = new \stdClass();
        $tp->lti_registrationname = $toolproxy->name;
        $tp->toolproxyid = $toolproxy->id;
        $tp->state = $toolproxy->state;
        $tp->lti_registrationurl = $toolproxy->regurl;
        $tp->lti_capabilities = explode("\n", $toolproxy->capabilityoffered);
        $tp->lti_services = explode("\n", $toolproxy->serviceoffered);

        return $tp;
    }

    /**
     * Gets the tool settings
     *
     * @param int  $toolproxyid   Id of tool proxy record (or tool ID if negative)
     * @param int  $courseid      Id of course (null if system settings)
     * @param int  $instanceid    Id of course module (null if system or context settings)
     *
     * @return array  Array settings
     */
    public static function get_tool_settings($toolproxyid, $courseid = null, $instanceid = null) {
        global $DB;

        $settings = array();
        if ($toolproxyid > 0) {
            $settingsstr = $DB->get_field('lti_tool_settings', 'settings', array('toolproxyid' => $toolproxyid,
                'course' => $courseid, 'coursemoduleid' => $instanceid));
        } else {
            $settingsstr = $DB->get_field('lti_tool_settings', 'settings', array('typeid' => -$toolproxyid,
                'course' => $courseid, 'coursemoduleid' => $instanceid));
        }
        if ($settingsstr !== false) {
            $settings = json_decode($settingsstr, true);
        }
        return $settings;
    }

    /**
     * Sets the tool settings (
     *
     * @param array  $settings      Array of settings
     * @param int    $toolproxyid   Id of tool proxy record (or tool ID if negative)
     * @param int    $courseid      Id of course (null if system settings)
     * @param int    $instanceid    Id of course module (null if system or context settings)
     */
    public static function set_tool_settings($settings, $toolproxyid, $courseid = null, $instanceid = null) {
        global $DB;

        $json = json_encode($settings);
        if ($toolproxyid >= 0) {
            $record = $DB->get_record('lti_tool_settings', array('toolproxyid' => $toolproxyid,
                'course' => $courseid, 'coursemoduleid' => $instanceid));
        } else {
            $record = $DB->get_record('lti_tool_settings', array('typeid' => -$toolproxyid,
                'course' => $courseid, 'coursemoduleid' => $instanceid));
        }
        if ($record !== false) {
            $DB->update_record('lti_tool_settings', (object)array('id' => $record->id, 'settings' => $json, 'timemodified' => time()));
        } else {
            $record = new \stdClass();
            if ($toolproxyid > 0) {
                $record->toolproxyid = $toolproxyid;
            } else {
                $record->typeid = -$toolproxyid;
            }
            $record->course = $courseid;
            $record->coursemoduleid = $instanceid;
            $record->settings = $json;
            $record->timecreated = time();
            $record->timemodified = $record->timecreated;
            $DB->insert_record('lti_tool_settings', $record);
        }
    }

    public static function ensure_url_is_https($url) {
        if (!strstr($url, '://')) {
            $url = 'https://' . $url;
        } else {
            // If the URL starts with http, replace with https.
            if (stripos($url, 'http://') === 0) {
                $url = 'https://' . substr($url, 7);
            }
        }

        return $url;
    }

    public static function request_is_using_ssl() {
        global $CFG;
        return (stripos($CFG->wwwroot, 'https://') === 0);
    }

    /**
     * Initializes an array with the capabilities supported by the LTI module
     *
     * @return array List of capability names (without a dollar sign prefix)
     */
    public static function get_capabilities() {
        $capabilities = array(
            'basic-lti-launch-request' => '',
            'ContentItemSelectionRequest' => '',
            'ToolProxyRegistrationRequest' => '',
            'Context.id' => 'context_id',
            'Context.title' => 'context_title',
            'Context.label' => 'context_label',
            'Context.id.history' => null,
            'Context.sourcedId' => 'lis_course_section_sourcedid',
            'Context.longDescription' => '$COURSE->summary',
            'Context.timeFrame.begin' => '$COURSE->startdate',
            'CourseSection.title' => 'context_title',
            'CourseSection.label' => 'context_label',
            'CourseSection.sourcedId' => 'lis_course_section_sourcedid',
            'CourseSection.longDescription' => '$COURSE->summary',
            'CourseSection.timeFrame.begin' => null,
            'CourseSection.timeFrame.end' => null,
            'ResourceLink.id' => 'resource_link_id',
            'ResourceLink.title' => 'resource_link_title',
            'ResourceLink.description' => 'resource_link_description',
            'User.id' => 'user_id',
            'User.username' => '$USER->username',
            'Person.name.full' => 'lis_person_name_full',
            'Person.name.given' => 'lis_person_name_given',
            'Person.name.family' => 'lis_person_name_family',
            'Person.email.primary' => 'lis_person_contact_email_primary',
            'Person.sourcedId' => 'lis_person_sourcedid',
            'Person.name.middle' => '$USER->middlename',
            'Person.address.street1' => '$USER->address',
            'Person.address.locality' => '$USER->city',
            'Person.address.country' => '$USER->country',
            'Person.address.timezone' => '$USER->timezone',
            'Person.phone.primary' => '$USER->phone1',
            'Person.phone.mobile' => '$USER->phone2',
            'Person.webaddress' => '$USER->url',
            'Membership.role' => 'roles',
            'Result.sourcedId' => 'lis_result_sourcedid',
            'Result.autocreate' => 'lis_outcome_service_url',
            'BasicOutcome.sourcedId' => 'lis_result_sourcedid',
            'BasicOutcome.url' => 'lis_outcome_service_url',
            'Moodle.Person.userGroupIds' => null);

        return $capabilities;
    }

    /**
     * Search for a tag within an XML DOMDocument
     *
     * @param  string $url The url of the cartridge to be loaded
     * @param  array  $map The map of tags to keys in the return array
     * @param  array  $propertiesmap The map of properties to keys in the return array
     * @return array An associative array with the given keys and their values from the cartridge
     * @throws moodle_exception if the cartridge could not be loaded correctly
     */
    public static function load_cartridge($url, $map, $propertiesmap = array()) {
        global $CFG;
        require_once($CFG->libdir. "/filelib.php");

        $curl = new curl();
        $response = $curl->get($url);

        // Got a completely empty response (real or error), cannot process this with
        // DOMDocument::loadXML() because it errors with ValueError. So let's throw
        // the moodle_exception before waiting to examine the errors later.
        if (trim($response) === '') {
            throw new moodle_exception('errorreadingfile', '', '', $url);
        }

        // TODO MDL-46023 Replace this code with a call to the new library.
        $origerrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $document = new DOMDocument();
        @$document->loadXML($response, LIBXML_NONET);

        $cartridge = new DomXpath($document);

        $errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors($origerrors);

        if (count($errors) > 0) {
            $message = 'Failed to load cartridge.';
            foreach ($errors as $error) {
                $message .= "\n" . trim($error->message, "\n\r\t .") . " at line " . $error->line;
            }
            throw new moodle_exception('errorreadingfile', '', '', $url, $message);
        }

        $toolinfo = array();
        foreach ($map as $tag => $key) {
            $value = self::get_tag($tag, $cartridge);
            if ($value) {
                $toolinfo[$key] = $value;
            }
        }
        if (!empty($propertiesmap)) {
            foreach ($propertiesmap as $property => $key) {
                $value = self::get_tag("property", $cartridge, $property);
                if ($value) {
                    $toolinfo[$key] = $value;
                }
            }
        }

        return $toolinfo;
    }

    /**
     * Search for a tag within an XML DOMDocument
     *
     * @param  stdClass $tagname The name of the tag to search for
     * @param  XPath    $xpath   The XML to find the tag in
     * @param  XPath    $attribute The attribute to search for (if we should search for a child node with the given
     * value for the name attribute
     */
    public static function get_tag($tagname, $xpath, $attribute = null) {
        if ($attribute) {
            $result = $xpath->query('//*[local-name() = \'' . $tagname . '\'][@name="' . $attribute . '"]');
        } else {
            $result = $xpath->query('//*[local-name() = \'' . $tagname . '\']');
        }
        if ($result->length > 0) {
            return $result->item(0)->nodeValue;
        }
        return null;
    }

    /**
     * Determines if the given url is for a IMS basic cartridge
     *
     * @param  string $url The url to be checked
     * @return True if the url is for a cartridge
     */
    public static function is_cartridge($url) {
        // If it is empty, it's not a cartridge.
        if (empty($url)) {
            return false;
        }
        // If it has xml at the end of the url, it's a cartridge.
        if (preg_match('/\.xml$/', $url)) {
            return true;
        }

        // Skip slow cartridge checks during tests.
        // During tests, working .xml cartridge URLs are used when testing cartridge support. These will match the '.xml' check
        // above (which is fast). Don't try to check whether other tool URLs are cartridges because most URLs used in tests will be
        // example URLs and won't be resolvable, resulting in network hangs within load_cartridge() - which is called every time a
        // tool is edited and will result in slow tests or seemingly random test failures.
        if (!defined('BEHAT_SITE_RUNNING') && !defined('PHPUNIT_TEST')) {
            // Even if it doesn't have .xml, load the url to check if it's a cartridge..
            try {
                $toolinfo = lti_load_cartridge($url,
                    array(
                        "launch_url" => "launchurl"
                    )
                );
                if (!empty($toolinfo['launchurl'])) {
                    return true;
                }
            } catch (moodle_exception $e) {
                return false; // Error loading the xml, so it's not a cartridge.
            }
        }
        return false;
    }

}
