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
 * This file contains the capabilities used by the lti module
 *
 * @package    mod_lti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis, marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$capabilities = array(

    // Whether the user can see the link to the external tool and follow it.
    'mod/lti:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    // Add an External tool activity to a course.
    'mod/lti:addinstance' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/course:manageactivities'
    ),

    // The ability to a preconfigured instance to the course.
    'mod/lti:addpreconfiguredinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'mod/lti:addinstance',
    ),

    // The ability to request the administrator to configure a particular
    // External tool globally.
    'mod/lti:requesttooladd' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    )
);
$deprecatedcapabilities = [
    // The ability to add a manual instance (i.e. not from a preconfigured tool) to the course.
    'mod/lti:addmanualinstance' => [
        'message' => 'Manual instance configuration is deprecated. Please create a course tool (moodle/ltix:addcoursetool) and ensure '.
            'users are able to add an instance of the course tool via the activity chooser (mod/lti:addpreconfiguredinstance).'
    ],
    'mod/lti:manage' => [
        'replacement' => 'moodle/ltix:manage',
        'message' => 'This capability has been replaced by an equivalent core capability as part of moving large parts of mod_lti'.
            ' to core.'
    ],
    'mod/lti:admin' => [
        'replacement' => 'moodle/ltix:admin',
        'message' => 'This capability has been replaced by an equivalent core capability as part of moving large parts of mod_lti'.
            ' to core.'
    ],
    'mod/lti:addcoursetool' => [
        'replacement' => 'moodle/ltix:addcoursetool',
        'message' => 'This capability has been replaced by an equivalent core capability as part of moving large parts of mod_lti'.
            ' to core.'
    ],
];
