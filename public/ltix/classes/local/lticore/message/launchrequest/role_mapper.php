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

namespace core_ltix\local\lticore\message\launchrequest;

use core\context;
use core_ltix\helper;

/**
 * Simple role mapping wrapper allowing role mapping to be an injectable dependency.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class role_mapper {

    /**
     * Map context roles to LTI roles.
     *
     * @param int $userid the user id.
     * @param int $contextid the context id.
     * @return array the list of LTI roles the user has in the context.
     */
    public function map_for(int $userid, int $contextid): array {
        return helper::get_lti_roles($userid, context::instance_by_id($contextid));
    }
}
