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

/**
 * This files exposes functions for LTI 1.3 Service Plugin Management.
 *
 * @package    core_ltix
 * @copyright  2023 Ismael Texidor-Rodriguez (Turnitin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core_ltix\local\ltiservice;


/**
 * This class exposes functions for LTI 1.3 Service Plugin Management.
 *
 * @package    core_ltix
 * @copyright  2023 Ismael Texidor-Rodriguez (Turnitin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_helper {
    public static function get_contexts($json) {

        $contexts = array();
        if (isset($json->{'@context'})) {
            foreach ($json->{'@context'} as $context) {
                if (is_object($context)) {
                    $contexts = array_merge(get_object_vars($context), $contexts);
                }
            }
        }
    
        return $contexts;
    
    }
}