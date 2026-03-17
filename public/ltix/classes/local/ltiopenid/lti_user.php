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
 * Represents an LTI user during launch.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
readonly class lti_user {

    /**
     * Constructor.
     *
     * @param string $id
     * @param string|null $name
     * @param string|null $givenname
     * @param string|null $familyname
     * @param string|null $email
     * @param string|null $idnumber
     * @param string|null $username
     */
    public function __construct(
        public string  $id,
        public ?string $name = null,
        public ?string $givenname = null,
        public ?string $familyname = null,
        public ?string $email = null,
        public ?string $idnumber = null,
        public ?string $username = null,
    ) {
    }

    /**
     * Get the user data as an array of LTI parameter names to values.
     *
     * @return array the user data as an unformatted array
     */
    public function get_unformatted_userdata(): array {
        return array_filter([
            'user_id' => $this->id,
            'lis_person_name_full' => $this->name,
            'lis_person_name_given' => $this->givenname,
            'lis_person_name_family' => $this->familyname,
            'lis_person_contact_email_primary' => $this->email,
            'lis_person_sourcedid' => $this->idnumber,
            'ext_user_username' => $this->username,
        ]);
    }
}
