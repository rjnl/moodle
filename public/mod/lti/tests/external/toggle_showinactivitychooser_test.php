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
use core_ltix\local\placement\placement_status;
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
 * @covers \mod_lti\external\toggle_showinactivitychooser
 */
final class toggle_showinactivitychooser_test extends externallib_advanced_testcase {

    /**
     * Test toggle_showinactivitychooser for course tool.
     *
     * @return void
     */
    public function test_toggle_showinactivitychooser_course_tool(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($editingteacher);

        // Create a course tool with the 'mod_lti:activityplacement' placement configured for use.
        /** @var \mod_lti_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('mod_lti');
        $typeid = $ltigenerator->create_tool_types([
            'name' => 'Example tool',
            'baseurl' => 'http://example.com/tool/1',
            'lti_coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
            'course' => $course->id,
            'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED
        ]);
        $placementtypeid = $DB->get_field('lti_placement_type', 'id', ['type' => 'mod_lti:activityplacement']);
        $ltigenerator->create_tool_placements([
            'toolid' => $typeid,
            'placementtypeid' => $placementtypeid,
            'config_default_usage' => placement_status::ENABLED->value,
        ]);

        // Set the placement status to disabled.
        $result = toggle_showinactivitychooser::execute($typeid, $course->id, false);
        $this->assertDebuggingCalled();
        $result = external_api::clean_returnvalue(toggle_showinactivitychooser::execute_returns(), $result);
        $this->assertTrue($result);
        $toolplacementstatuses = array_filter(
            \core_ltix\helper::get_placement_status_for_tool($typeid, $course->id),
            fn($x) => $x->type == 'mod_lti:activityplacement'
        );
        $placementstatus = array_pop($toolplacementstatuses);
        $this->assertEquals(placement_status::DISABLED->value, $placementstatus->status);

        // Set the placement status to enabled.
        $result = toggle_showinactivitychooser::execute($typeid, $course->id, true);
        $this->assertDebuggingCalled();
        $result = external_api::clean_returnvalue(toggle_showinactivitychooser::execute_returns(), $result);
        $this->assertTrue($result);
        $toolplacementstatuses = array_filter(
            \core_ltix\helper::get_placement_status_for_tool($typeid, $course->id),
            fn($x) => $x->type == 'mod_lti:activityplacement'
        );
        $placementstatus = array_pop($toolplacementstatuses);
        $this->assertEquals(placement_status::ENABLED->value, $placementstatus->status);
    }

    /**
     * Test toggle_showinactivitychooser for site tool.
     *
     * @return void
     */
    public function test_toggle_showinactivitychooser_site_tool(): void {
        global $DB;
        $this->resetAfterTest();

        $coursecat1 = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $coursecat1->id]);
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($editingteacher);

        // Create a site tool configured with the 'mod_lti:activityplacement' placement.
        /** @var \mod_lti_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('mod_lti');
        $typeid = $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured and activity chooser, restricted to category 1',
            'baseurl' => 'http://example.com/tool/1',
            'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
            'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat1->id
        ]);
        $placementtypeid = $DB->get_field('lti_placement_type', 'id', ['type' => 'mod_lti:activityplacement']);
        $ltigenerator->create_tool_placements([
            'toolid' => $typeid,
            'placementtypeid' => $placementtypeid,
            'config_default_usage' => placement_status::ENABLED->value,
        ]);

        // Set the placement status to disabled.
        $result = toggle_showinactivitychooser::execute($typeid, $course->id, false);
        $this->assertDebuggingCalled();
        $result = external_api::clean_returnvalue(toggle_showinactivitychooser::execute_returns(), $result);
        $this->assertTrue($result);
        $toolplacementstatuses = array_filter(
            \core_ltix\helper::get_placement_status_for_tool($typeid, $course->id),
            fn($x) => $x->type == 'mod_lti:activityplacement'
        );
        $placementstatus = array_pop($toolplacementstatuses);
        $this->assertEquals(placement_status::DISABLED->value, $placementstatus->status);

        // Set the placement status to enabled.
        $result = toggle_showinactivitychooser::execute($typeid, $course->id, true);
        $this->assertDebuggingCalled();
        $result = external_api::clean_returnvalue(toggle_showinactivitychooser::execute_returns(), $result);
        $this->assertTrue($result);
        $toolplacementstatuses = array_filter(
            \core_ltix\helper::get_placement_status_for_tool($typeid, $course->id),
            fn($x) => $x->type == 'mod_lti:activityplacement'
        );
        $placementstatus = array_pop($toolplacementstatuses);
        $this->assertEquals(placement_status::ENABLED->value, $placementstatus->status);
    }

    /**
     * Test toggle_showinactivitychooser for tools restricted to course categories
     *
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

        /** @var \mod_lti_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('mod_lti');
        $placementtypeid = $DB->get_field('lti_placement_type', 'id', ['type' => 'mod_lti:activityplacement']);
        $tool1id = $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured and activity chooser, restricted to category 1',
            'baseurl' => 'http://example.com/tool/1',
            'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
            'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat1->id
        ]);
        $ltigenerator->create_tool_placements([
            'toolid' => $tool1id,
            'placementtypeid' => $placementtypeid,
            'config_default_usage' => placement_status::ENABLED->value,
        ]);
        $tool2id = $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured and activity chooser, restricted to category 2',
            'baseurl' => 'http://example.com/tool/1',
            'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
            'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat2->id
        ]);
        $ltigenerator->create_tool_placements([
            'toolid' => $tool2id,
            'placementtypeid' => $placementtypeid,
            'config_default_usage' => placement_status::ENABLED->value,
        ]);

        // Teacher in course 1, category 1 is allowed to toggle the coursevisible for the tool in category 1.
        $result = toggle_showinactivitychooser::execute($tool1id, $course->id, false);
        $this->assertDebuggingCalled();
        $result = external_api::clean_returnvalue(toggle_showinactivitychooser::execute_returns(), $result);
        $this->assertTrue($result);

        $toolplacementstatuses = array_filter(
            \core_ltix\helper::get_placement_status_for_tool($tool1id, $course->id),
            fn($x) => $x->type == 'mod_lti:activityplacement'
        );
        $placementstatus = array_pop($toolplacementstatuses);
        $this->assertEquals(placement_status::DISABLED->value, $placementstatus->status);

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
            'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_NO,
            'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
        ]);

        $tool = $DB->get_record('lti_types', ['name' => 'site tool dont show']);
        $result = toggle_showinactivitychooser::execute($tool->id, $course->id, false);
        $this->assertDebuggingCalled();
        $result = external_api::clean_returnvalue(toggle_showinactivitychooser::execute_returns(), $result);
        $this->assertFalse($result);
    }

    /**
     * Test verifying that toggling the activity chooser placment isn't possible if the placement isn't configured.
     *
     * @return void
     */
    public function test_toggleshowinactivitychooser_no_placement(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $editingteacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($editingteacher);

        // Create a course tool without any placement configuration.
        // Overriding a placement status should not be possible if the placement is not configured.
        /** @var \mod_lti_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('mod_lti');
        $typeid = $ltigenerator->create_tool_types([
            'name' => 'Example tool',
            'baseurl' => 'http://example.com/tool/1',
            'lti_coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
            'course' => $course->id,
            'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED
        ]);

        // Set the placement status to disabled.
        $result = toggle_showinactivitychooser::execute($typeid, $course->id, false);
        $this->assertDebuggingCalled();
        $result = external_api::clean_returnvalue(toggle_showinactivitychooser::execute_returns(), $result);
        $this->assertFalse($result);
        $this->assertEquals(0, $DB->count_records('lti_placement_status'));
    }
}
