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

namespace core_ltix\local\lticore\message\launchrequest\builder\v1p3;

use core_ltix\constants;
use core_ltix\local\lticore\message\lti_message;
use core_ltix\local\lticore\models\resource_link;

/**
 * Handles creation of the init login request for an LtiResourceLinkRequest message type launch.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class v1p3_resource_link_launch_request_builder extends v1p3_launch_request_builder {

    /**
     * Build the message.
     *
     * @param \stdClass $toolconfig the tool configuration data.
     * @param resource_link $resourcelink the link to be launched.
     * @param int $userid the id of the user performing the launch.
     * @param array $roles the LIS or extension roles the launching user has for this launch.
     * @param array $extraclaims any optional extra claims.
     * @return lti_message
     */
    public function build_message(
        \stdClass $toolconfig,
        resource_link $resourcelink,
        int $userid,
        array $roles = [],
        array $extraclaims = [],
    ): lti_message {

        // Required claims trump extra claims.
        $claims = array_merge($extraclaims, $this->create_required_request_claims($toolconfig, $resourcelink));

        return $this->
        build(
            toolconfig: $toolconfig,
            messagetype: 'LtiResourceLinkRequest',
            issuer: $toolconfig->tool->issuer,
            targetlinkuri: $this->resolve_target_link_uri($toolconfig, $resourcelink),
            loginhint: strval($userid),
            roles: $roles,
            extraclaims: $claims
        );
    }

    /**
     * Adds required claims for this message type.
     *
     * @param \stdClass $toolconfig the tool configuration.
     * @param resource_link $resourcelink the resource link.
     * @return array the array of claims.
     */
    protected function create_required_request_claims(\stdClass $toolconfig, resource_link $resourcelink): array {
        // Format the description and convert to plain text.
        $description = $resourcelink->get('text');
        if (!is_null($description)) {
            $formatteddescription = format_text($description, $resourcelink->get('textformat'));
            $textdescription = trim(html_to_text($formatteddescription, 0, false));
            // This may look weird, but this is required for new lines
            // so we generate the same OAuth signature as the tool provider.
            $textdescription = str_replace("\n", "\r\n", $textdescription);
        }

        $claimprefix = constants::LTI_JWT_CLAIM_PREFIX;
        return [
            $claimprefix.'/claim/resource_link' => [
                'id' => $resourcelink->get('id'),
                'title' => $resourcelink->get('title'),
                ...(isset($textdescription) ? ['description' => $textdescription] : []),
            ],
            $claimprefix.'/claim/target_link_uri' => $this->resolve_target_link_uri($toolconfig, $resourcelink),
        ];
    }

    /**
     * Resolve the target link URI via either the link, or the tool, in that order.
     *
     * @param \stdClass $toolconfig the tool configuration.
     * @param resource_link $resourcelink the resource link.
     * @return string the target link URI.
     */
    protected function resolve_target_link_uri(\stdClass $toolconfig, resource_link $resourcelink): string {
        return $resourcelink->get('url') ?: $toolconfig->tool->baseurl;
    }
}
