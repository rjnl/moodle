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

namespace core_ltix\local\lticore\repository;

use core_ltix\helper;
use core_ltix\local\lticore\lti_version;

/**
 * Repository for fetching tool registrations.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_registration_repository {
    // TODO: implement a find_by_link() method which, internally, fetches the config either by:
    //  - checking the link->typeid (common case where links are directly attached to a tool type), then trying;
    //  - domain matching a tool based on link URL.
    //  This would naturally support backup-and-restored resource links, where the tool typeid may not be set for site tool links.
    //  As it stands, the 3 resource link services won't support restore link launches yet.

    /**
     * Get a registration by id.
     *
     * @param int $id the id of the registration
     * @return object|null the registration object or null if not found.
     */
    public function get_by_id(int $id): ?object {
        // TODO: could definitely save a few queries here eventually by doing a single query joining the type,
        //  config and proxy tables together, instead of doing separate queries for each.

        $registration = helper::get_type($id);
        if ($registration === false) {
            return null;
        }

        $registration = (object) [
            'tool' => $registration,
            'config' => (object) helper::get_type_config($id),
        ];

        if ($registration->tool->ltiversion == lti_version::LTI_VERSION_2->value && isset($registration->tool->toolproxyid)) {
            $proxy = helper::get_tool_proxy($registration->tool->toolproxyid);
            $registration->toolproxy = $proxy;
        }

        if ($registration->tool->ltiversion == lti_version::LTI_VERSION_1P3->value) {
            global $CFG;
            $registration->tool->issuer = $CFG->wwwroot;
        }

        return $registration;
    }
}
