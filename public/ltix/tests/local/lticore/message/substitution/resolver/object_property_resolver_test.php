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
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering object_property_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(object_property_resolver::class)]
class object_property_resolver_test extends \basic_testcase {

    /**
     * Helper returning a stub user object.
     * @return \stdClass
     */
    private function get_user_stub(): \stdClass {
        return (object) [
            'id' => '103000',
            'auth' => 'manual',
            'confirmed' => '1',
            'policyagreed' => '0',
            'deleted' => '0',
            'suspended' => '0',
            'mnethostid' => '1',
            'username' => 'username1',
            'password' => '',
            'idnumber' => 'UID:U123',
            'firstname' => '美羽',
            'lastname' => '斎藤',
            'email' => 'username1@example.com',
            'emailstop' => '0',
            'phone1' => '',
            'phone2' => '',
            'institution' => '',
            'department' => '',
            'address' => '',
            'city' => '',
            'country' => '',
            'lang' => 'en',
            'calendartype' => 'gregorian',
            'theme' => '',
            'timezone' => '99',
            'firstaccess' => '0',
            'lastaccess' => '0',
            'lastlogin' => '0',
            'currentlogin' => '0',
            'lastip' => '0.0.0.0',
            'secret' => '',
            'picture' => '0',
            'description' => NULL,
            'descriptionformat' => '1',
            'mailformat' => '1',
            'maildigest' => '0',
            'maildisplay' => '2',
            'autosubscribe' => '1',
            'trackforums' => '0',
            'timecreated' => '1765442016',
            'timemodified' => '1765442016',
            'trustbitmask' => '0',
            'imagealt' => NULL,
            'lastnamephonetic' => '高橋',
            'firstnamephonetic' => 'Michael',
            'middlename' => 'Leah',
            'alternatename' => '娜',
            'moodlenetprofile' => NULL,
        ];
    }

    /**
     * Helper returning a stub course object.
     * @return \stdClass
     */
    private function get_course_stub(): \stdClass {
        return (object) [
            'id' => '100000',
            'category' => '1',
            'sortorder' => '0',
            'fullname' => 'Test course 1',
            'shortname' => 'tc_1',
            'idnumber' => 'COURSE:12345',
            'summary' => 'Test course 1 Lorem ipsum dolor sit amet',
            'summaryformat' => '0',
            'format' => 'topics',
            'showgrades' => '1',
            'newsitems' => '0',
            'startdate' => '1765382400',
            'enddate' => '0',
            'relativedatesmode' => '0',
            'marker' => '0',
            'maxbytes' => '0',
            'legacyfiles' => '0',
            'showreports' => '0',
            'visible' => '1',
            'visibleold' => '1',
            'downloadcontent' => NULL,
            'groupmode' => '0',
            'groupmodeforce' => '0',
            'defaultgroupingid' => '0',
            'lang' => '',
            'calendartype' => '',
            'theme' => '',
            'timecreated' => '1765422564',
            'timemodified' => '1765422564',
            'requested' => '0',
            'enablecompletion' => '0',
            'completionnotify' => '0',
            'cacherev' => '0',
            'originalcourseid' => NULL,
            'showactivitydates' => '0',
            'showcompletionconditions' => '1',
            'pdfexportfont' => NULL,
            'hiddensections' => 1,
            'coursedisplay' => 0,
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
     * Data provider for resolve tests.
     *
     * {@see self::get_user_stub() for user fields referenced here}.
     * {@see self::get_course_stub() for course fields referenced here}.
     * @return array[] the test cases.
     */
    public static function resolve_provider(): array {
        return [
            'User variable resolves to user property' => [
                'map' => ['User.id' => '$USER->id'],
                'input' => '$User.id',
                'expected' => '103000',
            ],
            'Course variable resolves to course property' => [
                'map' => ['Course.id' => '$COURSE->id'],
                'input' => '$Course.id',
                'expected' => '100000',
            ],
            'Unsupported mapped object returns null' => [
                'map' => ['User.field' => '$UNSUPPORTED->field'],
                'input' => '$User.field',
                'expected' => null,
            ],
            'Non-substitution variable input returns null' => [
                'map' => ['User.id' => '$USER->id'],
                'input' => 'User.id', // Missing leading '$'.
                'expected' => null,
            ],
            'Missing object property returns null' => [
                'map' => ['User.missing' => '$USER->missing'],
                'input' => '$User.missing',
                'expected' => null,
            ],
            'Null-mapped variable returns null' => [
                'map' => ['User.something' => null],
                'input' => '$User.something',
                'expected' => null,
            ],
            'Empty-string-mapped variable returns null' => [
                'map' => ['User.something' => ''],
                'input' => '$User.something',
                'expected' => null,
            ],
            'Unprefixed-string-mapped variable returns null' => [
                'map' => ['User.something' => 'USER.something'],
                'input' => '$User.something',
                'expected' => null,
            ]
        ];
    }

    /**
     * Test resolve for a variety of inputs and mappings.
     *
     * @param array $map the mapping to use.
     * @param string $input the input to test.
     * @param string|null $expected the expected output.
     * @return void
     */
    #[DataProvider('resolve_provider')]
    public function test_resolve(array $map, string $input, ?string $expected): void {
        $resolver = new object_property_resolver($map);

        $context = $this->build_context(
            $this->get_user_stub(),
            $this->get_course_stub(),
        );

        $this->assertSame($expected, $resolver->resolve($input, $context));
    }

    /**
     * Test that all supported object properties are resolved.
     * @return void
     */
    public function test_all_supported_object_properties(): void {
        $map = \core_ltix\helper::get_capabilities();
        $resolver = new object_property_resolver($map);

        $objectpropsmap = array_filter($map, function($value) {
            return $value && str_starts_with($value, '$');
        });

        $allsupportedvars = array_map(fn($x) => '$'.$x, array_keys($objectpropsmap));

        // This variable is no longer supported but still exists in the canonical mapping.
        // Remove it from the test.
        $badkey = array_search('$Person.webaddress', $allsupportedvars);
        unset($allsupportedvars[$badkey]);

        $context = $this->build_context(
            $this->get_user_stub(),
            $this->get_course_stub(),
        );

        foreach ($allsupportedvars as $var) {
            $this->assertNotNull($resolver->resolve($var, $context));
        }
    }

    /**
     * Test that missing launch context throws an exception.
     *
     * @return void
     */
    public function test_missing_launch_context_throws(): void {
        $resolver = new object_property_resolver([
            'User.id' => '$USER->id',
        ]);
        $context = substitution_context::for_auth([]);

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*launch_context, but it was not provided.*/");
        $resolver->resolve('$User.id', $context);
    }
}
