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

namespace core_ltix\local\lticore\facades\service;

use core_ltix\local\lticore\token\context;
use core_ltix\local\lticore\models\resource_link;
use core_ltix\local\ltiservice\service_base;

/**
 * Facade for dealing with service plugins during a submission review launch.
 *
 * Simplifies querying the 'service' and 'source' plugins for various things.
 *
 * @package    core_ltix
 * @copyright  2025 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_review_launch_service_facade implements launch_service_facade_interface {
    /** @var string The LTI message type for this launch. */
    protected string $messagetype;

    /**
     * Constructor.
     *
     * @param \stdClass $toolconfig the tool configuration data.
     * @param \core\context $context the context object.
     * @param int $userid the id of the user performing the launch.
     * @param resource_link $resourcelink the link to be launched.
     */
    public function __construct(
        protected \stdClass $toolconfig,
        protected \core\context $context,
        protected int $userid,
        protected resource_link $resourcelink,
    ) {
        $this->messagetype = 'LtiSubmissionReviewRequest'; // TODO: make const, but also, don't services expect the 1p1 message type?
    }

    /**
     * Get the target link URI for the launch, allowing services to override it.
     *
     * @return string the target link URI.
     */
    public function get_target_link_uri(): string {
        // Call into each of the services, allowing them a chance to change the target_link_uri of the launch.
        $linkurl = $this->resourcelink->get('url');
        $targetlinkuri = $linkurl ?: $this->toolconfig->toolurl;

        /** @var service_base $service */
        foreach (\core_ltix\helper::get_services() as $service) {
            $targetlinkuri = $service->override_target_link_uri(
                toolconfig: $this->toolconfig,
                messagetype: $this->messagetype,
                targetlinkuri: $targetlinkuri,
                context: $this->context,
                userid: $this->userid,
                resourcelink: $this->resourcelink,
            );
        }
        return $targetlinkuri;
    }

    /**
     * Get the launch parameters for the launch.
     *
     * @return array the launch parameters.
     */
    public function get_launch_parameters(): array {
        $params = [];
        /** @var service_base $service */
        foreach (\core_ltix\helper::get_services() as $service) {
            $params = $service->get_launch_params(
                toolconfig: $this->toolconfig,
                messagetype: $this->messagetype,
                targetlinkuri: $this->resourcelink->get('url'),
                context: $this->context,
                userid: $this->userid,
                resourcelink: $this->resourcelink,
            );
        }
        return $params;
    }

    /**
     * Parse a custom parameter value.
     *
     * @param string $value the custom parameter value to parse.
     * @return string the parsed custom parameter value.
     */
    public function parse_custom_param_value(string $value): string {
        $val = $value;
        foreach (\core_ltix\helper::get_services() as $service) {
            $value = $service->parse_value($val);
            if ($val != $value) {
                break;
            }
        }
        return $value;
    }
}
