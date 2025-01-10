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

namespace mod_lti\external;

use core_external\external_api;
use core_ltix\helper;
use externallib_advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * PHPUnit tests for get_tool_types_and_proxies external function.
 *
 * @package    mod_lti
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_tool_types_and_proxies_test extends externallib_advanced_testcase {

    /**
     * This method runs before every test.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Create a proxy for testing.
     *
     * @param string $uniqueid
     * @return int the proxy id
     */
    protected function create_tool_proxy(string $uniqueid): int {
        $config = (object) [
            'lti_registrationurl' => $this->getExternalTestFileUrl("/proxy$uniqueid.html"),
            'lti_registrationname' => "Test proxy $uniqueid",
        ];
        return helper::add_tool_proxy($config);
    }

    /**
     * Create a fully configured tool, optionally based on a proxy.
     * @param string $uniqueid
     * @return int
     */
    protected function create_configured_tool(string $uniqueid, ?int $proxyid): int {
        /** @var \mod_lti_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('mod_lti');

        return $ltigenerator->create_tool_types([
            'state' => LTI_TOOL_STATE_CONFIGURED,
            'name' => "Test tool 1",
            'description' => "Example description 1",
            'toolproxyid' => $proxyid,
            'baseurl' => $this->getExternalTestFileUrl("/test1.html"),
        ]);
    }

    /**
     * Test get_tool_types_and_proxies.
     */
    public function test_mod_lti_get_tool_types_and_proxies(): void {
        $proxyid = $this->create_tool_proxy('1');
        $this->create_configured_tool('1', $proxyid);

        $data = get_tool_types_and_proxies::execute(0, false, 50, 0);
        $this->assertDebuggingCalled();
        $data = external_api::clean_returnvalue(get_tool_types_and_proxies::execute_returns(), $data);

        $this->assertCount(1, $data['types']);
        $type = $data['types'][0];
        $this->assertEquals('Test tool 1', $type['name']);
        $this->assertEquals('Example description 1', $type['description']);
        $this->assertCount(1, $data['proxies']);
        $proxy = $data['proxies'][0];
        $this->assertEquals('Test proxy 1', $proxy['name']);
        $this->assertEquals(50, $data['limit']);
        $this->assertEquals(0, $data['offset']);
    }

    /**
     * Test get_tool_types_and_proxies with multiple pages of tool types.
     */
    public function test_mod_lti_get_tool_types_and_proxies_with_multiple_pages(): void {
        for ($i = 0; $i < 3; $i++) {
            $proxyid = $this->create_tool_proxy($i);
            $this->create_configured_tool($i, $proxyid);
        }

        $data = get_tool_types_and_proxies::execute(0, false,  5, 0);
        $this->assertDebuggingCalled();
        $data = external_api::clean_returnvalue(get_tool_types_and_proxies::execute_returns(), $data);

        $this->assertCount(2, $data['types']);
        $this->assertCount(3, $data['proxies']);
        $this->assertEquals(5, $data['limit']);
        $this->assertEquals(0, $data['offset']);
    }

    /**
     * Test get_tool_types_and_proxies with multiple pages of tool types and offset.
     */
    public function test_mod_lti_get_tool_types_and_proxies_with_multiple_pages_last_page(): void {
        for ($i = 0; $i < 6; $i++) {
            $proxyid = $this->create_tool_proxy($i);
            $this->create_configured_tool($i, $proxyid);
        }

        $data = get_tool_types_and_proxies::execute(0, false, 5, 10);
        $this->assertDebuggingCalled();
        $data = external_api::clean_returnvalue(get_tool_types_and_proxies::execute_returns(), $data);

        $this->assertCount(2, $data['types']);
        $this->assertCount(0, $data['proxies']);
        $this->assertEquals(5, $data['limit']);
        $this->assertEquals(10, $data['offset']);
    }

    /**
     * Test get_tool_types_and_proxies without pagination.
     */
    public function test_mod_lti_get_tool_types_and_proxies_without_pagination(): void {
        for ($i = 0; $i < 10; $i++) {
            $proxyid = $this->create_tool_proxy($i);
            $this->create_configured_tool($i, $proxyid);
        }

        $data = get_tool_types_and_proxies::execute(0, false,  0, 0);
        $this->assertDebuggingCalled();
        $data = external_api::clean_returnvalue(get_tool_types_and_proxies::execute_returns(), $data);

        $this->assertCount(10, $data['types']);
        $this->assertCount(10, $data['proxies']);
    }
}
