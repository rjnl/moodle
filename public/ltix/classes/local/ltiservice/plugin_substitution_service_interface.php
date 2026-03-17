<?php

namespace core_ltix\local\ltiservice;

use core_ltix\local\lticore\message\context\collection\launch_context;

interface plugin_substitution_service_interface
{
    /**
     * Perform custom parameter substitution for all service plugins.
     *
     * @param launch_context $launchcontext launch context vars
     * @param string $paramstr the string containing the variable to substitute.
     * @return string the substituted value
     */
    public function substitute(launch_context $launchcontext, string $paramstr): string;
}
