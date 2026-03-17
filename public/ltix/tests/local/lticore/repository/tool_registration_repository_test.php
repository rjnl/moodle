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

namespace core_ltix\local\lticore\repository;

use core_ltix\constants;
use core_ltix\helper;
use core_ltix\local\lticore\lti_version;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test class covering tool_registration_repository.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(tool_registration_repository::class)]
class tool_registration_repository_test extends \advanced_testcase {

    /**
     * Create a tool type record and return its ID.
     *
     * @param array $typeoverrides fields to set on the $type stdClass passed to helper::add_type().
     * @param array $configoverrides lti_-prefixed config fields to set on the $config stdClass.
     * @return int the new tool type ID.
     */
    private function create_tool_type(array $typeoverrides = [], array $configoverrides = []): int {
        $type = (object) array_merge([
            'state'         => constants::LTI_TOOL_STATE_CONFIGURED,
            'name'          => 'Test tool ' . uniqid(),
            'baseurl'       => 'https://tool.example.com/',
            'coursevisible' => constants::LTI_COURSEVISIBLE_PRECONFIGURED,
        ], $typeoverrides);

        $config = (object) array_merge([
            'lti_coursevisible' => constants::LTI_COURSEVISIBLE_PRECONFIGURED,
        ], $configoverrides);

        return helper::add_type($type, $config);
    }

    /**
     * Test that get_by_id() returns null when no tool type exists for the given id.
     */
    public function test_get_by_id_returns_null_for_unknown_id(): void {
        $this->resetAfterTest();
        $repo = new tool_registration_repository();
        $this->assertNull($repo->get_by_id(0));
    }

    /**
     * Test that get_by_id() returns an object with 'tool' and 'config' properties for a valid ID.
     */
    public function test_get_by_id_returns_object_with_tool_and_config(): void {
        $this->resetAfterTest();
        $typeid = $this->create_tool_type();
        $repo = new tool_registration_repository();

        $registration = $repo->get_by_id($typeid);

        $this->assertIsObject($registration);
        $this->assertObjectHasProperty('tool', $registration);
        $this->assertObjectHasProperty('config', $registration);
        $this->assertIsObject($registration->tool);
        $this->assertIsObject($registration->config);
    }

    /**
     * Test that get_by_id() reflects the correct tool fields for an LTI 1.0 tool type.
     */
    public function test_get_by_id_returns_correct_tool_fields_for_lti1_tool(): void {
        $this->resetAfterTest();
        $typeid = $this->create_tool_type([
            'name'       => 'My LTI 1 Tool',
            'baseurl'    => 'https://tool.example.com/launch',
            'ltiversion' => lti_version::LTI_VERSION_1->value,
        ]);
        $repo = new tool_registration_repository();

        $registration = $repo->get_by_id($typeid);

        $this->assertEquals($typeid, $registration->tool->id);
        $this->assertEquals('My LTI 1 Tool', $registration->tool->name);
        $this->assertEquals('https://tool.example.com/launch', $registration->tool->baseurl);
        $this->assertEquals(lti_version::LTI_VERSION_1->value, $registration->tool->ltiversion);
    }

    /**
     * Test that get_by_id() surfaces the consumer key and secret stored in config for an LTI 1.0 tool.
     */
    public function test_get_by_id_returns_correct_config_fields_for_lti1_tool(): void {
        $this->resetAfterTest();
        $typeid = $this->create_tool_type(
            ['ltiversion' => lti_version::LTI_VERSION_1->value],
            ['lti_resourcekey' => 'my_key', 'lti_password' => 'my_secret'],
        );
        $repo = new tool_registration_repository();

        $registration = $repo->get_by_id($typeid);

        $this->assertEquals('my_key', $registration->config->resourcekey);
        $this->assertEquals('my_secret', $registration->config->password);
    }

    /**
     * Test that get_by_id() does not set 'issuer' or attach a toolproxy for LTI 1.0 tools.
     */
    public function test_get_by_id_lti1_tool_has_no_issuer_or_toolproxy(): void {
        $this->resetAfterTest();
        $typeid = $this->create_tool_type(['ltiversion' => lti_version::LTI_VERSION_1->value]);
        $repo = new tool_registration_repository();

        $registration = $repo->get_by_id($typeid);

        $this->assertObjectNotHasProperty('issuer', $registration->tool);
        $this->assertObjectNotHasProperty('toolproxy', $registration);
    }

    /**
     * Test that get_by_id() sets tool->issuer to $CFG->wwwroot for LTI 1.3 tools.
     */
    public function test_get_by_id_sets_issuer_for_lti1p3_tool(): void {
        global $CFG;
        $this->resetAfterTest();
        $typeid = $this->create_tool_type(
            ['ltiversion' => lti_version::LTI_VERSION_1P3->value],
            ['lti_clientid' => 'my-client-id'],
        );
        $repo = new tool_registration_repository();

        $registration = $repo->get_by_id($typeid);

        $this->assertEquals(lti_version::LTI_VERSION_1P3->value, $registration->tool->ltiversion);
        $this->assertEquals($CFG->wwwroot, $registration->tool->issuer);
    }

    /**
     * Test that get_by_id() does not attach a toolproxy for LTI 1.3 tools.
     */
    public function test_get_by_id_lti1p3_tool_has_no_toolproxy(): void {
        $this->resetAfterTest();
        $typeid = $this->create_tool_type(['ltiversion' => lti_version::LTI_VERSION_1P3->value]);
        $repo = new tool_registration_repository();

        $registration = $repo->get_by_id($typeid);

        $this->assertObjectNotHasProperty('toolproxy', $registration);
    }

    /**
     * Test that get_by_id() attaches the tool proxy for an LTI 2.0 tool that has a toolproxyid.
     */
    public function test_get_by_id_returns_toolproxy_for_lti2_tool(): void {
        $this->resetAfterTest();
        $proxyid = helper::add_tool_proxy((object) [
            'lti_registrationname' => 'My Proxy',
            'lti_registrationurl'  => 'https://tool.example.com/register',
        ]);
        $typeid = $this->create_tool_type([
            'ltiversion'   => lti_version::LTI_VERSION_2->value,
            'toolproxyid'  => $proxyid,
        ]);
        $repo = new tool_registration_repository();

        $registration = $repo->get_by_id($typeid);

        $this->assertObjectHasProperty('toolproxy', $registration);
        $this->assertEquals($proxyid, $registration->toolproxy->id);
        $this->assertEquals('My Proxy', $registration->toolproxy->name);
        $this->assertEquals('https://tool.example.com/register', $registration->toolproxy->regurl);
    }

    /**
     * Test that get_by_id() does not set 'issuer' for LTI 2.0 tools.
     */
    public function test_get_by_id_lti2_tool_has_no_issuer(): void {
        $this->resetAfterTest();
        $proxyid = helper::add_tool_proxy((object) [
            'lti_registrationurl' => 'https://tool.example.com/register',
        ]);
        $typeid = $this->create_tool_type([
            'ltiversion'  => lti_version::LTI_VERSION_2->value,
            'toolproxyid' => $proxyid,
        ]);
        $repo = new tool_registration_repository();

        $registration = $repo->get_by_id($typeid);

        $this->assertObjectNotHasProperty('issuer', $registration->tool);
    }

    /**
     * Test that get_by_id() does not attach a toolproxy for an LTI 2.0 tool that has no toolproxyid set.
     */
    public function test_get_by_id_lti2_tool_without_toolproxyid_has_no_toolproxy(): void {
        $this->resetAfterTest();
        $typeid = $this->create_tool_type(['ltiversion' => lti_version::LTI_VERSION_2->value]);
        $repo = new tool_registration_repository();

        $registration = $repo->get_by_id($typeid);

        $this->assertObjectNotHasProperty('toolproxy', $registration);
    }

    /**
     * Test that separate calls to get_by_id() with different IDs return independent registrations.
     */
    public function test_get_by_id_returns_correct_registration_for_each_id(): void {
        $this->resetAfterTest();
        $typeid1 = $this->create_tool_type(['name' => 'Tool Alpha', 'baseurl' => 'https://alpha.example.com/']);
        $typeid2 = $this->create_tool_type(['name' => 'Tool Beta',  'baseurl' => 'https://beta.example.com/']);
        $repo = new tool_registration_repository();

        $reg1 = $repo->get_by_id($typeid1);
        $reg2 = $repo->get_by_id($typeid2);

        $this->assertEquals('Tool Alpha', $reg1->tool->name);
        $this->assertEquals('https://alpha.example.com/', $reg1->tool->baseurl);
        $this->assertEquals('Tool Beta', $reg2->tool->name);
        $this->assertEquals('https://beta.example.com/', $reg2->tool->baseurl);
    }
}
