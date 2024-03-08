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

namespace mod_lti\external;

use core_external\external_api;
use core_ltix\helper;
use externallib_advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * PHPUnit tests for toggle_showinactivitychooser external function.
 *
 * @package    mod_lti
 * @copyright  2023 Ilya Tregubov <ilya.a.tregubov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_lti\external\toggle_showinactivitychooser
 */
class toggle_showinactivitychooser_test extends externallib_advanced_testcase {

    /**
     * Test toggle_showinactivitychooser for course tool.
     * @covers ::execute
     */
    public function test_toggle_showinactivitychooser_course_tool(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($editingteacher);

        $typeid = helper::add_type(
            (object) [
                'state' => LTI_TOOL_STATE_CONFIGURED,
                'course' => $course->id,
                'coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER
            ],
            (object) [
                'lti_typename' => "My course tool",
                'lti_toolurl' => 'http://example.com',
                'lti_ltiversion' => 'LTI-1p0',
                'lti_coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER
            ]
        );
        $result = toggle_showinactivitychooser::execute($typeid, $course->id, false);
        $this->assertDebuggingCalled();
        $result = external_api::clean_returnvalue(toggle_showinactivitychooser::execute_returns(), $result);
        $this->assertTrue($result);

        $sql = "SELECT lt.coursevisible coursevisible
                  FROM {lti_types} lt
                 WHERE lt.id = ?";
        $actual = $DB->get_record_sql($sql, [$typeid]);
        $this->assertEquals(LTI_COURSEVISIBLE_PRECONFIGURED, $actual->coursevisible);

        $result = toggle_showinactivitychooser::execute($typeid, $course->id, true);
        $this->assertDebuggingCalled();
        $result = external_api::clean_returnvalue(toggle_showinactivitychooser::execute_returns(), $result);
        $this->assertTrue($result);
        $actual = $DB->get_record_sql($sql, [$typeid]);
        $this->assertEquals(LTI_COURSEVISIBLE_ACTIVITYCHOOSER, $actual->coursevisible);
    }

    /**
     * Test toggle_showinactivitychooser for site tool.
     * @covers ::execute
     */
    public function test_toggle_showinactivitychooser_site_tool(): void {
        global $DB;

        $this->resetAfterTest();

        $coursecat1 = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $coursecat1->id]);

        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($editingteacher);

        $typeid = $this->getDataGenerator()->get_plugin_generator('mod_lti')->create_tool_types([
            'name' => 'site tool preconfigured and activity chooser, restricted to category 1',
            'baseurl' => 'http://example.com/tool/1',
            'coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER,
            'state' => LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat1->id
        ]);

        $result = toggle_showinactivitychooser::execute($typeid, $course->id, false);
        $this->assertDebuggingCalled();
        $result = external_api::clean_returnvalue(toggle_showinactivitychooser::execute_returns(), $result);
        $this->assertTrue($result);

        $sql = "SELECT lt.coursevisible coursevisible1, lc.coursevisible AS coursevisible2
                  FROM {lti_types} lt
             LEFT JOIN {lti_coursevisible} lc ON lt.id = lc.typeid
                 WHERE lt.id = ?
                   AND lc.courseid = ?";
        $actual = $DB->get_record_sql($sql, [$typeid, $course->id]);
        $this->assertEquals(LTI_COURSEVISIBLE_ACTIVITYCHOOSER, $actual->coursevisible1);
        $this->assertEquals(LTI_COURSEVISIBLE_PRECONFIGURED, $actual->coursevisible2);

        $result = toggle_showinactivitychooser::execute($typeid, $course->id, true);
        $this->assertDebuggingCalled();
        $result = external_api::clean_returnvalue(toggle_showinactivitychooser::execute_returns(), $result);
        $this->assertTrue($result);

        $actual = $DB->get_record_sql($sql, [$typeid, $course->id]);
        $this->assertEquals(LTI_COURSEVISIBLE_ACTIVITYCHOOSER, $actual->coursevisible1);
        $this->assertEquals(LTI_COURSEVISIBLE_ACTIVITYCHOOSER, $actual->coursevisible2);
    }

    /**
     * Test toggle_showinactivitychooser for tools restricted to course categories
     *
     * @covers ::execute
     * @return void
     */
    public function test_toggle_showinactivitychooser_course_category_restricted_tools(): void {
        global $DB;
        $this->resetAfterTest();

        $coursecat1 = $this->getDataGenerator()->create_category();
        $coursecat2 = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $coursecat1->id]);
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($editingteacher);

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('mod_lti');

        $tool1id = $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured and activity chooser, restricted to category 1',
            'baseurl' => 'http://example.com/tool/1',
            'coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER,
            'state' => LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat1->id
        ]);
        $tool2id = $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured and activity chooser, restricted to category 2',
            'baseurl' => 'http://example.com/tool/1',
            'coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER,
            'state' => LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat2->id
        ]);

        // Teacher in course 1, category 1 is allowed to toggle the coursevisible for the tool in category 1.
        $result = toggle_showinactivitychooser::execute($tool1id, $course->id, false);
        $this->assertDebuggingCalled();
        $result = external_api::clean_returnvalue(toggle_showinactivitychooser::execute_returns(), $result);
        $this->assertTrue($result);

        $sql = "SELECT lt.coursevisible coursevisible1, lc.coursevisible AS coursevisible2
                  FROM {lti_types} lt
             LEFT JOIN {lti_coursevisible} lc ON lt.id = lc.typeid
                 WHERE lt.id = ?
                   AND lc.courseid = ?";
        $actual = $DB->get_record_sql($sql, [$tool1id, $course->id]);
        $this->assertEquals(LTI_COURSEVISIBLE_ACTIVITYCHOOSER, $actual->coursevisible1);
        $this->assertEquals(LTI_COURSEVISIBLE_PRECONFIGURED, $actual->coursevisible2);

        // Teacher in course 1, category 1 is NOT allowed to toggle the coursevisible for the tool in category 2.
        try {
            toggle_showinactivitychooser::execute($tool2id, $course->id, true);
        } catch (\Exception $e) {
        } finally {
            $this->assertInstanceOf(\moodle_exception::class, $e);
            $this->assertStringContainsString('You are not allowed to change this setting for this tool.', $e->getMessage());
        }
        $this->assertDebuggingCalled();
    }

    /**
     * Test toggle_showinactivitychooser for a hidden site tool.
     *
     * @covers ::execute
     * @return void
     */
    public function test_toggleshowinactivitychooser_hidden_site_tool(): void {
        global $DB;
        $this->resetAfterTest();

        $coursecat1 = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $coursecat1->id]);
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($editingteacher);

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('mod_lti');
        $ltigenerator->create_tool_types([
            'name' => 'site tool dont show',
            'baseurl' => 'http://example.com/tool/1',
            'coursevisible' => LTI_COURSEVISIBLE_NO,
            'state' => LTI_TOOL_STATE_CONFIGURED,
        ]);
        $tool = $DB->get_record('lti_types', ['name' => 'site tool dont show']);
        $result = toggle_showinactivitychooser::execute($tool->id, $course->id, false);
        $this->assertDebuggingCalled();
        $result = external_api::clean_returnvalue(toggle_showinactivitychooser::execute_returns(), $result);
        $this->assertFalse($result);
    }
}
