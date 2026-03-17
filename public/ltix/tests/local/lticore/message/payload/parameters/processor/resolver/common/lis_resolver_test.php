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

namespace core_ltix\local\lticore\message\payload\parameters\processor\resolver\common;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\course_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\context\item\user_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering lis_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(lis_resolver::class)]
class lis_resolver_test extends \basic_testcase {

    /**
     * Helper returning a course stub.
     *
     * @return \stdClass the course object stub.
     */
    protected function get_course_stub(): \stdClass {
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
     * Helper returning a user stub.
     *
     * @return \stdClass the user object stub.
     */
    protected function get_user_stub(): \stdClass {
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
     * Test processing with typical course and user data.
     *
     * @return void
     */
    public function test_process_basic(): void {
        $resolver = new lis_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new course_context($this->get_course_stub()),
            new user_context($this->get_user_stub())
        );

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The existing params are unchanged.
        $this->assertEquals($params['existing_param'], $finalparams['existing_param']);

        // The expected LIS params were added.
        $this->assertArrayHasKey('lis_course_section_sourcedid', $finalparams);
        $this->assertEquals('COURSE:12345', $finalparams['lis_course_section_sourcedid']);

        $this->assertArrayHasKey('lis_person_name_given', $finalparams);
        $this->assertEquals('美羽', $finalparams['lis_person_name_given']);

        $this->assertArrayHasKey('lis_person_name_family', $finalparams);
        $this->assertEquals('斎藤', $finalparams['lis_person_name_family']);

        $this->assertArrayHasKey('lis_person_name_full', $finalparams);
        $this->assertEquals(fullname($this->get_user_stub()), $finalparams['lis_person_name_full']);

        $this->assertArrayHasKey('lis_person_sourcedid', $finalparams);
        $this->assertEquals('UID:U123', $finalparams['lis_person_sourcedid']);

        $this->assertArrayHasKey('lis_person_contact_email_primary', $finalparams);
        $this->assertEquals('username1@example.com', $finalparams['lis_person_contact_email_primary']);
    }

    /**
     * Test processing with empty course idnumber.
     *
     * @return void
     */
    public function test_process_with_empty_course_idnumber(): void {
        $resolver = new lis_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $course = $this->get_course_stub();
        $course->idnumber = '';

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new course_context($course),
            new user_context($this->get_user_stub())
        );

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // Course sourcedid should be empty string if no idnumber is set.
        $this->assertArrayHasKey('lis_course_section_sourcedid', $finalparams);
        $this->assertEquals('', $finalparams['lis_course_section_sourcedid']);
    }

    /**
     * Test processing with empty user idnumber.
     *
     * @return void
     */
    public function test_process_with_empty_user_idnumber(): void {
        $resolver = new lis_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $user = $this->get_user_stub();
        $user->idnumber = '';

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new course_context($this->get_course_stub()),
            new user_context($user)
        );

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // Person sourcedid should be empty string if no idnumber is set.
        $this->assertArrayHasKey('lis_person_sourcedid', $finalparams);
        $this->assertEquals('', $finalparams['lis_person_sourcedid']);
    }

    /**
     * Test processing without required course context.
     *
     * @return void
     */
    public function test_process_no_course_context(): void {
        $resolver = new lis_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new user_context($this->get_user_stub())
        );

        $params = ['existing_param' => 'value'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*course_context, but it was not provided.*/");
        $resolver->process($params, $launchcontext);
    }

    /**
     * Test processing without required user context.
     *
     * @return void
     */
    public function test_process_no_user_context(): void {
        $resolver = new lis_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new course_context($this->get_course_stub())
        );

        $params = ['existing_param' => 'value'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*user_context, but it was not provided.*/");
        $resolver->process($params, $launchcontext);
    }

    /**
     * Test processing with special characters in user names.
     *
     * @return void
     */
    public function test_process_with_special_characters_in_names(): void {
        $resolver = new lis_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $user = $this->get_user_stub();
        $user->firstname = 'François';
        $user->lastname = "O'Brien-García";

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new course_context($this->get_course_stub()),
            new user_context($user)
        );

        $params = [];
        $finalparams = $resolver->process($params, $launchcontext);

        // Special characters should be preserved.
        $this->assertArrayHasKey('lis_person_name_given', $finalparams);
        $this->assertEquals('François', $finalparams['lis_person_name_given']);

        $this->assertArrayHasKey('lis_person_name_family', $finalparams);
        $this->assertEquals("O'Brien-García", $finalparams['lis_person_name_family']);

        // Full name should also preserve special characters.
        $this->assertArrayHasKey('lis_person_name_full', $finalparams);
        $expectedFullName = fullname($user);
        $this->assertEquals($expectedFullName, $finalparams['lis_person_name_full']);
    }
}
