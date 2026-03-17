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

namespace core_ltix\local\lticore\message\substitution\factory;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\substitution\pipeline\variable_substitutor;
use core_ltix\local\lticore\message\substitution\policy\enabled_capabilities_only_policy;
use core_ltix\local\lticore\message\substitution\policy\substitute_all_policy;
use core_ltix\local\lticore\message\substitution\resolver\calculated_course_variable_resolver;
use core_ltix\local\lticore\message\substitution\resolver\calculated_user_variable_resolver;
use core_ltix\local\lticore\message\substitution\resolver\mapping\built_params_map_resolver;
use core_ltix\local\lticore\message\substitution\resolver\mapping\oidc_user_params_map_resolver;
use core_ltix\local\lticore\message\substitution\resolver\object_property_resolver;
use core_ltix\local\lticore\message\substitution\resolver\service_variable_resolver;
use core_ltix\local\lticore\message\substitution\variable_map;
use core_ltix\local\ltiservice\plugin_substitution_service_interface;

/**
 * Simple factory building variable_substitutor instances.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class variable_substitutor_factory {

    /**
     * Ctor.
     *
     * @param plugin_substitution_service_interface $pluginsubservice
     */
    public function __construct(protected plugin_substitution_service_interface $pluginsubservice) {
    }

    /**
     * Get a variable_substitutor instance handling substitution for the specified LTI version.
     *
     * @param lti_version $ltiversion the LTI version
     * @return variable_substitutor the substitutor instance.
     * @throws lti_exception if requesting an instance for an as-yet-unsupported LTI version.
     */
    public function get_for_version(
        lti_version $ltiversion,
    ): variable_substitutor {

        switch ($ltiversion) {
            case lti_version::LTI_VERSION_1:
            case lti_version::LTI_VERSION_1P3:
                $map = \core_ltix\helper::get_capabilities();
                return new variable_substitutor(
                    // LTI 1px substitutors unconditionally resolve substitution.
                    new substitute_all_policy(),
                    [
                        new built_params_map_resolver(new variable_map($map)),
                        new object_property_resolver($map),
                        new calculated_course_variable_resolver(),
                        new calculated_user_variable_resolver(),
                        new service_variable_resolver($this->pluginsubservice),
                    ],
                );
            case lti_version::LTI_VERSION_2:
                $map = \core_ltix\helper::get_capabilities();
                return new variable_substitutor(
                    // LTI 2p0 substitutors resolve substitution according to enabled tool capabilities.
                    new enabled_capabilities_only_policy(),
                    [
                        new built_params_map_resolver(new variable_map($map)),
                        new object_property_resolver($map),
                        new calculated_course_variable_resolver(),
                        new calculated_user_variable_resolver(),
                        new service_variable_resolver($this->pluginsubservice),
                    ],
                );
            default:
                throw new lti_exception("Unable to resolve variable_substitutor instance for LTI version: {$ltiversion->value}."
                    . " There is no composition mapped to the version.");
        }
    }

    /**
     * Get a variable_substitutor instance for OIDC authentication.
     *
     * @return variable_substitutor
     */
    public function get_for_oidc_auth(): variable_substitutor {
        $map = \core_ltix\helper::get_capabilities();
        return new variable_substitutor(
            new substitute_all_policy(),
            [
                new oidc_user_params_map_resolver(new variable_map($map))
            ],
        );
    }
}
