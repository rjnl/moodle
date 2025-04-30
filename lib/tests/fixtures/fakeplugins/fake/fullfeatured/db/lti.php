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
 * Mock LTI placement types for the mock plugin fake_fullfeatured.
 *
 * @package    core
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$placementtypes = [
    // Valid type string and handler.
    'fake_fullfeatured:myfirstplacementtype' => [
        'handler' => \fake_fullfeatured\lti\placement\myfirstplacementtype::class
    ],

    // No handler specified, which is also a valid configuration.
    'fake_fullfeatured:anotherplacementtype' => [],

    // Invalid type for a handler.
    'fake_fullfeatured:thirdplacementtype' => [
        'handler' => \stdClass::class,
    ],

    // Invalid format for the placement type string.
    'fake/fullfeatured_invalidplacementtypestring' => [
        'handler' => \fake_fullfeatured\lti\placement\myfirstplacementtype::class
    ],

    // Invalid component prefix (core_ltix doesn't match the owning component, fake_fullfeatured).
    'core_ltix:placementtypestring' => [
        'handler' => \fake_fullfeatured\lti\placement\myfirstplacementtype::class
    ],
];
