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

namespace core_ltix\local\lticore\message\payload\parameters\pipeline\factory;

use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\payload\parameters\processor\transformer\custom_parameter_normalisation_mode;
use core_ltix\local\lticore\message\payload\parameters\processor\transformer\custom_parameter_normaliser;

/**
 * Factory building custom_parameter_normaliser composites.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class custom_parameter_normaliser_factory {

    /**
     * Get a custom parameter normaliser for a given LTI version.
     *
     * @param lti_version $ltiversion the LTI version.
     * @return custom_parameter_normaliser the custom parameter normaliser.
     */
    public function get_for_version(lti_version $ltiversion): custom_parameter_normaliser {

        $normalisationmode = match ($ltiversion) {
            lti_version::LTI_VERSION_1 => custom_parameter_normalisation_mode::MODE_NORMALISED_ONLY,
            lti_version::LTI_VERSION_1P3, lti_version::LTI_VERSION_2 => custom_parameter_normalisation_mode::MODE_BOTH,
            // Note: default case omitted to force UnhandledMatch error to be thrown in the event of future versions.
        };

        return new custom_parameter_normaliser($normalisationmode);
    }
}
