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
 * PHPUnit tests for get_tool_types_and_proxies_count external function.
 *
 * @package    mod_lti
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_tool_types_and_proxies_count_test extends externallib_advanced_testcase {

    /**
     * This method runs before every test.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Test get_tool_types_and_proxies_count returns the correct number.
     */
    public function test_mod_lti_get_tool_types_and_proxies_count(): void {
        /** @var \mod_lti_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('mod_lti');

        for ($i = 0; $i < 10; $i++) {
            $config = (object) [
                'lti_registrationurl' => $this->getExternalTestFileUrl("/proxy$i.html"),
                'lti_registrationname' => "Test proxy $i",
            ];
            $proxyid = helper::add_tool_proxy($config);

            $ltigenerator->create_tool_types([
                'state' => LTI_TOOL_STATE_CONFIGURED,
                'name' => "Test tool $i",
                'description' => "Example description $i",
                'toolproxyid' => $proxyid,
                'baseurl' => $this->getExternalTestFileUrl("/test$i.html"),
            ]);
        }

        $data = get_tool_types_and_proxies_count::execute(0, false);
        $this->assertDebuggingCalled();
        $data = external_api::clean_returnvalue(get_tool_types_and_proxies_count::execute_returns(), $data);

        $this->assertEquals(20, $data['count']);
    }

    /**
     * Test get_tool_types_and_proxies_count returns the correct number.
     */
    public function test_mod_lti_get_tool_types_and_proxies_count_with_no_tools_configured(): void {
        $data = get_tool_types_and_proxies_count::execute(0, false);
        $this->assertDebuggingCalled();
        $data = external_api::clean_returnvalue(get_tool_types_and_proxies_count::execute_returns(), $data);

        $this->assertEquals(0, $data['count']);
    }
}
