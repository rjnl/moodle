<?php

namespace core_ltix\local\ltiservice;

use core_ltix\local\lticore\message\context\collection\launch_context;

/**
 * Interface for the plugin parameters service.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface plugin_parameters_service_interface {

    /**
     * Retrieves launch parameters for the given launch context.
     *
     * @param launch_context $launchcontext the launch context.
     * @return array the launch parameters.
     */
    public function get_launch_parameters(launch_context $launchcontext): array;
}
