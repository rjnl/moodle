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

namespace core_ltix\local\lticore\message\payload\parameters\pipeline\registry;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_processor;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering parameter_processor_registry.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(parameter_processor_registry::class)]
class parameter_processor_registry_test extends \basic_testcase {

    /**
     * Test that a registered processor can be resolved by key.
     *
     * @return void
     */
    public function test_get_registered_processor(): void {
        $processor = $this->createMock(parameters_processor::class);
        $registry = new parameter_processor_registry([
            'resolver.user' => $processor,
        ]);

        $resolved = $registry->get('resolver.user');

        $this->assertSame($processor, $resolved);
    }

    /**
     * Test that registry accepts any iterable in the constructor.
     *
     * @return void
     */
    public function test_constructor_accepts_iterable(): void {
        $processor = $this->createMock(parameters_processor::class);

        $iterable = (function() use ($processor) {
            yield 'resolver.user' => $processor;
        })();

        $registry = new parameter_processor_registry($iterable);

        $this->assertSame($processor, $registry->get('resolver.user'));
    }

    /**
     * Test that duplicate keys overwrite previous entries.
     *
     * @return void
     */
    public function test_duplicate_keys_last_wins(): void {
        $first = $this->createMock(parameters_processor::class);
        $second = $this->createMock(parameters_processor::class);

        $iterable = (function() use ($first, $second) {
            yield 'resolver.user' => $first;
            yield 'resolver.user' => $second;
        })();

        $registry = new parameter_processor_registry($iterable);

        $this->assertSame($second, $registry->get('resolver.user'));
    }

    /**
     * Test that looking up an unknown key throws.
     *
     * @return void
     */
    public function test_get_unknown_processor_throws(): void {
        $registry = new parameter_processor_registry([]);

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessage('Processor [resolver.missing] not registered.');
        $registry->get('resolver.missing');
    }
}
