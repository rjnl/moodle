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

use core_ltix\local\lticore\message\context\collection\substitution_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering substitute_all_policy.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(substitute_all_policy::class)]
class substitute_all_policy_test extends \basic_testcase {

    /**
     * Data provider for strings that start with $ and should be substituted.
     *
     * @return array test cases with $ prefixed strings.
     */
    public static function dollar_prefixed_string_provider(): array {
        return [
            'Simple variable with dollar prefix' => [
                'input' => '$User.id',
                'shouldsubstitute' => true,
            ],
            'Complex variable path with dollar prefix' => [
                'input' => '$Person.address.street.name',
                'shouldsubstitute' => true,
            ],
            'Mixed case with dollar prefix' => [
                'input' => '$MixedCase.Variable.NAME',
                'shouldsubstitute' => true,
            ],
            'Unicode characters with dollar prefix' => [
                'input' => '$Ñame.Válue',
                'shouldsubstitute' => true,
            ],
            'Dollar sign alone' => [
                'input' => '$',
                'shouldsubstitute' => true,
            ],
            'Dollar with special characters' => [
                'input' => '$!@#%^&*()',
                'shouldsubstitute' => true,
            ],
        ];
    }

    /**
     * Data provider for strings that do NOT start with $ and should not be substituted.
     *
     * @return array test cases with non-$ prefixed strings.
     */
    public static function non_dollar_prefixed_string_provider(): array {
        return [
            'Variable without dollar prefix' => [
                'input' => 'User.id',
                'shouldsubstitute' => false,
            ],
            'Empty string' => [
                'input' => '',
                'shouldsubstitute' => false,
            ],
            'Simple text' => [
                'input' => 'simple_text',
                'shouldsubstitute' => false,
            ],
            'Numeric value' => [
                'input' => '12345',
                'shouldsubstitute' => false,
            ],
            'Special characters without dollar' => [
                'input' => '!@#%^&*()',
                'shouldsubstitute' => false,
            ],
            'Whitespace' => [
                'input' => '   ',
                'shouldsubstitute' => false,
            ],
            'Text with dollar in middle' => [
                'input' => 'price_$100',
                'shouldsubstitute' => false,
            ],
            'Strings like null, false, 0' => [
                'input' => 'false',
                'shouldsubstitute' => false,
            ],
        ];
    }

    /**
     * Test that should_substitute returns true only for $ prefixed strings.
     *
     * @param string $input the input string to test.
     * @param bool $shouldsubstitute expected result.
     * @return void
     */
    #[DataProvider('dollar_prefixed_string_provider')]
    public function test_should_substitute_with_dollar_prefix(string $input, bool $shouldsubstitute): void {
        $policy = new substitute_all_policy();

        // Use OIDC auth context as it requires no external dependencies.
        $context = substitution_context::for_auth([]);

        $result = $policy->should_substitute($input, $context);

        $this->assertEquals($shouldsubstitute, $result);
    }

    /**
     * Test that should_substitute returns false for non-$ prefixed strings.
     *
     * @param string $input the input string to test.
     * @param bool $shouldsubstitute expected result.
     * @return void
     */
    #[DataProvider('non_dollar_prefixed_string_provider')]
    public function test_should_substitute_without_dollar_prefix(string $input, bool $shouldsubstitute): void {
        $policy = new substitute_all_policy();

        // Use OIDC auth context as it requires no external dependencies.
        $context = substitution_context::for_auth([]);

        $result = $policy->should_substitute($input, $context);

        $this->assertEquals($shouldsubstitute, $result);
    }

    /**
     * Test with different context types and $ prefixed strings.
     *
     * @return void
     */
    public function test_should_substitute_with_different_contexts(): void {
        $policy = new substitute_all_policy();

        // OIDC auth context - should allow $ prefixed strings.
        $oidccontext = substitution_context::for_auth([]);
        $this->assertTrue($policy->should_substitute('$User.id', $oidccontext));
        $this->assertFalse($policy->should_substitute('User.id', $oidccontext));

        // Parameter pipeline context - should allow $ prefixed strings.
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = \core_ltix\local\lticore\message\context\collection\launch_context::instance(
            new \core_ltix\local\lticore\message\context\item\message_context(
                $messagetypefactory->from_string('basic-lti-launch-request')
            )
        );
        $pipelinecontext = substitution_context::for_parameter_pipeline([], $launchcontext);
        $this->assertTrue($policy->should_substitute('$Course.id', $pipelinecontext));
        $this->assertFalse($policy->should_substitute('Course.id', $pipelinecontext));
    }
}
