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

declare(strict_types=1);

namespace core_ltix;

use context_course;
use core\context\course;
use mod_lti\local\ltiopenid\registration_helper;
use moodle_exception;
use stdClass;

define('LTI_URL_DOMAIN_REGEX', '/(?:https?:\/\/)?(?:www\.)?([^\/]+)(?:\/|$)/i');

define('LTI_LAUNCH_CONTAINER_DEFAULT', 1);
define('LTI_LAUNCH_CONTAINER_EMBED', 2);
define('LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS', 3);
define('LTI_LAUNCH_CONTAINER_WINDOW', 4);
define('LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW', 5);

define('LTI_TOOL_STATE_ANY', 0);
define('LTI_TOOL_STATE_CONFIGURED', 1);
define('LTI_TOOL_STATE_PENDING', 2);
define('LTI_TOOL_STATE_REJECTED', 3);
define('LTI_TOOL_PROXY_TAB', 4);

define('LTI_TOOL_PROXY_STATE_CONFIGURED', 1);
define('LTI_TOOL_PROXY_STATE_PENDING', 2);
define('LTI_TOOL_PROXY_STATE_ACCEPTED', 3);
define('LTI_TOOL_PROXY_STATE_REJECTED', 4);

define('LTI_SETTING_NEVER', 0);
define('LTI_SETTING_ALWAYS', 1);
define('LTI_SETTING_DELEGATE', 2);

define('LTI_COURSEVISIBLE_NO', 0);
define('LTI_COURSEVISIBLE_PRECONFIGURED', 1);
define('LTI_COURSEVISIBLE_ACTIVITYCHOOSER', 2);

define('LTI_VERSION_1', 'LTI-1p0');
define('LTI_VERSION_2', 'LTI-2p0');
define('LTI_VERSION_1P3', '1.3.0');
define('LTI_RSA_KEY', 'RSA_KEY');
define('LTI_JWK_KEYSET', 'JWK_KEYSET');

define('LTI_DEFAULT_ORGID_SITEID', 'SITEID');
define('LTI_DEFAULT_ORGID_SITEHOST', 'SITEHOST');

define('LTI_ACCESS_TOKEN_LIFE', 3600);

// Standard prefix for JWT claims.
define('LTI_JWT_CLAIM_PREFIX', 'https://purl.imsglobal.org/spec/lti');

/**
 * Helper class specifically dealing with LTI types (preconfigured tools).
 *
 * @package    core_ltix
 * @author     Godson Ahamba
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class types_helper {

    /**
     * Returns all LTI tool types (preconfigured tools) visible in the given course and for the given user.
     *
     * This list will contain both site level tools and course-level tools.
     *
     * @param int $courseid the id of the course.
     * @param int $userid the id of the user.
     * @param array $coursevisible options for 'coursevisible' field, which will default to
     *        [LTI_COURSEVISIBLE_PRECONFIGURED, LTI_COURSEVISIBLE_ACTIVITYCHOOSER] if omitted.
     * @return \stdClass[] the array of tool type objects.
     */
    public static function get_lti_types_by_course(int $courseid, int $userid, array $coursevisible = []): array {
        global $DB, $SITE;

        if (empty($coursevisible)) {
            $coursevisible = [LTI_COURSEVISIBLE_PRECONFIGURED, LTI_COURSEVISIBLE_ACTIVITYCHOOSER];
        }
        [$coursevisiblesql, $coursevisparams] = $DB->get_in_or_equal($coursevisible, SQL_PARAMS_NAMED, 'coursevisible');
        [$coursevisiblesql1, $coursevisparams1] = $DB->get_in_or_equal($coursevisible, SQL_PARAMS_NAMED, 'coursevisible');
        [$coursevisibleoverriddensql, $coursevisoverriddenparams] = $DB->get_in_or_equal(
            $coursevisible,
            SQL_PARAMS_NAMED,
            'coursevisibleoverridden');

        $coursecond = implode(" OR ", ["t.course = :courseid", "t.course = :siteid"]);
        $coursecategory = $DB->get_field('course', 'category', ['id' => $courseid]);
        $query = "SELECT *
                    FROM (SELECT t.*, c.coursevisible as coursevisibleoverridden
                            FROM {lti_types} t
                       LEFT JOIN {lti_types_categories} tc ON t.id = tc.typeid
                       LEFT JOIN {lti_coursevisible} c ON c.typeid = t.id AND c.courseid = $courseid
                           WHERE (t.coursevisible $coursevisiblesql
                                 OR (c.coursevisible $coursevisiblesql1 AND t.coursevisible NOT IN (:lticoursevisibleno)))
                             AND ($coursecond)
                             AND t.state = :active
                             AND (tc.id IS NULL OR tc.categoryid = :categoryid)) tt
                   WHERE tt.coursevisibleoverridden IS NULL
                      OR tt.coursevisibleoverridden $coursevisibleoverriddensql";

        return $DB->get_records_sql(
            $query,
            [
                'siteid' => $SITE->id,
                'courseid' => $courseid,
                'active' => LTI_TOOL_STATE_CONFIGURED,
                'categoryid' => $coursecategory,
                'coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER,
                'lticoursevisibleno' => LTI_COURSEVISIBLE_NO,
            ] + $coursevisparams + $coursevisparams1 + $coursevisoverriddenparams
        );
    }

    /**
     * Override coursevisible for a given tool on course level.
     *
     * @param int $tooltypeid Type ID
     * @param int $courseid Course ID
     * @param \core\context\course $context Course context
     * @param bool $showinactivitychooser Show or not show in activity chooser
     * @return bool True if the coursevisible was changed, false otherwise.
     */
    public static function override_type_showinactivitychooser(int $tooltypeid, int $courseid, \core\context\course $context,
        bool $showinactivitychooser): bool {
        global $DB;

        require_capability('moodle/ltix:addcoursetool', $context);

        $ltitype = self::get_type($tooltypeid);
        if ($ltitype && ($ltitype->coursevisible != LTI_COURSEVISIBLE_NO)) {
            $coursevisible = $showinactivitychooser ? LTI_COURSEVISIBLE_ACTIVITYCHOOSER : LTI_COURSEVISIBLE_PRECONFIGURED;
            $ltitype->coursevisible = $coursevisible;

            $config = new \stdClass();
            $config->lti_coursevisible = $coursevisible;

            if (intval($ltitype->course) != intval(get_site()->id)) {
                // It is course tool - just update it.
                self::update_type($ltitype, $config);
            } else {
                $coursecategory = $DB->get_field('course', 'category', ['id' => $courseid]);
                $sql = "SELECT COUNT(*) AS count
                      FROM {lti_types_categories} tc
                     WHERE tc.typeid = :typeid";
                $restrictedtool = $DB->count_records_sql($sql, ['typeid' => $tooltypeid]);
                if ($restrictedtool) {
                    $record = $DB->get_record('lti_types_categories', ['typeid' => $tooltypeid, 'categoryid' => $coursecategory]);
                    if (!$record) {
                        throw new \moodle_exception('You are not allowed to change this setting for this tool.');
                    }
                }

                // This is site tool, but we would like to have course level setting for it.
                $lticoursevisible = $DB->get_record('lti_coursevisible', ['typeid' => $tooltypeid, 'courseid' => $courseid]);
                if (!$lticoursevisible) {
                    $lticoursevisible = new \stdClass();
                    $lticoursevisible->typeid = $tooltypeid;
                    $lticoursevisible->courseid = $courseid;
                    $lticoursevisible->coursevisible = $coursevisible;
                    $DB->insert_record('lti_coursevisible', $lticoursevisible);
                } else {
                    $lticoursevisible->coursevisible = $coursevisible;
                    $DB->update_record('lti_coursevisible', $lticoursevisible);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Returns configuration details for the tool
     *
     * @param int $typeid Basic LTI tool typeid
     *
     * @return array        Tool Configuration
     */
    public static function get_type_config($typeid) {
        global $DB;

        $query = "SELECT name, value
                FROM {lti_types_config}
               WHERE typeid = :typeid1
           UNION ALL
              SELECT 'toolurl' AS name, baseurl AS value
                FROM {lti_types}
               WHERE id = :typeid2
           UNION ALL
              SELECT 'icon' AS name, icon AS value
                FROM {lti_types}
               WHERE id = :typeid3
           UNION ALL
              SELECT 'secureicon' AS name, secureicon AS value
                FROM {lti_types}
               WHERE id = :typeid4";

        $typeconfig = array();
        $configs = $DB->get_records_sql($query,
            array('typeid1' => $typeid, 'typeid2' => $typeid, 'typeid3' => $typeid, 'typeid4' => $typeid));

        if (!empty($configs)) {
            foreach ($configs as $config) {
                $typeconfig[$config->name] = $config->value;
            }
        }

        return $typeconfig;
    }

    public static function get_type($typeid) {
        global $DB;

        return $DB->get_record('lti_types', array('id' => $typeid));
    }

    /**
     * Returns all basicLTI tools configured by the administrator
     *
     * @param int $course
     *
     * @return array
     */
    public static function filter_get_types($course) {
        global $DB;

        if (!empty($course)) {
            $where = "WHERE t.course = :course";
            $params = array('course' => $course);
        } else {
            $where = '';
            $params = array();
        }
        $query = "SELECT t.id, t.name, t.baseurl, t.state, t.toolproxyid, t.timecreated, tp.name tpname
                FROM {lti_types} t LEFT OUTER JOIN {lti_tool_proxies} tp ON t.toolproxyid = tp.id
                {$where}";
        return $DB->get_records_sql($query, $params);
    }

    /**
     * Delete a Basic LTI configuration
     *
     * @param int $id Configuration id
     */
    public static function delete_type($id) {
        global $DB;

        // We should probably just copy the launch URL to the tool instances in this case... using a single query.
        /*
        $instances = $DB->get_records('lti', array('typeid' => $id));
        foreach ($instances as $instance) {
            $instance->typeid = 0;
            $DB->update_record('lti', $instance);
        }*/

        $DB->delete_records('lti_types', array('id' => $id));
        $DB->delete_records('lti_types_config', array('typeid' => $id));
        $DB->delete_records('lti_types_categories', array('typeid' => $id));
    }

    public static function set_state_for_type($id, $state) {
        global $DB;

        $DB->update_record('lti_types', (object) array('id' => $id, 'state' => $state));
    }

    public static function update_type($type, $config) {
        global $DB, $CFG;

        self::prepare_type_for_save($type, $config);

        if (tool_helper::request_is_using_ssl() && !empty($type->secureicon)) {
            $clearcache = !isset($config->oldicon) || ($config->oldicon !== $type->secureicon);
        } else {
            $clearcache = isset($type->icon) && (!isset($config->oldicon) || ($config->oldicon !== $type->icon));
        }
        unset($config->oldicon);

        if ($DB->update_record('lti_types', $type)) {
            foreach ($config as $key => $value) {
                if (substr($key, 0, 4) == 'lti_' && !is_null($value)) {
                    $record = new \StdClass();
                    $record->typeid = $type->id;
                    $record->name = substr($key, 4);
                    $record->value = $value;
                    self::update_config($record);
                }
                if (substr($key, 0, 11) == 'ltiservice_' && !is_null($value)) {
                    $record = new \StdClass();
                    $record->typeid = $type->id;
                    $record->name = $key;
                    $record->value = $value;
                    self::update_config($record);
                }
            }
            if (isset($type->toolproxyid) && $type->ltiversion === LTI_VERSION_1P3) {
                // We need to remove the tool proxy for this tool to function under 1.3.
                $toolproxyid = $type->toolproxyid;
                $DB->delete_records('lti_tool_settings', array('toolproxyid' => $toolproxyid));
                $DB->delete_records('lti_tool_proxies', array('id' => $toolproxyid));
                $type->toolproxyid = null;
                $DB->update_record('lti_types', $type);
            }
            $DB->delete_records('lti_types_categories', ['typeid' => $type->id]);
            if (isset($config->lti_coursecategories) && !empty($config->lti_coursecategories)) {
                self::type_add_categories($type->id, $config->lti_coursecategories);
            }
            require_once($CFG->libdir . '/modinfolib.php');
            if ($clearcache) {
                $sql = "SELECT cm.id, cm.course
                      FROM {course_modules} cm
                      JOIN {modules} m ON cm.module = m.id
                      JOIN {lti} l ON l.course = cm.course
                     WHERE m.name = :name AND l.typeid = :typeid";

                $rs = $DB->get_recordset_sql($sql, ['name' => 'lti', 'typeid' => $type->id]);

                $courseids = [];
                foreach ($rs as $record) {
                    $courseids[] = $record->course;
                    \course_modinfo::purge_course_module_cache($record->course, $record->id);
                }
                $rs->close();
                $courseids = array_unique($courseids);
                foreach ($courseids as $courseid) {
                    rebuild_course_cache($courseid, false, true);
                }
            }
        }
    }

    public static function prepare_type_for_save($type, $config) {
        if (isset($config->lti_toolurl)) {
            $type->baseurl = $config->lti_toolurl;
            if (isset($config->lti_tooldomain)) {
                $type->tooldomain = $config->lti_tooldomain;
            } else {
                $type->tooldomain = tool_helper::get_domain_from_url($config->lti_toolurl);
            }
        }
        if (isset($config->lti_description)) {
            $type->description = $config->lti_description;
        }
        if (isset($config->lti_typename)) {
            $type->name = $config->lti_typename;
        }
        if (isset($config->lti_ltiversion)) {
            $type->ltiversion = $config->lti_ltiversion;
        }
        if (isset($config->lti_clientid)) {
            $type->clientid = $config->lti_clientid;
        }
        if ((!empty($type->ltiversion) && $type->ltiversion === LTI_VERSION_1P3) && empty($type->clientid)) {
            $type->clientid = registration_helper::get()->new_clientid();
        } else if (empty($type->clientid)) {
            $type->clientid = null;
        }
        if (isset($config->lti_coursevisible)) {
            $type->coursevisible = $config->lti_coursevisible;
        }

        if (isset($config->lti_icon)) {
            $type->icon = $config->lti_icon;
        }
        if (isset($config->lti_secureicon)) {
            $type->secureicon = $config->lti_secureicon;
        }

        $type->forcessl = !empty($config->lti_forcessl) ? $config->lti_forcessl : 0;
        $config->lti_forcessl = $type->forcessl;
        if (isset($config->lti_contentitem)) {
            $type->contentitem = !empty($config->lti_contentitem) ? $config->lti_contentitem : 0;
            $config->lti_contentitem = $type->contentitem;
        }
        if (isset($config->lti_toolurl_ContentItemSelectionRequest)) {
            if (!empty($config->lti_toolurl_ContentItemSelectionRequest)) {
                $type->toolurl_ContentItemSelectionRequest = $config->lti_toolurl_ContentItemSelectionRequest;
            } else {
                $type->toolurl_ContentItemSelectionRequest = '';
            }
            $config->lti_toolurl_ContentItemSelectionRequest = $type->toolurl_ContentItemSelectionRequest;
        }

        $type->timemodified = time();

        unset ($config->lti_typename);
        unset ($config->lti_toolurl);
        unset ($config->lti_description);
        unset ($config->lti_ltiversion);
        unset ($config->lti_clientid);
        unset ($config->lti_icon);
        unset ($config->lti_secureicon);
    }

    /**
     * Updates a tool configuration in the database
     *
     * @param object  $config   Tool configuration
     *
     * @return mixed Record id number
     */
    public static function update_config($config) {
        global $DB;

        $old = $DB->get_record('lti_types_config', array('typeid' => $config->typeid, 'name' => $config->name));

        if ($old) {
            $config->id = $old->id;
            $return = $DB->update_record('lti_types_config', $config);
        } else {
            $return = $DB->insert_record('lti_types_config', $config);
        }
        return $return;
    }

    /**
     * Add LTI Type course category.
     *
     * @param int $typeid
     * @param string $lticoursecategories Comma separated list of course categories.
     * @return void
     */
    public static function type_add_categories(int $typeid, string $lticoursecategories = '') : void {
        global $DB;
        $coursecategories = explode(',', $lticoursecategories);
        foreach ($coursecategories as $coursecategory) {
            $DB->insert_record('lti_types_categories', ['typeid' => $typeid, 'categoryid' => $coursecategory]);
        }
    }

    public static function add_type($type, $config) {
        global $USER, $SITE, $DB;

        self::prepare_type_for_save($type, $config);

        if (!isset($type->state)) {
            $type->state = LTI_TOOL_STATE_PENDING;
        }

        if (!isset($type->ltiversion)) {
            $type->ltiversion = LTI_VERSION_1;
        }

        if (!isset($type->timecreated)) {
            $type->timecreated = time();
        }

        if (!isset($type->createdby)) {
            $type->createdby = $USER->id;
        }

        if (!isset($type->course)) {
            $type->course = $SITE->id;
        }

        // Create a salt value to be used for signing passed data to extension services
        // The outcome service uses the service salt on the instance. This can be used
        // for communication with services not related to a specific LTI instance.
        $config->lti_servicesalt = uniqid('', true);

        $id = $DB->insert_record('lti_types', $type);

        if ($id) {
            foreach ($config as $key => $value) {
                if (!is_null($value)) {
                    if (substr($key, 0, 4) === 'lti_') {
                        $fieldname = substr($key, 4);
                    } else if (substr($key, 0, 11) !== 'ltiservice_') {
                        continue;
                    } else {
                        $fieldname = $key;
                    }

                    $record = new \StdClass();
                    $record->typeid = $id;
                    $record->name = $fieldname;
                    $record->value = $value;

                    self::add_config($record);
                }
            }
            if (isset($config->lti_coursecategories) && !empty($config->lti_coursecategories)) {
                self::type_add_categories($id, $config->lti_coursecategories);
            }
        }

        return $id;
    }

    /**
     * Add a tool configuration in the database
     *
     * @param object $config   Tool configuration
     *
     * @return int Record id number
     */
    public static function add_config($config) {
        global $DB;

        return $DB->insert_record('lti_types_config', $config);
    }

    /** get Organization ID using default if no value provided
     * @param object $typeconfig
     * @return string
     */
    public static function get_organizationid($typeconfig) {
        global $CFG;
        // Default the organizationid if not specified.
        if (empty($typeconfig['organizationid'])) {
            if (($typeconfig['organizationid_default'] ?? LTI_DEFAULT_ORGID_SITEHOST) == LTI_DEFAULT_ORGID_SITEHOST) {
                $urlparts = parse_url($CFG->wwwroot);
                return $urlparts['host'];
            } else {
                return md5(get_site_identifier());
            }
        }
        return $typeconfig['organizationid'];
    }

    /**
     * Generates some of the tool configuration based on the admin configuration details
     *
     * @param int $id
     *
     * @return stdClass Configuration details
     */
    public static function get_type_type_config($id) {
        global $DB;

        $basicltitype = $DB->get_record('lti_types', array('id' => $id));
        $config = self::get_type_config($id);

        $type = new \stdClass();

        $type->lti_typename = $basicltitype->name;

        $type->typeid = $basicltitype->id;

        $type->course = $basicltitype->course;

        $type->toolproxyid = $basicltitype->toolproxyid;

        $type->lti_toolurl = $basicltitype->baseurl;

        $type->lti_ltiversion = $basicltitype->ltiversion;

        $type->lti_clientid = $basicltitype->clientid;
        $type->lti_clientid_disabled = $type->lti_clientid;

        $type->lti_description = $basicltitype->description;

        $type->lti_parameters = $basicltitype->parameter;

        $type->lti_icon = $basicltitype->icon;

        $type->lti_secureicon = $basicltitype->secureicon;

        if (isset($config['resourcekey'])) {
            $type->lti_resourcekey = $config['resourcekey'];
        }
        if (isset($config['password'])) {
            $type->lti_password = $config['password'];
        }
        if (isset($config['publickey'])) {
            $type->lti_publickey = $config['publickey'];
        }
        if (isset($config['publickeyset'])) {
            $type->lti_publickeyset = $config['publickeyset'];
        }
        if (isset($config['keytype'])) {
            $type->lti_keytype = $config['keytype'];
        }
        if (isset($config['initiatelogin'])) {
            $type->lti_initiatelogin = $config['initiatelogin'];
        }
        if (isset($config['redirectionuris'])) {
            $type->lti_redirectionuris = $config['redirectionuris'];
        }

        if (isset($config['sendname'])) {
            $type->lti_sendname = $config['sendname'];
        }
        if (isset($config['instructorchoicesendname'])) {
            $type->lti_instructorchoicesendname = $config['instructorchoicesendname'];
        }
        if (isset($config['sendemailaddr'])) {
            $type->lti_sendemailaddr = $config['sendemailaddr'];
        }
        if (isset($config['instructorchoicesendemailaddr'])) {
            $type->lti_instructorchoicesendemailaddr = $config['instructorchoicesendemailaddr'];
        }
        if (isset($config['acceptgrades'])) {
            $type->lti_acceptgrades = $config['acceptgrades'];
        }
        if (isset($config['instructorchoiceacceptgrades'])) {
            $type->lti_instructorchoiceacceptgrades = $config['instructorchoiceacceptgrades'];
        }
        if (isset($config['allowroster'])) {
            $type->lti_allowroster = $config['allowroster'];
        }
        if (isset($config['instructorchoiceallowroster'])) {
            $type->lti_instructorchoiceallowroster = $config['instructorchoiceallowroster'];
        }

        if (isset($config['customparameters'])) {
            $type->lti_customparameters = $config['customparameters'];
        }

        if (isset($config['forcessl'])) {
            $type->lti_forcessl = $config['forcessl'];
        }

        if (isset($config['organizationid_default'])) {
            $type->lti_organizationid_default = $config['organizationid_default'];
        } else {
            // Tool was configured before this option was available and the default then was host.
            $type->lti_organizationid_default = LTI_DEFAULT_ORGID_SITEHOST;
        }
        if (isset($config['organizationid'])) {
            $type->lti_organizationid = $config['organizationid'];
        }
        if (isset($config['organizationurl'])) {
            $type->lti_organizationurl = $config['organizationurl'];
        }
        if (isset($config['organizationdescr'])) {
            $type->lti_organizationdescr = $config['organizationdescr'];
        }
        if (isset($config['launchcontainer'])) {
            $type->lti_launchcontainer = $config['launchcontainer'];
        }

        if (isset($config['coursevisible'])) {
            $type->lti_coursevisible = $config['coursevisible'];
        }

        if (isset($config['contentitem'])) {
            $type->lti_contentitem = $config['contentitem'];
        }

        if (isset($config['toolurl_ContentItemSelectionRequest'])) {
            $type->lti_toolurl_ContentItemSelectionRequest = $config['toolurl_ContentItemSelectionRequest'];
        }

        if (isset($config['debuglaunch'])) {
            $type->lti_debuglaunch = $config['debuglaunch'];
        }

        if (isset($config['module_class_type'])) {
            $type->lti_module_class_type = $config['module_class_type'];
        }

        // Get the parameters from the LTI services.
        foreach ($config as $name => $value) {
            if (strpos($name, 'ltiservice_') === 0) {
                $type->{$name} = $config[$name];
            }
        }

        return $type;
    }

    /**
     * Get the total number of LTI tool types and tool proxies.
     *
     * @param bool $orphanedonly If true, only count orphaned proxies.
     * @param int $toolproxyid If not 0, only count tool types that have this tool proxy id.
     * @return int Count of tools.
     */
    public static function get_lti_types_and_proxies_count(bool $orphanedonly = false, int $toolproxyid = 0): int {
        global $DB;

        $typessql = "SELECT count(*)
                   FROM {lti_types}";
        $typesparams = [];
        if (!empty($toolproxyid)) {
            $typessql .= " WHERE toolproxyid = :toolproxyid";
            $typesparams['toolproxyid'] = $toolproxyid;
        }

        $proxiessql = tool_helper::get_tool_proxy_sql($orphanedonly, true);

        $countsql = "SELECT ($typessql) + ($proxiessql) as total" . $DB->sql_null_from_clause();

        return $DB->count_records_sql($countsql, $typesparams);
    }

    /**
     * Get both LTI tool proxies and tool types.
     *
     * If limit and offset are not zero, a subset of the tools will be returned. Tool proxies will be counted before tool
     * types.
     * For example: If 10 tool proxies and 10 tool types exist, and the limit is set to 15, then 10 proxies and 5 types
     * will be returned.
     *
     * @param int $limit Maximum number of tools returned.
     * @param int $offset Do not return tools before offset index.
     * @param bool $orphanedonly If true, only return orphaned proxies.
     * @param int $toolproxyid If not 0, only return tool types that have this tool proxy id.
     * @return array list(proxies[], types[]) List containing array of tool proxies and array of tool types.
     */
    public static function get_lti_types_and_proxies(int $limit = 0, int $offset = 0, bool $orphanedonly = false, int $toolproxyid = 0): array {
        global $DB;

        if ($orphanedonly) {
            $orphanedproxiessql = tool_helper::get_tool_proxy_sql($orphanedonly, false);
            $countsql = tool_helper::get_tool_proxy_sql($orphanedonly, true);
            $proxies  = $DB->get_records_sql($orphanedproxiessql, null, $offset, $limit);
            $totalproxiescount = $DB->count_records_sql($countsql);
        } else {
            $proxies = $DB->get_records('lti_tool_proxies', null, 'name ASC, state DESC, timemodified DESC',
                '*', $offset, $limit);
            $totalproxiescount = $DB->count_records('lti_tool_proxies');
        }

        // Find new offset and limit for tool types after getting proxies and set up query.
        $typesoffset = max($offset - $totalproxiescount, 0); // Set to 0 if negative.
        $typeslimit = max($limit - count($proxies), 0); // Set to 0 if negative.
        $typesparams = [];
        if (!empty($toolproxyid)) {
            $typesparams['toolproxyid'] = $toolproxyid;
        }

        $types = $DB->get_records('lti_types', $typesparams, 'name ASC, state DESC, timemodified DESC',
            '*', $typesoffset, $typeslimit);

        return [$proxies, array_map('serialise_tool_type', $types)];
    }

    /**
     * Returns information on the current state of the tool type
     *
     * @param stdClass $type The tool type
     *
     * @return array An array with a text description of the state, and boolean for whether it is in each state:
     * pending, configured, rejected, unknown
     */
    public static function get_tool_type_state_info(stdClass $type) {
        $isconfigured = false;
        $ispending = false;
        $isrejected = false;
        $isunknown = false;
        switch ($type->state) {
            case LTI_TOOL_STATE_CONFIGURED:
                $state = get_string('active', 'ltix');
                $isconfigured = true;
                break;
            case LTI_TOOL_STATE_PENDING:
                $state = get_string('pending', 'ltix');
                $ispending = true;
                break;
            case LTI_TOOL_STATE_REJECTED:
                $state = get_string('rejected', 'ltix');
                $isrejected = true;
                break;
            default:
                $state = get_string('unknownstate', 'ltix');
                $isunknown = true;
                break;
        }

        return array(
            'text' => $state,
            'pending' => $ispending,
            'configured' => $isconfigured,
            'rejected' => $isrejected,
            'unknown' => $isunknown
        );
    }

    /**
     * Returns a summary of each LTI capability this tool type requires in plain language
     *
     * @param stdClass $type The tool type
     *
     * @return array An array of text descriptions of each of the capabilities this tool type requires
     */
    public static function get_tool_type_capability_groups($type) {
        $capabilities = tool_helper::get_enabled_capabilities($type);
        $groups = array();
        $hascourse = false;
        $hasactivities = false;
        $hasuseraccount = false;
        $hasuserpersonal = false;

        foreach ($capabilities as $capability) {
            // Bail out early if we've already found all groups.
            if (count($groups) >= 4) {
                continue;
            }

            if (!$hascourse && preg_match('/^CourseSection/', $capability)) {
                $hascourse = true;
                $groups[] = get_string('courseinformation', 'core_ltix');
            } else if (!$hasactivities && preg_match('/^ResourceLink/', $capability)) {
                $hasactivities = true;
                $groups[] = get_string('courseactivitiesorresources', 'core_ltix');
            } else if (!$hasuseraccount && preg_match('/^User/', $capability) || preg_match('/^Membership/', $capability)) {
                $hasuseraccount = true;
                $groups[] = get_string('useraccountinformation', 'core_ltix');
            } else if (!$hasuserpersonal && preg_match('/^Person/', $capability)) {
                $hasuserpersonal = true;
                $groups[] = get_string('userpersonalinformation', 'core_ltix');
            }
        }

        return $groups;
    }

    /**
     * Create a new access token.
     *
     * @param int $typeid Tool type ID
     * @param string[] $scopes Scopes permitted for new token
     *
     * @return stdClass Access token
     */
    public static function new_access_token($typeid, $scopes) {
        global $DB;

        // Make sure the token doesn't exist (even if it should be almost impossible with the random generation).
        $numtries = 0;
        do {
            $numtries ++;
            $generatedtoken = md5(uniqid(rand(), 1));
            if ($numtries > 5) {
                throw new moodle_exception('Failed to generate LTI access token');
            }
        } while ($DB->record_exists('lti_access_tokens', array('token' => $generatedtoken)));
        $newtoken = new stdClass();
        $newtoken->typeid = $typeid;
        $newtoken->scope = json_encode(array_values($scopes));
        $newtoken->token = $generatedtoken;

        $newtoken->timecreated = time();
        $newtoken->validuntil = $newtoken->timecreated + LTI_ACCESS_TOKEN_LIFE;
        $newtoken->lastaccess = null;

        $DB->insert_record('lti_access_tokens', $newtoken);

        return $newtoken;
    }

    /**
     * Allows you to load settings for an external tool type from an IMS cartridge.
     *
     * @param  string   $url     The URL to the cartridge
     * @param  stdClass $type    The tool type object to be filled in
     * @throws moodle_exception if the cartridge could not be loaded correctly
     */
    public static function load_type_from_cartridge($url, $type) {
        $toolinfo = tool_helper::load_cartridge($url,
            array(
                "title" => "lti_typename",
                "launch_url" => "lti_toolurl",
                "description" => "lti_description",
                "icon" => "lti_icon",
                "secure_icon" => "lti_secureicon"
            ),
            array(
                "icon_url" => "lti_extension_icon",
                "secure_icon_url" => "lti_extension_secureicon"
            )
        );
        // If an activity name exists, unset the cartridge name so we don't override it.
        if (isset($type->lti_typename)) {
            unset($toolinfo['lti_typename']);
        }

        // Always prefer cartridge core icons first, then, if none are found, look at the extension icons.
        if (empty($toolinfo['lti_icon']) && !empty($toolinfo['lti_extension_icon'])) {
            $toolinfo['lti_icon'] = $toolinfo['lti_extension_icon'];
        }
        unset($toolinfo['lti_extension_icon']);

        if (empty($toolinfo['lti_secureicon']) && !empty($toolinfo['lti_extension_secureicon'])) {
            $toolinfo['lti_secureicon'] = $toolinfo['lti_extension_secureicon'];
        }
        unset($toolinfo['lti_extension_secureicon']);

        // Ensure Custom icons aren't overridden by cartridge params.
        if (!empty($type->lti_icon)) {
            unset($toolinfo['lti_icon']);
        }

        if (!empty($type->lti_secureicon)) {
            unset($toolinfo['lti_secureicon']);
        }

        foreach ($toolinfo as $property => $value) {
            $type->$property = $value;
        }
    }

    /**
     * Loads the cartridge information into the tool type, if the launch url is for a cartridge file
     *
     * @param stdClass $type The tool type object to be filled in
     */
    public static function load_type_if_cartridge($type) {
        if (!empty($type->lti_toolurl) && \core_ltix\tool_helper::is_cartridge($type->lti_toolurl)) {
            self::load_type_from_cartridge($type->lti_toolurl, $type);
        }
    }

}
