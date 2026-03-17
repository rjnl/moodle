<?php

namespace core_ltix\local\lticore\message\payload\parameters\processor\resolver\common;

use core_ltix\helper;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\resource_link_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\context\item\user_context;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_processor;

/**
 * Resolves lis basic outcomes parameters.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lis_bo_resolver implements parameters_processor {

    public function process(array $parameters, launch_context $data): array {
        global $CFG;

        $resourcelink = $data->require(resource_link_context::class)->resourcelink;
        $toolconfig = $data->require(tool_context::class)->toolconfig;
        $user = $data->require(user_context::class)->user;

        if (!empty($resourcelink->get('servicesalt'))) {
            $sourcedid = json_encode(
                helper::build_sourcedid(
                    $resourcelink->get('id'),
                    $user->id,
                    $resourcelink->get('servicesalt'),
                    $toolconfig->tool->id
                )
            );
            $parameters['lis_result_sourcedid'] = $sourcedid;

            $serviceurl = new \moodle_url('/ltix/service.php');
            $serviceurl = $serviceurl->out();

            $forcessl = false;
            if (!empty($CFG->ltix_forcessl)) {
                $forcessl = true;
            } else if (!empty($CFG->mod_lti_forcessl)) {
                // TODO: final removal of mod_lti_forcessl in Moodle 6.0.
                debugging('mod_lti_forcessl is deprecated. Please use ltix_forcessl instead.', DEBUG_DEVELOPER);
                $forcessl = true;
            }

            if ((isset($toolconfig->config->forcessl) && ($toolconfig->config->forcessl == '1')) or $forcessl) {
                $serviceurl = helper::ensure_url_is_https($serviceurl);
            }
            $parameters['lis_outcome_service_url'] = $serviceurl;
        }

        return $parameters;
    }
}
