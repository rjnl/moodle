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

namespace core_ltix\local\lticore\message\context\collection;

use core_ltix\local\lticore\exception\lti_exception;

/**
 * Interface for a collection of context objects, which can be used to store and retrieve various types of context information
 * related to an LTI message.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface context_collection_interface {
    /**
     * Return a new immutable context instance where the additional object is also included.
     *
     * @param object $context the object to add to the context
     * @return context_collection
     */
    public function with(object $context): static;

    /**
     * Is an object of type $contextclass stored within.
     *
     * @param string $contextclass
     * @return bool true if stored, false otherwise.
     */
    public function has(string $contextclass): bool;

    /**
     * Try to get a context class by classname.
     *
     * For cases where the context class isn't strictly required by calling code.
     *
     * @param string $contextclass
     * @return object|null
     */
    public function get(string $contextclass): ?object;

    /**
     * Get a required object of type $contextclass.
     *
     * For cases where the context class is strictly required by callers.
     *
     * @param string $contextclass
     * @return object
     * @throws lti_exception if the context class cannot be found.
     */
    public function require(string $contextclass): object;
}
