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

use core_ltix\constants;
use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\context\item\resource_link_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\context\item\user_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use core_ltix\local\lticore\models\resource_link;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering lis_bo_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(lis_bo_resolver::class)]
class lis_bo_resolver_test extends \advanced_testcase {

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
     * Helper returning a tool config stub.
     *
     * @param bool $forcessl whether to force SSL.
     * @return \stdClass the object stub.
     */
    protected function get_tool_config_stub(bool $forcessl = false): \stdClass {
        return (object) [
            'tool' => (object) [
                'id' => '123',
                'clientid' => '123456-abcd',
                'ltiversion' => '1.3.0',
            ],
            'config' => (object) [
                'initiatelogin' => 'https://tool.example.com/lti/initiatelogin',
                'organizationid' => 'https://platform.example.com',
                'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_EMBED,
                ...($forcessl ? ['forcessl' => '1'] : []),
            ],
        ];
    }

    /**
     * Helper returning a resource link stub.
     *
     * @param string|null $servicesalt the service salt, or null for no service salt.
     * @return resource_link the resource link stub.
     */
    protected function get_resource_link_stub(?string $servicesalt = 'abc123'): resource_link {
        return new resource_link(0, (object) [
            'id' => 24,
            'typeid' => 123,
            'component' => 'mod_lti',
            'itemtype' => 'launch',
            'itemid' => 1,
            'contextid' => 456,
            'url' => 'https://tool.example.com/lti/resource/1',
            'title' => 'Test Resource Link',
            'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_DEFAULT,
            'gradable' => true,
            'servicesalt' => $servicesalt,
        ]);
    }

    /**
     * Test processing with service salt.
     *
     * @return void
     */
    public function test_process_with_service_salt(): void {
        $resolver = new lis_bo_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new resource_link_context($this->get_resource_link_stub('abc123')),
            new tool_context($this->get_tool_config_stub()),
            new user_context($this->get_user_stub())
        );

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The existing params are unchanged.
        $this->assertEquals($params['existing_param'], $finalparams['existing_param']);

        // The expected LIS params were added.
        $this->assertArrayHasKey('lis_result_sourcedid', $finalparams);
        $this->assertIsString($finalparams['lis_result_sourcedid']);

        // Verify the sourcedid is valid JSON.
        $sourcedid = json_decode($finalparams['lis_result_sourcedid']);
        $this->assertNotNull($sourcedid);
        $this->assertObjectHasProperty('data', $sourcedid);
        $this->assertObjectHasProperty('hash', $sourcedid);

        // Verify the outcome service URL was added.
        $this->assertArrayHasKey('lis_outcome_service_url', $finalparams);
        $this->assertStringContainsString('/ltix/service.php', $finalparams['lis_outcome_service_url']);
    }

    /**
     * Test processing without service salt.
     *
     * @return void
     */
    public function test_process_without_service_salt(): void {
        $resolver = new lis_bo_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new resource_link_context($this->get_resource_link_stub(null)),
            new tool_context($this->get_tool_config_stub()),
            new user_context($this->get_user_stub())
        );

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The existing params are unchanged.
        $this->assertEquals($params['existing_param'], $finalparams['existing_param']);

        // The LIS params should NOT be added when there's no service salt.
        $this->assertArrayNotHasKey('lis_result_sourcedid', $finalparams);
        $this->assertArrayNotHasKey('lis_outcome_service_url', $finalparams);
    }

    /**
     * Test processing with force SSL enabled in tool config.
     *
     * @return void
     */
    public function test_process_with_force_ssl_in_tool_config(): void {
        $resolver = new lis_bo_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new resource_link_context($this->get_resource_link_stub('abc123')),
            new tool_context($this->get_tool_config_stub(true)),
            new user_context($this->get_user_stub())
        );

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The outcome service URL should be HTTPS when force SSL is enabled.
        $this->assertArrayHasKey('lis_outcome_service_url', $finalparams);
        $this->assertStringStartsWith('https://', $finalparams['lis_outcome_service_url']);
    }

    /**
     * Test processing with force SSL enabled globally via ltix_forcessl.
     *
     * @return void
     */
    public function test_process_with_global_ltix_force_ssl(): void {
        global $CFG;

        $resolver = new lis_bo_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new resource_link_context($this->get_resource_link_stub('abc123')),
            new tool_context($this->get_tool_config_stub()),
            new user_context($this->get_user_stub())
        );

        // Temporarily set the global force SSL config.
        $originalforcessl = $CFG->ltix_forcessl ?? null;
        $CFG->ltix_forcessl = true;

        try {
            $params = ['existing_param' => 'value'];
            $finalparams = $resolver->process($params, $launchcontext);

            // The outcome service URL should be HTTPS when global force SSL is enabled.
            $this->assertArrayHasKey('lis_outcome_service_url', $finalparams);
            $this->assertStringStartsWith('https://', $finalparams['lis_outcome_service_url']);
        } finally {
            // Restore original value.
            if ($originalforcessl === null) {
                unset($CFG->ltix_forcessl);
            } else {
                $CFG->ltix_forcessl = $originalforcessl;
            }
        }
    }

    /**
     * Test processing with force SSL enabled globally via ltix_forcessl (deprecated fallback).
     *
     * @return void
     */
    public function test_process_with_deprecated_mod_lti_force_ssl(): void {
        global $CFG;

        $resolver = new lis_bo_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new resource_link_context($this->get_resource_link_stub('abc123')),
            new tool_context($this->get_tool_config_stub()),
            new user_context($this->get_user_stub())
        );

        // Temporarily set the mod_lti force SSL config.
        $originalforcessl = $CFG->mod_lti_forcessl ?? null;
        $CFG->mod_lti_forcessl = true;

        try {
            $params = ['existing_param' => 'value'];
            $finalparams = $resolver->process($params, $launchcontext);
            $this->assertDebuggingCalled();

            // The outcome service URL should be HTTPS when global force SSL is enabled.
            $this->assertArrayHasKey('lis_outcome_service_url', $finalparams);
            $this->assertStringStartsWith('https://', $finalparams['lis_outcome_service_url']);
        } finally {
            // Restore original value.
            if ($originalforcessl === null) {
                unset($CFG->mod_lti_forcessl);
            } else {
                $CFG->mod_lti_forcessl = $originalforcessl;
            }
        }
    }

    /**
     * Test processing without a required resource link context.
     *
     * @return void
     */
    public function test_process_no_resource_link_context(): void {
        $resolver = new lis_bo_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub()),
            new user_context($this->get_user_stub())
        );

        $params = ['existing_param' => 'value'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*resource_link_context, but it was not.*/");
        $resolver->process($params, $launchcontext);
    }

    /**
     * Test processing without a required tool context.
     *
     * @return void
     */
    public function test_process_no_tool_context(): void {
        $resolver = new lis_bo_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new resource_link_context($this->get_resource_link_stub()),
            new user_context($this->get_user_stub())
        );

        $params = ['existing_param' => 'value'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*tool_context, but it was not provided.*/");
        $resolver->process($params, $launchcontext);
    }

    /**
     * Test processing without a required user context.
     *
     * @return void
     */
    public function test_process_no_user_context(): void {
        $resolver = new lis_bo_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new resource_link_context($this->get_resource_link_stub()),
            new tool_context($this->get_tool_config_stub())
        );

        $params = ['existing_param' => 'value'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*user_context, but it was not provided.*/");
        $resolver->process($params, $launchcontext);
    }
}
