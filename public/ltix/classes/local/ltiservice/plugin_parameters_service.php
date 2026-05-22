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
 * Service responsible for retrieving parameters from registered service plugins and providing them for launch.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class plugin_parameters_service implements plugin_parameters_service_interface {

    /**
     * Constructor.
     *
     * @param service_plugin_registry $pluginregistry registry to fetch plugin implementations.
     */
    public function __construct(private service_plugin_registry $pluginregistry) {
    }

    public function get_launch_parameters(launch_context $launchcontext): array {
        $toolconfig = $launchcontext->require(tool_context::class)->toolconfig;

        $pluginparams = [];
        foreach ($this->pluginregistry->get_all() as $serviceplugin) {
            $serviceplugin->set_type($toolconfig->tool);
            $serviceplugin->set_typeconfig((array) $toolconfig->config);

            $params = $serviceplugin->get_launch_params($launchcontext);
            foreach ($params as $paramkey => $paramvalue) {
                $pluginparams['custom_'.$paramkey] = $paramvalue;
            }
        }
        return $pluginparams;
    }
}
