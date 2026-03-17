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

use core_component;

/**
 * Simple injectable registry for ltixservice plugins, wrapping the core component registry.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class service_plugin_registry {

    /**
     * Gets all ltixservice plugin instances.
     *
     * @return service_base[]
     */
    public function get_all(): array {
        return array_map(function($name) {
            $classname = "\\ltixservice_{$name}\\local\\service\\{$name}";
            return new $classname();
        }, array_keys(core_component::get_plugin_list('ltixservice')));
    }
}
