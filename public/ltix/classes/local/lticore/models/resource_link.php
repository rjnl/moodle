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

namespace core_ltix\local\lticore\models;

use core\persistent;
use core_ltix\local\lticore\messages\lti_message;
use core_ltix\local\lticore\messages\lti_resource_link_request;
use core_ltix\local\placement\placements_manager;

/**
 * Resource link persistent.
 *
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class resource_link extends persistent {

    /** @var string The table name. */
    public const TABLE = 'lti_resource_link';

    protected static function define_properties(): array {

        return [
            'typeid' => [
                'type' => PARAM_INT,
            ],
            'component' => [
                'type' => PARAM_COMPONENT,
            ],
            'itemtype' => [
                'type' => PARAM_RAW,
            ],
            'itemid' => [
                'type' => PARAM_INT,
            ],
            'contextid' => [
                'type' => PARAM_INT,
            ],
            'url' => [
                'type' => PARAM_URL,
            ],
            'title' => [
                'type' => PARAM_TEXT,
            ],
            'text' => [
                'type' => PARAM_CLEANHTML,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'textformat' => [
                'type' => PARAM_INT,
                'default' => FORMAT_MOODLE,
                'null' => NULL_ALLOWED,
                'choices' => [
                    FORMAT_MOODLE,
                    FORMAT_HTML,
                    FORMAT_PLAIN,
                    FORMAT_MARKDOWN,
                ],
            ],
            'gradable' => [
                'type' => PARAM_BOOL,
                'default' => false,
            ],
            'launchcontainer' => [
                'type' => PARAM_INT,
                'default' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                'null' => NULL_NOT_ALLOWED,
                'choices' => [
                    \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                    \core_ltix\constants::LTI_LAUNCH_CONTAINER_EMBED,
                    \core_ltix\constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
                    \core_ltix\constants::LTI_LAUNCH_CONTAINER_WINDOW,
                    \core_ltix\constants::LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW,
                ],
            ],
            'customparams' => [
                'type' => PARAM_TEXT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'icon' => [
                'type' => PARAM_URL,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'servicesalt' => [
                'type' => PARAM_TEXT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
        ];
    }
}
