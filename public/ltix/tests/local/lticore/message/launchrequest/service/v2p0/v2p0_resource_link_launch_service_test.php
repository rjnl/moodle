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

namespace core_ltix\local\lticore\message\launchrequest\service\v2p0;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\launchrequest\builder\v2p0\v2p0_resource_link_launch_request_builder;
use core_ltix\local\lticore\message\launchrequest\role_mapper;
use core_ltix\local\lticore\message\launchrequest\service\datarepository\launch_data_repository;
use core_ltix\local\lticore\message\lti_message;
use core_ltix\local\lticore\message\payload\lis_vocab_converter;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_builder;
use core_ltix\local\lticore\message\payload\parameters\pipeline\factory\parameters_builder_factory;
use core_ltix\local\lticore\message\type\message_type;
use core_ltix\local\lticore\message\type\message_type_factory;
use core_ltix\local\lticore\message\type\message_type_registry;
use core_ltix\local\lticore\models\resource_link;
use core_ltix\local\lticore\repository\tool_registration_repository;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for v2p0_resource_link_launch_service.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(v2p0_resource_link_launch_service::class)]
class v2p0_resource_link_launch_service_test extends \basic_testcase {

    /**
     * Test ::launch().
     *
     * This test verifies service orchestration.
     *
     * Individual pipeline unit tests cover specific logic controlling parameter inclusion,
     * substitution rules, and a plethora of other aspects of the launch payload generation.
     *
     * @return void
     */
    public function test_launch_invokes_pipeline_and_request_builder(): void {

        $course = $this->createStub(\stdClass::class);
        $toolconfig = $this->createStub(\stdClass::class);
        $link = new resource_link(0, (object) []);
        $optionalparams = ['example_param' => 'example_value'];
        $ltimessage = new lti_message('http://example.com/', []);
        $vocabconverter = new lis_vocab_converter();
        $messagetypefactory = new message_type_factory(
            new message_type_registry([])
        );

        $linkid = 10;
        $user = (object) ['id' => 103000];

        $registrationrepository = $this->createMock(tool_registration_repository::class);
        $registrationrepository->expects($this->once())
            ->method('get_by_id')
            ->willReturn($toolconfig);

        $launchdatarepository = $this->createMock(launch_data_repository::class);
        $launchdatarepository->expects($this->once())
            ->method('get_course')
            ->willReturn($course);
        $launchdatarepository->expects($this->once())
            ->method('get_resource_link')
            ->willReturn($link);

        $rolemap = $this->createMock(role_mapper::class);

        $pipeline = $this->createMock(parameters_builder::class);
        $pipeline->expects($this->once())
            ->method('build')
            ->willReturn($optionalparams);

        $pipelinefactory = $this->createMock(parameters_builder_factory::class);
        $pipelinefactory->expects($this->once())
            ->method('create_for')
            ->with(
                $this->equalTo(lti_version::LTI_VERSION_2),
                $this->equalTo(message_type::create('basic-lti-launch-request', 'basic-lti-launch-request')),
            )
            ->willReturn($pipeline);

        $requestbuilder = $this->createMock(v2p0_resource_link_launch_request_builder::class);
        $requestbuilder->expects($this->once())
            ->method('build_message')
            ->with($toolconfig, $link, $user->id)
            ->willReturn($ltimessage);

        $service = new v2p0_resource_link_launch_service(
            $pipelinefactory,
            $requestbuilder,
            $registrationrepository,
            $launchdatarepository,
            $rolemap,
            $vocabconverter,
            $messagetypefactory,
        );

        $result = $service->launch($linkid, $user);

        $this->assertSame($ltimessage, $result);
    }

    /**
     * Test that missing resource link throws an exception.
     * @return void
     */
    public function test_link_not_found_throws(): void {

        $registrationrepository = $this->createMock(tool_registration_repository::class);
        $rolemap = $this->createMock(role_mapper::class);
        $optionalparamsbuilderfactory = $this->createMock(parameters_builder_factory::class);
        $requestbuilder = $this->createMock(v2p0_resource_link_launch_request_builder::class);
        $vocabconverter = new lis_vocab_converter();
        $messagetypefactory = new message_type_factory(
            new message_type_registry([])
        );

        // Mock repository, returning null to represent a link not found.
        $launchdatarepository = $this->createMock(launch_data_repository::class);
        $launchdatarepository->method('get_resource_link')
            ->willReturn(null);

        $service = new v2p0_resource_link_launch_service(
            $optionalparamsbuilderfactory,
            $requestbuilder,
            $registrationrepository,
            $launchdatarepository,
            $rolemap,
            $vocabconverter,
            $messagetypefactory,
        );

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches('/.*resource link does not exist/');
        $service->launch(10, (object) ['id' => 103000]);
    }

    /**
     * Test that missing course throws an exception.
     * @return void
     */
    public function test_course_not_found_throws(): void {

        $registrationrepository = $this->createMock(tool_registration_repository::class);
        $rolemap = $this->createMock(role_mapper::class);
        $optionalparamsbuilderfactory = $this->createMock(parameters_builder_factory::class);
        $requestbuilder = $this->createMock(v2p0_resource_link_launch_request_builder::class);
        $vocabconverter = new lis_vocab_converter();
        $messagetypefactory = new message_type_factory(
            new message_type_registry([])
        );

        // Mock repository, returning null for the course.
        // The link must be present to hit the course conditional.
        $launchdatarepository = $this->createStub(launch_data_repository::class);
        $launchdatarepository->method('get_resource_link')
            ->willReturn(new resource_link(0, (object) []));
        $launchdatarepository->method('get_course')
            ->willReturn(null);

        $service = new v2p0_resource_link_launch_service(
            $optionalparamsbuilderfactory,
            $requestbuilder,
            $registrationrepository,
            $launchdatarepository,
            $rolemap,
            $vocabconverter,
            $messagetypefactory,
        );

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches('/.*resource links must reside under a course/');
        $service->launch(10, (object) ['id' => 103000]);
    }

    /**
     * Test that missing tool config throws an exception.
     * @return void
     */
    public function test_tool_config_not_found_throws(): void {

        $rolemap = $this->createMock(role_mapper::class);
        $optionalparamsbuilderfactory = $this->createMock(parameters_builder_factory::class);
        $requestbuilder = $this->createMock(v2p0_resource_link_launch_request_builder::class);
        $vocabconverter = new lis_vocab_converter();
        $messagetypefactory = new message_type_factory(
            new message_type_registry([])
        );

        // The tool config check falls after link and course checks, so mock those.
        $launchdatarepository = $this->createStub(launch_data_repository::class);
        $launchdatarepository->method('get_resource_link')
            ->willReturn(new resource_link(0, (object) []));
        $launchdatarepository->method('get_course')
            ->willReturn($this->createMock(\stdClass::class));

        // Mock repository, returning null for the tool config.
        $registrationrepository = $this->createMock(tool_registration_repository::class);
        $registrationrepository->method('get_by_id')
            ->willReturn(null);

        $service = new v2p0_resource_link_launch_service(
            $optionalparamsbuilderfactory,
            $requestbuilder,
            $registrationrepository,
            $launchdatarepository,
            $rolemap,
            $vocabconverter,
            $messagetypefactory,
        );

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessage(get_string('errortooltypenotfound', 'core_ltix'));
        $service->launch(10, (object) ['id' => 103000]);
    }
}
