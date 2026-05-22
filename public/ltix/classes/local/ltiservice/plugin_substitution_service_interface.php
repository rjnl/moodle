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

/**
 * Interface for the plugin substitution service.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface plugin_substitution_service_interface {
    /**
     * Perform custom parameter substitution for all service plugins.
     *
     * @param launch_context $launchcontext launch context vars
     * @param string $paramstr the string containing the variable to substitute.
     * @return string the substituted value
     */
    public function substitute(launch_context $launchcontext, string $paramstr): string;
}
