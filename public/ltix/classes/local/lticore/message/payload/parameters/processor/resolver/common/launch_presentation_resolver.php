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

namespace core_ltix\local\lticore\message\payload\parameters\processor\resolver\common;

use core_ltix\constants;
use core_ltix\helper;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\course_context;
use core_ltix\local\lticore\message\context\item\resource_link_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_processor;

/**
 * Resolves launch presentation parameters.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class launch_presentation_resolver implements parameters_processor {

    public function process(array $parameters, launch_context $data): array {

        $toolconfig = $data->require(tool_context::class)->toolconfig;
        $resourcelink = $data->require(resource_link_context::class)->resourcelink;
        $course = $data->require(course_context::class)->course;

        $launchcontainer = helper::get_launch_container(
            (object) ['launchcontainer' => $resourcelink->get('launchcontainer')],
            (array) $toolconfig->config // Coerce into expected array format.
        );
        $target = '';
        switch($launchcontainer) {
            case constants::LTI_LAUNCH_CONTAINER_EMBED:
            case constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS:
                $target = 'iframe';
                break;
            case constants::LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW:
                $target = 'frame';
                break;
            case constants::LTI_LAUNCH_CONTAINER_WINDOW:
                $target = 'window';
                break;
        }

        // Add the return URL. We send the launch container along to help us avoid frames-within-frames when the user returns.
        // Note: launch_presentation_return_url is only set for course-context-related launches presently,
        // given the return endpoint (ltix/return.php) is a legacy endpoint and only works in that situation.
        // The 'instanceid' param, which the endpoint supports as an optional param, is deliberately omitted here, since the
        // endpoint expects that to match a legacy 'lti' record (being a legacy endpoint).
        // It's not a required part of the return flow and can be safely omitted.
        $returnurlparams = [
            'course' => $course->id,
            'launch_container' => $launchcontainer,
            'sesskey' => sesskey()
        ];
        $url = new \moodle_url('/ltix/return.php', $returnurlparams);
        $returnurl = $url->out(false);

        if (isset($toolconfig->config->forcessl) && ($toolconfig->config->forcessl == '1')) {
            $returnurl = helper::ensure_url_is_https($returnurl);
        }


        return array_merge(
            $parameters,
            [
                'launch_presentation_locale' => current_language(),
                'launch_presentation_document_target' => $target,
                'launch_presentation_return_url' => $returnurl
            ]
        );
    }
}
