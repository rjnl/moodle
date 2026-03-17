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

namespace core_ltix\local\lticore\message\payload\parameters\processor\resolver\common;

use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use core_ltix\local\ltiservice\plugin_parameters_service_interface;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering ltixservice_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(ltixservice_resolver::class)]
class ltixservice_resolver_test extends \basic_testcase {

    /**
     * Test processing with service parameters.
     *
     * @return void
     */
    public function test_process_with_service_parameters(): void {
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        // Create a stub for the plugin_parameters_service_interface.
        $servicestub = $this->createStub(plugin_parameters_service_interface::class);

        // Configure the stub to return specific parameters.
        $servicestub->method('get_launch_parameters')
            ->willReturn([
                'custom_context_memberships_url' => 'https://example.com/api/lti/courses/123/memberships',
                'custom_lineitems_url' => 'https://example.com/api/lti/courses/123/lineitems',
                'custom_lineitem_url' => 'https://example.com/api/lti/courses/123/lineitems/456',
            ]);

        $resolver = new ltixservice_resolver($servicestub);
        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The existing params are unchanged.
        $this->assertEquals($params['existing_param'], $finalparams['existing_param']);

        // The service parameters were added.
        $this->assertArrayHasKey('custom_context_memberships_url', $finalparams);
        $this->assertEquals(
            'https://example.com/api/lti/courses/123/memberships',
            $finalparams['custom_context_memberships_url']
        );
        $this->assertArrayHasKey('custom_lineitems_url', $finalparams);
        $this->assertEquals(
            'https://example.com/api/lti/courses/123/lineitems',
            $finalparams['custom_lineitems_url']
        );
        $this->assertArrayHasKey('custom_lineitem_url', $finalparams);
        $this->assertEquals(
            'https://example.com/api/lti/courses/123/lineitems/456',
            $finalparams['custom_lineitem_url']
        );
    }

    /**
     * Test processing with empty service parameters.
     *
     * @return void
     */
    public function test_process_with_empty_service_parameters(): void {
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        // Create a stub that returns an empty array.
        $servicestub = $this->createStub(plugin_parameters_service_interface::class);
        $servicestub->method('get_launch_parameters')
            ->willReturn([]);

        $resolver = new ltixservice_resolver($servicestub);
        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The existing params are unchanged.
        $this->assertEquals($params['existing_param'], $finalparams['existing_param']);

        // No additional parameters were added.
        $this->assertCount(1, $finalparams);
    }

    /**
     * Test processing merges service parameters correctly.
     *
     * @return void
     */
    public function test_process_merges_parameters_correctly(): void {
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        // Create a stub that returns multiple service parameters.
        $servicestub = $this->createStub(plugin_parameters_service_interface::class);
        $servicestub->method('get_launch_parameters')
            ->willReturn([
                'custom_param1' => 'value1',
                'custom_param2' => 'value2',
                'custom_param3' => 'value3',
            ]);

        $resolver = new ltixservice_resolver($servicestub);
        $params = [
            'custom_param1' => 'existing_value1',
            'custom_param2' => 'existing_value2',
        ];
        $finalparams = $resolver->process($params, $launchcontext);

        // Any existing params are overridden with service values.
        $this->assertEquals('value1', $finalparams['custom_param1']);
        $this->assertEquals('value2', $finalparams['custom_param2']);
        $this->assertEquals('value3', $finalparams['custom_param3']);

        // Total count is correct.
        $this->assertCount(3, $finalparams);
    }

    /**
     * Test that service is called with the correct launch context.
     *
     * @return void
     */
    public function test_process_passes_launch_context_to_service(): void {
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        // Create a mock to verify the launch context is passed correctly.
        $servicemock = $this->createMock(plugin_parameters_service_interface::class);
        $servicemock->expects($this->once())
            ->method('get_launch_parameters')
            ->with($this->identicalTo($launchcontext))
            ->willReturn(['custom_test' => 'test_value']);

        $resolver = new ltixservice_resolver($servicemock);
        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // Verify the parameters were merged correctly.
        $this->assertArrayHasKey('custom_test', $finalparams);
        $this->assertEquals('test_value', $finalparams['custom_test']);
    }
}
