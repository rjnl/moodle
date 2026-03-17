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

namespace core_ltix\local\lticore\message\launchrequest\builder\v2p0;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\lti_message;
use core_ltix\local\lticore\models\resource_link;

/**
 * Class handling the creation of resource link launch request messages for LTI 2p0.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class v2p0_resource_link_launch_request_builder extends v2p0_launch_request_builder {

    /**
     * Build the link launch message.
     *
     * @param \stdClass $toolconfig the tool configuration data.
     * @param resource_link $resourcelink the link to launch.
     * @param string $userid the id of the user performing the launch.
     * @param array $roles the LIS or extension roles the launching user has for this launch.
     * @param array $extraparams any extra params which should be included in the launch.
     * @return lti_message the built launch message.
     */
    public function build_message(
        \stdClass $toolconfig,
        resource_link $resourcelink,
        string $userid = '',
        array $roles = [],
        array $extraparams = []
    ): lti_message {
        // Tool capabilities control parameter inclusion, including the required resource_link_id (which is controlled by the
        // capability 'ResourceLink.id'). Should the capability prevent it's inclusion, the launch message cannot be built.
        $allparams = array_merge($extraparams, $this->create_required_request_params($resourcelink));
        $filteredparams = $this->enforce_tool_capabilities($toolconfig, $allparams);
        if (!isset($filteredparams['resource_link_id'])) {
            throw new lti_exception('ResourceLink.id capability is required for v2p0 resource link launch requests');
        }

        return $this->build(
            toolconfig: $toolconfig,
            messagetype: 'basic-lti-launch-request',
            launchurl: $this->resolve_launch_url($toolconfig, $resourcelink),
            userid: $userid,
            roles: $roles,
            // Required params take precedence over extra params.
            extraparams: $allparams
        );
    }

    /**
     * Resolve the launch URL, via either the link, or the tool, in that order.
     *
     * @param \stdClass $toolconfig
     * @param resource_link $resourcelink
     * @return string the URL to launch.
     */
    protected function resolve_launch_url(\stdClass $toolconfig, resource_link $resourcelink): string {
        return $resourcelink->get('url') ?: $toolconfig->tool->baseurl;
    }

    /**
     * Adds required params for this message type.
     *
     * @param resource_link $resourcelink
     * @return array the array of claims.
     */
    protected function create_required_request_params(resource_link $resourcelink): array {
        // Format the description and convert to plain text.
        $description = $resourcelink->get('text');
        if (!is_null($description)) {
            $formatteddescription = format_text($description, $resourcelink->get('textformat'));
            $textdescription = trim(html_to_text($formatteddescription, 0, false));
            // This may look weird, but this is required for new lines
            // so we generate the same OAuth signature as the tool provider.
            $textdescription = str_replace("\n", "\r\n", $textdescription);
        }

        return [
            'resource_link_id' => strval($resourcelink->get('id')),
            'resource_link_title' => $resourcelink->get('title'),
            ...(isset($textdescription) ? ['resource_link_description' => $textdescription] : []),
        ];
    }

}
