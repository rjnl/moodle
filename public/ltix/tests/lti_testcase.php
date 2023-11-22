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

use core_ltix\helper;
use stdClass;

/**
 * Abstract base testcase for lti unit tests.
 *
 * @package    core_ltix
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class lti_testcase extends \externallib_advanced_testcase {

    /**
     * Generate a tool type.
     *
     * @param string $uniqueid Each tool type needs a different base url. Provide a unique string for every tool type created.
     * @param int|null $toolproxyid Optional proxy to associate with tool type.
     * @return stdClass A tool type.
     */
    protected function generate_tool_type(string $uniqueid, ?int $toolproxyid = null): stdClass {
        // Create a tool type.
        $type = new stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool $uniqueid";
        $type->description = "Example description $uniqueid";
        $type->toolproxyid = $toolproxyid;
        $type->baseurl = $this->getExternalTestFileUrl("/test$uniqueid.html");
        $type->coursevisible = LTI_COURSEVISIBLE_ACTIVITYCHOOSER;
        $config = new stdClass();
        $config->lti_coursevisible = LTI_COURSEVISIBLE_ACTIVITYCHOOSER;

        $type->id = helper::add_type($type, $config);
        return $type;
    }

    /**
     * Generate a tool proxy.
     *
     * @param string $uniqueid Each tool proxy needs a different reg url. Provide a unique string for every tool proxy created.
     * @param string $registrationurl Optional registrational URL
     * @return stdClass A tool proxy.
     */
    protected function generate_tool_proxy(string $uniqueid, string $registrationurl = null): stdClass {
        // Create a tool proxy.
        $name = "Test proxy $uniqueid";
        if ($registrationurl == null) {
            $registrationurl = $this->getExternalTestFileUrl("/proxy$uniqueid.html");
        }

        $duplicates = helper::get_tool_proxies_from_registration_url($registrationurl);
        if (!empty($duplicates)) {
            throw new \moodle_exception('duplicateregurl', 'core_ltix');
        }

        $config = new stdClass();
        $config->lti_registrationurl = $registrationurl;
        $config->lti_registrationname = $name;

        $id = helper::add_tool_proxy($config);
        $toolproxy = helper::get_tool_proxy($id);

        // Pending makes more sense than configured as the first state, since
        // the next step is to register, which requires the state be pending.
        $toolproxy->state = LTI_TOOL_PROXY_STATE_PENDING;
        helper::update_tool_proxy($toolproxy);

        return $toolproxy;
    }

    /**
     * Generate a number of LTI tool types and proxies.
     *
     * @param int $toolandproxycount How many tool types and associated proxies to create. E.g. Value of 10 will create 10 types
     * and 10 proxies.
     * @param int $orphanproxycount How many orphaned proxies to create.
     * @return array[]
     */
    protected function generate_tool_types_and_proxies(int $toolandproxycount = 0, int $orphanproxycount = 0) {
        $proxies = [];
        $types = [];
        for ($i = 0; $i < $toolandproxycount; $i++) {
            $proxies[$i] = $this->generate_tool_proxy($i);
            $types[$i] = $this->generate_tool_type($i, $proxies[$i]->id);

        }
        for ($i = $toolandproxycount; $i < ($toolandproxycount + $orphanproxycount); $i++) {
            $proxies[$i] = $this->generate_tool_proxy($i);
        }

        return ['proxies' => $proxies, 'types' => $types];
    }
}
