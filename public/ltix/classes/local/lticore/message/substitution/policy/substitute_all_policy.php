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

namespace core_ltix\local\lticore\message\substitution\policy;

use core_ltix\local\lticore\message\context\collection\substitution_context;
use core_ltix\local\lticore\message\substitution\pipeline\substitution_policy;

/**
 * Substitution policy which permits substitution of any custom parameter.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class substitute_all_policy implements substitution_policy {

    #[\Override]
    public function should_substitute(string $str, substitution_context $context): bool {
        return str_starts_with($str, '$');
    }
}
