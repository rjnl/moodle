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

use core_ltix\constants;
use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering custom_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(tool_custom_resolver::class)]
class tool_custom_resolver_test extends \basic_testcase {

    /**
     * Helper returning a tool config stub.
     *
     * @return \stdClass the object stub.
     */
    protected function get_tool_config_stub(): \stdClass {
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
                'acceptgrades' => constants::LTI_SETTING_ALWAYS,
                'ltixservice_gradesynchronization' => 2,
                'ltixservice_memberships' => 1,
                'customparameters' => "idnumber=\$Person.sourcedId\nuser#TIME#zone=\$Person.address.timezone\n".
                    "toollevelparam=test",
            ],
        ];
    }

    /**
     * Test processing using tool config.
     *
     * @return void
     */
    public function test_process(): void {

        $resolver = new tool_custom_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub()))
        ;

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The existing params are unchanged.
        $this->assertEquals($params['existing_param'], $finalparams['existing_param']);

        // The expected context params were added.
        $this->assertArrayHasKey('custom_idnumber', $finalparams);
        $this->assertEquals('$Person.sourcedId', $finalparams['custom_idnumber']);
        $this->assertArrayHasKey('custom_user#TIME#zone', $finalparams);
        $this->assertEquals('$Person.address.timezone', $finalparams['custom_user#TIME#zone']);
        $this->assertArrayHasKey('custom_toollevelparam', $finalparams);
        $this->assertEquals('test', $finalparams['custom_toollevelparam']);
    }

    /**
     * Test processing without a tool config context in the launch context.
     *
     * @return void
     */
    public function test_process_no_tool_config(): void {

        $resolver = new tool_custom_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        $params = ['existing_param' => 'value'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*tool_context, but it was not provided.*/");
        $resolver->process($params, $launchcontext);
    }
}
