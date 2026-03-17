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
use core_ltix\local\lticore\message\context\item\resource_link_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use core_ltix\local\lticore\models\resource_link;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering lis_bo_policy.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(lis_bo_policy::class)]
class lis_bo_policy_test extends \basic_testcase {

    /**
     * Helper returning a tool config stub.
     *
     * @param string $ltiversion the LTI version to set.
     * @param int $acceptgrades the accept grades setting.
     * @return \stdClass the object stub.
     */
    protected function get_tool_config_stub(string $ltiversion, int $acceptgrades): \stdClass {
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
                'acceptgrades' => $acceptgrades,
            ],
        ];
    }

    /**
     * Helper returning a resource link stub.
     *
     * @param string|null $servicesalt the service salt, or null for no service salt.
     * @param bool $gradable whether the link is gradable.
     * @return resource_link the resource link stub.
     */
    protected function get_resource_link_stub(?string $servicesalt, bool $gradable): resource_link {
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
            'gradable' => $gradable,
            'servicesalt' => $servicesalt,
        ]);
    }

    /**
     * Data provider for LIS Basic Outcomes policy scenarios.
     *
     * @return array test cases.
     */
    public static function lis_bo_policy_provider(): array {
        return [
            'LTI 1.0 acceptgrades always with service salt and gradable true' => [
                'ltiversion' => lti_version::LTI_VERSION_1->value,
                'acceptgrades' => constants::LTI_SETTING_ALWAYS,
                'servicesalt' => 'abc123',
                'gradable' => true,
                'shouldkeep' => true,
            ],
            'LTI 1.0 acceptgrades always with service salt and gradable false' => [
                // This represents the case where tool config was changed, setting acceptgrade=ALWAYS, after the link was created.
                // Unless the link is updated, gradable will remain false. The policy only considers this value when the tool
                // config's 'acceptgrades' is set to DELEGATE.
                'ltiversion' => lti_version::LTI_VERSION_1->value,
                'acceptgrades' => constants::LTI_SETTING_ALWAYS,
                'servicesalt' => 'abc123',
                'gradable' => false,
                'shouldkeep' => true,
            ],
            'LTI 1.3 acceptgrades delegate gradable' => [
                'ltiversion' => lti_version::LTI_VERSION_1P3->value,
                'acceptgrades' => constants::LTI_SETTING_DELEGATE,
                'servicesalt' => 'abc123',
                'gradable' => true,
                'shouldkeep' => true,
            ],
            'LTI 1.3 acceptgrades delegate not gradable' => [
                'ltiversion' => lti_version::LTI_VERSION_1P3->value,
                'acceptgrades' => constants::LTI_SETTING_DELEGATE,
                'servicesalt' => 'abc123',
                'gradable' => false,
                'shouldkeep' => false,
            ],
            'LTI 1.3 acceptgrades never' => [
                // Again, this represents the case where the tool config was changed after link creation, setting acceptgrades to
                // NEVER. In this case, LIS BO parameters should be removed regardless of the gradable value.
                'ltiversion' => lti_version::LTI_VERSION_1P3->value,
                'acceptgrades' => constants::LTI_SETTING_NEVER,
                'servicesalt' => 'abc123',
                'gradable' => true,
                'shouldkeep' => false,
            ],
            'LTI 1.3 without service salt' => [
                'ltiversion' => lti_version::LTI_VERSION_1P3->value,
                'acceptgrades' => constants::LTI_SETTING_ALWAYS,
                'servicesalt' => null,
                'gradable' => true,
                'shouldkeep' => false,
            ],
            'LTI 2.0 policy not applied' => [
                'ltiversion' => lti_version::LTI_VERSION_2->value,
                'acceptgrades' => constants::LTI_SETTING_NEVER,
                'servicesalt' => null,
                'gradable' => false,
                'shouldkeep' => true,
            ],
        ];
    }

    /**
     * Test processing with different LIS Basic Outcomes policy scenarios.
     *
     * @param string $ltiversion the LTI version to test.
     * @param int $acceptgrades the accept grades setting.
     * @param string|null $servicesalt the service salt.
     * @param bool $gradable whether the link is gradable.
     * @param bool $shouldkeep whether LIS BO parameters should be preserved.
     * @return void
     */
    #[DataProvider('lis_bo_policy_provider')]
    public function test_process_lis_bo_policy(
        string $ltiversion,
        int $acceptgrades,
        ?string $servicesalt,
        bool $gradable,
        bool $shouldkeep
    ): void {
        $policy = new lis_bo_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub($ltiversion, $acceptgrades)),
            new resource_link_context($this->get_resource_link_stub($servicesalt, $gradable))
        );

        $params = [
            'lis_result_sourcedid' => 'sourcedid',
            'lis_outcome_service_url' => 'https://platform.example.com/ltix/service.php',
            'custom_param' => 'value',
        ];

        $finalparams = $policy->process($params, $launchcontext);

        if ($shouldkeep) {
            $this->assertArrayHasKey('lis_result_sourcedid', $finalparams);
            $this->assertEquals('sourcedid', $finalparams['lis_result_sourcedid']);
            $this->assertArrayHasKey('lis_outcome_service_url', $finalparams);
            $this->assertEquals('https://platform.example.com/ltix/service.php', $finalparams['lis_outcome_service_url']);
        } else {
            $this->assertArrayNotHasKey('lis_result_sourcedid', $finalparams);
            $this->assertArrayNotHasKey('lis_outcome_service_url', $finalparams);
        }

        // Non-LIS parameters should always be preserved.
        $this->assertArrayHasKey('custom_param', $finalparams);
        $this->assertEquals('value', $finalparams['custom_param']);
    }

    /**
     * Test processing without required tool context.
     *
     * @return void
     */
    public function test_process_no_tool_context(): void {
        $policy = new lis_bo_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new resource_link_context($this->get_resource_link_stub('abc123', true))
        );

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches(
            "/^.*context_collection requires context.*tool_context, but it was not provided.*/"
        );
        $policy->process(['lis_result_sourcedid' => 'sourcedid'], $launchcontext);
    }

    /**
     * Test processing without required resource link context.
     *
     * @return void
     */
    public function test_process_no_resource_link_context(): void {
        $policy = new lis_bo_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(lti_version::LTI_VERSION_1P3->value, constants::LTI_SETTING_ALWAYS))
        );

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches(
            "/^.*context_collection requires context.*resource_link_context, but it was not provided.*/"
        );
        $policy->process(['lis_result_sourcedid' => 'sourcedid'], $launchcontext);
    }
}
