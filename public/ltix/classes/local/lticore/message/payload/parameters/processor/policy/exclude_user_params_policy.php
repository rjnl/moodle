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

use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_processor;

/**
 * Enforces exclusion of user parameters in v1p3 pipelines, as these parameters will be added at OIDC auth time.
 *
 * Only operates on v1p3 parameter pipelines.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class exclude_user_params_policy implements parameters_processor {

    /**
     * Those params containing user data.
     */
    private const array USER_PARAMS = [
        'user_id',
        'lis_person_name_full',
        'lis_person_name_given',
        'lis_person_name_family',
        'lis_person_contact_email_primary',
    ];

    public function process(array $parameters, launch_context $data): array {
        $toolconfig = $data->require(tool_context::class)->toolconfig;

        if (in_array($toolconfig->tool->ltiversion, [lti_version::LTI_VERSION_1P3->value])) {
            foreach (self::USER_PARAMS as $param) {
                unset($parameters[$param]);
            }
        }

        return $parameters;
    }
}
