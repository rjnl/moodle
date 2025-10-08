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

namespace mod_lti;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for LTI activity.
 *
 * @package    mod_lti
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Get SQL to query DB for LTI tool proxy records.
     *
     * @deprecated since Moodle 5.1
     * @param bool $orphanedonly If true, return SQL to get orphaned proxies only.
     * @param bool $count If true, return SQL to get the count of the records instead of the records themselves.
     * @return string SQL.
     */
    #[\core\attribute\deprecated(
        since: '5.1',
        reason: 'Use \core_ltix\helper::get_tool_proxy_sql() instead',
        mdl: 'MDL-79113',
    )]
    public static function get_tool_proxy_sql(bool $orphanedonly = false, bool $count = false): string {
        \core\deprecation::emit_deprecation_if_present([self::class, __FUNCTION__]);
        return \core_ltix\helper::get_tool_proxy_sql($orphanedonly, $count);
    }
}
