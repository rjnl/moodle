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

use core_ltix\local\placement\placement_service;

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

// TODO: MDL-88222: implement support for legacy, manually-configure instances.
//  These are NOT supported by core_ltix and are supported only through mod_lti.
//  Perhaps the launch controllers can still be used if mod_lti sets a specific concrete tool repository using DI?
//  To do that, we'd roughly need:
//  - extract an interface out of registration_repository (mod_lti implements this).
//  - inside the repository implementation in mod_lti, for a given link , return the config stored in the associated lti instance
//  table.
//  This way, core_ltix launch just treats it as a normal link launch, while mod_lti maintains the legacy support locally.

// TODO: MDL-87963 this must be replaced by a core_ltix API call. Data persistence classes should be internal to core_ltix ONLY.
//  Note also that the resource_link_manager class is supposed to serve this need, but its method ::get_by_itemid() doesn't capture
//  enough data to uniquely identify a link.
$link = \core_ltix\local\lticore\models\resource_link::get_record([
    'component' => 'mod_lti',
    'itemtype' => 'mod_lti:activityplacement',
    'itemid' => $cm->id,
    'contextid' => $context->id
], MUST_EXIST);

// TODO: MDL-87963: for now, placement_service resolves the tool URL for the link since the link is just a persistent and doesn't
//  contain any inherited tool values. If/when link becomes a domain object, tool URL and launch container can be resolved there,
//  and this client code should be updated to call $link->launch_container() or the like.
// TODO: MDL-88220: tool URL is only used presently to construct the iframe HTML, which will eventually move into core_ltix as
//  well, at which point get_tool_url_for_link() will no longer be needed as a public API.
// TODO: Also, the DB is hit several times unnecessarily using the code below, which is also a smell.
$launchcontainer = placement_service::get_launch_container_for_link($link);
$toolurl = placement_service::get_tool_url_for_link($link);

if ($launchcontainer == \core_ltix\constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS) {
    $PAGE->set_pagelayout('incourse');
    $PAGE->blocks->show_only_fake_blocks(); // Disable blocks for layouts which do include pre-post blocks.
} else if ($launchcontainer == \core_ltix\constants::LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW) {
    if (!$forceview) {
        $launchurl = placement_service::get_launch_url_for_link($link);
        redirect($launchurl);
    }
} else { // Handles LTI_LAUNCH_CONTAINER_DEFAULT, LTI_LAUNCH_CONTAINER_EMBED, LTI_LAUNCH_CONTAINER_WINDOW.
    $PAGE->set_pagelayout('incourse');
}
$launchurl = placement_service::get_launch_url_for_link($link);

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

if ($action) {
    $launchurl->param('action', $action);
}
if ($foruserid) {
    $launchurl->param('user', $foruserid);
}
unset($SESSION->lti_initiatelogin_status);

// TODO: MDL-88220: create the relevant launch-container-specific API methods, allowing placements to launch their links.
//  E.g. for LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW, placement service could have a method like launch_link_in_own_window($link)
//  which would handle the redirect and exit, allowing client code to just call that and then exit immediately after without needing
//  to worry about the details of how to do that properly and securely. For embedded launches, maybe a method like
//  get_launch_html_for_link($link) which would return the appropriate iframe HTML for the link, and client code would just echo
//  that. This would encapsulate all the launch-container-specific logic in the service layer, and keep it out of the client code.
//  And for new window launches, to handle the case where you wanted to provide a link instead of an automatic popup, maybe a method
//  like get_launch_link_for_link($link) which would return the appropriate URL and target for the link, and client code would just
//  use that to build the link however they wanted (e.g. a normal link, a button, etc).
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
    $urlparts = parse_url($toolurl->out(false));
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
        "autoplay $ltiallow;";

    // Only allow local-network-access if it is enabled in config.php.
    if (isset($CFG->ltiallowlocalnetwork) && $CFG->ltiallowlocalnetwork) {
        $attributes['allow'] .= " local-network-access $ltiallow;";
    }

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
