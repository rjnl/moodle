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

namespace core_ltix\local\lticore\message\type;

/**
 * Value object representing an LTI message type.
 *
 * An LTI message type has a value (the raw input) and a canonical form (the standardized form).
 * Two message types are considered equal if their canonical forms match.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class message_type {
    /**
     * Constructor (private - use create() factory method instead).
     *
     * @param string $value the raw message type value
     * @param string $canonical the canonical form of the message type
     */
    private function __construct(private string $value, private string $canonical) {
    }

    /**
     * Create a new message type instance.
     *
     * @param string $value the raw message type value
     * @param string $canonical the canonical form of the message type
     * @return self the created message type instance
     */
    public static function create(string $value, string $canonical): self {
        return new self($value, $canonical);
    }

    /**
     * Get the raw message type value.
     *
     * @return string the raw value
     */
    public function value(): string {
        return $this->value;
    }

    /**
     * Get the canonical form of the message type.
     *
     * @return string the canonical form
     */
    public function canonical(): string {
        return $this->canonical;
    }

    /**
     * Check if this message type equals another.
     *
     * Two message types are equal if their canonical forms are identical.
     *
     * @param self $other the other message type to compare with
     * @return bool true if the message types are equal, false otherwise
     */
    public function equals(self $other): bool {
        return $this->canonical === $other->canonical;
    }

    /**
     * Return the string representation of the message type.
     *
     * @return string the raw message type value
     */
    public function __toString(): string {
        return $this->value;
    }
}

