<?php

namespace core_ltix\local\lticore\message\payload\parameters\processor\resolver\custom;

use core_ltix\helper;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\resource_link_context;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_processor;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\tool_custom_resolver;

/**
 * Resolves resource link launch custom parameters.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resource_link_launch_custom_resolver extends tool_custom_resolver implements parameters_processor {

    public function process(array $parameters, launch_context $data): array {
        // Get the tool-config-based custom params.
        $parameters = parent::process($parameters, $data);

        $resourcelink = $data->require(resource_link_context::class)->resourcelink;

        $linkcustomstr = !empty($resourcelink->get('customparams')) ? $resourcelink->get('customparams') : '';
        $parsedlinkcustom = [];
        if ($linkcustomstr) {
            $linkcustom = helper::split_parameters($linkcustomstr);
            foreach ($linkcustom as $key => $val) {
                $parsedlinkcustom['custom_' . $key] = $val;
            }
        }

        // Tool level custom params override link-level custom params in cases where the same key is found.
        return array_merge($parsedlinkcustom, $parameters);
    }
}
