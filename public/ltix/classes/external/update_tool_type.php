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
 * External function to update a tool type.
 *
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_tool_type extends external_api {

    /**
     * Parameters definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'Tool type id'),
                'name' => new external_value(PARAM_RAW, 'Tool type name', VALUE_DEFAULT, null),
                'description' => new external_value(PARAM_RAW, 'Tool type description', VALUE_DEFAULT, null),
                'state' => new external_value(PARAM_INT, 'Tool type state', VALUE_DEFAULT, null)
            )
        );
    }

    /**
     * Update a tool type.
     *
     * @param int $id The id of the tool type to update
     * @param string|null $name The name of the tool type
     * @param string|null $description The name of the tool type
     * @param int|null $state The state of the tool type
     * @return array updated tool type
     * @throws \moodle_exception
     */
    public static function execute(int $id, ?string $name = null, ?string $description = null, ?int $state = null): array {
        $params = self::validate_parameters(self::execute_parameters(),
            array(
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'state' => $state,
            ));
        $id = $params['id'];
        $name = $params['name'];
        $description = $params['description'];
        $state = $params['state'];

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $type = helper::get_type($id);

        if (empty($type)) {
            throw new \moodle_exception('unabletofindtooltype', 'core_ltix', '', array('id' => $id));
        }

        if (!empty($name)) {
            $type->name = $name;
        }

        if (!empty($description)) {
            $type->description = $description;
        }

        if (!empty($state)) {
            // Valid state range.
            if (in_array($state, array(1, 2, 3))) {
                $type->state = $state;
            } else {
                throw new \moodle_exception("Invalid state: $state - must be 1, 2, or 3");
            }
        }

        helper::update_type($type, new \stdClass());

        return helper::serialise_tool_type($type);
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return structs::tool_type_return_structure();
    }
}
