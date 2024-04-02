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

namespace core_ltix\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use core_ltix\helper;

/**
 * External function to determine whether a URL is a cartridge URL.
 *
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class is_cartridge extends external_api {

    /**
     * Parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'url' => new external_value(PARAM_URL, 'Tool url'),
            )
        );
    }

    /**
     * Determine if the url to a tool is for a cartridge.
     *
     * @param string $url Url that may or may not be an xml cartridge
     * @return array ['iscartrdige' => bool], indicating whether the URL is a cartridge or not.
     */
    public static function execute(string $url): array {
        $params = self::validate_parameters(self::execute_parameters(),
            array(
                'url' => $url,
            ));
        $url = $params['url'];

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $iscartridge = helper::is_cartridge($url);

        return array('iscartridge' => $iscartridge);
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            array(
                'iscartridge' => new external_value(PARAM_BOOL, 'True if the URL is a cartridge'),
            )
        );
    }
}
