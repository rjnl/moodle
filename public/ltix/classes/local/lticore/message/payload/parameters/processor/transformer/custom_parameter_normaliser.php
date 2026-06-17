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

use core_ltix\helper;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_processor;

/**
 * Processor ensuring key normalisation takes place for custom parameters.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class custom_parameter_normaliser implements parameters_processor {

    /**
     * Ctor.
     *
     * @param custom_parameter_normalisation_mode $normalisationmode which normalisation mode to enforce.
     */
    public function __construct(protected custom_parameter_normalisation_mode $normalisationmode) {
    }

    /**
     * Perform normalisation on an array of custom parameters, returning the array of [normalisedkey => val].
     *
     * @param array $customparams assoc array of custom param names => values.
     * @return array the array with all custom-prefixed data normalised per the normalisation mode.
     */
    private function normalise(array $customparams): array {
        $normalisedparams = [];
        foreach ($customparams as $key => $val) {
            $name = substr($key, strlen('custom_'));
            $normalisedname = helper::map_keyname($name);
            $normalisedparams['custom_'.$normalisedname] = $val;
        }
        return $normalisedparams;
    }

    #[\Override]
    public function process(array $parameters, launch_context $data): array {
        $customparams = array_filter($parameters, function ($key) {
            return str_starts_with($key, "custom_");
        }, ARRAY_FILTER_USE_KEY);

        return match($this->normalisationmode) {
            custom_parameter_normalisation_mode::MODE_NORMALISED_ONLY => (function() use ($customparams, $parameters) {
                $normalisedcustomparams = $this->normalise($customparams);
                return array_merge(
                    array_filter($parameters, fn($x) => !str_starts_with($x, 'custom_'), ARRAY_FILTER_USE_KEY),
                    $normalisedcustomparams
                );
            })(),
            custom_parameter_normalisation_mode::MODE_BOTH => array_merge($parameters, $this->normalise($customparams))
        };
    }
}
