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

namespace core_ltix\local\lticore\message\type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering message_type_definition.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(message_type_definition::class)]
class message_type_definition_test extends \basic_testcase {

    /**
     * Data provider for message type definition creation tests.
     *
     * @return array test cases with [canonical, aliases]
     */
    public static function definition_creations(): array {
        return [
            'with multiple aliases' => [
                'canonical' => 'LtiResourceLinkRequest',
                'aliases' => ['basic-lti-launch-request', 'another-alias'],
            ],
            'with empty aliases' => [
                'canonical' => 'basic-lti-launch-request',
                'aliases' => [],
            ],
            'with single alias' => [
                'canonical' => 'basic-lti-launch-request',
                'aliases' => ['BasicLTILaunchRequest'],
            ],
            'with three aliases' => [
                'canonical' => 'canonical',
                'aliases' => ['alias1', 'alias2', 'alias3'],
            ],
            'with custom canonical form' => [
                'canonical' => 'my-canonical-form',
                'aliases' => [],
            ],
        ];
    }

    /**
     * Test that a message type definition is created correctly and returns values unchanged.
     *
     * @param string $canonical the canonical form
     * @param array $aliases the aliases
     * @return void
     */
    #[DataProvider('definition_creations')]
    public function test_definition_creation(string $canonical, array $aliases): void {
        $definition = new message_type_definition($canonical, $aliases);

        $this->assertSame($canonical, $definition->canonical());
        $this->assertSame($aliases, $definition->aliases());
    }
}
