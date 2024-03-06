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
 * Post-install for the ltixservice_gradebookservices plugin.
 *
 * @package    ltixservice_gradebookservices
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_ltixservice_gradebookservices_install() {
    global $DB;
    $dbman = $DB->get_manager();

    // Migrate data from the now-defunct ltiservice_gradebookservices plugin if this plugin is being installed during an upgrade.
    if ($dbman->table_exists('tmp_ltiservice_gradebookservices')) {
        $sql = 'INSERT INTO {ltiservice_gradebookservices}
                            (gradeitemid, courseid, toolproxyid, typeid, baseurl, ltilinkid, resourceid, tag, subreviewurl,
                             subreviewparams)
                     SELECT gradeitemid, courseid, toolproxyid, typeid, baseurl, ltilinkid, resourceid, tag, subreviewurl,
                            subreviewparams
                       FROM {tmp_ltiservice_gradebookservices}';
        $DB->execute($sql);

        $dbman->drop_table(new xmldb_table('tmp_ltiservice_gradebookservices'));
    }
}
