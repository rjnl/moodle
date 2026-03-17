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

namespace core_ltix\local\lticore\message\launchrequest\service\v1p3;

use core\exception\moodle_exception;
use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\course_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\context\item\resource_link_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\context\item\user_context;
use core_ltix\local\lticore\message\launchrequest\builder\v1p3\v1p3_resource_link_launch_request_builder;
use core_ltix\local\lticore\message\launchrequest\role_mapper;
use core_ltix\local\lticore\message\launchrequest\service\datarepository\launch_data_repository;
use core_ltix\local\lticore\message\lti_message;
use core_ltix\local\lticore\message\payload\lis_vocab_converter;
use core_ltix\local\lticore\message\payload\parameters\pipeline\factory\parameters_builder_factory;
use core_ltix\local\lticore\message\type\message_type_factory;
use core_ltix\local\lticore\repository\tool_registration_repository;

/**
 * Application service which builds a v1p3 resource link request message.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class v1p3_resource_link_launch_service {

    /**
     * Ctor.
     *
     * @param parameters_builder_factory $pipelinefactory
     * @param v1p3_resource_link_launch_request_builder $requestbuilder
     * @param tool_registration_repository $registrationrepository
     * @param launch_data_repository $datarepository
     * @param role_mapper $ltirolemapper
     * @param lis_vocab_converter $lisvocabconverter
     * @param message_type_factory $messagetypefactory
     */
    public function __construct(
        private parameters_builder_factory $pipelinefactory,
        private v1p3_resource_link_launch_request_builder $requestbuilder,
        private tool_registration_repository $registrationrepository,
        private launch_data_repository $datarepository,
        private role_mapper $ltirolemapper,
        private lis_vocab_converter $lisvocabconverter,
        private message_type_factory $messagetypefactory,
    ) {
    }

    /**
     * Create the launch message.
     *
     * @param int $resourcelinkid the resource link id to launch.
     * @param \stdClass $user the auth'd Moodle user.
     * @return lti_message
     * @throws lti_exception if any of the requisite data is missing, preventing launch message creation.
     */
    public function launch(
        int $resourcelinkid,
        \stdClass $user,
    ): lti_message {


        // TODO: MDL-88221: Implement access control. currently, ltix doesn't have a "can use/launch a tool" capability.
        //  Currently, the route will include a link id, e.g. resourcelink/1/launch.
        //  But what's to stop a user launching something they cannot see or do not have access to by guessing the URL?
        //  Sure, the link (e.g. mod/lti/view) won't be present in the course, but they could figure out the URL and hit launch
        //  directly.
        //  E.g. where we previously had 'mod/lti:view', how are we now checking the equivalent?
        //  placement handler?
        //  Consider a way for placements to participate in launch permission checks, maybe:
        //  1. placement allows link create, but placement can also be then turned off/disabled. links should still be able to launch.
        //   - placement type implementation (and handler) of course still exists
        //  2. ask the placement handler what checks are needed to launch links. even if the placement is disabled, it can make a judgment.
        //  3. if handler is registered and cannot be found, it'll error at the 'find handler' level, that's expected.
        //  4. if no handler is found, then we're ok to launch.
        //  The above seems to suggest we'd benefit from a 'launch tools' capability at the context. Otherwise, it's possible a placement
        //  omits any cap checks and anyone from any context can hit launch.php?id=x and launch links from other courses.
        //  That, or launch must also be controlled, for now, as a course-only action (despite the API being context centric).
        //  This would then allow require_course_login() checks based on the course coming from the context (and error if one can't be found).

        $resourcelink = $this->datarepository->get_resource_link($resourcelinkid);
        if (is_null($resourcelink)) {
            throw new lti_exception('resource link does not exist');
        }
        $course = $this->datarepository->get_course($resourcelink);
        if (is_null($course)) {
            throw new lti_exception('resource links must reside under a course');
        }
        $toolconfig = $this->registrationrepository->get_by_id($resourcelink->get('typeid'));
        if (is_null($toolconfig)) {
            throw new lti_exception('errortooltypenotfound', 'core_ltix');
        }

        $messagetype = $this->messagetypefactory->from_string('LtiResourceLinkRequest');
        $optionalparamspipeline = $this->pipelinefactory->create_for(lti_version::LTI_VERSION_1P3, $messagetype);
        $optionalclaims = $optionalparamspipeline->build(
            launch_context::instance(
                new message_context($messagetype),
                new tool_context($toolconfig),
                new user_context($user),
                new course_context($course),
                new resource_link_context($resourcelink),
            )
        );

        return $this->requestbuilder->build_message(
            toolconfig: $toolconfig,
            resourcelink: $resourcelink,
            userid: $user->id,
            roles: $this->lisvocabconverter->to_v2_roles(
                $this->ltirolemapper->map_for(
                    $user->id,
                    $resourcelink->get('contextid')
                )
            ),
            extraclaims: $optionalclaims,
        );
    }
}
