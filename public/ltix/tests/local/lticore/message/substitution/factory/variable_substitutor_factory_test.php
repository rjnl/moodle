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

namespace core_ltix\local\lticore\message\substitution\factory;

use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\substitution\factory\variable_substitutor_factory;
use core_ltix\local\lticore\message\substitution\pipeline\variable_substitutor;
use core_ltix\local\lticore\message\substitution\policy\enabled_capabilities_only_policy;
use core_ltix\local\lticore\message\substitution\policy\substitute_all_policy;
use core_ltix\local\lticore\message\substitution\resolver\calculated_course_variable_resolver;
use core_ltix\local\lticore\message\substitution\resolver\calculated_user_variable_resolver;
use core_ltix\local\lticore\message\substitution\resolver\mapping\built_params_map_resolver;
use core_ltix\local\lticore\message\substitution\resolver\mapping\oidc_user_params_map_resolver;
use core_ltix\local\lticore\message\substitution\resolver\object_property_resolver;
use core_ltix\local\lticore\message\substitution\resolver\service_variable_resolver;
use core_ltix\local\ltiservice\plugin_substitution_service_interface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering variable_substitutor_factory.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(variable_substitutor_factory::class)]
class variable_substitutor_factory_test extends \basic_testcase {

    #[DataProvider('pipeline_data_provider')]
    public function test_builds_registered_pipelines(lti_version $ltiversion, string $policyclass, array $expectedresolvers): void {
        $pluginsubservice = $this->createMock(plugin_substitution_service_interface::class);
        $factory = new variable_substitutor_factory($pluginsubservice);

        $pipeline = $factory->get_for_version($ltiversion);

        $reflection = new \ReflectionClass($pipeline);
        $policyproperty = $reflection->getProperty('policy');
        $policyproperty->setAccessible(true);
        $policy = $policyproperty->getValue($pipeline);

        $resolverproperty = $reflection->getProperty('resolvers');
        $resolverproperty->setAccessible(true);
        $resolvers = $resolverproperty->getValue($pipeline);

        $this->assertInstanceOf($policyclass, $policy);
        $this->assertCount(count($expectedresolvers), $resolvers);
        $this->assertEquals($expectedresolvers, array_map(fn($resolver) => get_class($resolver), $resolvers));
    }

    /**
     * Data provider for testing the pipeline creation.
     *
     * @return array[] the test case data.
     */
    public static function pipeline_data_provider(): array {
        return [
            'LTI 1p1 uses substitute all policy' => [
                'ltiversion' => lti_version::LTI_VERSION_1,
                'policyclass' => substitute_all_policy::class,
                'expectedresolvers' => [
                    built_params_map_resolver::class,
                    object_property_resolver::class,
                    calculated_course_variable_resolver::class,
                    calculated_user_variable_resolver::class,
                    service_variable_resolver::class,
                ],
            ],
            'LTI 1p3 uses substitute all policy' => [
                'ltiversion' => lti_version::LTI_VERSION_1P3,
                'policyclass' => substitute_all_policy::class,
                'expectedresolvers' => [
                    built_params_map_resolver::class,
                    object_property_resolver::class,
                    calculated_course_variable_resolver::class,
                    calculated_user_variable_resolver::class,
                    service_variable_resolver::class,
                ],
            ],
            'LTI 2p0 uses enabled capabilities policy' => [
                'ltiversion' => lti_version::LTI_VERSION_2,
                'policyclass' => enabled_capabilities_only_policy::class,
                'expectedresolvers' => [
                    built_params_map_resolver::class,
                    object_property_resolver::class,
                    calculated_course_variable_resolver::class,
                    calculated_user_variable_resolver::class,
                    service_variable_resolver::class,
                ],
            ],
        ];
    }

    /**
     * Tests that get_for_oidc_auth creates the correct substitutor for OIDC authentication.
     *
     * @return void
     */
    public function test_builds_oidc_auth_pipeline(): void {
        $pluginsubservice = $this->createMock(plugin_substitution_service_interface::class);
        $factory = new variable_substitutor_factory($pluginsubservice);

        $pipeline = $factory->get_for_oidc_auth();

        $this->assertInstanceOf(variable_substitutor::class, $pipeline);

        $reflection = new \ReflectionClass($pipeline);
        $policyproperty = $reflection->getProperty('policy');
        $policyproperty->setAccessible(true);
        $policy = $policyproperty->getValue($pipeline);

        $resolverproperty = $reflection->getProperty('resolvers');
        $resolverproperty->setAccessible(true);
        $resolvers = $resolverproperty->getValue($pipeline);

        $this->assertInstanceOf(substitute_all_policy::class, $policy);
        $this->assertIsArray($resolvers);
        $this->assertCount(1, $resolvers);
        $this->assertInstanceOf(oidc_user_params_map_resolver::class, $resolvers[0]);
    }
}
