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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering built_params_map_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(built_params_map_resolver::class)]
class built_params_map_resolver_test extends \basic_testcase {

    /**
     * Data provider for resolve scenarios.
     *
     * @return array test cases.
     */
    public static function resolve_provider(): array {
        return [
            'Mapped substitution param resolves to a value' => [
                'params' => ['user_id' => '123'],
                'map' => ['User.id' => 'user_id'],
                'input' => '$User.id',
                'expected' => '123',
            ],
            'Non-substitution param input returns null' => [
                'params' => ['user_id' => '123'],
                'map' => ['User.id' => 'user_id'],
                'input' => 'User.id',
                'expected' => null,
            ],
            'Unmapped substitution param returns null' => [
                'params' => ['user_id' => '123'],
                'map' => ['User.id' => 'user_id'],
                'input' => '$User.name',
                'expected' => null,
            ],
            'Mapped key missing in params returns null' => [
                'params' => ['course_id' => '456'],
                'map' => ['User.id' => 'user_id'],
                'input' => '$User.id',
                'expected' => null,
            ],
            'Key mapped to object-sourced variable returns null' => [
                'params' => ['course_id' => '456'],
                'map' => ['User.name' => '$USER->username'],
                'input' => '$User.name',
                'expected' => null,
            ],
            'Key mapped to calculated variable returns null' => [
                'params' => ['course_id' => '456'],
                'map' => ['User.name' => null],
                'input' => '$User.name',
                'expected' => null,
            ],
            'Key mapped to empty string returns null' => [
                'params' => ['course_id' => '456'],
                'map' => ['User.name' => ''],
                'input' => '$User.name',
                'expected' => null,
            ],
        ];
    }

    /**
     * Test resolve behavior for common scenarios.
     *
     * @param array $params pipeline params.
     * @param array $map variable map.
     * @param string $input input variable string.
     * @param string|null $expected expected resolved value.
     * @return void
     */
    #[DataProvider('resolve_provider')]
    public function test_resolve(array $params, array $map, string $input, ?string $expected): void {
        $resolver = new built_params_map_resolver(new variable_map($map));
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('basic-lti-launch-request'))
        );
        $context = substitution_context::for_parameter_pipeline($params, $launchcontext);

        $this->assertSame($expected, $resolver->resolve($input, $context));
    }

    /**
     * Test resolve throws when required pipeline params context is missing.
     *
     * @return void
     */
    public function test_resolve_requires_pipeline_params_context(): void {
        $resolver = new built_params_map_resolver(new variable_map(['User.id' => 'user_id']));
        $context = substitution_context::for_auth([]);

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches(
            '/^.*context_collection requires context.*pipeline_params_context, but it was not provided.*/'
        );
        $resolver->resolve('$User.id', $context);
    }
}
