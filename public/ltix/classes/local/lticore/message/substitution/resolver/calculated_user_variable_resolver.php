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
 * Class implementing resolution of calculated user variables during parameter expansion.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class calculated_user_variable_resolver implements variable_resolver {

    #[\Override]
    public function resolve(string $str, substitution_context $resolvecontext): ?string {
        $launchcontext = $resolvecontext->require(launch_context::class);
        $user = $launchcontext->require(user_context::class)->user;
        $course = $launchcontext->require(course_context::class)->course;

        return match($str) {
            '$Moodle.Person.userGroupIds' => implode(",", groups_get_user_groups($course->id, $user->id)[0]),
            default => null
        };
    }
}
