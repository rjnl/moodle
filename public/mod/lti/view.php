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
//
// This file is part of BasicLTI4Moodle
//
// BasicLTI4Moodle is an IMS BasicLTI (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. BasicLTI is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS BasicLTI
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support BasicLTI. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// BasicLTI4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// SimpleLTI consumer for Moodle is an implementation of the early specification of LTI
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// BasicLTI4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu.

/**
 * This file contains all necessary code to view a lti activity instance
 *
 * @package mod_lti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/mod/lti/lib.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$l  = optional_param('l', 0, PARAM_INT);  // lti ID.
$action = optional_param('action', '', PARAM_TEXT);
$foruserid = optional_param('user', 0, PARAM_INT);
$forceview = optional_param('forceview', 0, PARAM_BOOL);

if ($l) {  // Two ways to specify the module.
    $lti = $DB->get_record('lti', array('id' => $l), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('lti', $lti->id, $lti->course, false, MUST_EXIST);
} else {
    $cm = get_coursemodule_from_id('lti', $id, 0, false, MUST_EXIST);
    $lti = $DB->get_record('lti', array('id' => $cm->instance), '*', MUST_EXIST);
}
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);


// TODO: view currently handles a number of things:
//  - all presentation modes (embed, new window, etc).
//  - new window launches (will force you to launch in a new window if you hit view.php directly)
//  - domain matching tools (for restored instances which are not tied directly to tools any more)
//  - manually configured instances (legacy) where no typeid present.
//  - crudely handles submission review concerns
//  Now that we have resource_links:
//  - fetch the link, see if it's mapped to a type. If so, launch.
//  - Else, we still have domain matching to consider, since bnr can leave orphaned links.
//  - manually configured instances (MCI) _should_ be covered by the above 2 points; it still comes down to launching a link, however,
//    for a manually configured instance, there IS no tool (that data is on the lti record), so we do need code to handle that case.

// TODO In terms of launch container stuff:
//  - we should defer to the tool, or the MCI.
//  - should the iframe be generated herein? that might be a later concern. perhaps there's a use case for sending back the iframe
//   html from core_ltix if callers want to embed, letting callers control the rest of their page and just dumping the iframe into
//  it. Likewise, if it's a new window launch, we probably want core_ltix to give us back a link to launch it.
//  So, some basic understanding is required by the client code to juggle these...How can THAT be improved?
//  - existing window: replaces everything in current window: redirect to core_ltix launch.
//  - new window: should open a tab: either open a new window, or present a link to new window.
//  - embed x2: sets pagelayout in current page + embeds - the issue here is current page....
//  Maybe we can provide a page-centric launch helper in core_ltix, as well as a link-centric one (if you want to get a link).
//  e.g. if you're ON a page, or in client code somewhere, you likely need the same kind of handling as mod_lti does.
//  \core_ltix\launch_presentation_helper::page_launch($link);
//  This would consolidate that pagelayout code into core_ltix, and could return HTML for the case where we're expecting a new window
//  launch which cannot be done in-page. Clients could just go:
//  $launchhtml = \core_ltix\launch_helper::launch_link($link); // This might redirect for existing window launch.
//  if ($launchhtml) {
//      // Put the launch html somewhere in the client page.
//      // it'll either return iframe HTML for the client to output or HTML supporting a fallback for "open in new window".
//      // Client code will ideally control the new window case with a link to launch the thing elsewhere...
//  }
//  Take course nav as another little example of the problem:
//  at render time (in the course navigation callback), the client code will ideally KNOW the launch type supported by each link,
//  so that it can create links the right way to launch the resource. Either:
//  - a link with target="_blank" set to use core_ltix launch endpoint in new window.
//  - a link to core_ltix launch endpoint, which will just launch in the existing window when followed.
//  - a link to some intermediary page which WOULD support embedding (the above 2 don't), but this page needs a context + defaults.
//  This is an example where we very likely want to let core_ltix handle everything and just need the relevant link back.

$typeid = $lti->typeid;
if (empty($typeid) && ($tool = \core_ltix\helper::get_tool_by_url_match($lti->toolurl))) {
    $typeid = $tool->id;
}
if ($typeid) {
    $toolconfig = \core_ltix\helper::get_type_config($typeid);
    $missingtooltype = empty($toolconfig);
    if (!$missingtooltype) {
        $toolurl = $toolconfig['toolurl'];
    }
} else {
    $toolconfig = array();
    $toolurl = $lti->toolurl;
}

$PAGE->set_cm($cm, $course); // Set's up global $COURSE.
$context = context_module::instance($cm->id);
$PAGE->set_context($context);

require_login($course, true, $cm);
require_capability('mod/lti:view', $context);

if (!empty($foruserid) && (int)$foruserid !== (int)$USER->id) {
    require_capability('gradereport/grader:view', $context);
}

$url = new moodle_url('/mod/lti/view.php', array('id' => $cm->id));
$PAGE->set_url($url);


if (!empty($missingtooltype)) {
    $PAGE->set_pagelayout('incourse');
    echo $OUTPUT->header();
    throw new moodle_exception('tooltypenotfounderror', 'mod_lti');
}

$launchcontainer = \core_ltix\helper::get_launch_container($lti, $toolconfig);

if ($launchcontainer == \core_ltix\constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS) {
    $PAGE->set_pagelayout('incourse');
    $PAGE->blocks->show_only_fake_blocks(); // Disable blocks for layouts which do include pre-post blocks.
} else if ($launchcontainer == \core_ltix\constants::LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW) {
    if (!$forceview) {
        $url = new moodle_url('/mod/lti/launch.php', array('id' => $cm->id));
        redirect($url);
    }
} else { // Handles LTI_LAUNCH_CONTAINER_DEFAULT, LTI_LAUNCH_CONTAINER_EMBED, LTI_LAUNCH_CONTAINER_WINDOW.
    $PAGE->set_pagelayout('incourse');
}

lti_view($lti, $course, $cm, $context);

$pagetitle = strip_tags($course->shortname.': '.format_string($lti->name));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

$activityheader = $PAGE->activityheader;
if (!$lti->showtitlelaunch) {
    $header['title'] = '';
}
if (!$lti->showdescriptionlaunch) {
    $header['description'] = '';
}
$activityheader->set_attrs($header ?? []);

// Print the page header.
echo $OUTPUT->header();

if ($typeid) {
    $config = \core_ltix\helper::get_type_type_config($typeid);
} else {
    $config = new stdClass();
    $config->lti_ltiversion = \core_ltix\constants::LTI_VERSION_1;
}
//$launchurl = new moodle_url('/mod/lti/launch.php', ['id' => $cm->id, 'triggerview' => 0]);

// TODO: Here, we'll need fetch the tool's (and in future, maybe link's) launch presentation and use that.


// TODO: delegate the launch to ltix/launch.php.
$link = \core_ltix\local\lticore\models\resource_link::get_record([
    'component' => 'mod_lti',
    'itemtype' => 'mod_lti:activityplacement',
    'itemid' => $cm->id,
    'contextid' => $context->id
]);
// TODO: throw error if there is no link...really shouldn't happen.
$launchurl = new moodle_url('/ltix/launch.php', ['id' => $link->get('id')]);


if ($action) {
    $launchurl->param('action', $action);
}
if ($foruserid) {
    $launchurl->param('user', $foruserid);
}
unset($SESSION->lti_initiatelogin_status);
if (($launchcontainer == \core_ltix\constants::LTI_LAUNCH_CONTAINER_WINDOW)) {
    if (!$forceview) {
        echo "<script language=\"javascript\">//<![CDATA[\n";
        echo "window.open('{$launchurl->out(true)}','lti-$cm->id');";
        echo "//]]\n";
        echo "</script>\n";
        echo "<p>".get_string("basiclti_in_new_window", "lti")."</p>\n";
    }
    echo html_writer::start_tag('p');
    echo html_writer::link($launchurl->out(false), get_string("basiclti_in_new_window_open", "lti"), array('target' => '_blank'));
    echo html_writer::end_tag('p');
} else {
    $content = '';
    // Build the allowed URL, since we know what it will be from $lti->toolurl,
    // If the specified toolurl is invalid the iframe won't load, but we still want to avoid parse related errors here.
    // So we set an empty default allowed url, and only build a real one if the parse is successful.
    $ltiallow = '';
    $urlparts = parse_url($toolurl);
    if ($urlparts && array_key_exists('scheme', $urlparts) && array_key_exists('host', $urlparts)) {
        $ltiallow = $urlparts['scheme'] . '://' . $urlparts['host'];
        // If a port has been specified we append that too.
        if (array_key_exists('port', $urlparts)) {
            $ltiallow .= ':' . $urlparts['port'];
        }
    }

    // Request the launch content with an iframe tag.
    $attributes = [];
    $attributes['id'] = "contentframe";
    $attributes['height'] = '600px';
    $attributes['width'] = '100%';
    $attributes['src'] = $launchurl;
    $attributes['allow'] = "microphone $ltiallow; " .
        "camera $ltiallow; " .
        "geolocation $ltiallow; " .
        "midi $ltiallow; " .
        "encrypted-media $ltiallow; " .
        "autoplay $ltiallow";
    $attributes['allowfullscreen'] = 1;
    $iframehtml = html_writer::tag('iframe', $content, $attributes);
    echo $iframehtml;


    // Output script to make the iframe tag be as large as possible.
    $resize = '
        <script type="text/javascript">
        //<![CDATA[
            YUI().use("node", "event", function(Y) {
                var doc = Y.one("body");
                var frame = Y.one("#contentframe");
                var padding = 15; //The bottom of the iframe wasn\'t visible on some themes. Probably because of border widths, etc.
                var lastHeight;
                var resize = function(e) {
                    var viewportHeight = doc.get("winHeight");
                    if(lastHeight !== Math.min(doc.get("docHeight"), viewportHeight)){
                        frame.setStyle("height", viewportHeight - frame.getY() - padding + "px");
                        lastHeight = Math.min(doc.get("docHeight"), doc.get("winHeight"));
                    }
                };

                resize();

                Y.on("windowresize", resize);
            });
        //]]
        </script>
';

    echo $resize;
}

// Finish the page.
echo $OUTPUT->footer();
