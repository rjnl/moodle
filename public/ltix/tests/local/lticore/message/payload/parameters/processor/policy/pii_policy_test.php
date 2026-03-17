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

namespace core_ltix\local\lticore\message\payload\parameters\processor\policy;

use core_ltix\constants;
use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering pii_policy.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(pii_policy::class)]
class pii_policy_test extends \basic_testcase {

    /**
     * Helper returning a tool config stub.
     *
     * @param string $ltiversion the LTI version to set.
     * @param int $sendname the sendname setting.
     * @param int $sendemailaddr the sendemailaddr setting.
     * @return \stdClass the object stub.
     */
    protected function get_tool_config_stub(
        string $ltiversion = '1.3.0',
        int $sendname = constants::LTI_SETTING_ALWAYS,
        int $sendemailaddr = constants::LTI_SETTING_ALWAYS
    ): \stdClass {
        return (object) [
            'tool' => (object) [
                'id' => '123',
                'clientid' => '123456-abcd',
                'ltiversion' => $ltiversion,
            ],
            'config' => (object) [
                'initiatelogin' => 'https://tool.example.com/lti/initiatelogin',
                'organizationid' => 'https://platform.example.com',
                'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
                'sendname' => $sendname,
                'sendemailaddr' => $sendemailaddr,
            ],
        ];
    }

    /**
     * Data provider for PII policy scenarios.
     *
     * @return array test cases.
     */
    public static function pii_policy_provider(): array {
        return [
            'LTI 1.0 sendname always sendemailaddr always' => [
                'ltiversion' => lti_version::LTI_VERSION_1->value,
                'sendname' => constants::LTI_SETTING_ALWAYS,
                'sendemailaddr' => constants::LTI_SETTING_ALWAYS,
                'shouldkeepnames' => true,
                'shouldkeepemails' => true,
            ],
            'LTI 1.0 sendname never sendemailaddr always' => [
                'ltiversion' => lti_version::LTI_VERSION_1->value,
                'sendname' => constants::LTI_SETTING_NEVER,
                'sendemailaddr' => constants::LTI_SETTING_ALWAYS,
                'shouldkeepnames' => false,
                'shouldkeepemails' => true,
            ],
            'LTI 1.0 sendname always sendemailaddr never' => [
                'ltiversion' => lti_version::LTI_VERSION_1->value,
                'sendname' => constants::LTI_SETTING_ALWAYS,
                'sendemailaddr' => constants::LTI_SETTING_NEVER,
                'shouldkeepnames' => true,
                'shouldkeepemails' => false,
            ],
            'LTI 1.0 sendname never sendemailaddr never' => [
                'ltiversion' => lti_version::LTI_VERSION_1->value,
                'sendname' => constants::LTI_SETTING_NEVER,
                'sendemailaddr' => constants::LTI_SETTING_NEVER,
                'shouldkeepnames' => false,
                'shouldkeepemails' => false,
            ],
            'LTI 1.3.0 sendname always sendemailaddr always' => [
                'ltiversion' => lti_version::LTI_VERSION_1P3->value,
                'sendname' => constants::LTI_SETTING_ALWAYS,
                'sendemailaddr' => constants::LTI_SETTING_ALWAYS,
                'shouldkeepnames' => true,
                'shouldkeepemails' => true,
            ],
            'LTI 1.3.0 sendname never sendemailaddr never' => [
                'ltiversion' => lti_version::LTI_VERSION_1P3->value,
                'sendname' => constants::LTI_SETTING_NEVER,
                'sendemailaddr' => constants::LTI_SETTING_NEVER,
                'shouldkeepnames' => false,
                'shouldkeepemails' => false,
            ],
            'LTI 1.3.0 sendname delegate sendemailaddr delegate' => [
                'ltiversion' => lti_version::LTI_VERSION_1P3->value,
                'sendname' => constants::LTI_SETTING_DELEGATE,
                'sendemailaddr' => constants::LTI_SETTING_DELEGATE,
                'shouldkeepnames' => false,
                'shouldkeepemails' => false,
            ],
            'LTI 2.0 sendname never sendemailaddr never' => [
                'ltiversion' => lti_version::LTI_VERSION_2->value,
                'sendname' => constants::LTI_SETTING_NEVER,
                'sendemailaddr' => constants::LTI_SETTING_NEVER,
                'shouldkeepnames' => true,
                'shouldkeepemails' => true,
            ],
        ];
    }

    /**
     * Test processing with different PII policy scenarios.
     *
     * @param string $ltiversion the LTI version to test.
     * @param int $sendname the sendname setting.
     * @param int $sendemailaddr the sendemailaddr setting.
     * @param bool $shouldkeepnames whether name parameters should be preserved.
     * @param bool $shouldkeepemails whether email parameters should be preserved.
     * @return void
     */
    #[DataProvider('pii_policy_provider')]
    public function test_process_pii_policy(
        string $ltiversion,
        int $sendname,
        int $sendemailaddr,
        bool $shouldkeepnames,
        bool $shouldkeepemails
    ): void {
        $policy = new pii_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub($ltiversion, $sendname, $sendemailaddr))
        );

        $params = [
            'lis_person_name_given' => 'John',
            'lis_person_name_family' => 'Doe',
            'lis_person_name_full' => 'John Doe',
            'ext_user_username' => 'johndoe',
            'lis_person_contact_email_primary' => 'john@example.com',
            'user_id' => '123',
            'context_id' => '456',
        ];

        $finalparams = $policy->process($params, $launchcontext);

        if ($shouldkeepnames) {
            $this->assertArrayHasKey('lis_person_name_given', $finalparams);
            $this->assertEquals('John', $finalparams['lis_person_name_given']);
            $this->assertArrayHasKey('lis_person_name_family', $finalparams);
            $this->assertEquals('Doe', $finalparams['lis_person_name_family']);
            $this->assertArrayHasKey('lis_person_name_full', $finalparams);
            $this->assertEquals('John Doe', $finalparams['lis_person_name_full']);
            $this->assertArrayHasKey('ext_user_username', $finalparams);
            $this->assertEquals('johndoe', $finalparams['ext_user_username']);
        } else {
            $this->assertArrayNotHasKey('lis_person_name_given', $finalparams);
            $this->assertArrayNotHasKey('lis_person_name_family', $finalparams);
            $this->assertArrayNotHasKey('lis_person_name_full', $finalparams);
            $this->assertArrayNotHasKey('ext_user_username', $finalparams);
        }

        if ($shouldkeepemails) {
            $this->assertArrayHasKey('lis_person_contact_email_primary', $finalparams);
            $this->assertEquals('john@example.com', $finalparams['lis_person_contact_email_primary']);
        } else {
            $this->assertArrayNotHasKey('lis_person_contact_email_primary', $finalparams);
        }

        // Non-PII parameters should always be preserved.
        $this->assertArrayHasKey('user_id', $finalparams);
        $this->assertEquals('123', $finalparams['user_id']);
        $this->assertArrayHasKey('context_id', $finalparams);
        $this->assertEquals('456', $finalparams['context_id']);
    }

    /**
     * Test processing with only non-PII parameters.
     *
     * @return void
     */
    public function test_process_with_only_non_pii_params(): void {
        $policy = new pii_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(
                lti_version::LTI_VERSION_1P3->value,
                constants::LTI_SETTING_NEVER,
                constants::LTI_SETTING_NEVER
            ))
        );

        $params = [
            'context_id' => '123',
            'resource_link_id' => '456',
            'custom_param1' => 'value1',
            'ext_param1' => 'value2',
        ];

        $finalparams = $policy->process($params, $launchcontext);

        // All parameters should be preserved as there are no PII parameters.
        $this->assertEquals($params, $finalparams);
    }

    /**
     * Test processing with empty parameters array.
     *
     * @return void
     */
    public function test_process_with_empty_parameters(): void {
        $policy = new pii_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(
                lti_version::LTI_VERSION_1P3->value,
                constants::LTI_SETTING_ALWAYS,
                constants::LTI_SETTING_ALWAYS
            ))
        );

        $params = [];
        $finalparams = $policy->process($params, $launchcontext);

        // Should return empty array.
        $this->assertEmpty($finalparams);
    }

    /**
     * Test processing without required tool context.
     *
     * @return void
     */
    public function test_process_no_tool_context(): void {
        $policy = new pii_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        $params = ['lis_person_name_full' => 'John Doe'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*tool_context, but it was not provided.*/");
        $policy->process($params, $launchcontext);
    }

    /**
     * Test that PII parameters with different casing are not excluded.
     *
     * @return void
     */
    public function test_process_case_sensitive_parameter_matching(): void {
        $policy = new pii_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(
                lti_version::LTI_VERSION_1P3->value,
                constants::LTI_SETTING_NEVER,
                constants::LTI_SETTING_NEVER
            ))
        );

        $params = [
            'lis_person_name_full' => 'John Doe',
            'LIS_PERSON_NAME_FULL' => 'Jane Doe',  // Different case, should be preserved.
            'Lis_Person_Name_Full' => 'Jack Doe',  // Different case, should be preserved.
            'lis_person_contact_email_primary' => 'john@example.com',
            'LIS_PERSON_CONTACT_EMAIL_PRIMARY' => 'jane@example.com',  // Different case, should be preserved.
        ];

        $finalparams = $policy->process($params, $launchcontext);

        // Exact matches should be excluded.
        $this->assertArrayNotHasKey('lis_person_name_full', $finalparams);
        $this->assertArrayNotHasKey('lis_person_contact_email_primary', $finalparams);

        // Different casing should be preserved (case-sensitive matching).
        $this->assertArrayHasKey('LIS_PERSON_NAME_FULL', $finalparams);
        $this->assertEquals('Jane Doe', $finalparams['LIS_PERSON_NAME_FULL']);
        $this->assertArrayHasKey('Lis_Person_Name_Full', $finalparams);
        $this->assertEquals('Jack Doe', $finalparams['Lis_Person_Name_Full']);
        $this->assertArrayHasKey('LIS_PERSON_CONTACT_EMAIL_PRIMARY', $finalparams);
        $this->assertEquals('jane@example.com', $finalparams['LIS_PERSON_CONTACT_EMAIL_PRIMARY']);
    }
}
