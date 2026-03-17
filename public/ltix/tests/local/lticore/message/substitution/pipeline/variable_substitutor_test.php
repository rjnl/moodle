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

namespace core_ltix\local\lticore\message\substitution\pipeline;

use core_ltix\local\lticore\message\context\collection\substitution_context;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering variable_substitutor.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(variable_substitutor::class)]
class variable_substitutor_test extends \basic_testcase {

    /**
     * Tests that the policy can block substitution and resolvers are not invoked.
     *
     * @return void
     */
    public function test_policy_can_block_substitution(): void {
        $params = ['param1' => '$User.username', 'param2' => '$Course.id'];
        $context = substitution_context::for_auth([]);

        $policy = $this->createMock(substitution_policy::class);
        $policy->expects($this->exactly(2))
            ->method('should_substitute')
            ->with($this->isString(), $this->identicalTo($context))
            ->willReturn(false);

        $resolver = $this->createMock(variable_resolver::class);
        $resolver->expects($this->never())
            ->method('resolve');

        $substitutor = new variable_substitutor($policy, [$resolver]);
        $result = $substitutor->substitute($params, $context);

        $this->assertSame($params, $result);
    }

    /**
     * Tests that substitution stops once a resolver returns a non-null value.
     *
     * @return void
     */
    public function test_resolvers_run_until_value_is_resolved(): void {
        $params = ['param1' => '$User.id'];
        $context = substitution_context::for_auth([]);

        $policy = $this->createMock(substitution_policy::class);
        $policy->expects($this->once())
            ->method('should_substitute')
            ->with($params['param1'], $this->identicalTo($context))
            ->willReturn(true);

        $resolver1 = $this->createMock(variable_resolver::class);
        $resolver1->expects($this->once())
            ->method('resolve')
            ->with($params['param1'], $this->identicalTo($context))
            ->willReturn(null);

        $resolver2 = $this->createMock(variable_resolver::class);
        $resolver2->expects($this->once())
            ->method('resolve')
            ->with($params['param1'], $this->identicalTo($context))
            ->willReturn('42');

        $resolver3 = $this->createMock(variable_resolver::class);
        $resolver3->expects($this->never())
            ->method('resolve');

        $substitutor = new variable_substitutor($policy, [$resolver1, $resolver2, $resolver3]);
        $result = $substitutor->substitute($params, $context);

        $this->assertSame(['param1' => '42'], $result);
    }
}
