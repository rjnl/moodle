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

namespace core_ltix\local\placement;

use core\url;
use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\models\resource_link;
use core_ltix\local\lticore\repository\tool_registration_repository;
use core_ltix\route\controller\launchrequest\v1p1_resource_link_launch_controller;
use core_ltix\route\controller\launchrequest\v1p3_resource_link_launch_controller;
use core_ltix\route\controller\launchrequest\v2p0_resource_link_launch_controller;
use core_useragent;

/**
 * Placement service class.
 *
 * @package    core_ltix
 * @copyright  2025 Muhammad Arnaldo <muhammad.arnaldo@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class placement_service {
    /**
     * Get the launch container for a specific LTI resource link.
     *
     * This method allows placements to determine the expected launch container for a link
     * so they can make decisions about how to present the link.
     *
     * @param resource_link $link The resource link object
     * @return int The launch container constant value
     */
    public static function get_launch_container_for_link(resource_link $link): int {
        $devicetype = core_useragent::get_device_type();

        // Scrolling within the object element doesn't work on iOS or Android.
        // Opening the popup window also had some issues in testing.
        // For mobile devices, always take up the entire screen to ensure the best experience.
        if ($devicetype === core_useragent::DEVICETYPE_MOBILE || $devicetype === core_useragent::DEVICETYPE_TABLET) {
            return \core_ltix\constants::LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW;
        }

        // Get the tool configuration.
        $toolconfig = !empty($link->get('typeid')) ? \core_ltix\helper::get_type_config($link->get('typeid')) : [];

        $launchcontainer = match (true) {
            // Use link's container if it's set and not default.
            !empty($link->get('launchcontainer')) &&
                intval($link->get('launchcontainer')) != \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT
            => intval($link->get('launchcontainer')),

            // Otherwise use tool config if available.
            isset($toolconfig['launchcontainer']) &&
                $toolconfig['launchcontainer'] != \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT
            => $toolconfig['launchcontainer'],

            // Final fallback.
            default => \core_ltix\constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS
        };

        return $launchcontainer;
    }

    /**
     * Get the tool url for a specific LTI resource link.
     *
     * @param resource_link $link the link instance.
     * @return url the launch URL.
     * @throws lti_exception
     */
    // TODO: unit test missing
    public static function get_tool_url_for_link(resource_link $link): url {

        $registrationrepo = new tool_registration_repository();
        $toolid = $link->get('typeid');

        if ($toolid === 0) {
            // Detached link - try to domain match a tool to determine the base url.
            // TODO: implement domain match logic, probably via a new method registrationrepo->find_by_domain().
        } else {
            $registration = $registrationrepo->get_by_id($toolid);
        }

        if (empty($registration)) {
            throw new lti_exception('tooltypenotfounderror', 'core_ltix');
        }

        return new url($registration->tool->baseurl);
    }

    /**
     * Get the launch url for a specific LTI resource link.
     *
     * @param resource_link $link the link instance
     * @return url the launch URL.
     * @throws lti_exception
     */
    // TODO: unit test missing
    public static function get_launch_url_for_link(resource_link $link): url {
        $registrationrepo = new tool_registration_repository();
        $toolid = $link->get('typeid');

        if ($toolid === 0) {
            // Detached link - try to domain match a tool to determine the base url.
            // TODO: implement domain match logic, probably via a new method registrationrepo->find_by_domain().
        } else {
            $registration = $registrationrepo->get_by_id($toolid);
        }

        if (empty($registration)) {
            throw new lti_exception('tooltypenotfounderror', 'core_ltix');
        }

        $controllerclass = match($registration->tool->ltiversion) {
            lti_version::LTI_VERSION_1P3->value => v1p3_resource_link_launch_controller::class,
            lti_version::LTI_VERSION_1->value => v1p1_resource_link_launch_controller::class,
            lti_version::LTI_VERSION_2->value => v2p0_resource_link_launch_controller::class,
        };

        return \core\router\util::get_path_for_callable([
            $controllerclass,
            'launch_resource_link',
        ], ['resourcelinkid' => $link->get('id')]);
    }
}
