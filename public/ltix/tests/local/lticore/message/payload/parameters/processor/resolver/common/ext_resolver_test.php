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
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\context\item\user_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering context_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(ext_resolver::class)]
class ext_resolver_test extends \basic_testcase {

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
     * Test processing using tool config.
     *
     * @return void
     */
    public function test_process(): void {
        $resolver = new ext_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new user_context($this->get_user_stub())
        );
        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The existing params are unchanged.
        $this->assertEquals($params['existing_param'], $finalparams['existing_param']);

        // The expected context params were added.
        $this->assertArrayHasKey('ext_user_username', $finalparams);
        $this->assertEquals('username1', $finalparams['ext_user_username']);
        $this->assertArrayHasKey('ext_lms', $finalparams);
        $this->assertEquals('moodle-2', $finalparams['ext_lms']);
    }

    /**
     * Test resolve without a required course context in the launch context.
     *
     * @return void
     */
    public function test_resolve_no_user(): void {
        $resolver = new ext_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
        );
        $params = ['existing_param' => 'value'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*user_context, but it was not provided.*/");
        $resolver->process($params, $launchcontext);
    }
}
