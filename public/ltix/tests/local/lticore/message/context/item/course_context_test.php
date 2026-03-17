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

namespace core_ltix\local\lticore\message\context\item;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering course_context.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(course_context::class)]
class course_context_test extends \basic_testcase {

    /**
     * Test that the course property holds the object passed to the constructor.
     *
     * @return void
     */
    public function test_course_property_holds_the_supplied_object(): void {
        $course = (object) ['id' => 42, 'shortname' => 'CS101', 'fullname' => 'Introduction to Computer Science'];
        $ctx = new course_context($course);

        $this->assertSame($course, $ctx->course);
    }

    /**
     * Test that the course property is the exact same object instance (no cloning).
     *
     * @return void
     */
    public function test_course_property_is_same_instance(): void {
        $course = (object) ['id' => 1];
        $ctx = new course_context($course);

        $this->assertSame($course, $ctx->course);
    }
}
