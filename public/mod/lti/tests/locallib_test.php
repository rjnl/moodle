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
 * This file contains unit tests for (some of) lti/locallib.php
 *
 * @package    mod_lti
 * @category   phpunit
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Charles Severance csev@unmich.edu
 * @author     Marc Alier (marc.alier@upc.edu)
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_lti;

use mod_lti_external;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/mod/lti/locallib.php');
require_once($CFG->dirroot . '/mod/lti/servicelib.php');

/**
 * Local library tests
 *
 * @package    mod_lti
 * @copyright  Copyright (c) 2012 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class locallib_test extends \externallib_advanced_testcase {

    /**
     * @covers core_ltix\helper::get_launch_data()
     *
     * Test for_user is passed as parameter when specified.
     */
    public function test_lti_get_launch_data_with_for_user(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $config = new \stdClass();
        $config->lti_organizationid = '';
        $course = $this->getDataGenerator()->create_course();
        $type = $this->create_type($config);
        $link = $this->create_instance($type, $course);
        $launchdata = \core_ltix\helper::get_launch_data($link, '', '', 345);
        $this->assertEquals($launchdata[1]['lti_message_type'], 'basic-lti-launch-request');
        $this->assertEquals($launchdata[1]['for_user_id'], 345);
    }

    /**
     * Test default orgid is host if not specified in config (tool installed in earlier version of Moodle).
     */
    public function test_lti_get_launch_data_default_organizationid_unset_usehost(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $config = new \stdClass();
        $config->lti_organizationid = '';
        $course = $this->getDataGenerator()->create_course();
        $type = $this->create_type($config);
        $link = $this->create_instance($type, $course);
        $launchdata = \core_ltix\helper::get_launch_data($link);
        $this->assertEquals($launchdata[1]['tool_consumer_instance_guid'], 'www.example.com');
    }

    /**
     * Test default org id is set to host when config is usehost.
     */
    public function test_lti_get_launch_data_default_organizationid_set_usehost(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $config = new \stdClass();
        $config->lti_organizationid = '';
        $config->lti_organizationid_default = LTI_DEFAULT_ORGID_SITEHOST;
        $course = $this->getDataGenerator()->create_course();
        $type = $this->create_type($config);
        $link = $this->create_instance($type, $course);
        $launchdata = \core_ltix\helper::get_launch_data($link);
        $this->assertEquals($launchdata[1]['tool_consumer_instance_guid'], 'www.example.com');
    }

    /**
     * Test default org id is set to site id when config is usesiteid.
     */
    public function test_lti_get_launch_data_default_organizationid_set_usesiteid(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $config = new \stdClass();
        $config->lti_organizationid = '';
        $config->lti_organizationid_default = LTI_DEFAULT_ORGID_SITEID;
        $course = $this->getDataGenerator()->create_course();
        $type = $this->create_type($config);
        $link = $this->create_instance($type, $course);
        $launchdata = \core_ltix\helper::get_launch_data($link);
        $this->assertEquals($launchdata[1]['tool_consumer_instance_guid'], md5(get_site_identifier()));
    }

    /**
     * Test orgid can be overridden in which case default is ignored.
     */
    public function test_lti_get_launch_data_default_organizationid_orgid_override(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $config = new \stdClass();
        $config->lti_organizationid = 'overridden!';
        $config->lti_organizationid_default = LTI_DEFAULT_ORGID_SITEID;
        $course = $this->getDataGenerator()->create_course();
        $type = $this->create_type($config);
        $link = $this->create_instance($type, $course);
        $launchdata = \core_ltix\helper::get_launch_data($link);
        $this->assertEquals($launchdata[1]['tool_consumer_instance_guid'], 'overridden!');
    }

    /**
     * Create an LTI Tool.
     *
     * @param object $config tool config.
     *
     * @return object tool.
     */
    private function create_type(object $config) {
        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->clientid = "Test client ID";
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $configbase = new \stdClass();
        $configbase->lti_acceptgrades = LTI_SETTING_NEVER;
        $configbase->lti_sendname = LTI_SETTING_NEVER;
        $configbase->lti_sendemailaddr = LTI_SETTING_NEVER;
        $mergedconfig = (object) array_merge( (array) $configbase, (array) $config);
        $typeid = \core_ltix\helper::add_type($type, $mergedconfig);
        return \core_ltix\helper::get_type($typeid);
    }

    /**
     * Create an LTI Instance for the tool in a given course.
     *
     * @param object $type tool for which an instance should be added.
     * @param object $course course where the instance should be added.
     *
     * @return object instance.
     */
    private function create_instance(object $type, object $course) {
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_lti');
        return $generator->create_instance(array('course' => $course->id,
                  'toolurl' => $type->baseurl,
                  'typeid' => $type->id
                  ), array());
    }

    /**
     * Test for lti_get_lti_types_by_course.
     *
     * Note: This includes verification of the broken legacy behaviour in which the inclusion of course and site tools could be
     * controlled independently, based on the capabilities 'mod/lti:addmanualinstance' (to include course tools) and
     * 'mod/lti:addpreconfiguredinstance' (to include site tools). This behaviour is deprecated in 4.3 and all preconfigured tools
     * are controlled by the single capability 'mod/lti:addpreconfiguredinstance'.
     *
     * @covers ::lti_get_lti_types_by_course()
     * @return void
     */
    public function test_lti_get_lti_types_by_course(): void {
        $this->resetAfterTest();

        global $DB;
        $coursecat1 = $this->getDataGenerator()->create_category();
        $coursecat2 = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $coursecat1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $coursecat2->id]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $teacher2 = $this->getDataGenerator()->create_and_enrol($course2, 'editingteacher');

        // Create the following tool types for testing:
        // - Site tool configured as "Do not show" (LTI_COURSEVISIBLE_NO).
        // - Site tool configured as "Show as a preconfigured tool only" (LTI_COURSEVISIBLE_PRECONFIGURED).
        // - Site tool configured as "Show as a preconfigured tool and in the activity chooser" (LTI_COURSEVISIBLE_ACTIVITYCHOOSER).
        // - Course tool which, by default, is configured as LTI_COURSEVISIBLE_ACTIVITYCHOOSER).
        // - Site tool configured to "Show as a preconfigured tool and in the activity chooser" but restricted to a category.

        /** @var \mod_lti_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('mod_lti');
        $ltigenerator->create_tool_types([
            'name' => 'site tool do not show',
            'baseurl' => 'http://example.com/tool/1',
            'coursevisible' => LTI_COURSEVISIBLE_NO,
            'state' => LTI_TOOL_STATE_CONFIGURED
        ]);
        $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured only',
            'baseurl' => 'http://example.com/tool/2',
            'coursevisible' => LTI_COURSEVISIBLE_PRECONFIGURED,
            'state' => LTI_TOOL_STATE_CONFIGURED
        ]);
        $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured and activity chooser',
            'baseurl' => 'http://example.com/tool/3',
            'coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER,
            'state' => LTI_TOOL_STATE_CONFIGURED
        ]);
        $ltigenerator->create_course_tool_types([
            'name' => 'course tool preconfigured and activity chooser',
            'baseurl' => 'http://example.com/tool/4',
            'course' => $course->id
        ]);
        $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured and activity chooser, restricted to category 2',
            'baseurl' => 'http://example.com/tool/5',
            'coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER,
            'state' => LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat2->id
        ]);

        $this->setUser($teacher); // Important: this deprecated method depends on the global user for cap checks.

        // Request using the default 'coursevisible' param will include all tools except the one configured as "Do not show".
        $coursetooltypes = lti_get_lti_types_by_course($course->id);
        $this->assertDebuggingCalled();
        $this->assertCount(3, $coursetooltypes);
        $this->assertEmpty(array_diff(
            ['http://example.com/tool/2', 'http://example.com/tool/3', 'http://example.com/tool/4'],
            array_column($coursetooltypes, 'baseurl')
        ));

        // Request for only those tools configured to show in the activity chooser for the teacher.
        $coursetooltypes = lti_get_lti_types_by_course($course->id, [LTI_COURSEVISIBLE_ACTIVITYCHOOSER]);
        $this->assertDebuggingCalled();
        $this->assertCount(2, $coursetooltypes);
        $this->assertEmpty(array_diff(
            ['http://example.com/tool/3', 'http://example.com/tool/4'],
            array_column($coursetooltypes, 'baseurl')
        ));

        // Request for only those tools configured to show as a preconfigured tool for the teacher.
        $coursetooltypes = lti_get_lti_types_by_course($course->id, [LTI_COURSEVISIBLE_PRECONFIGURED]);
        $this->assertDebuggingCalled();
        $this->assertCount(1, $coursetooltypes);
        $this->assertEmpty(array_diff(
            ['http://example.com/tool/2'],
            array_column($coursetooltypes, 'baseurl')
        ));

        // Request for teacher2 in course2 (course category 2).
        $this->setUser($teacher2);
        $coursetooltypes = lti_get_lti_types_by_course($course2->id);
        $this->assertDebuggingCalled();
        $this->assertCount(3, $coursetooltypes);
        $this->assertEmpty(array_diff(
            ['http://example.com/tool/2', 'http://example.com/tool/3', 'http://example.com/tool/5'],
            array_column($coursetooltypes, 'baseurl')
        ));

        // Request for a teacher who cannot use preconfigured tools in the course.
        // No tools are available.
        $this->setUser($teacher);
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        assign_capability('mod/lti:addpreconfiguredinstance', CAP_PROHIBIT, $teacherrole->id,
            \core\context\course::instance($course->id));
        $coursetooltypes = lti_get_lti_types_by_course($course->id);
        $this->assertDebuggingCalled();
        $this->assertCount(0, $coursetooltypes);
        $this->unassignUserCapability('mod/lti:addpreconfiguredinstance', (\core\context\course::instance($course->id))->id,
            $teacherrole->id);
    }
}
