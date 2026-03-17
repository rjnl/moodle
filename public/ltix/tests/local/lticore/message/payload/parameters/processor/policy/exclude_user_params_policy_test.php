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
 * Tests covering exclude_user_params_policy.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(exclude_user_params_policy::class)]
class exclude_user_params_policy_test extends \basic_testcase {

    /**
     * Helper returning a tool config stub.
     *
     * @param string $ltiversion the LTI version to set.
     * @return \stdClass the object stub.
     */
    protected function get_tool_config_stub(string $ltiversion = '1.3.0'): \stdClass {
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
            ],
        ];
    }

    /**
     * Data provider for LTI version tests.
     *
     * @return array test cases.
     */
    public static function lti_version_provider(): array {
        return [
            'LTI 1.0' => [
                'ltiversion' => lti_version::LTI_VERSION_1->value,
                'shouldexclude' => false,
            ],
            'LTI 2.0' => [
                'ltiversion' => lti_version::LTI_VERSION_2->value,
                'shouldexclude' => false,
            ],
            'LTI 1.3.0' => [
                'ltiversion' => lti_version::LTI_VERSION_1P3->value,
                'shouldexclude' => true,
            ],
        ];
    }

    /**
     * Test processing with different LTI versions.
     *
     * @param string $ltiversion the LTI version to test.
     * @param bool $shouldexclude whether user params should be excluded.
     * @return void
     */
    #[DataProvider('lti_version_provider')]
    public function test_process_with_different_lti_versions(string $ltiversion, bool $shouldexclude): void {
        $policy = new exclude_user_params_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub($ltiversion))
        );

        $params = [
            'user_id' => '123',
            'lis_person_name_full' => 'John Doe',
            'lis_person_name_given' => 'John',
            'lis_person_name_family' => 'Doe',
            'lis_person_contact_email_primary' => 'john@example.com',
            'context_id' => '456',
        ];

        $finalparams = $policy->process($params, $launchcontext);

        if ($shouldexclude) {
            $this->assertArrayNotHasKey('user_id', $finalparams);
            $this->assertArrayNotHasKey('lis_person_name_full', $finalparams);
            $this->assertArrayNotHasKey('lis_person_name_given', $finalparams);
            $this->assertArrayNotHasKey('lis_person_name_family', $finalparams);
            $this->assertArrayNotHasKey('lis_person_contact_email_primary', $finalparams);
        } else {
            $this->assertEquals(count($params), count($finalparams));
            $this->assertArrayHasKey('user_id', $finalparams);
            $this->assertEquals('123', $finalparams['user_id']);
            $this->assertArrayHasKey('lis_person_name_full', $finalparams);
            $this->assertEquals('John Doe', $finalparams['lis_person_name_full']);
            $this->assertArrayHasKey('lis_person_name_given', $finalparams);
            $this->assertEquals('John', $finalparams['lis_person_name_given']);
            $this->assertArrayHasKey('lis_person_name_family', $finalparams);
            $this->assertEquals('Doe', $finalparams['lis_person_name_family']);
            $this->assertArrayHasKey('lis_person_contact_email_primary', $finalparams);
            $this->assertEquals('john@example.com', $finalparams['lis_person_contact_email_primary']);
        }

        // Non-user parameters should always be preserved.
        $this->assertArrayHasKey('context_id', $finalparams);
        $this->assertEquals('456', $finalparams['context_id']);
    }

    /**
     * Test processing with only non-user parameters.
     *
     * @return void
     */
    public function test_process_with_only_non_user_params(): void {
        $policy = new exclude_user_params_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(lti_version::LTI_VERSION_1P3->value))
        );

        $params = [
            'context_id' => '123',
            'resource_link_id' => '456',
            'custom_param1' => 'value1',
            'ext_param1' => 'value2',
        ];

        $finalparams = $policy->process($params, $launchcontext);

        // All parameters should be preserved as there are no user parameters.
        $this->assertEquals($params, $finalparams);
    }

    /**
     * Test processing with empty parameters array.
     *
     * @return void
     */
    public function test_process_with_empty_parameters(): void {
        $policy = new exclude_user_params_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(lti_version::LTI_VERSION_1P3->value))
        );

        $params = [];
        $finalparams = $policy->process($params, $launchcontext);

        // Should return empty array.
        $this->assertEmpty($finalparams);
    }

    /**
     * Test processing with only user parameters in LTI 1.3.0.
     *
     * @return void
     */
    public function test_process_with_only_user_params_in_lti_1p3(): void {
        $policy = new exclude_user_params_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(lti_version::LTI_VERSION_1P3->value))
        );

        $params = [
            'user_id' => '123',
            'lis_person_name_full' => 'John Doe',
            'lis_person_name_given' => 'John',
            'lis_person_name_family' => 'Doe',
            'lis_person_contact_email_primary' => 'john@example.com',
        ];

        $finalparams = $policy->process($params, $launchcontext);

        // All user parameters should be excluded, resulting in empty array.
        $this->assertEmpty($finalparams);
    }

    /**
     * Test processing without required tool context.
     *
     * @return void
     */
    public function test_process_no_tool_context(): void {
        $policy = new exclude_user_params_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        $params = ['user_id' => '123'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*tool_context, but it was not provided.*/");
        $policy->process($params, $launchcontext);
    }

    /**
     * Test that user parameters with different casing are not excluded.
     *
     * @return void
     */
    public function test_process_case_sensitive_parameter_matching(): void {
        $policy = new exclude_user_params_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(lti_version::LTI_VERSION_1P3->value))
        );

        $params = [
            'user_id' => '123',
            'USER_ID' => '456',  // Different case, should be preserved.
            'User_Id' => '789',  // Different case, should be preserved.
            'lis_person_name_full' => 'John Doe',
        ];

        $finalparams = $policy->process($params, $launchcontext);

        // Exact match should be excluded.
        $this->assertArrayNotHasKey('user_id', $finalparams);
        $this->assertArrayNotHasKey('lis_person_name_full', $finalparams);

        // Different casing should be preserved (case-sensitive matching).
        $this->assertArrayHasKey('USER_ID', $finalparams);
        $this->assertEquals('456', $finalparams['USER_ID']);
        $this->assertArrayHasKey('User_Id', $finalparams);
        $this->assertEquals('789', $finalparams['User_Id']);
    }
}
