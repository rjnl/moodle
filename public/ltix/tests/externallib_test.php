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

namespace core_ltix;

use core_external\external_api;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/ltix/tests/lti_testcase.php');

/**
 * External tool external functions tests
 *
 * @coversDefaultClass \core_ltix\external
 * @package    core_ltix
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class externallib_test extends lti_testcase {

    /**
     * Set up for every test
     */
    public function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Test get_tool_proxies.
     * @covers ::get_tool_proxies
     */
    public function test_get_tool_proxies(): void {
        // Create two tool proxies. One to associate with tool, and one to leave orphaned.
        $this->setAdminUser();
        $proxy = $this->generate_tool_proxy("1");
        $orphanedproxy = $this->generate_tool_proxy("2");
        $this->generate_tool_type("1", $proxy->id); // Associate proxy 1 with tool type.

        // Fetch all proxies.
        $proxies = external::get_tool_proxies(false);
        $proxies = external_api::clean_returnvalue(external::get_tool_proxies_returns(), $proxies);

        $this->assertCount(2, $proxies);
        $this->assertEqualsCanonicalizing([(array) $proxy, (array) $orphanedproxy], $proxies);
    }

    /**
     * Test get_tool_proxies with orphaned proxies only.
     * @covers ::get_tool_proxies
     */
    public function test_get_orphaned_tool_proxies(): void {
        // Create two tool proxies. One to associate with tool, and one to leave orphaned.
        $this->setAdminUser();
        $proxy = $this->generate_tool_proxy("1");
        $orphanedproxy = $this->generate_tool_proxy("2");
        $this->generate_tool_type("1", $proxy->id); // Associate proxy 1 with tool type.

        // Fetch all proxies.
        $proxies = external::get_tool_proxies(true);
        $proxies = external_api::clean_returnvalue(external::get_tool_proxies_returns(), $proxies);

        $this->assertCount(1, $proxies);
        $this->assertEqualsCanonicalizing([(array) $orphanedproxy], $proxies);
    }


    /**
     * Test create_tool_proxy.
     * @covers ::create_tool_proxy
     */
    public function test_create_tool_proxy(): void {
        $this->setAdminUser();
        $capabilities = ['AA', 'BB'];
        $proxy = external::create_tool_proxy('Test proxy', $this->getExternalTestFileUrl('/test.html'), $capabilities, []);
        $proxy = (object) external_api::clean_returnvalue(external::create_tool_proxy_returns(), $proxy);

        $this->assertEquals('Test proxy', $proxy->name);
        $this->assertEquals($this->getExternalTestFileUrl('/test.html'), $proxy->regurl);
        $this->assertEquals(LTI_TOOL_PROXY_STATE_PENDING, $proxy->state);
        $this->assertEquals(implode("\n", $capabilities), $proxy->capabilityoffered);
    }

    /**
     * Test create_tool_proxy with a duplicate url.
     * @covers ::create_tool_proxy
     */
    public function test_create_tool_proxy_duplicateurl(): void {
        $this->setAdminUser();
        external::create_tool_proxy('Test proxy 1', $this->getExternalTestFileUrl('/test.html'), array(), array());

        $this->expectException(\moodle_exception::class);
        external::create_tool_proxy('Test proxy 2', $this->getExternalTestFileUrl('/test.html'), array(), array());
    }

    /**
     * Test create_tool_proxy for a user without the required capability.
     * @covers ::create_tool_proxy
     */
    public function test_create_tool_proxy_without_capability(): void {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);
        $this->expectException(\required_capability_exception::class);
        external::create_tool_proxy('Test proxy', $this->getExternalTestFileUrl('/test.html'), array(), array());
    }

    /**
     * Test delete_tool_proxy.
     * @covers ::delete_tool_proxy
     */
    public function test_delete_tool_proxy(): void {
        $this->setAdminUser();
        $proxy = external::create_tool_proxy('Test proxy', $this->getExternalTestFileUrl('/test.html'), array(), array());
        $proxy = (object) external_api::clean_returnvalue(external::create_tool_proxy_returns(), $proxy);
        $this->assertNotEmpty(helper::get_tool_proxy($proxy->id));

        $proxy = external::delete_tool_proxy($proxy->id);
        $proxy = (object) external_api::clean_returnvalue(external::delete_tool_proxy_returns(), $proxy);

        $this->assertEquals('Test proxy', $proxy->name);
        $this->assertEquals($this->getExternalTestFileUrl('/test.html'), $proxy->regurl);
        $this->assertEquals(LTI_TOOL_PROXY_STATE_PENDING, $proxy->state);
        $this->assertEmpty(helper::get_tool_proxy($proxy->id));
    }

    /**
     * Test get_tool_proxy_registration_request.
     * @covers ::get_tool_proxy_registration_request
     */
    public function test_get_tool_proxy_registration_request(): void {
        $this->setAdminUser();
        $proxy = external::create_tool_proxy('Test proxy', $this->getExternalTestFileUrl('/test.html'), array(), array());
        $proxy = (object) external_api::clean_returnvalue(external::create_tool_proxy_returns(), $proxy);

        $request = external::get_tool_proxy_registration_request($proxy->id);
        $request = external_api::clean_returnvalue(external::get_tool_proxy_registration_request_returns(),
            $request);

        $this->assertEquals('ToolProxyRegistrationRequest', $request['lti_message_type']);
        $this->assertEquals('LTI-2p0', $request['lti_version']);
    }

    /**
     * Test get_tool_types.
     * @covers ::get_tool_types
     */
    public function test_get_tool_types(): void {
        $this->setAdminUser();
        $proxy = external::create_tool_proxy('Test proxy', $this->getExternalTestFileUrl('/test.html'), array(), array());
        $proxy = (object) external_api::clean_returnvalue(external::create_tool_proxy_returns(), $proxy);

        // Create a tool type, associated with that proxy.
        $type = new \stdClass();
        $data = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->toolproxyid = $proxy->id;
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');
        helper::add_type($type, $data);

        $types = external::get_tool_types($proxy->id);
        $types = external_api::clean_returnvalue(external::get_tool_types_returns(), $types);

        $this->assertCount(1, $types);
        $type = $types[0];
        $this->assertEquals('Test tool', $type['name']);
        $this->assertEquals('Example description', $type['description']);
    }

    /**
     * Test create_tool_type.
     * @covers ::create_tool_type
     */
    public function test_create_tool_type(): void {
        $this->setAdminUser();
        $type = external::create_tool_type($this->getExternalTestFileUrl('/ims_cartridge_basic_lti_link.xml'), '', '');
        $type = external_api::clean_returnvalue(external::create_tool_type_returns(), $type);

        $this->assertEquals('Example tool', $type['name']);
        $this->assertEquals('Example tool description', $type['description']);
        $this->assertEquals('https://download.moodle.org/unittest/test.jpg', $type['urls']['icon']);
        $typeentry = helper::get_type($type['id']);
        $this->assertEquals('http://www.example.com/lti/provider.php', $typeentry->baseurl);
        $config = helper::get_type_config($type['id']);
        $this->assertTrue(isset($config['sendname']));
        $this->assertTrue(isset($config['sendemailaddr']));
        $this->assertTrue(isset($config['acceptgrades']));
        $this->assertTrue(isset($config['forcessl']));
    }

    /**
     * Test create_tool_type failure from non existent file.
     * @covers ::create_tool_type
     */
    public function test_create_tool_type_nonexistant_file(): void {
        $this->expectException(\moodle_exception::class);
        external::create_tool_type($this->getExternalTestFileUrl('/doesntexist.xml'), '', '');
    }

    /**
     * Test create_tool_type failure from xml that is not a cartridge.
     * @covers ::create_tool_type
     */
    public function test_create_tool_type_bad_file(): void {
        $this->expectException(\moodle_exception::class);
        external::create_tool_type($this->getExternalTestFileUrl('/rsstest.xml'), '', '');
    }

    /**
     * Test create_tool_type as a user without the required capability.
     * @covers ::create_tool_type
     */
    public function test_create_tool_type_without_capability(): void {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);
        $this->expectException(\required_capability_exception::class);
        external::create_tool_type($this->getExternalTestFileUrl('/ims_cartridge_basic_lti_link.xml'), '', '');
    }

    /**
     * Test update_tool_type.
     * @covers ::update_tool_type
     */
    public function test_update_tool_type(): void {
        $this->setAdminUser();
        $type = external::create_tool_type($this->getExternalTestFileUrl('/ims_cartridge_basic_lti_link.xml'), '', '');
        $type = external_api::clean_returnvalue(external::create_tool_type_returns(), $type);

        $type = external::update_tool_type($type['id'], 'New name', 'New description', LTI_TOOL_STATE_PENDING);
        $type = external_api::clean_returnvalue(external::update_tool_type_returns(), $type);

        $this->assertEquals('New name', $type['name']);
        $this->assertEquals('New description', $type['description']);
        $this->assertEquals('Pending', $type['state']['text']);
    }

    /**
     * Test delete_tool_type for a user with the required capability.
     * @covers ::delete_tool_type
     */
    public function test_delete_tool_type(): void {
        $this->setAdminUser();
        $type = external::create_tool_type($this->getExternalTestFileUrl('/ims_cartridge_basic_lti_link.xml'), '', '');
        $type = external_api::clean_returnvalue(external::create_tool_type_returns(), $type);
        $this->assertNotEmpty(helper::get_type($type['id']));

        $type = external::delete_tool_type($type['id']);
        $type = external_api::clean_returnvalue(external::delete_tool_type_returns(), $type);
        $this->assertEmpty(helper::get_type($type['id']));
    }

    /**
     * Test delete_tool_type for a user without the required capability.
     * @covers ::delete_tool_type
     */
    public function test_delete_tool_type_without_capability(): void {
        $this->setAdminUser();
        $type = external::create_tool_type($this->getExternalTestFileUrl('/ims_cartridge_basic_lti_link.xml'), '', '');
        $type = external_api::clean_returnvalue(external::create_tool_type_returns(), $type);
        $this->assertNotEmpty(helper::get_type($type['id']));

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);
        $this->expectException(\required_capability_exception::class);
        external::delete_tool_type($type['id']);
    }

    /**
     * Test is_cartridge.
     * @covers ::is_cartridge
     */
    public function test_is_cartridge(): void {
        $this->setAdminUser();
        $result = external::is_cartridge($this->getExternalTestFileUrl('/ims_cartridge_basic_lti_link.xml'));
        $result = external_api::clean_returnvalue(external::is_cartridge_returns(), $result);
        $this->assertTrue($result['iscartridge']);

        $result = external::is_cartridge($this->getExternalTestFileUrl('/test.html'));
        $result = external_api::clean_returnvalue(external::is_cartridge_returns(), $result);
        $this->assertFalse($result['iscartridge']);
    }
}
