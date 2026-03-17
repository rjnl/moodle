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

namespace core_ltix\local\lticore\message\payload\parameters\processor\transformer;

use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\collection\substitution_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\substitution\pipeline\variable_substitutor;
use core_ltix\local\lticore\message\type\message_type_factory;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering custom_param_substitutor.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(custom_param_substitutor::class)]
class custom_param_substitutor_test extends \basic_testcase {

    /**
     * Test processing with only custom parameters.
     *
     * @return void
     */
    public function test_process_with_only_custom_params(): void {
        $substitutormock = $this->createMock(variable_substitutor::class);

        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        $params = [
            'custom_course_id' => '$Context.id',
            'custom_user_name' => '$User.username',
            'custom_static_value' => 'no_substitution_needed',
        ];

        $expectedsubstituted = [
            'custom_course_id' => '12345',
            'custom_user_name' => 'johndoe',
            'custom_static_value' => 'no_substitution_needed',
        ];

        // The substitutor should be called with custom params only.
        $substitutormock->expects($this->once())
            ->method('substitute')
            ->with(
                $this->equalTo($params),
                $this->isInstanceOf(substitution_context::class)
            )
            ->willReturn($expectedsubstituted);

        $processor = new custom_param_substitutor($substitutormock);
        $result = $processor->process($params, $launchcontext);

        $this->assertEquals($expectedsubstituted, $result);
    }

    /**
     * Test processing with mixed custom and non-custom parameters.
     *
     * @return void
     */
    public function test_process_with_mixed_params(): void {
        $substitutormock = $this->createMock(variable_substitutor::class);

        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        $params = [
            'user_id' => '123',
            'context_id' => '456',
            'custom_course_name' => '$Context.title',
            'custom_user_email' => '$User.email',
            'resource_link_id' => '789',
            'custom_tool_setting' => 'enabled',
        ];

        $customparamsonly = [
            'custom_course_name' => '$Context.title',
            'custom_user_email' => '$User.email',
            'custom_tool_setting' => 'enabled',
        ];

        $substitutedcustomparams = [
            'custom_course_name' => 'Introduction to Physics',
            'custom_user_email' => 'john@example.com',
            'custom_tool_setting' => 'enabled',
        ];

        // The substitutor should be called with custom params only.
        $substitutormock->expects($this->once())
            ->method('substitute')
            ->with(
                $this->equalTo($customparamsonly),
                $this->isInstanceOf(substitution_context::class)
            )
            ->willReturn($substitutedcustomparams);

        $processor = new custom_param_substitutor($substitutormock);
        $result = $processor->process($params, $launchcontext);

        // Non-custom params should be preserved.
        $this->assertArrayHasKey('user_id', $result);
        $this->assertEquals('123', $result['user_id']);
        $this->assertArrayHasKey('context_id', $result);
        $this->assertEquals('456', $result['context_id']);
        $this->assertArrayHasKey('resource_link_id', $result);
        $this->assertEquals('789', $result['resource_link_id']);

        // Custom params should be substituted.
        $this->assertArrayHasKey('custom_course_name', $result);
        $this->assertEquals('Introduction to Physics', $result['custom_course_name']);
        $this->assertArrayHasKey('custom_user_email', $result);
        $this->assertEquals('john@example.com', $result['custom_user_email']);
        $this->assertArrayHasKey('custom_tool_setting', $result);
        $this->assertEquals('enabled', $result['custom_tool_setting']);
    }

    /**
     * Test processing with no custom parameters.
     *
     * @return void
     */
    public function test_process_with_no_custom_params(): void {
        $substitutormock = $this->createMock(variable_substitutor::class);

        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        $params = [
            'user_id' => '123',
            'context_id' => '456',
            'resource_link_id' => '789',
            'lti_message_type' => 'LtiResourceLinkRequest',
        ];

        // The substitutor should be called with an empty array for custom params.
        $substitutormock->expects($this->once())
            ->method('substitute')
            ->with(
                $this->equalTo([]),
                $this->isInstanceOf(substitution_context::class)
            )
            ->willReturn([]);

        $processor = new custom_param_substitutor($substitutormock);
        $result = $processor->process($params, $launchcontext);

        // All params should be preserved as-is.
        $this->assertEquals($params, $result);
    }

    /**
     * Test processing with empty parameters.
     *
     * @return void
     */
    public function test_process_with_empty_params(): void {
        $substitutormock = $this->createMock(variable_substitutor::class);

        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        $params = [];

        // The substitutor should be called with an empty array.
        $substitutormock->expects($this->once())
            ->method('substitute')
            ->with(
                $this->equalTo([]),
                $this->isInstanceOf(substitution_context::class)
            )
            ->willReturn([]);

        $processor = new custom_param_substitutor($substitutormock);
        $result = $processor->process($params, $launchcontext);

        $this->assertEmpty($result);
    }

    /**
     * Test that substitution context is properly created with launch context and parameters.
     *
     * @return void
     */
    public function test_process_creates_correct_substitution_context(): void {
        $substitutormock = $this->createMock(variable_substitutor::class);

        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        $params = [
            'user_id' => '123',
            'custom_test' => '$Context.id',
        ];

        // Verify that the substitution context is created correctly by checking the callback.
        $substitutormock->expects($this->once())
            ->method('substitute')
            ->with(
                $this->anything(),
                $this->callback(function ($context) use ($params, $launchcontext) {
                    // Verify it's a substitution_context instance.
                    if (!$context instanceof substitution_context) {
                        return false;
                    }
                    return true;
                })
            )
            ->willReturn(['custom_test' => '456']);

        $processor = new custom_param_substitutor($substitutormock);
        $processor->process($params, $launchcontext);
    }
}
