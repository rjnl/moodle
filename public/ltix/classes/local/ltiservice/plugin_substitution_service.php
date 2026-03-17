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

namespace core_ltix\local\ltiservice;

use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\tool_context;

/**
 * Service for performing custom parameter substitution for all service plugins.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class plugin_substitution_service implements plugin_substitution_service_interface {

    /**
     * Ctor.
     *
     * @param service_plugin_registry $pluginregistry registry to fetch plugin implementations.
     */
    public function __construct(private service_plugin_registry $pluginregistry) {
    }

    /**
     * Perform custom parameter substitution for all service plugins.
     *
     * @param launch_context $launchcontext the launch context.
     * @param string $paramstr the string containing the variable to substitute.
     * @return string the substituted value
     */
    public function substitute(launch_context $launchcontext, string $paramstr): string {
        // Reads should be avoided here because of the way this is called during substitution pipelines.
        // This will be called for any variable which isn't resolve by other, earlier resolvers.
        // If there are 10 vars which must be resolved by services, then 1 read here will be hit 10 times: once per service.
        // Definitely don't read inside the foreach loop!
        $toolconfig = $launchcontext->require(tool_context::class)->toolconfig;

        $val = $paramstr;
        foreach ($this->pluginregistry->get_all() as $service) {
            $service->set_type($toolconfig->tool);
            $service->set_tool_proxy($toolconfig->toolproxy ?? null);
            $value = $service->parse_val($val, $launchcontext);
            if ($val != $value) {
                break;
            }
        }
        return $value;
    }
}
