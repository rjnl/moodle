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

namespace core_ltix\local\lticore\message\payload\parameters\pipeline\core;

use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\payload\parameters\pipeline\factory\parameters_builder_factory;

/**
 * Parameter builder pipeline.
 *
 * A pipeline takes an array of processors {@see parameters_processor}, iterating over them to build up an array of parameters,
 * to be included in launch messages to tools. Once all processors have run, the final parameters array is returned.
 *
 * The pipeline can be composed of any number, or type, of processors, each implementing data addition (resolvers),
 * removal (policy), augmentation (converters, transformers) etc. making it a flexible way to create payload.
 *
 * If a specific behavior is needed for a specific message type or LTI version, a new processor can be implemented supporting
 * the behaviour, and it can be wired up in {@see parameters_builder_factory}.
 *
 * {@see parameters_builder_factory} for existing compositions supporting the core {messagetype, ltiversion} combinations.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
readonly class parameters_builder {

    /**
     * Get an instance of the pipeline with the given processors, where processor order matters.
     *
     * @param array $processors the ordered array of processors which will be run, in order, to create the parameters array.
     */
    public function __construct(private array $processors) {
    }

    /**
     * Run the pipeline to build the parameters array.
     *
     * @param launch_context $data any runtime data needed by processors during processing.
     * @return array the associate params array.
     */
    public function build(launch_context $data): array {
        $params = [];

        /** @var parameters_processor $processor */
        foreach ($this->processors as $processor) {
            $params = $processor->process($params, $data);
        }

        return $params;
    }
}
