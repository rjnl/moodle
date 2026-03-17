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
use core_ltix\local\lticore\message\context\item\course_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\context\item\resource_link_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use core_ltix\local\lticore\models\resource_link;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering launch_presentation_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(launch_presentation_resolver::class)]
class launch_presentation_resolver_test extends \basic_testcase {

    /**
     * Helper returning a course stub.
     *
     * @return \stdClass the course object stub.
     */
    protected function get_course_stub(): \stdClass {
        return (object) [
            'id' => '100000',
            'category' => '1',
            'sortorder' => '0',
            'fullname' => 'Test course 1',
            'shortname' => 'tc_1',
            'idnumber' => '',
            'summary' => 'Test course 1',
            'summaryformat' => '0',
            'format' => 'topics',
            'showgrades' => '1',
            'newsitems' => '0',
            'startdate' => '1765382400',
            'enddate' => '0',
            'relativedatesmode' => '0',
            'marker' => '0',
            'maxbytes' => '0',
            'legacyfiles' => '0',
            'showreports' => '0',
            'visible' => '1',
            'visibleold' => '1',
            'downloadcontent' => NULL,
            'groupmode' => '0',
            'groupmodeforce' => '0',
            'defaultgroupingid' => '0',
            'lang' => '',
            'calendartype' => '',
            'theme' => '',
            'timecreated' => '1765422564',
            'timemodified' => '1765422564',
            'requested' => '0',
            'enablecompletion' => '0',
            'completionnotify' => '0',
            'cacherev' => '0',
            'originalcourseid' => NULL,
            'showactivitydates' => '0',
            'showcompletionconditions' => '1',
            'pdfexportfont' => NULL,
            'hiddensections' => 1,
            'coursedisplay' => 0,
        ];
    }

    /**
     * Helper returning a tool config stub.
     *
     * @param int $launchcontainer the launch container value.
     * @param bool $forcessl whether to force SSL.
     * @return \stdClass the object stub.
     */
    protected function get_tool_config_stub(
        int $launchcontainer = constants::LTI_LAUNCH_CONTAINER_EMBED,
        bool $forcessl = false
    ): \stdClass {

        return (object) [
            'tool' => (object) [
                'id' => '123',
                'clientid' => '123456-abcd',
                'ltiversion' => '1.3.0',
            ],
            'config' => (object) [
                'initiatelogin' => 'https://tool.example.com/lti/initiatelogin',
                'organizationid' => 'https://platform.example.com',
                'launchcontainer' => $launchcontainer,
                ...($forcessl ? ['forcessl' => '1'] : [])
            ],
        ];
    }

    /**
     * Helper returning a resource link stub.
     *
     * @param int $launchcontainer the launch container value.
     * @return resource_link the resource link stub.
     */
    protected function get_resource_link_stub(int $launchcontainer = constants::LTI_LAUNCH_CONTAINER_DEFAULT): resource_link {
        return new resource_link(0, (object) [
            'id' => 24,
            'typeid' => 123,
            'component' => 'mod_lti',
            'itemtype' => 'mod_lti:myplacement',
            'itemid' => 1,
            'contextid' => 456,
            'url' => 'https://tool.example.com/lti/resource/1',
            'title' => 'Test Resource Link',
            'launchcontainer' => $launchcontainer,
        ]);
    }

    /**
     * Data provider for launch container tests.
     *
     * @return array test cases.
     */
    public static function launch_container_provider(): array {
        return [
            'embed' => [
                'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_EMBED,
                'expectedtarget' => 'iframe',
            ],
            'embed_no_blocks' => [
                'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
                'expectedtarget' => 'iframe',
            ],
            'replace_moodle_window' => [
                'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW,
                'expectedtarget' => 'frame',
            ],
            'window' => [
                'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_WINDOW,
                'expectedtarget' => 'window',
            ],
        ];
    }

    /**
     * Test processing with different launch containers.
     *
     * @param int $launchcontainer the launch container value.
     * @param string $expectedtarget the expected document target value.
     * @return void
     */
    #[DataProvider('launch_container_provider')]
    public function test_process_launch_containers(int $launchcontainer, string $expectedtarget): void {
        $resolver = new launch_presentation_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub($launchcontainer)),
            new resource_link_context($this->get_resource_link_stub($launchcontainer)),
            new course_context($this->get_course_stub())
        );

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The existing params are unchanged.
        $this->assertEquals($params['existing_param'], $finalparams['existing_param']);

        // The expected launch presentation params were added.
        $this->assertArrayHasKey('launch_presentation_locale', $finalparams);
        $this->assertEquals(current_language(), $finalparams['launch_presentation_locale']);

        $this->assertArrayHasKey('launch_presentation_document_target', $finalparams);
        $this->assertEquals($expectedtarget, $finalparams['launch_presentation_document_target']);

        $this->assertArrayHasKey('launch_presentation_return_url', $finalparams);
        $this->assertStringContainsString('/ltix/return.php', $finalparams['launch_presentation_return_url']);
        $this->assertStringContainsString('course=100000', $finalparams['launch_presentation_return_url']);
        $this->assertStringContainsString('launch_container=' . $launchcontainer, $finalparams['launch_presentation_return_url']);
    }

    /**
     * Test processing with force SSL enabled.
     *
     * @return void
     */
    public function test_process_with_force_ssl(): void {
        $resolver = new launch_presentation_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(constants::LTI_LAUNCH_CONTAINER_EMBED, true)),
            new resource_link_context($this->get_resource_link_stub()),
            new course_context($this->get_course_stub())
        );

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The return URL should be HTTPS when force SSL is enabled.
        $this->assertArrayHasKey('launch_presentation_return_url', $finalparams);
        $this->assertStringStartsWith('https://', $finalparams['launch_presentation_return_url']);
    }

    /**
     * Test processing without a required tool context.
     *
     * @return void
     */
    public function test_process_no_tool_context(): void {
        $resolver = new launch_presentation_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new resource_link_context($this->get_resource_link_stub()),
            new course_context($this->get_course_stub())
        );

        $params = ['existing_param' => 'value'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*tool_context, but it was not provided.*/");
        $resolver->process($params, $launchcontext);
    }

    /**
     * Test processing without a required resource link context.
     *
     * @return void
     */
    public function test_process_no_resource_link_context(): void {
        $resolver = new launch_presentation_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub()),
            new course_context($this->get_course_stub())
        );

        $params = ['existing_param' => 'value'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*resource_link_context, but it was not.*/");
        $resolver->process($params, $launchcontext);
    }

    /**
     * Test processing without a required course context.
     *
     * @return void
     */
    public function test_process_no_course_context(): void {
        $resolver = new launch_presentation_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub()),
            new resource_link_context($this->get_resource_link_stub())
        );

        $params = ['existing_param' => 'value'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*course_context, but it was not provided.*/");
        $resolver->process($params, $launchcontext);
    }
}
