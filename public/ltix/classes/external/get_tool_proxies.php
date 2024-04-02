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
use core_external\external_multiple_structure;
use core_external\external_value;
use core_ltix\helper;

/**
 * External function to get tool proxies.
 *
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_tool_proxies extends external_api {

    /**
     * Parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'orphanedonly' => new external_value(PARAM_BOOL, 'Orphaned tool types only', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Returns the tool proxies.
     *
     * @param bool $orphanedonly Retrieve only tool proxies that do not have a corresponding tool type.
     * @return array
     */
    public static function execute(bool $orphanedonly = false): array {
        $params = self::validate_parameters(self::execute_parameters(),
            array(
                'orphanedonly' => $orphanedonly
            ));
        $orphanedonly = $params['orphanedonly'];

        $context = \context_system::instance();

        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        return helper::get_tool_proxies($orphanedonly);
    }

    /**
     * Return definition.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            structs::tool_proxy_return_structure()
        );
    }
}
