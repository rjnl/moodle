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

namespace core_ltix\local\ltiopenid;

/**
 * Interface for LTI user authentication.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface lti_user_authenticator_interface {
    /**
     * Authenticate an LTI user from a login hint.
     *
     * @param \stdClass $toolconfig The tool configuration object
     * @param string $loginhint The login hint identifying the user
     * @return lti_user The authenticated LTI user
     */
    public function authenticate(\stdClass $toolconfig, string $loginhint): lti_user;
}
