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

namespace mod_lti\lti\placement;

use core\context;
use core_ltix\local\placement\contentitemformatter\content_item_data_formatter;
use core_ltix\local\placement\deeplinking_placement_handler;
use mod_lti\lti\placement\contentitemformatter\form\content_item_to_form_formatter;

/**
 * Deep linking placement handler.
 *
 * @package    mod_lti
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activityplacement extends deeplinking_placement_handler {

    public static function instance(): static {
        return new self();
    }

    public function content_item_selection_capabilities(context $context): void {
        require_capability('moodle/course:manageactivities', $context);
    }

    #[\Override]
    public static function get_content_item_data_formatter(): content_item_data_formatter {
        return new content_item_to_form_formatter();
    }
}
