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

namespace core_ltix\local\lticore\message\payload\parameters\processor\policy;

use core_ltix\constants;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\resource_link_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_processor;
use core_ltix\local\lticore\models\resource_link;

/**
 * Policy processor enforcing conditional inclusion of the LIS Basic Outcomes parameters.
 *
 * This policy excludes these claims when the link is not gradable.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class lis_bo_policy implements parameters_processor {

    /**
     * List of LIS Basic Outcomes keys to exclude.
     */
    private const array LIS_BO_KEYS = [
        'lis_result_sourcedid',
        'lis_outcome_service_url',
    ];

    public function process(array $parameters, launch_context $data): array {

        $toolconfig = $data->require(tool_context::class)->toolconfig;
        /** @var resource_link $link */
        $link = $data->require(resource_link_context::class)->resourcelink;

        if (in_array($toolconfig->tool->ltiversion, [lti_version::LTI_VERSION_1->value, lti_version::LTI_VERSION_1P3->value])) {
            // v1px only sets if gradepost is enabled.
            $gradingallowed = !empty($link->get('servicesalt')) && (
                    $toolconfig->config->acceptgrades == constants::LTI_SETTING_ALWAYS ||
                    ($toolconfig->config->acceptgrades == constants::LTI_SETTING_DELEGATE && $link->is_gradable())
                );

            if (!$gradingallowed) {
                foreach (self::LIS_BO_KEYS as $key) {
                    unset($parameters[$key]);
                }
            }
        }

        return $parameters;
    }
}
