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
use core_ltix\local\lticore\message\context\item\course_context;
use core_ltix\local\lticore\message\context\item\user_context;
use core_ltix\local\lticore\message\substitution\pipeline\variable_resolver;

/**
 * Resolves variable values using an input:object-property map.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class object_property_resolver implements variable_resolver {

    private array $map;

    /**
     * Ctor.
     * @param array $map the map of input:sourcedatakey.
     */
    public function __construct(array $map) {
        // Filter the map, removing any non-object-prop resolutions e.g. keep only ['My.Var' => '$Obj->x']
        $this->map = array_filter($map, function($value) {
            return $value && str_starts_with($value, '$');
        });
    }

    #[\Override]
    public function resolve(string $str, substitution_context $resolvecontext): ?string {
        if (!str_starts_with($str, "$")) {
            return null;
        }

        $str = substr($str, 1);
        if (!array_key_exists($str, $this->map)) {
            return null;
        }

        $launchcontext = $resolvecontext->require(launch_context::class);
        $user = $launchcontext->require(user_context::class)->user;
        $course = $launchcontext->require(course_context::class)->course;

        $objects = [
            ...(isset($user) ? ['USER' => $user]: []),
            ...(isset($course) ? ['COURSE' => $course]: [])
        ];

        $mapobjectprop = $this->map[$str];
        $pieces = explode('->', substr($mapobjectprop, 1), 2);
        $objname = $pieces[0];
        $objprop = $pieces[1];
        $obj = $objects[$objname] ?? null;

        return $obj ? ($obj->$objprop ?? null) : null;
    }
}
