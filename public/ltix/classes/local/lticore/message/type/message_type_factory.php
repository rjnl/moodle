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
 * Factory for creating message type instances.
 *
 * Uses a message type registry to resolve message types to their canonical forms.
 * If a message type is not found in the registry, it is created with the input
 * value as both the value and canonical form.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class message_type_factory {

    /**
     * Constructor.
     *
     * @param message_type_registry $registry the message type registry
     */
    public function __construct(private message_type_registry $registry) {
    }

    /**
     * Create a message type from a string value.
     *
     * @param string $value the message type value to create
     * @return message_type the created message type instance
     */
    public function from_string(string $value): message_type {
        $definition = $this->registry->resolve($value);

        if ($definition === null) {
            return message_type::create($value, $value);
        }

        return message_type::create(
            $value,
            $definition->canonical()
        );
    }
}
