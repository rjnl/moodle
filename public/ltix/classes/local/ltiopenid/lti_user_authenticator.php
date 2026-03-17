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

use core_ltix\local\lticore\exception\lti_exception;

/**
 * Concrete user authenticator, responsible for performing auth during OIDC and returning the user info.
 *
 * User privacy settings are handled by this implementation since they are controlled at the tool leve, by by tool config.
 * For legacy launches, where privacy settings can vary per-link, this should be resolved after auth, in calling code.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lti_user_authenticator implements lti_user_authenticator_interface {

    /**
     * Constructor. The instance is scoped to the auth'd user, and loginhint must match this user's id.
     *
     * @param \stdClass $user the user with the active session who we want to auth. Will be verified against loginhint.
     */
    public function __construct(protected \stdClass $user) {
    }

    public function authenticate(\stdClass $toolconfig, string $loginhint): lti_user {

        // Validate loginhint user against the user being auth'd - they must match.
        $userid = $loginhint;
        if ($this->user->id !== $userid) {
            throw new lti_exception(
                sprintf('Login hint user id %s does not match authenticated user id %s', $userid, $this->user->id)
            );
        }

        $isanonymousname = $this->get_is_anonymous_name($toolconfig);
        $isanonymousemail = $this->get_is_anonymous_email($toolconfig);

        $ltiuser = new lti_user(
            id: $this->user->id,
            name: !$isanonymousname ? fullname($this->user) : null,
            givenname: !$isanonymousname ? $this->user->firstname : null,
            familyname: !$isanonymousname ? $this->user->lastname : null,
            email: !$isanonymousemail ? $this->user->email : null,
            idnumber: $this->user->idnumber ?? null,
            username: !$isanonymousname ? $this->user->username : null,
        );

        return $ltiuser;
    }

    /**
     * Check whether the user's name should be anonymised, according to tool-level privacy settings.
     *
     * Since 4.3, this is ONLY controlled by tool-level privacy settings; link-level is no longer supported.
     * For legacy launches where link-level privacy is possible, this should be resolved in calling code.
     *
     * @param \stdClass $toolconfig the tool config
     * @return bool true if the name should be anonymised, false otherwise.
     */
    protected function get_is_anonymous_name(\stdClass $toolconfig): bool {
        return $toolconfig->config->sendname == \core_ltix\constants::LTI_SETTING_NEVER;
    }

    /**
     * Check whether the user's email should be anonymised, according to tool-level privacy settings.
     *
     * Since 4.3, this is ONLY controlled by tool-level privacy settings; link-level is no longer supported.
     * For legacy launches where link-level privacy is possible, this should be resolved in calling code.
     *
     * @param \stdClass $toolconfig the tool config
     * @return bool true if the email should be anonymised, false otherwise.
     */
    protected function get_is_anonymous_email(\stdClass $toolconfig): bool {
        return $toolconfig->config->sendemailaddr == \core_ltix\constants::LTI_SETTING_NEVER;
    }
}
