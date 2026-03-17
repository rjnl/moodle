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

namespace core_ltix\local\lticore\message\substitution\resolver;

use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\collection\substitution_context;
use core_ltix\local\lticore\message\substitution\pipeline\variable_resolver;
use core_ltix\local\ltiservice\plugin_substitution_service_interface;

/**
 * Class implementing resolution of service variables during substitution parameter expansion.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class service_variable_resolver implements variable_resolver {

    /**
     * Ctor.
     *
     * @param plugin_substitution_service_interface $pluginsubservice a service handling substitution by ltixservice plugins.
     */
    public function __construct(private plugin_substitution_service_interface $pluginsubservice) {
    }

    public function resolve(string $str, substitution_context $resolvecontext): ?string {
        if (!str_starts_with($str, "$")) {
            return null;
        }
        $launchcontext = $resolvecontext->require(launch_context::class);

        return $this->pluginsubservice->substitute($launchcontext, $str);
    }
}
