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

namespace core_ltix\local\lticore\message\payload\parameters\pipeline\factory;

use core\di;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\payload\parameters\pipeline\factory\custom_param_substitutor_factory;
use core_ltix\local\lticore\message\payload\parameters\pipeline\factory\custom_parameter_normaliser_factory;
use core_ltix\local\lticore\message\payload\parameters\pipeline\factory\parameters_builder_factory;
use core_ltix\local\lticore\message\payload\parameters\pipeline\registry\parameter_processor_registry;
use core_ltix\local\lticore\message\payload\parameters\processor\converter\jwt_claim_converter;
use core_ltix\local\lticore\message\payload\parameters\processor\policy\exclude_user_params_policy;
use core_ltix\local\lticore\message\payload\parameters\processor\policy\lis_bo_policy;
use core_ltix\local\lticore\message\payload\parameters\processor\policy\pii_policy;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\context_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\ext_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\launch_presentation_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\lis_bo_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\lis_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\ltixservice_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\tool_consumer_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\user_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\custom\resource_link_launch_custom_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\transformer\custom_param_substitutor;
use core_ltix\local\lticore\message\payload\parameters\processor\transformer\custom_parameter_normaliser;
use core_ltix\local\lticore\message\substitution\factory\variable_substitutor_factory;
use core_ltix\local\lticore\message\type\message_type_factory;
use core_ltix\local\lticore\message\type\message_type_registry;
use core_ltix\local\ltiservice\plugin_substitution_service_interface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering the parameters_builder_factory class.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(parameters_builder_factory::class)]
class parameters_builder_factory_test extends \basic_testcase {

    #[DataProvider('pipeline_data_provider')]
    public function test_builds_registered_pipelines(array $inputs, array $expectedcomposition): void {

        $messagetyperegistry = di::get(message_type_registry::class);
        $processorregistry = di::get(parameter_processor_registry::class);

        $messagetypefactory = new message_type_factory($messagetyperegistry);

        $factory = new parameters_builder_factory(
            $processorregistry,
            new custom_param_substitutor_factory(
                new variable_substitutor_factory(
                    $this->createMock(plugin_substitution_service_interface::class)
                )
            ),
            new custom_parameter_normaliser_factory(),
            $messagetypefactory
        );

        $pipeline = $factory->create_for(
            $inputs['ltiversion'],
            $messagetypefactory->from_string($inputs['messagetypestring'])
        );

        $reflection = new \ReflectionClass($pipeline);
        $property = $reflection->getProperty('processors');
        $property->setAccessible(true);

        $procs = $property->getValue($pipeline);

        $this->assertCount(count($expectedcomposition), $procs);
        $this->assertEquals($expectedcomposition, array_map(fn($proc) => get_class($proc), $procs));
    }

    /**
     * Data provider for testing the pipeline creation.
     *
     * @return array[] the test case data.
     */
    public static function pipeline_data_provider(): array {
        return [
            '1p3 Resource Link Request' => [
                'inputs' => [
                    'ltiversion' => lti_version::LTI_VERSION_1P3,
                    'messagetypestring' => 'LtiResourceLinkRequest',
                ],
                'expectedcomposition' => [
                    context_resolver::class,
                    resource_link_launch_custom_resolver::class,
                    custom_parameter_normaliser::class,
                    lis_resolver::class,
                    ext_resolver::class,
                    lis_bo_resolver::class,
                    tool_consumer_resolver::class,
                    launch_presentation_resolver::class,
                    ltixservice_resolver::class,
                    lis_bo_policy::class,
                    pii_policy::class,
                    exclude_user_params_policy::class,
                    custom_param_substitutor::class,
                    jwt_claim_converter::class,
                ]
            ],
            '1p1 basic launch request' => [
                'inputs' => [
                    'ltiversion' => lti_version::LTI_VERSION_1,
                    'messagetypestring' => 'basic-lti-launch-request',
                ],
                'expectedcomposition' => [
                    context_resolver::class,
                    resource_link_launch_custom_resolver::class,
                    custom_parameter_normaliser::class,
                    user_resolver::class,
                    lis_resolver::class,
                    ext_resolver::class,
                    lis_bo_resolver::class,
                    tool_consumer_resolver::class,
                    launch_presentation_resolver::class,
                    ltixservice_resolver::class,
                    pii_policy::class,
                    lis_bo_policy::class,
                    custom_param_substitutor::class
                ]
            ],
            '2p0 basic launch request' => [
                'inputs' => [
                    'ltiversion' => lti_version::LTI_VERSION_2,
                    'messagetypestring' => 'basic-lti-launch-request',
                ],
                'expectedcomposition' => [
                    context_resolver::class,
                    resource_link_launch_custom_resolver::class,
                    custom_parameter_normaliser::class,
                    user_resolver::class,
                    lis_resolver::class,
                    ext_resolver::class,
                    lis_bo_resolver::class,
                    tool_consumer_resolver::class,
                    launch_presentation_resolver::class,
                    custom_param_substitutor::class
                ]
            ]
        ];
    }
}
