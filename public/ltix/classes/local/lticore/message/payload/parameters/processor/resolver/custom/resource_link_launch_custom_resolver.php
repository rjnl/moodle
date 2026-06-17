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

    #[\Override]
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
