<?php

namespace core_ltix\local\lticore\message\payload\parameters\processor\resolver\common;

use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_processor;
use core_ltix\local\ltiservice\plugin_parameters_service_interface;

/**
 * Resolves ltixservice parameters.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class ltixservice_resolver implements parameters_processor {

    /**
     * Constructor.
     *
     * @param plugin_parameters_service_interface $pluginparamsservice The service to retrieve plugin parameters from.
     */
    public function __construct(private plugin_parameters_service_interface $pluginparamsservice) {
    }

    public function process(array $parameters, launch_context $launchcontext): array {
        return array_merge(
            $parameters,
            $this->pluginparamsservice->get_launch_parameters($launchcontext)
        );
    }
}
