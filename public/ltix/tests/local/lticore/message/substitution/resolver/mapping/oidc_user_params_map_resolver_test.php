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

namespace core_ltix\local\lticore\message\substitution\resolver\mapping;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\collection\substitution_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\substitution\variable_map;
use core_ltix\local\lticore\message\type\message_type_factory;
use core_ltix\local\ltiopenid\lti_user;
use core_ltix\helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering oidc_user_params_map_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(oidc_user_params_map_resolver::class)]
class oidc_user_params_map_resolver_test extends \basic_testcase {

    /**
     * Data provider for resolve scenarios.
     *
     * @return array test cases.
     */
    public static function resolve_provider(): array {
        return [
            'Mapped substitution param resolves user value' => [
                'userfields' => ['user_id' => '123'],
                'map' => ['User.id' => 'user_id'],
                'input' => '$User.id',
                'expected' => '123',
            ],
            'Non-substitution param input returns null' => [
                'userfields' => ['user_id' => '123'],
                'map' => ['User.id' => 'user_id'],
                'input' => 'User.id',
                'expected' => null,
            ],
            'Unmapped substitution param returns null' => [
                'userfields' => ['user_id' => '123'],
                'map' => ['User.id' => 'user_id'],
                'input' => '$User.name',
                'expected' => null,
            ],
            'Mapped key missing in user fields returns null' => [
                'userfields' => ['user_id' => '456'],
                'map' => ['User.id' => 'user_id'],
                'input' => '$User.username',
                'expected' => null,
            ],
            'Key mapped to object-sourced variable returns null' => [
                'userfields' => ['user_id' => '456'],
                'map' => ['User.name' => '$USER->username'],
                'input' => '$User.name',
                'expected' => null,
            ],
            'Key mapped to calculated variable returns null' => [
                'userfields' => ['user_id' => '456'],
                'map' => ['User.name' => null],
                'input' => '$User.name',
                'expected' => null,
            ],
            'Key mapped to empty string returns null' => [
                'userfields' => ['user_id' => '456'],
                'map' => ['User.name' => ''],
                'input' => '$User.name',
                'expected' => null,
            ],
        ];
    }

    /**
     * Test resolve behavior for common scenarios.
     *
     * @param array $userfields OIDC user fields.
     * @param array $map variable map.
     * @param string $input input variable string.
     * @param string|null $expected expected resolved value.
     * @return void
     */
    #[DataProvider('resolve_provider')]
    public function test_resolve(array $userfields, array $map, string $input, ?string $expected): void {
        $resolver = new oidc_user_params_map_resolver(new variable_map($map));
        $context = substitution_context::for_auth($userfields);

        $this->assertSame($expected, $resolver->resolve($input, $context));
    }

    /**
     * Test resolve throws when required OIDC user context is missing.
     *
     * @return void
     */
    public function test_resolve_requires_oidc_user_context(): void {
        $resolver = new oidc_user_params_map_resolver(new variable_map(['User.id' => 'user_id']));
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('basic-lti-launch-request'))
        );
        $context = substitution_context::for_parameter_pipeline([], $launchcontext);

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches(
            '/^.*context_collection requires context.*oidc_user_context, but it was not provided.*/'
        );
        $resolver->resolve('$User.id', $context);
    }

    /**
     * Test resolving with real user data structure and capability map.
     *
     * @return void
     */
    public function test_resolve_with_real_user_data_and_capabilities_map(): void {
        $user = new lti_user(
            '123',
            'Jane Doe',
            'Jane',
            'Doe',
            'jane.doe@example.com',
            'jd-123',
            'janedoe'
        );
        $userfields = $user->get_unformatted_userdata();
        $map = helper::get_capabilities();

        // Generate a map of inputs to expected values. Only those fields matching a userfield key will be mapped.
        $expectediomap = [];
        foreach ($map as $variable => $fieldkey) {
            $inputvar = '$' . $variable;
            $expectediomap[$inputvar] = array_key_exists($fieldkey, $userfields) ? $userfields[$fieldkey] : null;
        }

        $resolver = new oidc_user_params_map_resolver(new variable_map($map));
        $context = substitution_context::for_auth($userfields);

        foreach ($expectediomap as $mappedvariable => $expected) {
            $this->assertSame($expected, $resolver->resolve($mappedvariable, $context));
        }
    }
}
