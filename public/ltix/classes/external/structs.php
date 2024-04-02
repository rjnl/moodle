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
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

/**
 * Encapsulates shared return structures.
 *
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class structs {

    /**
     * Returns structure be used for returning a tool type from a web service.
     *
     * @return external_single_structure
     */
    public static function tool_type_return_structure(): external_single_structure {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Tool type id'),
                'name' => new external_value(PARAM_NOTAGS, 'Tool type name'),
                'description' => new external_value(PARAM_NOTAGS, 'Tool type description'),
                'platformid' => new external_value(PARAM_TEXT, 'Platform ID'),
                'clientid' => new external_value(PARAM_TEXT, 'Client ID'),
                'deploymentid' => new external_value(PARAM_INT, 'Deployment ID'),
                'urls' => new external_single_structure(
                    array(
                        'icon' => new external_value(PARAM_URL, 'Tool type icon URL'),
                        'edit' => new external_value(PARAM_URL, 'Tool type edit URL'),
                        'course' => new external_value(PARAM_URL, 'Tool type edit URL', VALUE_OPTIONAL),
                        'publickeyset' => new external_value(PARAM_URL, 'Public Keyset URL'),
                        'accesstoken' => new external_value(PARAM_URL, 'Access Token URL'),
                        'authrequest' => new external_value(PARAM_URL, 'Authorisation Request URL'),
                    )
                ),
                'state' => new external_single_structure(
                    array(
                        'text' => new external_value(PARAM_TEXT, 'Tool type state name string'),
                        'pending' => new external_value(PARAM_BOOL, 'Is the state pending'),
                        'configured' => new external_value(PARAM_BOOL, 'Is the state configured'),
                        'rejected' => new external_value(PARAM_BOOL, 'Is the state rejected'),
                        'unknown' => new external_value(PARAM_BOOL, 'Is the state unknown'),
                    )
                ),
                'hascapabilitygroups' => new external_value(PARAM_BOOL, 'Indicate if capabilitygroups is populated'),
                'capabilitygroups' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Tool type capability groups enabled'),
                    'Array of capability groups', VALUE_DEFAULT, array()
                ),
                'courseid' => new external_value(PARAM_INT, 'Tool type course', VALUE_DEFAULT, 0),
                'instanceids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'LTI instance ID'),
                    'IDs for the LTI instances using this type', VALUE_DEFAULT, array()
                ),
                'instancecount' => new external_value(PARAM_INT, 'The number of times this tool is being used')
            ), 'Tool'
        );
    }

    /**
     * Returns description of a tool proxy.
     *
     * @return external_single_structure
     */
    public static function tool_proxy_return_structure(): external_single_structure {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'Tool proxy id'),
                'name' => new external_value(PARAM_TEXT, 'Tool proxy name'),
                'regurl' => new external_value(PARAM_URL, 'Tool proxy registration URL'),
                'state' => new external_value(PARAM_INT, 'Tool proxy state'),
                'guid' => new external_value(PARAM_TEXT, 'Tool proxy globally unique identifier'),
                'secret' => new external_value(PARAM_TEXT, 'Tool proxy shared secret'),
                'vendorcode' => new external_value(PARAM_TEXT, 'Tool proxy consumer code'),
                'capabilityoffered' => new external_value(PARAM_TEXT, 'Tool proxy capabilities offered'),
                'serviceoffered' => new external_value(PARAM_TEXT, 'Tool proxy services offered'),
                'toolproxy' => new external_value(PARAM_TEXT, 'Tool proxy'),
                'timecreated' => new external_value(PARAM_INT, 'Tool proxy time created'),
                'timemodified' => new external_value(PARAM_INT, 'Tool proxy modified'),
                'createdby' => new external_value(PARAM_INT, 'Tool proxy created by userid'),
            )
        );
    }
}
