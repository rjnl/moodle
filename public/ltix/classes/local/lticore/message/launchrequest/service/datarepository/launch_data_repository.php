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

namespace core_ltix\local\lticore\message\launchrequest\service\datarepository;

use core_ltix\local\lticore\models\resource_link;

/**
 * Crude, read-only repository to get required objects for a resource link request.
 *
 * This is a bare-minium abstraction that facilitates:
 * - decoupling the service layer from direct database calls, where repositories for things like course don't exist.
 * - mocking data dependencies for services under test.
 * - di autowiring of launch services + controllers, where this class can be injected into the relevant services and will be
 * autowired.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class launch_data_repository {

    /**
     * Get a resource link instance.
     *
     * @param int $resourcelinkid the id.
     * @return resource_link|null the instance, or null if not found.
     */
    public function get_resource_link(int $resourcelinkid): ?resource_link {
        if (!resource_link::record_exists($resourcelinkid)) {
            return null;
        }
        return new resource_link($resourcelinkid);
    }


    /**
     * Get the associated course for a given resource link.
     *
     * @param resource_link $resourcelink the resource link instance.
     * @return \stdClass|null the course object, or null if not found.
     */
    public function get_course(resource_link $resourcelink): ?\stdClass {
        $linkcontext = \core\context::instance_by_id($resourcelink->get('contextid'));
        $coursecontext = $linkcontext->get_course_context();
        if (!$coursecontext) {
            return null;
        }
        return get_course($coursecontext->instanceid);
    }
}
