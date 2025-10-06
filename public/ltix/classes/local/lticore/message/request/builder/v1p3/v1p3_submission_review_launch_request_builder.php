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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace core_ltix\local\lticore\message\request\builder\v1p3;

use core_ltix\local\lticore\models\resource_link;

/**
 * Handles creation of the init login request for an LtiSubmissionReviewRequest message type launch.
 *
 * @package    core_ltix
 * @copyright  2025 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class v1p3_submission_review_launch_request_builder extends v1p3_resource_link_launch_request_builder {
    /**
     * Constructor.
     *
     * @param \stdClass $toolconfig the tool configuration data, must be sourced from \core_ltix\helper::get_type_type_config().
     * @param resource_link $resourcelink the link to be launched.
     * @param string $issuer the issuer URL.
     * @param int $userid the id of the user performing the launch.
     * @param \stdClass $foruser the user who made the submission.
     * @param string $returnurl the url to return to.
     * @param array $roles the LIS or extension roles the launching user has for this launch.
     * @param array $extraclaims any optional extra claims.
     * @param string $messagetype the message type for this request.
     */
    public function __construct(
        protected \stdClass $toolconfig,
        protected resource_link $resourcelink,
        string $issuer,
        int $userid,
        protected \stdClass $foruser,
        protected string $returnurl,
        array $roles = [],
        array $extraclaims = [],
        string $messagetype = 'LtiSubmissionReviewRequest', // Should we use \Packback\Lti1p3\LtiConstants::MESSAGE_TYPE_SUBMISSIONREVIEW ???
    ) {
        // TODO: this WILL be called in the subreview launch builder. Just a note to remember to check that.
        // Once that's there, this can all be deleted - it's not needed at all in RLL launches.
        // During a resource link launch, we don't need this permit services to override the target. It's always the link/tool URL.
        // $targetlinkuri = $servicefacade->get_target_link_uri(); // Allows services to override the target.

        // Required claims trump extra claims.
        $claims = array_merge($extraclaims, $this->create_required_request_claims());

        parent::__construct(
            toolconfig: $toolconfig,
            resourcelink: $resourcelink,
            messagetype: $messagetype,
            issuer: $issuer,
            userid: $userid,
            // targetlinkuri: $this->resolve_target_link_uri(),
            // loginhint: strval($userid),
            roles: $roles,
            extraclaims: $claims
        );
    }

    /**
     * Adds required claims for this message type.
     *
     * @return array the array of claims.
     */
    protected function create_required_request_claims(): array {
        $claimprefix = \core_ltix\constants::LTI_JWT_CLAIM_PREFIX;

        // $foruser = \core_user::get_user($this->for_userid);
        $foruser = $this->foruser;

        return [
            // TODO: This is only needed if line item is associated with a resource link.
            $claimprefix . '/claim/resource_link' => [
                'id' => $this->resourcelink->get('id'),
                'title' => $this->resourcelink->get('title'),
                ...(!is_null($this->resourcelink->get('text')) ? ['description' => $this->resourcelink->get('text')] : []),
            ],
            $claimprefix . '/claim/target_link_uri' => $this->resolve_target_link_uri(),
            $claimprefix . '/claim/for_user' => [
                'user_id' => $foruser->id, // $this->for_userid,
                'person_sourcedid' => $foruser->idnumber, // TODO: Is this correct?
                'given_name' => $foruser->firstname,
                'family_name' => $foruser->lastname,
                'email' => $foruser->email,
                'roles' => ["http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"], // TODO: use get_launch_roles() ??
            ],
            $claimprefix . '/claim/launch_presentation' => [
                'locale' => current_language(),
                'document_target' => 'iframe', // TODO: should this be configurable?
                'return_url' => $this->returnurl,
            ],
            // The https://purl.imsglobal.org/spec/lti-ags/claim/endpoint claim is required.
            // As it identifies for which line item the LtiSubmissionReviewRequest message is made,
            // it must contain the lineitem property referring to the line item from where the launch originates.

            // "https://purl.imsglobal.org/spec/lti-ags/claim/endpoint": {
            //     "scope": [
            //         "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
            //         "https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly",
            //         "https://purl.imsglobal.org/spec/lti-ags/scope/score"
            //     ],
            //     "lineitems": "https://www.myuniv.edu/2344/lineitems/",
            //     "lineitem": "https://www.myuniv.edu/2344/lineitems/1234/lineitem"
            // },
            'https://purl.imsglobal.org/spec/lti-ags/claim/endpoint' => [ // TODO: make const ??
                'scope' => [
                    'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem',
                    'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly',
                    'https://purl.imsglobal.org/spec/lti-ags/scope/score',
                ],
                // 'lineitems' => '', // TODO: what's the correct URL?
                // 'lineitem' => '', // TODO: what's the correct URL?
            ],
        ];
    }

    /**
     * Resolve the target link URI via either the link, or the tool, in that order.
     *
     * @return string the target link URI.
     */
    protected function resolve_target_link_uri(): string {
        return $this->resourcelink->get('url') ?: $this->toolconfig->lti_toolurl;
    }
}
