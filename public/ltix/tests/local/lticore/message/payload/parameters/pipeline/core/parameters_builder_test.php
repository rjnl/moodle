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

namespace core_ltix\local\lticore\message\payload\parameters\pipeline\core;

use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_builder;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_processor;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering parameters_builder.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(parameters_builder::class)]
class parameters_builder_test extends \basic_testcase {

    /**
     * Test build() method calls steps in order and that each step can augment the data returned by the previous step.
     *
     * @return void
     */
    public function test_build_steps_in_order(): void {
        $proc1 = $this->createMock(parameters_processor::class);
        $proc2 = $this->createMock(parameters_processor::class);

        $proc1->expects($this->once())
            ->method('process')
            ->willReturn(['a' => 1]);

        $proc2->expects($this->once())
            ->method('process')
            ->with(['a' => 1])
            ->willReturn(['a' => 55, 'b' => 2]);

        $pipeline = new parameters_builder([$proc1, $proc2]);

        $mocklaunchcontext = $this->createMock(launch_context::class);
        $result = $pipeline->build($mocklaunchcontext);

        $this->assertSame([
            'a' => 55,
            'b' => 2,
        ], $result);
    }

    /**
     * Tests that ::build returns an empty array when initialized with an empty configuration.
     *
     * @return void
     */
    public function test_build_steps_can_be_empty(): void {
        $pipeline = new parameters_builder([]);
        $mocklaunchcontext = $this->createMock(launch_context::class);
        $result = $pipeline->build($mocklaunchcontext);
        $this->assertSame([], $result);
    }

    /**
     * Tests that the pipeline stops processing and throws an exception when a processor throws an exception.
     *
     * @return void
     */
    public function test_stops_when_processor_throws(): void {
        $proc = $this->createMock(parameters_processor::class);
        $proc->method('process')
            ->willThrowException(new \Exception('error'));

        $pipeline = new parameters_builder([$proc]);
        $this->expectException(\Exception::class);
        $pipeline->build($this->createMock(launch_context::class));
    }
}
