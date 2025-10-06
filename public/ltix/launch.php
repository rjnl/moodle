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

/**
 * Handles resource link launches and their derivatives (e.g. submission review).
 *
 * This page is typically used in one of two ways:
 * 1. For embedded launches: This will be the src of an iframe to be placed in client code.
 * 2. For new window launches: This will be the src for target="_blank" link in client code.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_ltix;

use core\context;
use core\exception\moodle_exception;
use core_ltix\local\lticore\message\request\builder\builder_factory;
use core_ltix\local\lticore\models\resource_link;
use moodle_url;

require_once('../config.php');
global $PAGE, $OUTPUT, $USER;
require_login(null, false);

$linkid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);
$foruserid = optional_param('user', 0, PARAM_INT); // For Submission review launches.

// TODO: how are we going to launch legacy, manually configured instances...
//  these may need to continue to be launched via mod_lti's launch, where the specific 'grab tool config from instance' logic can
//  still be used, and a shim config object passed into the launch request builders. We can't use this endpoint, since that assumes
//  config exists as tool-level config only (i.e. doesn't support legacy per-instance config at all).

$link = new resource_link($linkid);
$context = context::instance_by_id($link->get('contextid'));
$toolconfig = helper::get_tool_config_for_link($link);
if (is_null($toolconfig)) {
    $PAGE->set_url(new moodle_url('/ltix/launch.php'));
    $PAGE->set_context($context);
    $PAGE->set_pagelayout('incourse');
    echo $OUTPUT->header();
    throw new moodle_exception('errortooltypenotfound', 'core_ltix');
}

// TODO: once fully tested, need to deprecate the following methods (for 1p3 and 1p1/2p0, respectively):
//  \core_ltix\helper::initiate_login(..);
//  \core_ltix\helper::launch_tool($lti, $foruserid);
//  others...


// TODO: consider access control. currently, ltix doesn't have a "can use/launch a tool" capability.
//  Currently, this endpoint uses: launch.php?id=1
//  But what's to stop a user launching something they cannot see or do not have access to by guessing the URL.
//  Sure, the link (e.g. mod/lti/view) won't be present in the course, but they could figure out the URL and hit launch
//  directly.
//  E.g. where we previously had 'mod/lti:view', how are we now checking the equivalent?
//  placement handler?
//  Maybe like:
//  1. placement allows link create, but placement can also be then turned off/disabled. links should still be able to launch.
//   - placement type implementation (and handler) of course still exists
//  2. ask the placement handler what checks are needed to launch links. even if the placement is disabled, it can make a judgment.
//  3. if handler is registered and cannot be found, it'll error at the 'find handler' level, that's expected.
//  4. if no handler is found, then we're ok to launch.
//  The above seems to suggest we'd benefit from a 'launch tools' capability at the context. Otherwise, it's possible a placement
//  omits any cap checks and anyone from any context can hit launch.php?id=x and launch links from other courses.
//  That, or launch must also be controlled, for now, as a course-only action (despite the API being context centric).
//  This would then allow require_course_login() checks based on the course coming from the context (and error if one can't be found).
//  Later, we could expand launch to permit other contexts and require_capability() etc based on those (e.g. admin launches).

if ($action === 'gradeReport') {
    $messagetype = 'LtiSubmissionReviewRequest';

    $returnurlparams = [
        'course' => $context->get_course_context()->instanceid,
        // Using link's launch container. MDL-86149 will introduce placement_service::get_launch_container_for_link().
        'launch_container' => $link->get('launchcontainer'),
        // 'sesskey' => sesskey()
    ];

    // Add the return URL. We send the launch container along to help us avoid frames-within-frames when the user returns.
    $url = new \moodle_url('/ltix/return.php', $returnurlparams);
    $returnurl = $url->out(false);
}

$launchconfig = (object) [
    'toolconfig' => $toolconfig,
    'context' => $context,
    'user' => $USER,
    'resourcelink' => $link,
    // TODO: perhaps we can be explicit about message type here.
    //  That way, the factory won't have to infer that based on other information like resourcelink being present (which may be the
    //  case for several types of launches (e.g. resourcelink, submissionreview).
    'messagetype' => $messagetype ?? '',
    'foruserid' => $foruserid,
    'returnurl' => $returnurl ?? '',
];
$requestbuilderfactory = \core\di::get(builder_factory::class);
echo $requestbuilderfactory->get_request_builder($launchconfig)
    ->build_message()
    ->to_html_form();
exit();
