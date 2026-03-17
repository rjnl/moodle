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
use core_ltix\local\lticore\message\context\item\user_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering calculated_user_variable_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(calculated_user_variable_resolver::class)]
class calculated_user_variable_resolver_test extends \advanced_testcase {

    /**
     * Helper returning a stub user object.
     *
     * @return \stdClass the user stub.
     */
    private function get_user_stub(): \stdClass {
        return (object) [
            'id' => 10,
            'auth' => 'manual',
            'confirmed' => '1',
            'deleted' => '0',
            'suspended' => '0',
            'mnethostid' => '1',
            'username' => 'username1',
            'password' => '',
            'idnumber' => 'UID:U123',
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'email' => 'username1@example.com',
            'timezone' => '99',
        ];
    }

    /**
     * Helper to build a substitution context with user and course.
     *
     * @param \stdClass $user the user object.
     * @param \stdClass $course the course object.
     * @return substitution_context the context for testing.
     */
    private function build_context(\stdClass $user, \stdClass $course): substitution_context {
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('basic-lti-launch-request')),
            new user_context($user),
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
        $user = $this->get_user_stub();
        $course = (object) ['id' => 20];

        $resolver = new calculated_user_variable_resolver();
        $context = $this->build_context($user, $course);

        $this->assertNull($resolver->resolve('Moodle.Person.userGroupIds', $context));
    }

    /**
     * Test that unknown variables return null.
     *
     * @return void
     */
    public function test_unknown_variable_returns_null(): void {
        $user = $this->get_user_stub();
        $course = (object) ['id' => 20];

        $resolver = new calculated_user_variable_resolver();
        $context = $this->build_context($user, $course);

        $this->assertNull($resolver->resolve('$Unknown.Variable', $context));
    }

    /**
     * Test that missing user context throws an exception.
     *
     * @return void
     */
    public function test_missing_user_context_throws(): void {
        $resolver = new calculated_user_variable_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('basic-lti-launch-request')),
            new course_context((object) ['id' => 20])
        );
        $context = substitution_context::for_parameter_pipeline([], $launchcontext);

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches(
            "/^.*context_collection requires context.*user_context, but it was not provided.*/"
        );
        $resolver->resolve('$Moodle.Person.userGroupIds', $context);
    }

    /**
     * Test that missing course context throws an exception.
     *
     * @return void
     */
    public function test_missing_course_context_throws(): void {
        $resolver = new calculated_user_variable_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('basic-lti-launch-request')),
            new user_context($this->get_user_stub())
        );
        $context = substitution_context::for_parameter_pipeline([], $launchcontext);

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches(
            "/^.*context_collection requires context.*course_context, but it was not provided.*/"
        );
        $resolver->resolve('$Moodle.Person.userGroupIds', $context);
    }

    /**
     * Test that user groups in course are returned.
     *
     * @return void
     */
    public function test_resolve_user_groups_in_course(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $studentuser = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'group 1']);
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'group 1']);
        $this->getDataGenerator()->create_group_member(['userid' => $studentuser->id, 'groupid' => $group1->id,]);
        $this->getDataGenerator()->create_group_member(['userid' => $studentuser->id, 'groupid' => $group2->id,]);

        $resolver = new calculated_user_variable_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('basic-lti-launch-request')),
            new course_context($course),
            new user_context($studentuser),
        );
        $context = substitution_context::for_parameter_pipeline([], $launchcontext);

        $value = $resolver->resolve('$Moodle.Person.userGroupIds', $context);
        $this->assertStringContainsString($group1->id, $value);
        $this->assertStringContainsString($group2->id, $value);
    }
}
