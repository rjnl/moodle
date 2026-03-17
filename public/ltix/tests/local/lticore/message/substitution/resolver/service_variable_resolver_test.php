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

namespace core_ltix\local\lticore\message\substitution\resolver;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\collection\substitution_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use core_ltix\local\ltiservice\plugin_substitution_service_interface;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering service_variable_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(service_variable_resolver::class)]
class service_variable_resolver_test extends \basic_testcase {

    /**
     * Test that non-substitution variable input returns null without delegating to service.
     *
     * @return void
     */
    public function test_non_substitution_variable_input_returns_null(): void {
        $service = $this->createMock(plugin_substitution_service_interface::class);
        $service->expects($this->never())
            ->method('substitute');

        $resolver = new service_variable_resolver($service);
        $context = substitution_context::for_auth([]);

        $this->assertNull($resolver->resolve('User.id', $context));
    }

    /**
     * Test that substitution variables are delegated to the plugin substitution service.
     *
     * @return void
     */
    public function test_delegates_to_plugin_service(): void {
        $service = $this->createMock(plugin_substitution_service_interface::class);
        $service->expects($this->once())
            ->method('substitute')
            ->with(
                $this->isInstanceOf(launch_context::class),
                '$LtiServiceVar'
            )
            ->willReturn('resolved');

        $resolver = new service_variable_resolver($service);

        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('basic-lti-launch-request'))
        );
        $context = substitution_context::for_parameter_pipeline([], $launchcontext);

        $this->assertSame('resolved', $resolver->resolve('$LtiServiceVar', $context));
    }

    /**
     * Test that missing launch context throws an exception.
     *
     * @return void
     */
    public function test_missing_launch_context_throws(): void {
        $service = $this->createMock(plugin_substitution_service_interface::class);
        $resolver = new service_variable_resolver($service);
        $context = substitution_context::for_auth([]);

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches(
            "/^.*context_collection requires context.*launch_context, but it was not provided.*/"
        );
        $resolver->resolve('$LtiServiceVar', $context);
    }
}
