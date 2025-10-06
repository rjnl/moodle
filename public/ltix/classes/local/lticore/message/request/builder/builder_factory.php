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

namespace core_ltix\local\lticore\message\request\builder;

use core\context;
use core_ltix\helper;
use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\facades\service\resource_link_launch_service_facade;
use core_ltix\local\lticore\facades\service\submission_review_launch_service_facade;
use core_ltix\local\lticore\message\payload\custom\custom_param_parser;
use core_ltix\local\lticore\message\payload\lis_vocab_converter;
use core_ltix\local\lticore\message\payload\lti_1px_payload_converter;
use core_ltix\local\lticore\message\payload\v1p1_resource_link_launch_payload_builder;
use core_ltix\local\lticore\message\payload\v1p3_resource_link_launch_payload_builder;
use core_ltix\local\lticore\message\payload\v1p3_submission_review_launch_payload_builder;
use core_ltix\local\lticore\message\request\builder\v1p1\v1p1_resource_link_launch_request_builder;
use core_ltix\local\lticore\message\request\builder\v1p3\v1p3_resource_link_launch_request_builder;
use core_ltix\local\lticore\message\request\builder\v1p3\v1p3_submission_review_launch_request_builder;

/**
 * Simple factory handling the creation of all LTI request builders.
 *
 * All message type builders for all LTI versions and message types need to be constructed here.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class builder_factory {
    /**
     * Get a builder instance based on launch config.
     *
     * @param object $launchconfig the launch configuration data.
     * @return lti_request_builder an instance of lti_request_builder scoped to a specific LTI version and message type.
     * @throws lti_exception if a builder cannot be resolved for the given launchconfig data.
     */
    public function get_request_builder(object $launchconfig): lti_request_builder {
        global $CFG;

        if ($launchconfig->toolconfig->lti_ltiversion === \core_ltix\constants::LTI_VERSION_1P3) {
            $issuer = $CFG->wwwroot;

            // Submission Review launches.
            if ($launchconfig->messagetype === 'LtiSubmissionReviewRequest') { // Should we use \Packback\Lti1p3\LtiConstants::MESSAGE_TYPE_SUBMISSIONREVIEW ?
                // Submission review launches may or may not have a resource link.
                // Following the same pattern as resource link launches for now.

                $servicefacade = new submission_review_launch_service_facade(
                    toolconfig: $launchconfig->toolconfig,
                    context: $launchconfig->context,
                    userid: $launchconfig->user->id,
                    resourcelink: $launchconfig->resourcelink
                );
                $customparamparser = new custom_param_parser(
                    sourcedatamap: helper::get_capabilities(),
                    servicefacade: $servicefacade,
                    context: $launchconfig->context,
                    user: $launchconfig->user
                );
                $claimconverter = new lti_1px_payload_converter(
                    lisvocabconverter: new lis_vocab_converter()
                );
                $extraclaims = (new v1p3_submission_review_launch_payload_builder(
                    toolconfig: $launchconfig->toolconfig,
                    resourcelink: $launchconfig->resourcelink,
                    user: $launchconfig->user,
                    servicefacade: $servicefacade,
                    customparamparser: $customparamparser,
                    claimconverter: $claimconverter,
                ))->get_claims();

                // TODO: course context is an assumption in this code...what happens if we can't get it?
                $coursecontext = context::instance_by_id($launchconfig->resourcelink->get('contextid'))->get_course_context();

                // TODO: I think it's actually better to use the resource link context and let that be the decider for the roles
                //  the role code can very likely call into the placement implementation at a later point to resolve specific
                //  context roles the placement knows about. Just blindly using course is too limited and not in line with the old
                //  code in helper::get_ims_role(), which DOES check at the mod context.

                return new v1p3_submission_review_launch_request_builder(
                    toolconfig: $launchconfig->toolconfig,
                    resourcelink: $launchconfig->resourcelink,
                    issuer: $issuer,
                    userid: $launchconfig->user->id,
                    foruser: \core_user::get_user($launchconfig->foruserid),
                    returnurl: $launchconfig->returnurl,
                    roles: \core_ltix\helper::get_lti_message_roles($launchconfig->user->id, $coursecontext),
                    extraclaims: $extraclaims
                );
            } else if (!empty($launchconfig->resourcelink)) { // Resource Link launches.
                $servicefacade = new resource_link_launch_service_facade(
                    toolconfig: $launchconfig->toolconfig,
                    context: $launchconfig->context,
                    userid: $launchconfig->user->id,
                    resourcelink: $launchconfig->resourcelink
                );
                $customparamparser = new custom_param_parser(
                    sourcedatamap: helper::get_capabilities(),
                    servicefacade: $servicefacade,
                    context: $launchconfig->context,
                    user: $launchconfig->user
                );
                $claimconverter = new lti_1px_payload_converter(
                    lisvocabconverter: new lis_vocab_converter()
                );
                $extraclaims = (new v1p3_resource_link_launch_payload_builder(
                    toolconfig: $launchconfig->toolconfig,
                    resourcelink: $launchconfig->resourcelink,
                    user: $launchconfig->user,
                    servicefacade: $servicefacade,
                    customparamparser: $customparamparser,
                    claimconverter: $claimconverter,
                ))->get_claims();

                // TODO: course context is an assumption in this code...what happens if we can't get it?
                $coursecontext = context::instance_by_id($launchconfig->resourcelink->get('contextid'))->get_course_context();

                // TODO: I think it's actually better to use the resource link context and let that be the decider for the roles
                //  the role code can very likely call into the placement implementation at a later point to resolve specific
                //  context roles the placement knows about. Just blindly using course is too limited and not in line with the old
                //  code in helper::get_ims_role(), which DOES check at the mod context.

                return new v1p3_resource_link_launch_request_builder(
                    toolconfig: $launchconfig->toolconfig,
                    resourcelink: $launchconfig->resourcelink,
                    issuer: $issuer,
                    userid: $launchconfig->user->id,
                    roles: \core_ltix\helper::get_lti_message_roles($launchconfig->user->id, $coursecontext),
                    extraclaims: $extraclaims
                );
            } else {
                // TODO: Need to add support above for other message types (e.g submission review, deep linking).
                throw new lti_exception('builder_factory error: cannot create request builder. Unknown message type');
            }
        } else if ($launchconfig->toolconfig->lti_ltiversion === \core_ltix\constants::LTI_VERSION_1) {
            // Resource Link launches.
            if (!empty($launchconfig->resourcelink)) {
                $servicefacade = new resource_link_launch_service_facade(
                    toolconfig: $launchconfig->toolconfig,
                    context: $launchconfig->context,
                    userid: $launchconfig->user->id,
                    resourcelink: $launchconfig->resourcelink
                );
                $customparamparser = new custom_param_parser(
                    sourcedatamap: helper::get_capabilities(),
                    servicefacade: $servicefacade,
                    context: $launchconfig->context,
                    user: $launchconfig->user
                );
                $extraparams = (new v1p1_resource_link_launch_payload_builder(
                    toolconfig: $launchconfig->toolconfig,
                    resourcelink: $launchconfig->resourcelink,
                    user: $launchconfig->user,
                    servicefacade: $servicefacade,
                    customparamparser: $customparamparser,
                ))->get_params();

                $builder = new v1p1_resource_link_launch_request_builder(
                    toolconfig: $launchconfig->toolconfig,
                    resourcelink: $launchconfig->resourcelink,
                    roles: helper::get_launch_roles($launchconfig->user->id, $launchconfig->context),
                    extraparams: $extraparams
                );
                return $builder;
            } else {
                // TODO: Need to add support above for other message types (e.g deep linking).
                throw new lti_exception('builder_factory error: cannot create request builder. Unknown message type');
            }
        } else if ($launchconfig->toolconfig->lti_ltiversion === \core_ltix\constants::LTI_VERSION_2) {
            // TODO: implement launch builder for LTI 2.0.
            //  Remember: convert the roles to v2 roles when using LTI 2.0.
            //  e.g.
            //  roles: $vc->to_v2_roles(helper::get_launch_roles($launchconfig->user->id, $launchconfig->context), true)
        } else {
            throw new lti_exception('builder_factory error: cannot create request builder for ltiversion '.$launchconfig->ltiversion);
        }
    }
}
