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
 * Steps definitions related to core_ltix.
 *
 * @package   core_ltix
 * @category  test
 * @copyright 2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../lib/behat/behat_base.php');

/**
 * Steps definitions related to core_ltix.
 */
class behat_core_ltix extends behat_base {

    /**
     * Convert page names to URLs for steps like 'When I am on the "core_ltix > [page name]" page'.
     *
     * Recognised page names are:
     * | Page         | Description                            |
     * | manage tools | Site admin page for managing LTI tools |
     *
     * @param string $page name of the page, with the component name removed e.g. 'Admin notification'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch (strtolower($page)) {
            case 'manage tools':
                return new moodle_url('/ltix/toolconfigure.php');
            default:
                throw new Exception('Unrecognised core_ltix page type "' . $page . '."');
        }
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * Recognised page names are:
     * | pagetype     | name meaning | description                                 |
     * | Course tools | Course name  | The LTI course tools page (coursetools.php) |
     *
     * E.g. When I am on the "Course 1" "core_ltix > Course tools" page logged in as admin
     *
     * @param string $type identifies which type of page this is, e.g. 'Course tools'.
     * @param string $identifier identifies the particular page data, e.g. 'Course 1'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        switch (strtolower($type)) {
            case 'course tools':
                return new moodle_url('/ltix/coursetools.php',
                    ['id' => $this->get_course_id($identifier)]);
            default:
                throw new Exception('Unrecognised core_ltix page type "' . $type . '."');
        }
    }
}
