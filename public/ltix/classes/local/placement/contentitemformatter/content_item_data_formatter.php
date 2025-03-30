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

namespace core_ltix\local\placement\contentitemformatter;

/**
 * Abstract class for formatting content item data.
 *
 * This class serves as a blueprint for formatting content item data. Implementing classes must define
 * the logic for formatting the content items before returning them. The formatter allows content item data
 * to be transformed or manipulated as needed before being returned to the calling context, such as a JS module.
 *
 * @package    core_ltix
 * @copyright  2025 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class content_item_data_formatter {

    /**
     * Format the provided content item data.
     *
     * This method should be implemented by subclasses to define the specific formatting logic for content items.
     * It receives the array of content items returned by the tool and a tool object, formats the content, and returns a
     * formatted object.
     *
     * @param array $contentitems An array of content item data to be formatted.
     * @param object $tool The tool object that contain additional context to assist in formatting.
     * @return object|null Returns the formatted content as an object, or null if no formatting is necessary or possible.
     */
    abstract public function format(array $contentitems, object $tool): ?object;
}
