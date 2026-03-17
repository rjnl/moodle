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
use core_ltix\local\lticore\message\payload\parameters\processor\transformer\custom_param_substitutor;
use core_ltix\local\lticore\message\substitution\factory\variable_substitutor_factory;

/**
 * Factory building custom_param_substitutor composites.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class custom_param_substitutor_factory {

    /**
     * Constructor.
     * @param variable_substitutor_factory $subfactory
     */
    public function __construct(
        private variable_substitutor_factory $subfactory,
    ) {}

    /**
     * Get a custom parameter substitutor for a given LTI version.
     *
     * @param lti_version $ltiversion
     * @return custom_param_substitutor
     */
    public function get_for_version(lti_version $ltiversion): custom_param_substitutor {
        $substitutionpipeline = $this->subfactory->get_for_version($ltiversion);

        return new custom_param_substitutor($substitutionpipeline);
    }
}
