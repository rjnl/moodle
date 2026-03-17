<?php

namespace core_ltix\local\lticore\message\payload\parameters\processor\resolver\common;

use core_ltix\helper;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_processor;

/**
 * Resolves tool custom parameters.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_custom_resolver implements parameters_processor {

    public function process(array $parameters, launch_context $data): array {

        $toolconfig = $data->require(tool_context::class)->toolconfig;
        $toolcustomstr = !empty($toolconfig->config->customparameters) ? $toolconfig->config->customparameters : '';
        if ($toolcustomstr) {
            $parsedtoolcustom = [];
            $toolcustom = helper::split_parameters($toolcustomstr);
            foreach ($toolcustom as $key => $val) {
                $parsedtoolcustom['custom_'.$key] = $val;
            }
            $parameters = array_merge($parameters, $parsedtoolcustom);
        }

        return $parameters;
    }
}
