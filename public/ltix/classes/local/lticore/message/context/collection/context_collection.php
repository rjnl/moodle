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
 * General-purpose immutable collection of typed context objects for an LTI message pipeline.
 *
 * Each context object type may appear at most once. Objects are keyed by their fully-qualified
 * class name, allowing pipeline stages and resolvers to request strongly-typed data without
 * coupling to a fixed set of properties.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class context_collection implements context_collection_interface {

    /** @var array<class-string, object> map of class name to context object instance. */
    private array $contexts;

    /**
     * Constructor.
     *
     * @param object ...$contexts one or more context objects to store; each class may appear at most once.
     * @throws lti_exception if the same context class is provided more than once.
     */
    public function __construct(object ...$contexts) {
        $this->contexts = $this->validate_contexts($contexts);
    }

    /**
     * Validate the given contexts and index them by class name.
     *
     * @param array $contexts raw list of context objects.
     * @return array<class-string, object> validated map of class name to object.
     * @throws lti_exception if duplicate context classes are detected.
     */
    private function validate_contexts(array $contexts): array {
        $validated = [];
        foreach ($contexts as $context) {
            if (isset($validated[$context::class])) {
                throw new lti_exception("Duplicate context class added to collection: ".$context::class);
            }
            $validated[$context::class] = $context;
        }
        return $validated;
    }

    /**
     * Return a new immutable context instance where the additional object is also included.
     *
     * @param object $context the object to add to the context
     * @return self
     */
    public function with(object $context): static {
        $newcontexts = $this->contexts;
        $newcontexts[$context::class] = $context;
        return new self(...$newcontexts);
    }

    /**
     * Is an object of type $contextclass stored within.
     *
     * @param string $contextclass
     * @return bool true if stored, false otherwise.
     */
    public function has(string $contextclass): bool {
        return isset($this->contexts[$contextclass]);
    }

    /**
     * Try to get a context class by classname.
     *
     * For cases where the context class isn't strictly required by calling code.
     *
     * @param string $contextclass
     * @return object|null
     */
    public function get(string $contextclass): ?object {
        return $this->contexts[$contextclass] ?? null;
    }

    /**
     * Get a required object of type $contextclass.
     *
     * For cases where the context class is strictly required by callers.
     *
     * @param string $contextclass
     * @return object
     * @throws lti_exception if the context class cannot be found.
     */
    public function require(string $contextclass): object {
        if (!$this->has($contextclass)) {
            throw new lti_exception(sprintf('%s requires context %s, but it was not provided', self::class, $contextclass));
        }

        return $this->contexts[$contextclass];
    }
}
