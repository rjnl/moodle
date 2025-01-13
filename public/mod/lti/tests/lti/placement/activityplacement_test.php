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

namespace mod_lti;

use core_ltix\local\placement\deeplinking_placement_handler;
use core_ltix\local\placement\placements_manager;

/**
 * Mod LTI activityplacement handler testing.
 *
 * @covers \mod_lti\lti\placement\activityplacement
 * @package    mod_lti
 * @copyright  Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class activityplacement_test extends \advanced_testcase {

    /**
     * Test confirming that an instance of the handler can be fetched when needed.
     *
     * @return void
     */
    public function test_get_instance(): void {
        $this->assertInstanceOf(
            deeplinking_placement_handler::class,
            placements_manager::get_instance()->get_deeplinking_placement_instance('mod_lti:activityplacement')
        );
    }

    /**
     * Confirm that the placement instance can invoke that capabilities are thrown exceptions when needed or null otherwise.
     *
     * @return void
     */
    public function test_content_item_selection_capabilities(): void {
        $this->resetAfterTest();
        // Create a test course with some phony users.
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = $this->getDataGenerator()->create_and_enrol($course);
        // Get our placement instance to run capabilities checks against
        $placementinstance = placements_manager::get_instance()->get_deeplinking_placement_instance('mod_lti:activityplacement');

        // Confirm that students cannot select a content item, but teachers can.
        $this->setUser($teacher);
        $this->assertEquals(null, $placementinstance->content_item_selection_capabilities($context));
        $this->setUser($student);
        $this->expectException(\Exception::class);
        $placementinstance->content_item_selection_capabilities($context);
    }
}
