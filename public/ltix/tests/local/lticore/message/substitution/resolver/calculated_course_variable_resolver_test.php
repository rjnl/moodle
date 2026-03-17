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

namespace core_ltix\local\lticore\message\substitution\resolver;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\collection\substitution_context;
use core_ltix\local\lticore\message\context\item\course_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering calculated_course_variable_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(calculated_course_variable_resolver::class)]
class calculated_course_variable_resolver_test extends \advanced_testcase {

    /**
     * Helper to build a substitution context with a course.
     *
     * @param \stdClass $course the course object.
     * @return substitution_context the context for testing.
     */
    private function build_context(\stdClass $course): substitution_context {
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('basic-lti-launch-request')),
            new course_context($course)
        );

        return substitution_context::for_parameter_pipeline([], $launchcontext);
    }

    /**
     * Test that non-substitution variable input returns null.
     *
     * @return void
     */
    public function test_non_substitution_variable_input_returns_null(): void {
        $resolver = new calculated_course_variable_resolver();
        $context = $this->build_context((object) ['id' => 1, 'startdate' => 0, 'enddate' => 0]);

        $this->assertNull($resolver->resolve('CourseSection.timeFrame.begin', $context));
    }

    /**
     * Test that timeframe begin and end variables are properly formatted as ISO 8601 timestamps.
     *
     * @return void
     */
    public function test_timeframe_begin_and_end_are_formatted(): void {
        $course = (object) [
            'id' => '1',
            'startdate' => '1770000000',
            'enddate' => '1780000000',
        ];
        $context = $this->build_context($course);
        $resolver = new calculated_course_variable_resolver();

        $expectedbegin = (new \DateTime('@' . $course->startdate, new \DateTimeZone('UTC')))->format(\DateTime::ATOM);
        $expectedend = (new \DateTime('@' . $course->enddate, new \DateTimeZone('UTC')))->format(\DateTime::ATOM);

        $this->assertSame($expectedbegin, $resolver->resolve('$CourseSection.timeFrame.begin', $context));
        $this->assertSame($expectedend, $resolver->resolve('$CourseSection.timeFrame.end', $context));
    }

    /**
     * Test that empty course timeframe dates return empty strings.
     *
     * @return void
     */
    public function test_empty_timeframe_dates_return_empty_string(): void {
        $course = (object) ['id' => '1', 'startdate' => '0', 'enddate' => '0'];
        $context = $this->build_context($course);
        $resolver = new calculated_course_variable_resolver();

        $this->assertSame('', $resolver->resolve('$CourseSection.timeFrame.begin', $context));
        $this->assertSame('', $resolver->resolve('$CourseSection.timeFrame.end', $context));
    }

    /**
     * Test that missing course context throws an exception.
     *
     * @return void
     */
    public function test_missing_course_context_throws(): void {
        $resolver = new calculated_course_variable_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('basic-lti-launch-request'))
        );
        $context = substitution_context::for_parameter_pipeline([], $launchcontext);

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches(
            "/^.*context_collection requires context.*course_context, but it was not provided.*/"
        );
        $resolver->resolve('$CourseSection.timeFrame.begin', $context);
    }

    /**
     * Test that Context.id.history resolves to comma-separated course IDs in the hierarchy.
     *
     * @return void
     */
    public function test_context_id_history_resolves_course_hierarchy(): void {
        $this->resetAfterTest();

        $gen = $this->getDataGenerator();

        // Create a simulated course history: parent -> child -> grandchild.
        $parentcourse = $gen->create_course();
        $childcourse = $gen->create_course(['originalcourseid' => $parentcourse->id]);
        $grandchildcourse = $gen->create_course(['originalcourseid' => $childcourse->id]);

        $resolver = new calculated_course_variable_resolver();
        $context = $this->build_context($grandchildcourse);

        // The history should include the IDs from the course history.
        $result = $resolver->resolve('$Context.id.history', $context);

        // The result should be a comma-separated string of course IDs.
        $this->assertIsString($result);
        $this->assertStringContainsString($parentcourse->id, $result);
    }
}
