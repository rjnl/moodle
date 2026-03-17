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

namespace core_ltix\local\lticore\message\substitution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering variable_map.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(variable_map::class)]
class variable_map_test extends \basic_testcase {

    /**
     * Data provider for variable resolutions.
     *
     * @return array test cases with [map, sourcedata, variable, expected]
     */
    public static function variable_resolutions(): array {
        return [
            'resolve string values' => [
                'map' => ['Context.title' => 'context_title', 'User.username' => 'user_name'],
                'sourcedata' => ['context_title' => 'My Course', 'user_name' => 'john_doe'],
                'variable' => 'Context.title',
                'expected' => 'My Course',
            ],
            'resolve second mapped variable' => [
                'map' => ['Context.title' => 'context_title', 'User.username' => 'user_name'],
                'sourcedata' => ['context_title' => 'My Course', 'user_name' => 'john_doe'],
                'variable' => 'User.username',
                'expected' => 'john_doe',
            ],
            'resolve numeric string values' => [
                'map' => ['Course.id' => 'course_id'],
                'sourcedata' => ['course_id' => '123'],
                'variable' => 'Course.id',
                'expected' => '123',
            ],
            'resolve empty string values' => [
                'map' => ['Context.description' => 'description'],
                'sourcedata' => ['description' => ''],
                'variable' => 'Context.description',
                'expected' => '',
            ],
            'unmapped variable' => [
                'map' => ['Context.title' => 'context_title'],
                'sourcedata' => ['context_title' => 'My Course'],
                'variable' => 'Context.unknown',
                'expected' => null,
            ],
            'mapped variable missing from source data' => [
                'map' => ['Context.title' => 'context_title'],
                'sourcedata' => ['other_key' => 'Some Value'],
                'variable' => 'Context.title',
                'expected' => null,
            ],
            'empty source data' => [
                'map' => ['Context.title' => 'context_title'],
                'sourcedata' => [],
                'variable' => 'Context.title',
                'expected' => null,
            ],
            'empty map' => [
                'map' => [],
                'sourcedata' => ['context_title' => 'My Course'],
                'variable' => 'Context.title',
                'expected' => null,
            ],
        ];
    }

    /**
     * Test that variables are resolved correctly, returning either the mapped value or null.
     *
     * @param array $map the variable map
     * @param array $sourcedata the source data
     * @param string $variable the variable to resolve
     * @param string|null $expected the expected resolved value or null
     * @return void
     */
    #[DataProvider('variable_resolutions')]
    public function test_resolve_variable(array $map, array $sourcedata, string $variable, ?string $expected): void {
        $variablemap = new variable_map($map);
        $result = $variablemap->resolve($sourcedata, $variable);

        if ($expected === null) {
            $this->assertNull($result);
        } else {
            $this->assertSame($expected, $result);
        }
    }

    /**
     * Test that multiple resolutions work independently.
     *
     * @return void
     */
    public function test_multiple_resolutions(): void {
        $map = [
            'Context.title' => 'context_title',
            'User.username' => 'user_name',
            'Context.id' => 'context_id',
        ];
        $sourcedata = [
            'context_title' => 'My Course',
            'user_name' => 'jane_doe',
            'context_id' => '42',
        ];

        $variablemap = new variable_map($map);
        $this->assertSame('My Course', $variablemap->resolve($sourcedata, 'Context.title'));
        $this->assertSame('jane_doe', $variablemap->resolve($sourcedata, 'User.username'));
        $this->assertSame('42', $variablemap->resolve($sourcedata, 'Context.id'));
        $this->assertNull($variablemap->resolve($sourcedata, 'User.email'));
    }
}
