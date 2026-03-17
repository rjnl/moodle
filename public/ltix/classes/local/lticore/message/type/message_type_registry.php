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
 * Registry for mapping message type aliases to their canonical definitions.
 *
 * The registry builds a lookup table from message type definitions, mapping each
 * alias to its corresponding definition. This allows fast resolution of any
 * message type to its canonical form.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class message_type_registry {

    /** @var array<string, message_type_definition> lookup array for lookups by canonical or alias*/
    private array $lookup;

    /** @var array<string, message_type_definition> canonical lookup array*/
    private array $bycanonical;

    /**
     * Constructor.
     *
     * Builds a lookup table from message type definitions.
     *
     * @param message_type_definition[] $definitions list of message type definitions
     */
    public function __construct(array $definitions) {
        $lookup = [];
        $bycanonical = [];
        foreach ($definitions as $definition) {
            $canonical = $definition->canonical();

            $lookup[$canonical] = $definition;
            $bycanonical[$canonical] = $definition;

            foreach ($definition->aliases() as $alias) {
                $lookup[$alias] = $definition;
            }
        }
        $this->lookup = $lookup;
        $this->bycanonical = $bycanonical;
    }

    /**
     * Resolve a message type value to its definition.
     *
     * @param string $value the message type value to resolve
     * @return message_type_definition|null the definition if found, null otherwise
     */
    public function resolve(string $value): ?message_type_definition {
        return $this->lookup[$value] ?? null;
    }
}
