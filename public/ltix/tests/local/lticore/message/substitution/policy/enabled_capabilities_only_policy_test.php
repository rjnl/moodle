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

namespace core_ltix\local\lticore\message\substitution\policy;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\collection\substitution_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering enabled_capabilities_only_policy.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(enabled_capabilities_only_policy::class)]
class enabled_capabilities_only_policy_test extends \basic_testcase {

    /**
     * Helper returning a tool config stub with specified enabled capabilities.
     *
     * @param string $enabledcapabilities the enabled capabilities as a newline-separated string.
     * @return \stdClass the object stub.
     */
    protected function get_tool_config_stub(string $enabledcapabilities = ''): \stdClass {
        return (object) [
            'tool' => (object) [
                'id' => '123',
                'clientid' => '123456-abcd',
                'ltiversion' => '2.0',
                'enabledcapability' => $enabledcapabilities,
            ],
            'config' => (object) [
                'initiatelogin' => 'https://tool.example.com/lti/initiatelogin',
                'organizationid' => 'https://platform.example.com',
            ],
        ];
    }

    /**
     * Data provider for capability substitution scenarios.
     *
     * @return array test cases.
     */
    public static function capability_provider(): array {
        return [
            'Variable in enabled capabilities should be substituted' => [
                'enabledcapabilities' => "User.id\nCourse.id\nUser.username",
                'variable' => '$User.id',
                'shouldsubstitute' => true,
            ],
            'Variable not in enabled capabilities should not be substituted' => [
                'enabledcapabilities' => "User.id\nCourse.id",
                'variable' => '$User.username',
                'shouldsubstitute' => false,
            ],
            'Empty enabled capabilities should reject all variables' => [
                'enabledcapabilities' => '',
                'variable' => '$User.id',
                'shouldsubstitute' => false,
            ],
            'Variable without $ prefix should not be substituted' => [
                'enabledcapabilities' => "User.id\nCourse.id",
                'variable' => 'User.id',
                'shouldsubstitute' => false,
            ],
            'Single capability enabled matches' => [
                'enabledcapabilities' => 'User.username',
                'variable' => '$User.username',
                'shouldsubstitute' => true,
            ],
            'Case sensitive matching - different case should not match' => [
                'enabledcapabilities' => 'User.id',
                'variable' => '$user.id',
                'shouldsubstitute' => false,
            ],
            'Whitespace in capabilities list' => [
                'enabledcapabilities' => "User.id\n\nCourse.id\n",
                'variable' => '$Course.id',
                'shouldsubstitute' => true,
            ],
            'Variable with extra characters should not match' => [
                'enabledcapabilities' => 'User.id',
                'variable' => '$User.id.extra',
                'shouldsubstitute' => false,
            ],
            'Multiple capabilities with trailing newline' => [
                'enabledcapabilities' => "User.id\nCourse.id\nContext.id\n",
                'variable' => '$Context.id',
                'shouldsubstitute' => true,
            ],
        ];
    }

    /**
     * Test should_substitute with different capability scenarios.
     *
     * @param string $enabledcapabilities the enabled capabilities string.
     * @param string $variable the variable to test.
     * @param bool $shouldsubstitute expected result.
     * @return void
     */
    #[DataProvider('capability_provider')]
    public function test_should_substitute_with_capabilities(
        string $enabledcapabilities,
        string $variable,
        bool $shouldsubstitute
    ): void {
        $policy = new enabled_capabilities_only_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('basic-lti-launch-request')),
            new tool_context($this->get_tool_config_stub($enabledcapabilities))
        );

        $substitutioncontext = substitution_context::for_parameter_pipeline([], $launchcontext);

        $result = $policy->should_substitute($variable, $substitutioncontext);

        $this->assertEquals($shouldsubstitute, $result);
    }

    /**
     * Test with multiple variables to ensure independence.
     *
     * @return void
     */
    public function test_should_substitute_multiple_variables(): void {
        $policy = new enabled_capabilities_only_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $enabledcapabilities = "User.id\nCourse.id";
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('basic-lti-launch-request')),
            new tool_context($this->get_tool_config_stub($enabledcapabilities))
        );

        $substitutioncontext = substitution_context::for_parameter_pipeline([], $launchcontext);

        // Enabled variable should be substituted.
        $this->assertTrue($policy->should_substitute('$User.id', $substitutioncontext));
        $this->assertTrue($policy->should_substitute('$Course.id', $substitutioncontext));

        // Non-enabled variable should not be substituted.
        $this->assertFalse($policy->should_substitute('$User.username', $substitutioncontext));
        $this->assertFalse($policy->should_substitute('$Context.id', $substitutioncontext));

        // Non-variable strings should not be substituted.
        $this->assertFalse($policy->should_substitute('User.id', $substitutioncontext));
        $this->assertFalse($policy->should_substitute('regular_value', $substitutioncontext));
    }

    /**
     * Test processing without required tool context.
     *
     * @return void
     */
    public function test_should_substitute_no_tool_context(): void {
        $policy = new enabled_capabilities_only_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('basic-lti-launch-request'))
        );

        $substitutioncontext = substitution_context::for_parameter_pipeline([], $launchcontext);

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches(
            "/^.*context_collection requires context.*tool_context, but it was not provided.*/"
        );
        $policy->should_substitute('$User.id', $substitutioncontext);
    }

    /**
     * Test processing without required launch context.
     *
     * @return void
     */
    public function test_should_substitute_no_launch_context(): void {
        $policy = new enabled_capabilities_only_policy();

        $substitutioncontext = substitution_context::for_auth([]);

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches(
            "/^.*context_collection requires context.*launch_context, but it was not provided.*/"
        );
        $policy->should_substitute('$User.id', $substitutioncontext);
    }

    /**
     * Test with null enabledcapability property.
     *
     * @return void
     */
    public function test_should_substitute_with_null_enabled_capability(): void {
        $policy = new enabled_capabilities_only_policy();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $toolconfig = (object) [
            'tool' => (object) [
                'id' => '123',
                'clientid' => '123456-abcd',
                'ltiversion' => '2.0',
            ],
            'config' => (object) [
                'initiatelogin' => 'https://tool.example.com/lti/initiatelogin',
                'organizationid' => 'https://platform.example.com',
            ],
        ];

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('basic-lti-launch-request')),
            new tool_context($toolconfig)
        );

        $substitutioncontext = substitution_context::for_parameter_pipeline([], $launchcontext);

        // When enabledcapability is null/not set, no variables should be substituted.
        $this->assertFalse($policy->should_substitute('$User.id', $substitutioncontext));
        $this->assertFalse($policy->should_substitute('$Course.id', $substitutioncontext));
    }
}
