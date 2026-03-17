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

namespace core_ltix\local\lticore\message\payload\parameters\pipeline\registry;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_processor;

/**
 * A registry of parameters processors.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class parameter_processor_registry {
    /** @var array<string, parameters_processor> */
    private array $processors;

    /**
     * Constructor.
     * @param iterable $processors the processors to register.
     */
    public function __construct(iterable $processors) {
        foreach ($processors as $key => $processor) {
            $this->processors[$key] = $processor;
        }
    }

    /**
     * Get a processor by key.
     * @param string $key the key of the processor to get.
     * @return parameters_processor the processor.
     * @throws lti_exception if the processor is not registered.
     */
    public function get(string $key): parameters_processor {
        if (!isset($this->processors[$key])) {
            throw new lti_exception("Processor [$key] not registered.");
        }

        return $this->processors[$key];
    }
}
