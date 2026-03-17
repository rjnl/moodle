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

namespace core_ltix\local\lticore\message\payload\parameters\processor\transformer;

use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\collection\substitution_context;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_processor;
use core_ltix\local\lticore\message\substitution\pipeline\variable_substitutor;

/**
 * Processor implementing variable substitution within custom parameters.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class custom_param_substitutor implements parameters_processor {

    /**
     * Ctor
     *
     * @param variable_substitutor $substitutor the substitutor doing the substitution work.
     */
    public function __construct(private variable_substitutor $substitutor) {
    }

    public function process(array $parameters, launch_context $launchcontext): array {

        $customparams = array_filter($parameters, function ($key) {
            return str_starts_with($key, "custom_");
        }, ARRAY_FILTER_USE_KEY);

        $subbed = $this->substitutor->substitute(
            $customparams,
            substitution_context::for_parameter_pipeline($parameters, $launchcontext),
        );

        return array_merge($parameters, $subbed);
    }
}
