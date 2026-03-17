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

namespace core_ltix;

use core\hook\di_configuration;
use core_ltix\local\lticore\message\payload\parameters\pipeline\registry\parameter_processor_registry;
use core_ltix\local\lticore\message\payload\parameters\processor\converter\jwt_claim_converter;
use core_ltix\local\lticore\message\payload\parameters\processor\policy\exclude_user_params_policy;
use core_ltix\local\lticore\message\payload\parameters\processor\policy\lis_bo_policy;
use core_ltix\local\lticore\message\payload\parameters\processor\policy\pii_policy;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\context_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\ext_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\launch_presentation_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\lis_bo_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\lis_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\ltixservice_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\tool_consumer_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\tool_custom_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\user_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\custom\resource_link_launch_custom_resolver;
use core_ltix\local\lticore\message\type\message_type_definition;
use core_ltix\local\lticore\message\type\message_type_registry;
use core_ltix\local\ltiservice\plugin_parameters_service;
use core_ltix\local\ltiservice\plugin_parameters_service_interface;
use core_ltix\local\ltiservice\plugin_substitution_service;
use core_ltix\local\ltiservice\plugin_substitution_service_interface;
use core_ltix\local\ltiservice\service_plugin_registry;

/**
 * Hook listeners for core_ltix.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener {
    /**
     * Tell the DIC about various concrete objects.
     *
     * @param di_configuration $hook
     * @return void
     */
    public static function inject_dependencies(di_configuration $hook): void {
        $hook->add_definition(
            id: plugin_parameters_service_interface::class,
            definition: function (service_plugin_registry $pluginregistry): plugin_parameters_service_interface {
                return new plugin_parameters_service($pluginregistry);
            }
        );
        $hook->add_definition(
            id: plugin_substitution_service_interface::class,
            definition: function (service_plugin_registry $pluginregistry): plugin_substitution_service_interface {
                return new plugin_substitution_service($pluginregistry);
            }
        );
        $hook->add_definition(
            id: message_type_registry::class,
            definition: function (): message_type_registry {
                return new message_type_registry([
                    new message_type_definition(
                        'LtiResourceLinkRequest', // Canonical value is v1p3.
                        [
                            'basic-lti-launch-request' // Version 1.1/2.0.
                        ]
                    )
                ]);
            }
        );
        // Note: processors depending on runtime values (ltiversion) must be resolved elsewhere.
        $hook->add_definition(
            id: parameter_processor_registry::class,
            definition: function ($c): parameter_processor_registry {
                return new parameter_processor_registry([
                    jwt_claim_converter::class => $c->get(jwt_claim_converter::class),
                    exclude_user_params_policy::class => $c->get(exclude_user_params_policy::class),
                    lis_bo_policy::class => $c->get(lis_bo_policy::class),
                    pii_policy::class => $c->get(pii_policy::class),
                    context_resolver::class => $c->get(context_resolver::class),
                    ext_resolver::class => $c->get(ext_resolver::class),
                    launch_presentation_resolver::class => $c->get(launch_presentation_resolver::class),
                    lis_bo_resolver::class => $c->get(lis_bo_resolver::class),
                    lis_resolver::class => $c->get(lis_resolver::class),
                    ltixservice_resolver::class => $c->get(ltixservice_resolver::class),
                    tool_consumer_resolver::class => $c->get(tool_consumer_resolver::class),
                    tool_custom_resolver::class => $c->get(tool_custom_resolver::class),
                    user_resolver::class => $c->get(user_resolver::class),
                    resource_link_launch_custom_resolver::class => $c->get(resource_link_launch_custom_resolver::class),
                ]);
            }
        );
    }
}
