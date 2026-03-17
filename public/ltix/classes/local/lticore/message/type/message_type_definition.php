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
 * Definition of an LTI message type with its canonical form and aliases.
 *
 * A message type definition consists of a canonical (standard) form and a set of
 * aliases that map to the same canonical form.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class message_type_definition {

    /**
     * Constructor.
     *
     * @param string $canonical the canonical form of the message type
     * @param array $aliases list of aliases that map to this canonical form
     */
    public function __construct(private string $canonical, private array $aliases) {
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
     * Get the list of aliases for this message type.
     *
     * @return string[] the aliases
     */
    public function aliases(): array {
        return $this->aliases;
    }
}
