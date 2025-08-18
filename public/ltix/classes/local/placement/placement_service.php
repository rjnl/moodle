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
     * @param object $link The resource link object
     * @return int The launch container constant value
     */
    public static function get_launch_container_for_link(object $link): int {
        $devicetype = core_useragent::get_device_type();

        // Scrolling within the object element doesn't work on iOS or Android.
        // Opening the popup window also had some issues in testing.
        // For mobile devices, always take up the entire screen to ensure the best experience.
        if ($devicetype === core_useragent::DEVICETYPE_MOBILE || $devicetype === core_useragent::DEVICETYPE_TABLET) {
            return \core_ltix\constants::LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW;
        }

        // Get the tool configuration.
        $toolconfig = !empty($link->typeid) ? \core_ltix\helper::get_type_config($link->typeid) : [];

        $launchcontainer = match (true) {
            // Use link's container if it's set and not default.
            !empty($link->launchcontainer) &&
                $link->launchcontainer != \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT
            => $link->launchcontainer,

            // Otherwise use tool config if available.
            isset($toolconfig['launchcontainer']) &&
                $toolconfig['launchcontainer'] != \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT
            => $toolconfig['launchcontainer'],

            // Final fallback.
            default => \core_ltix\constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS
        };

        return $launchcontainer;
    }
}
