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
use core_ltix\local\lticore\message\context\item\message_context;

/**
 * Typed context collection representing the runtime data available at LTI message launch time.
 *
 * A {@see message_context} is mandatory and must always be present. Additional context objects
 * (e.g. course, user, resource link) can be supplied as variadic arguments and retrieved later
 * by type using the {@see context_collection_interface} methods.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
readonly class launch_context implements context_collection_interface {

    /** @var context_collection the underlying generic collection. */
    private context_collection $contextcollection;

    /**
     * Constructor.
     *
     * @param context_collection $contextcollection the pre-built context collection.
     */
    private function __construct(context_collection $contextcollection) {
        $this->contextcollection = $contextcollection;
    }

    /**
     * Create a launch_context instance from a mandatory message context and any additional context objects.
     *
     * @param message_context $messagecontext the LTI message type context (required).
     * @param object ...$contexts additional context objects, e.g. course_context, user_context.
     * @return self
     */
    public static function instance(
        message_context $messagecontext,
        object ...$contexts
    ) {
        return new self(new context_collection($messagecontext, ...$contexts));
    }

    /**
     * Return a new immutable instance that also contains the given context object.
     *
     * @param object $context the context object to add.
     * @return static
     */
    public function with(object $context): static {
        return new self($this->contextcollection->with($context));
    }

    /**
     * Check whether a context object of the given class is present in the collection.
     *
     * @param string $contextclass fully-qualified class name to look up.
     * @return bool true if present, false otherwise.
     */
    public function has(string $contextclass): bool {
        return $this->contextcollection->has($contextclass);
    }

    /**
     * Return the context object for the given class, or null if not present.
     *
     * @param string $contextclass fully-qualified class name to look up.
     * @return object|null the context object, or null if not found.
     */
    public function get(string $contextclass): ?object {
        return $this->contextcollection->get($contextclass);
    }

    /**
     * Return the context object for the given class, throwing if it is absent.
     *
     * @param string $contextclass fully-qualified class name to look up.
     * @return object the context object.
     * @throws lti_exception if the context class is not present.
     */
    public function require(string $contextclass): object {
        return $this->contextcollection->require($contextclass);
    }
}
