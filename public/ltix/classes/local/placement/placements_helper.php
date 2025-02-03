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

/**
 * Placements helper.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class placements_helper {

    /**
     * Updates the lti_placement_type table with the component placement type definitions.
     * If no parameters are given, the function updates the core moodle placement types only.
     *
     * Note that the absence of the db/lti.php, placement type definition file
     * will cause any stored placement types for the component to be removed from
     * the database.
     *
     * @param string $component the frankenstyle component name.
     * @return void
     */
    public static function update_placement_types(string $component = 'moodle'): void {
        global $DB;

        $fileplacementtypes = self::load_placement_types($component);
        $fileplacementtypesnames = array_keys($fileplacementtypes);

        $registeredplacementtypes = $DB->get_records_menu('lti_placement_type', ['component' => $component]);

        $newplacementtypes = array_diff($fileplacementtypesnames, $registeredplacementtypes);

        $deletedplacementtypes = array_diff($registeredplacementtypes, $fileplacementtypesnames);

        foreach ($newplacementtypes as $newplacementtype) {
            $DB->insert_record('lti_placement_type', ['component' => $component, 'type' => $newplacementtype]);
        }

        self::placement_types_cleanup($component, $deletedplacementtypes);
    }

    /**
     * Loads the lti placement types from disk for the given component.
     *
     * @param string $component the frankenstyle component name.
     * @return array the array of placement types.
     */
    private static function load_placement_types(string $component = 'moodle'): array {
        $filepath = \core\component::get_component_directory($component).'/db/lti.php';

        $placementtypes = [];
        if (file_exists($filepath)) {
            require($filepath);
        }

        return $placementtypes;
    }

    /**
     * Handle removal of a placement type from a component's lti.php.
     *
     * @param string $component the frankenstyle component name.
     * @param array $deletedplacementtypes array of deleted placement types, indexed by record id.
     * @return void
     */
    private static function placement_types_cleanup(string $component, array $deletedplacementtypes): void {
        global $DB;
        if (empty($deletedplacementtypes)) {
            return;
        }

        // Note: while lti_resource_link records have an association with placements, they belong to component/placement from which
        // they were created. They will not be removed here as part of a placement type removal.

        list($insql, $inparams) = $DB->get_in_or_equal(array_keys($deletedplacementtypes), SQL_PARAMS_NAMED);
        $DB->delete_records_select('lti_placement', "placementtypeid $insql", $inparams);

        foreach ($deletedplacementtypes as $deletedplacementtype) {
            $DB->delete_records('lti_placement_type', ['component' => $component, 'type' => $deletedplacementtype]);
        }
    }
}
