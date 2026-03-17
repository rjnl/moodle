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

namespace core_ltix\local\lticore\message\context\item;

/**
 * Immutable value object carrying OIDC user field data into the LTI substitution pipeline.
 *
 * Provides resolvers with user-specific variable values that are available after the LTI 1.3
 * OIDC authentication step has completed.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class oidc_user_context {

    /**
     * Constructor.
     *
     * @param array $userfields map of user variable name to user variable value,
     *                          e.g. ['Person.name.full' => 'Jane Doe'].
     */
    public function __construct(public array $userfields) {
    }
}
