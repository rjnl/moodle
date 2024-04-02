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

namespace core_ltix;

use lti_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/ltix/constants.php');
require_once($CFG->dirroot . '/ltix/tests/lti_testcase.php');

/**
 * Types helper tests.
 *
 * @package    core_ltix
 * @copyright  2023 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_ltix\types_helper
 */
class types_helper_test extends lti_testcase {

    /**
     * Test fetching tool types for a given course and user.
     *
     * @covers ::get_lti_types_by_course
     * @return void.
     */
    public function test_get_lti_types_by_course(): void {
        $this->resetAfterTest();

        $coursecat1 = $this->getDataGenerator()->create_category();
        $coursecat2 = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $coursecat1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $coursecat2->id]);

        // Create the following tool types for testing:
        // - Site tool configured as "Do not show" (LTI_COURSEVISIBLE_NO).
        // - Site tool configured as "Show as a preconfigured tool only" (LTI_COURSEVISIBLE_PRECONFIGURED).
        // - Site tool configured as "Show as a preconfigured tool and in the activity chooser" (LTI_COURSEVISIBLE_ACTIVITYCHOOSER).
        // - Course tool which, by default, is configured as LTI_COURSEVISIBLE_ACTIVITYCHOOSER).
        // - Site tool configured to "Show as a preconfigured tool and in the activity chooser" but restricted to a category.

        /** @var \core_ltix_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');
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

        // Request using the default 'coursevisible' param will include all tools except the one configured as "Do not show" and
        // the tool restricted to category 2.
        $coursetooltypes = types_helper::get_lti_types_by_course($course->id);
        $this->assertCount(3, $coursetooltypes);
        $expected = [
            'http://example.com/tool/2',
            'http://example.com/tool/3',
            'http://example.com/tool/4',
        ];
        sort($expected);
        $actual = array_column($coursetooltypes, 'baseurl');
        sort($actual);
        $this->assertEquals($expected, $actual);

        // Request for only those tools configured to show in the activity chooser.
        $coursetooltypes = types_helper::get_lti_types_by_course($course->id, [LTI_COURSEVISIBLE_ACTIVITYCHOOSER]);
        $this->assertCount(2, $coursetooltypes);
        $expected = [
            'http://example.com/tool/3',
            'http://example.com/tool/4',
        ];
        sort($expected);
        $actual = array_column($coursetooltypes, 'baseurl');
        sort($actual);
        $this->assertEquals($expected, $actual);

        // Request for only those tools configured to show as a preconfigured tool.
        $coursetooltypes = types_helper::get_lti_types_by_course($course->id, [LTI_COURSEVISIBLE_PRECONFIGURED]);
        $this->assertCount(1, $coursetooltypes);
        $expected = [
            'http://example.com/tool/2',
        ];
        $actual = array_column($coursetooltypes, 'baseurl');
        $this->assertEquals($expected, $actual);

        // Request for course2 (course category 2).
        $coursetooltypes = types_helper::get_lti_types_by_course($course2->id);
        $this->assertCount(3, $coursetooltypes);
        $expected = [
            'http://example.com/tool/2',
            'http://example.com/tool/3',
            'http://example.com/tool/5',
        ];
        sort($expected);
        $actual = array_column($coursetooltypes, 'baseurl');
        sort($actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test fetching tool types for a given course and user.
     *
     * @covers ::override_type_showinactivitychooser
     * @return void.
     */
    public function test_override_type_showinactivitychooser(): void {
        $this->resetAfterTest();

        global $DB;
        $coursecat1 = $this->getDataGenerator()->create_category();
        $coursecat2 = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $coursecat1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $coursecat2->id]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $teacher2 = $this->getDataGenerator()->create_and_enrol($course2, 'editingteacher');
        $context =  \core\context\course::instance($course->id);

        $this->setUser($teacher);

        /*
            Create the following tool types for testing:
            | tooltype | coursevisible                     | restrictedtocategory |
            | site     | LTI_COURSEVISIBLE_NO              |                      |
            | site     | LTI_COURSEVISIBLE_PRECONFIGURED   |                      |
            | site     | LTI_COURSEVISIBLE_ACTIVITYCHOOSER | yes                  |
            | site     | LTI_COURSEVISIBLE_ACTIVITYCHOOSER | yes                  |
            | course   | LTI_COURSEVISIBLE_ACTIVITYCHOOSER |                      |
        */

        /** @var \core_ltix_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');
        $tool1id = $ltigenerator->create_tool_types([
            'name' => 'site tool do not show',
            'baseurl' => 'http://example.com/tool/1',
            'coursevisible' => LTI_COURSEVISIBLE_NO,
            'state' => LTI_TOOL_STATE_CONFIGURED
        ]);
        $tool2id = $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured only',
            'baseurl' => 'http://example.com/tool/2',
            'coursevisible' => LTI_COURSEVISIBLE_PRECONFIGURED,
            'state' => LTI_TOOL_STATE_CONFIGURED
        ]);
        $tool3id = $ltigenerator->create_course_tool_types([
            'name' => 'course tool preconfigured and activity chooser',
            'baseurl' => 'http://example.com/tool/3',
            'course' => $course->id
        ]);
        $tool4id = $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured and activity chooser, restricted to category 2',
            'baseurl' => 'http://example.com/tool/4',
            'coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER,
            'state' => LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat2->id
        ]);
        $tool5id = $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured and activity chooser, restricted to category 1',
            'baseurl' => 'http://example.com/tool/5',
            'coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER,
            'state' => LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat1->id
        ]);

        // LTI_COURSEVISIBLE_NO can't be updated.
        $result = types_helper::override_type_showinactivitychooser($tool1id, $course->id, $context, true);
        $this->assertFalse($result);

        // Tool not exist.
        $result = types_helper::override_type_showinactivitychooser($tool5id + 1, $course->id, $context, false);
        $this->assertFalse($result);

        $result = types_helper::override_type_showinactivitychooser($tool2id, $course->id, $context, true);
        $this->assertTrue($result);
        $coursevisibleoverriden = $DB->get_field('lti_coursevisible', 'coursevisible',
            ['typeid' => $tool2id, 'courseid' => $course->id]);
        $this->assertEquals(LTI_COURSEVISIBLE_ACTIVITYCHOOSER, $coursevisibleoverriden);

        $result = types_helper::override_type_showinactivitychooser($tool3id, $course->id, $context, false);
        $this->assertTrue($result);
        $coursevisible = $DB->get_field('lti_types', 'coursevisible', ['id' => $tool3id]);
        $this->assertEquals(LTI_COURSEVISIBLE_PRECONFIGURED, $coursevisible);

        // Restricted category no allowed.
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('You are not allowed to change this setting for this tool.');
        types_helper::override_type_showinactivitychooser($tool4id, $course->id, $context, false);

        // Restricted category allowed.
        $result = types_helper::override_type_showinactivitychooser($tool5id, $course->id, $context, false);
        $this->assertTrue($result);
        $coursevisibleoverriden = $DB->get_field('lti_coursevisible', 'coursevisible',
            ['typeid' => $tool5id, 'courseid' => $course->id]);
        $this->assertEquals(LTI_COURSEVISIBLE_PRECONFIGURED, $coursevisibleoverriden);

        $this->setUser($teacher2);
        $this->expectException(\required_capability_exception::class);
        types_helper::override_type_showinactivitychooser($tool5id, $course->id, $context, false);
    }

    /**
     * Tests prepare_type_for_save's handling of the "Force SSL" configuration.
     */
    public function test_prepare_type_for_save_forcessl(): void {
        $type = new \stdClass();
        $config = new \stdClass();

        // Try when the forcessl config property is not set.
        types_helper::prepare_type_for_save($type, $config);
        $this->assertObjectHasAttribute('lti_forcessl', $config);
        $this->assertEquals(0, $config->lti_forcessl);
        $this->assertEquals(0, $type->forcessl);

        // Try when forcessl config property is set.
        $config->lti_forcessl = 1;
        types_helper::prepare_type_for_save($type, $config);
        $this->assertObjectHasAttribute('lti_forcessl', $config);
        $this->assertEquals(1, $config->lti_forcessl);
        $this->assertEquals(1, $type->forcessl);

        // Try when forcessl config property is set to 0.
        $config->lti_forcessl = 0;
        types_helper::prepare_type_for_save($type, $config);
        $this->assertObjectHasAttribute('lti_forcessl', $config);
        $this->assertEquals(0, $config->lti_forcessl);
        $this->assertEquals(0, $type->forcessl);
    }

    /**
     * Tests load_type_from_cartridge and lti_load_type_if_cartridge
     */
    public function test_load_type_from_cartridge(): void {
        $type = new \stdClass();
        $type->lti_toolurl = $this->getExternalTestFileUrl('/ims_cartridge_basic_lti_link.xml');

        types_helper::load_type_if_cartridge($type);

        $this->assertEquals('Example tool', $type->lti_typename);
        $this->assertEquals('Example tool description', $type->lti_description);
        $this->assertEquals('http://www.example.com/lti/provider.php', $type->lti_toolurl);
        $this->assertEquals('http://download.moodle.org/unittest/test.jpg', $type->lti_icon);
        $this->assertEquals('https://download.moodle.org/unittest/test.jpg', $type->lti_secureicon);
    }

    /**
     * Test get_lti_types_and_proxies with no limit or offset.
     */
    public function test_get_lti_types_and_proxies_with_no_limit(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->generate_tool_types_and_proxies(10);
        list($proxies, $types) = types_helper::get_lti_types_and_proxies();

        $this->assertCount(10, $proxies);
        $this->assertCount(10, $types);
    }

    /**
     * Test get_lti_types_and_proxies with limits.
     */
    public function test_get_lti_types_and_proxies_with_limit(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->generate_tool_types_and_proxies(10);

        // Get the middle 10 data sets (of 20 total).
        list($proxies, $types) = types_helper::get_lti_types_and_proxies(10, 5);

        $this->assertCount(5, $proxies);
        $this->assertCount(5, $types);

        // Get the last 5 data sets with large limit (of 20 total).
        list($proxies, $types) = types_helper::get_lti_types_and_proxies(50, 15);

        $this->assertCount(0, $proxies);
        $this->assertCount(5, $types);

        // Get the last 13 data sets with large limit (of 20 total).
        list($proxies, $types) = types_helper::get_lti_types_and_proxies(50, 7);

        $this->assertCount(3, $proxies);
        $this->assertCount(10, $types);
    }

    /**
     * Test get_lti_types_and_proxies with limits and only fetching orphaned proxies.
     */
    public function test_get_lti_types_and_proxies_with_limit_and_orphaned_proxies(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->generate_tool_types_and_proxies(10, 5);

        // Get the first 10 data sets (of 15 total).
        list($proxies, $types) = types_helper::get_lti_types_and_proxies(10, 0, true);

        $this->assertCount(5, $proxies);
        $this->assertCount(5, $types);

        // Get the middle 10 data sets with large limit (of 15 total).
        list($proxies, $types) = types_helper::get_lti_types_and_proxies(10, 2, true);

        $this->assertCount(3, $proxies);
        $this->assertCount(7, $types);

        // Get the last 5 data sets with large limit (of 15 total).
        list($proxies, $types) = types_helper::get_lti_types_and_proxies(50, 10, true);

        $this->assertCount(0, $proxies);
        $this->assertCount(5, $types);
    }

    /**
     * Test get_lti_types_and_proxies_count.
     */
    public function test_get_lti_types_and_proxies_count_with_no_filters(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->generate_tool_types_and_proxies(10, 5);

        $totalcount = types_helper::get_lti_types_and_proxies_count();
        $this->assertEquals(25, $totalcount); // 10 types, 15 proxies.
    }

    /**
     * Test get_lti_types_and_proxies_count only counting orphaned proxies.
     */
    public function test_get_lti_types_and_proxies_count_with_only_orphaned_proxies(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->generate_tool_types_and_proxies(10, 5);

        $orphanedcount = types_helper::get_lti_types_and_proxies_count(true);
        $this->assertEquals(15, $orphanedcount); // 10 types, 5 proxies.
    }

    /**
     * Test get_lti_types_and_proxies_count only matching tool type with toolproxyid.
     */
    public function test_get_lti_types_and_proxies_count_type_with_proxyid(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        ['proxies' => $proxies, 'types' => $types] = $this->generate_tool_types_and_proxies(10, 5);

        $countwithproxyid = types_helper::get_lti_types_and_proxies_count(false, $proxies[0]->id);
        $this->assertEquals(16, $countwithproxyid); // 1 type, 15 proxies.
    }

}
