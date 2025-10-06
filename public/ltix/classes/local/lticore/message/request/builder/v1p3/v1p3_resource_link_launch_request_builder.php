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

namespace core_ltix\local\lticore\message\request\builder\v1p3;

use core_ltix\local\lticore\models\resource_link;

/**
 * Handles creation of the init login request for an LtiResourceLinkRequest message type launch.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class v1p3_resource_link_launch_request_builder extends v1p3_launch_request_builder {

    /**
     * Constructor.
     *
     * @param \stdClass $toolconfig the tool configuration data, must be sourced from \core_ltix\helper::get_type_type_config().
     * @param resource_link $resourcelink the link to be launched.
     * @param string $issuer the issuer URL.
     * @param int $userid the id of the user performing the launch.
     * @param array $roles the LIS or extension roles the launching user has for this launch.
     * @param array $extraclaims any optional extra claims.
     * @param string $messagetype the message type for this request.
     */
    public function __construct(
        protected \stdClass $toolconfig,
        protected resource_link $resourcelink,
        string $issuer,
        int $userid,
        array $roles = [],
        array $extraclaims = [],
        string $messagetype = 'LtiResourceLinkRequest',
    ) {
        // TODO: this WILL be called in the subreview launch builder. Just a note to remember to check that.
        //  Once that's there, this can all be deleted - it's not needed at all in RLL launches.
        // During a resource link launch, we don't need this permit services to override the target. It's always the link/tool URL.
        //$targetlinkuri = $servicefacade->get_target_link_uri(); // Allows services to override the target.

        // Required claims trump extra claims.
        $claims = array_merge($extraclaims, $this->create_required_request_claims());

        parent::__construct(
            toolconfig: $toolconfig,
            messagetype: $messagetype,
            issuer: $issuer,
            targetlinkuri: $this->resolve_target_link_uri(),
            loginhint: strval($userid),
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
        return [
            $claimprefix.'/claim/resource_link' => [
                'id' => $this->resourcelink->get('id'),
                'title' => $this->resourcelink->get('title'),
                ...(!is_null($this->resourcelink->get('text')) ? ['description' => $this->resourcelink->get('text')] : []),
            ],
            $claimprefix.'/claim/target_link_uri' => $this->resolve_target_link_uri(),
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
