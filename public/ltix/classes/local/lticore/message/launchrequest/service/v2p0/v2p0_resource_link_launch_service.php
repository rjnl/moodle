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

namespace core_ltix\local\lticore\message\launchrequest\service\v2p0;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\course_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\context\item\resource_link_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\context\item\user_context;
use core_ltix\local\lticore\message\launchrequest\builder\v2p0\v2p0_resource_link_launch_request_builder;
use core_ltix\local\lticore\message\launchrequest\role_mapper;
use core_ltix\local\lticore\message\launchrequest\service\datarepository\launch_data_repository;
use core_ltix\local\lticore\message\lti_message;
use core_ltix\local\lticore\message\payload\lis_vocab_converter;
use core_ltix\local\lticore\message\payload\parameters\pipeline\factory\parameters_builder_factory;
use core_ltix\local\lticore\message\type\message_type_factory;
use core_ltix\local\lticore\repository\tool_registration_repository;

/**
 * Application service which builds a v2p0 basic-lti-launch-request message.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class v2p0_resource_link_launch_service {

    /**
     * Ctor.
     *
     * @param parameters_builder_factory $pipelinefactory
     * @param v2p0_resource_link_launch_request_builder $requestbuilder
     * @param tool_registration_repository $registrationrepository
     * @param launch_data_repository $datarepository
     * @param role_mapper $ltirolemapper
     * @param lis_vocab_converter $lisvocabconverter
     * @param message_type_factory $messagetypefactory
     */
    public function __construct(
        private parameters_builder_factory $pipelinefactory,
        private v2p0_resource_link_launch_request_builder $requestbuilder,
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
        //  See detailed notes in v1p3_resource_link_launch_service::launch().

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

        $messagetype = $this->messagetypefactory->from_string('basic-lti-launch-request');
        $optionalparamspipeline = $this->pipelinefactory->create_for(lti_version::LTI_VERSION_2, $messagetype);
        $optionalparams = $optionalparamspipeline->build(
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
            // Note: Technically, v2p0 supports LIS v2 role definitions..but Moodle's LTI 2p0 provider doesn't.
            // Accordingly, v1 will be used (v2p0 is b/c, supporting v1 roles too).
            roles: $this->lisvocabconverter->to_v1_roles(
                $this->ltirolemapper->map_for(
                    $user->id,
                    $resourcelink->get('contextid')
                )
            ),
            extraparams: $optionalparams,
        );
    }
}
