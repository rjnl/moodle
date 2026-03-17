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

/**
 * Modes impacting how custom parameters are transformed for final inclusion in the parameters array.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
enum custom_parameter_normalisation_mode: int {
    /**
     * MODE_NORMALISED_ONLY:
     * For a [key => val] pairing, normalises the key and includes ONLY [normalisedkey => val] in the array.
     */
    case MODE_NORMALISED_ONLY = 0;

    /**
     * MODE_BOTH:
     * For a [key => val] pairing, normalises the key and includes BOTH [key => val] AND [normalisedkey => val] in the array.
     * This can result in only a single [key => val] entry if the key is already normalised, and normalisation produces the same
     * key as a result.
     */
    case MODE_BOTH = 1;
}
