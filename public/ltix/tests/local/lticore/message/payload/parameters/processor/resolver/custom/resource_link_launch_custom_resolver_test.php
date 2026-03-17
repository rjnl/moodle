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

namespace core_ltix\local\lticore\message\payload\parameters\processor\resolver\custom;

use core_ltix\constants;
use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\context\item\resource_link_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use core_ltix\local\lticore\models\resource_link;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering resource_link_launch_custom_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(resource_link_launch_custom_resolver::class)]
class resource_link_launch_custom_resolver_test extends \basic_testcase {

    /**
     * Helper returning a tool config stub.
     *
     * @param string|null $customparameters the tool-level custom parameters, or null for none.
     * @return \stdClass the object stub.
     */
    protected function get_tool_config_stub(?string $customparameters = null): \stdClass {
        return (object) [
            'tool' => (object) [
                'id' => '123',
                'clientid' => '123456-abcd',
                'ltiversion' => '1.3.0',
            ],
            'config' => (object) [
                'initiatelogin' => 'https://tool.example.com/lti/initiatelogin',
                'organizationid' => 'https://platform.example.com',
                'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
                ...($customparameters !== null ? ['customparameters' => $customparameters] : []),
            ],
        ];
    }

    /**
     * Helper returning a resource link stub.
     *
     * @param string|null $customparams the resource-link-level custom parameters, or null for none.
     * @return resource_link the resource link stub.
     */
    protected function get_resource_link_stub(?string $customparams = null): resource_link {
        return new resource_link(0, (object) [
            'id' => 24,
            'typeid' => 123,
            'component' => 'mod_lti',
            'itemtype' => 'launch',
            'itemid' => 1,
            'contextid' => 456,
            'url' => 'https://tool.example.com/lti/resource/1',
            'title' => 'Test Resource Link',
            'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_DEFAULT,
            ...(!empty($customparams) ? ['customparams' => $customparams] : []),
        ]);
    }

    /**
     * Test processing with tool-level custom parameters only.
     *
     * @return void
     */
    public function test_process_with_tool_custom_parameters_only(): void {
        $resolver = new resource_link_launch_custom_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $toolcustomparams = "tool_param1=value1\ntool_param2=value2\ntool_param3=value3";

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub($toolcustomparams)),
            new resource_link_context($this->get_resource_link_stub(null))
        );

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The existing params are unchanged.
        $this->assertEquals($params['existing_param'], $finalparams['existing_param']);

        // The tool-level custom params were added.
        $this->assertArrayHasKey('custom_tool_param1', $finalparams);
        $this->assertEquals('value1', $finalparams['custom_tool_param1']);
        $this->assertArrayHasKey('custom_tool_param2', $finalparams);
        $this->assertEquals('value2', $finalparams['custom_tool_param2']);
        $this->assertArrayHasKey('custom_tool_param3', $finalparams);
        $this->assertEquals('value3', $finalparams['custom_tool_param3']);
    }

    /**
     * Test processing with resource-link-level custom parameters only.
     *
     * @return void
     */
    public function test_process_with_resource_link_custom_parameters_only(): void {
        $resolver = new resource_link_launch_custom_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $linkcustomparams = "link_param1=link_value1\nlink_param2=link_value2";

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(null)),
            new resource_link_context($this->get_resource_link_stub($linkcustomparams))
        );

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The existing params are unchanged.
        $this->assertEquals($params['existing_param'], $finalparams['existing_param']);

        // The resource-link-level custom params were added.
        $this->assertArrayHasKey('custom_link_param1', $finalparams);
        $this->assertEquals('link_value1', $finalparams['custom_link_param1']);
        $this->assertArrayHasKey('custom_link_param2', $finalparams);
        $this->assertEquals('link_value2', $finalparams['custom_link_param2']);
    }

    /**
     * Test that tool-level custom parameters override link-level parameters of the same name.
     *
     * @return void
     */
    public function test_tool_params_override_link_params(): void {
        $resolver = new resource_link_launch_custom_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $toolcustomparams = "shared_param=tool_value\ntool_only=from_tool";
        $linkcustomparams = "shared_param=link_value\nlink_only=from_link";

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub($toolcustomparams)),
            new resource_link_context($this->get_resource_link_stub($linkcustomparams))
        );

        $params = [];
        $finalparams = $resolver->process($params, $launchcontext);

        // The shared param should use the tool value (override).
        $this->assertArrayHasKey('custom_shared_param', $finalparams);
        $this->assertEquals('tool_value', $finalparams['custom_shared_param']);

        // Tool-only param should be from tool.
        $this->assertArrayHasKey('custom_tool_only', $finalparams);
        $this->assertEquals('from_tool', $finalparams['custom_tool_only']);

        // Link-only param should be from link.
        $this->assertArrayHasKey('custom_link_only', $finalparams);
        $this->assertEquals('from_link', $finalparams['custom_link_only']);
    }

    /**
     * Test processing with substitution variables in tool parameters.
     *
     * @return void
     */
    public function test_process_with_substitution_variables(): void {
        $resolver = new resource_link_launch_custom_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $toolcustomparams = "userid=\$Person.sourcedId\ntimezone=\$Person.address.timezone";

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub($toolcustomparams)),
            new resource_link_context($this->get_resource_link_stub(null))
        );

        $params = [];
        $finalparams = $resolver->process($params, $launchcontext);

        // Substitution variables should be preserved as-is.
        $this->assertArrayHasKey('custom_userid', $finalparams);
        $this->assertEquals('$Person.sourcedId', $finalparams['custom_userid']);

        $this->assertArrayHasKey('custom_timezone', $finalparams);
        $this->assertEquals('$Person.address.timezone', $finalparams['custom_timezone']);
    }

    /**
     * Test processing with multiple newline formats.
     *
     * @return void
     */
    public function test_process_with_different_newline_formats(): void {
        $resolver = new resource_link_launch_custom_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        // Mix of Unix (\n) and Windows (\r\n) line endings.
        $linkcustomparams = "param1=value1\r\nparam2=value2\nparam3=value3";

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(null)),
            new resource_link_context($this->get_resource_link_stub($linkcustomparams))
        );

        $params = [];
        $finalparams = $resolver->process($params, $launchcontext);

        // All parameters should be parsed correctly regardless of newline format.
        $this->assertArrayHasKey('custom_param1', $finalparams);
        $this->assertEquals('value1', $finalparams['custom_param1']);

        $this->assertArrayHasKey('custom_param2', $finalparams);
        $this->assertEquals('value2', $finalparams['custom_param2']);

        $this->assertArrayHasKey('custom_param3', $finalparams);
        $this->assertEquals('value3', $finalparams['custom_param3']);
    }

    /**
     * Test processing with whitespace in parameter names and values.
     *
     * @return void
     */
    public function test_process_with_whitespace_handling(): void {
        $resolver = new resource_link_launch_custom_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        // Parameters with leading/trailing whitespace.
        $linkcustomparams = "  param_with_spaces  =  value_with_spaces  \nnormal_param=normal_value";

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(null)),
            new resource_link_context($this->get_resource_link_stub($linkcustomparams))
        );

        $params = [];
        $finalparams = $resolver->process($params, $launchcontext);

        // Whitespace should be trimmed from keys and values.
        $this->assertArrayHasKey('custom_param_with_spaces', $finalparams);
        $this->assertEquals('value_with_spaces', $finalparams['custom_param_with_spaces']);

        $this->assertArrayHasKey('custom_normal_param', $finalparams);
        $this->assertEquals('normal_value', $finalparams['custom_normal_param']);
    }

    /**
     * Test processing with no custom parameters.
     *
     * @return void
     */
    public function test_process_with_no_custom_parameters(): void {
        $resolver = new resource_link_launch_custom_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(null)),
            new resource_link_context($this->get_resource_link_stub(null))
        );

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // Only existing params should be present.
        $this->assertEquals($params['existing_param'], $finalparams['existing_param']);
        $this->assertCount(1, $finalparams);
    }

    /**
     * Test processing without required resource link context.
     *
     * @return void
     */
    public function test_process_no_resource_link_context(): void {
        $resolver = new resource_link_launch_custom_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub())
        );

        $params = ['existing_param' => 'value'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*resource_link_context, but it was not.*/");
        $resolver->process($params, $launchcontext);
    }

    /**
     * Test processing without required tool context.
     *
     * @return void
     */
    public function test_process_no_tool_context(): void {
        $resolver = new resource_link_launch_custom_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new resource_link_context($this->get_resource_link_stub())
        );

        $params = ['existing_param' => 'value'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*tool_context, but it was not provided.*/");
        $resolver->process($params, $launchcontext);
    }

    /**
     * Test processing with special characters in parameter values.
     *
     * @return void
     */
    public function test_process_with_special_characters(): void {
        $resolver = new resource_link_launch_custom_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $linkcustomparams = "email=user@example.com\nurl=https://example.com/path?query=value\nspecial=value with = equals";

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(null)),
            new resource_link_context($this->get_resource_link_stub($linkcustomparams))
        );

        $params = [];
        $finalparams = $resolver->process($params, $launchcontext);

        // Special characters should be preserved.
        $this->assertArrayHasKey('custom_email', $finalparams);
        $this->assertEquals('user@example.com', $finalparams['custom_email']);

        $this->assertArrayHasKey('custom_url', $finalparams);
        $this->assertEquals('https://example.com/path?query=value', $finalparams['custom_url']);

        $this->assertArrayHasKey('custom_special', $finalparams);
        $this->assertEquals('value with = equals', $finalparams['custom_special']);
    }
}
