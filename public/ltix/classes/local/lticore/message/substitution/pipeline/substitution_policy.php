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

namespace core_ltix\local\lticore\message\substitution\pipeline;

use core_ltix\local\lticore\message\context\collection\substitution_context;

/**
 * Interface defining substitution policy behaviour.
 *
 * Policies control whether a variable is eligible for resolving, not the resolving process itself which
 * {@see core_ltix\local\lticore\message\substitution\resolver\variable_resolver} defines.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface substitution_policy {
    /**
     * Determine whether the variable should be resolved, according to the policy rules.
     *
     * @param string $str the variable string.
     * @param substitution_context $context the execution context.
     * @return bool true if it should be resolved, false otherwise.
     */
    public function should_substitute(string $str, substitution_context $context): bool;
}
