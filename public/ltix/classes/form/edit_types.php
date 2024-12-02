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

namespace core_ltix\form;

use moodleform;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

/**
 * LTI Edit Form
 *
 * @package    core_ltix
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_types extends moodleform {

    /**
     * Define this form.
     */
    public function definition() {
        global $CFG, $DB, $OUTPUT;

        $mform    =& $this->_form;

        $istool = $this->_customdata && isset($this->_customdata->istool) && $this->_customdata->istool;
        $typeid = $this->_customdata->id ?? '';
        $clientid = $this->_customdata->clientid ?? '';

        // Add basiclti elements.
        $mform->addElement('header', 'setup', get_string('tool_settings', 'core_ltix'));

        $mform->addElement('text', 'lti_typename', get_string('typename', 'core_ltix'));
        $mform->setType('lti_typename', PARAM_TEXT);
        $mform->addHelpButton('lti_typename', 'typename', 'core_ltix');
        $mform->addRule('lti_typename', null, 'required', null, 'client');

        $mform->addElement('text', 'lti_toolurl', get_string('toolurl', 'core_ltix'), array('size' => '64'));
        $mform->setType('lti_toolurl', PARAM_URL);
        $mform->addHelpButton('lti_toolurl', 'toolurl', 'core_ltix');

        $mform->addElement('textarea', 'lti_description', get_string('tooldescription', 'core_ltix'),
            array('rows' => 4, 'cols' => 60));
        $mform->setType('lti_description', PARAM_TEXT);
        $mform->addHelpButton('lti_description', 'tooldescription', 'core_ltix');
        if (!$istool) {
            $mform->addRule('lti_toolurl', null, 'required', null, 'client');
        } else {
            $mform->disabledIf('lti_toolurl', null);
        }

        if (!$istool) {
            $options = array(
                \core_ltix\constants::LTI_VERSION_1 => get_string('oauthsecurity', 'core_ltix'),
                \core_ltix\constants::LTI_VERSION_1P3 => get_string('jwtsecurity', 'core_ltix'),
            );
            $mform->addElement('select', 'lti_ltiversion', get_string('ltiversion', 'core_ltix'), $options);
            $mform->setType('lti_ltiversion', PARAM_TEXT);
            $mform->addHelpButton('lti_ltiversion', 'ltiversion', 'core_ltix');
            $mform->setDefault('lti_ltiversion', \core_ltix\constants::LTI_VERSION_1);

            $mform->addElement('text', 'lti_resourcekey', get_string('resourcekey_admin', 'core_ltix'));
            $mform->setType('lti_resourcekey', PARAM_TEXT);
            $mform->addHelpButton('lti_resourcekey', 'resourcekey_admin', 'core_ltix');
            $mform->hideIf('lti_resourcekey', 'lti_ltiversion', 'eq', \core_ltix\constants::LTI_VERSION_1P3);
            $mform->setForceLtr('lti_resourcekey');

            $mform->addElement('passwordunmask', 'lti_password', get_string('password_admin', 'core_ltix'));
            $mform->setType('lti_password', PARAM_RAW);
            $mform->addHelpButton('lti_password', 'password_admin', 'core_ltix');
            $mform->hideIf('lti_password', 'lti_ltiversion', 'eq', \core_ltix\constants::LTI_VERSION_1P3);

            if (!empty($typeid)) {
                $mform->addElement('text', 'lti_clientid_disabled', get_string('clientidadmin', 'core_ltix'));
                $mform->setType('lti_clientid_disabled', PARAM_TEXT);
                $mform->addHelpButton('lti_clientid_disabled', 'clientidadmin', 'core_ltix');
                $mform->hideIf('lti_clientid_disabled', 'lti_ltiversion', 'neq', \core_ltix\constants::LTI_VERSION_1P3);
                $mform->disabledIf('lti_clientid_disabled', null);
                $mform->setForceLtr('lti_clientid_disabled');
                $mform->addElement('hidden', 'lti_clientid');
                $mform->setType('lti_clientid', PARAM_TEXT);
            }

            $keyoptions = [
                \core_ltix\constants::LTI_RSA_KEY => get_string('keytype_rsa', 'core_ltix'),
                \core_ltix\constants::LTI_JWK_KEYSET => get_string('keytype_keyset', 'core_ltix'),
            ];
            $mform->addElement('select', 'lti_keytype', get_string('keytype', 'core_ltix'), $keyoptions);
            $mform->setType('lti_keytype', PARAM_TEXT);
            $mform->addHelpButton('lti_keytype', 'keytype', 'core_ltix');
            $mform->setDefault('lti_keytype', \core_ltix\constants::LTI_JWK_KEYSET);
            $mform->hideIf('lti_keytype', 'lti_ltiversion', 'neq', \core_ltix\constants::LTI_VERSION_1P3);

            $mform->addElement('textarea', 'lti_publickey', get_string('publickey', 'core_ltix'), ['rows' => 8, 'cols' => 60]);
            $mform->setType('lti_publickey', PARAM_TEXT);
            $mform->addHelpButton('lti_publickey', 'publickey', 'core_ltix');
            $mform->hideIf('lti_publickey', 'lti_keytype', 'neq', \core_ltix\constants::LTI_RSA_KEY);
            $mform->hideIf('lti_publickey', 'lti_ltiversion', 'neq', \core_ltix\constants::LTI_VERSION_1P3);
            $mform->setForceLtr('lti_publickey');

            $mform->addElement('text', 'lti_publickeyset', get_string('publickeyset', 'core_ltix'), ['size' => '64']);
            $mform->setType('lti_publickeyset', PARAM_TEXT);
            $mform->addHelpButton('lti_publickeyset', 'publickeyset', 'core_ltix');
            $mform->hideIf('lti_publickeyset', 'lti_keytype', 'neq', \core_ltix\constants::LTI_JWK_KEYSET);
            $mform->hideIf('lti_publickeyset', 'lti_ltiversion', 'neq', \core_ltix\constants::LTI_VERSION_1P3);
            $mform->setForceLtr('lti_publickeyset');

            $mform->addElement('text', 'lti_initiatelogin', get_string('initiatelogin', 'core_ltix'), array('size' => '64'));
            $mform->setType('lti_initiatelogin', PARAM_URL);
            $mform->addHelpButton('lti_initiatelogin', 'initiatelogin', 'core_ltix');
            $mform->hideIf('lti_initiatelogin', 'lti_ltiversion', 'neq', \core_ltix\constants::LTI_VERSION_1P3);

            $mform->addElement('textarea', 'lti_redirectionuris', get_string('redirectionuris', 'core_ltix'),
                array('rows' => 3, 'cols' => 60));
            $mform->setType('lti_redirectionuris', PARAM_TEXT);
            $mform->addHelpButton('lti_redirectionuris', 'redirectionuris', 'core_ltix');
            $mform->hideIf('lti_redirectionuris', 'lti_ltiversion', 'neq', \core_ltix\constants::LTI_VERSION_1P3);
            $mform->setForceLtr('lti_redirectionuris');
        }

        if ($istool) {
            $mform->addElement('textarea', 'lti_parameters', get_string('parameter', 'core_ltix'),
                array('rows' => 4, 'cols' => 60));
            $mform->setType('lti_parameters', PARAM_TEXT);
            $mform->addHelpButton('lti_parameters', 'parameter', 'core_ltix');
            $mform->disabledIf('lti_parameters', null);
            $mform->setForceLtr('lti_parameters');
        }

        $mform->addElement('textarea', 'lti_customparameters', get_string('custom', 'core_ltix'), array('rows' => 4, 'cols' => 60));
        $mform->setType('lti_customparameters', PARAM_TEXT);
        $mform->addHelpButton('lti_customparameters', 'custom', 'core_ltix');
        $mform->setForceLtr('lti_customparameters');

        if (!empty($this->_customdata->isadmin)) {
            // Only site-level preconfigured tools allow the control of course visibility in the site admin tool type form.
            if (empty($this->_customdata->iscoursetool) || !$this->_customdata->iscoursetool) {
                $options = array(
                    \core_ltix\constants::LTI_COURSEVISIBLE_NO => get_string('show_in_course_no', 'core_ltix'),
                    \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED => get_string('show_in_course_preconfigured', 'core_ltix'),
                    \core_ltix\constants::LTI_COURSEVISIBLE_ACTIVITYCHOOSER => get_string('show_in_course_activity_chooser', 'core_ltix'),
                );
                if ($istool) {
                    // LTI2 tools can not be matched by URL, they have to be either in preconfigured tools or in activity chooser.
                    unset($options[\core_ltix\constants::LTI_COURSEVISIBLE_NO]);
                    $stringname = 'show_in_course_lti2';
                } else {
                    $stringname = 'show_in_course_lti1';
                }
                $mform->addElement('select', 'lti_coursevisible', get_string($stringname, 'core_ltix'), $options);
                $mform->addHelpButton('lti_coursevisible', $stringname, 'core_ltix');
                $mform->setDefault('lti_coursevisible', '1');
            }
        } else {
            $mform->addElement('hidden', 'lti_coursevisible', \core_ltix\constants::LTI_COURSEVISIBLE_ACTIVITYCHOOSER);
        }
        $mform->setType('lti_coursevisible', PARAM_INT);

        $mform->addElement('hidden', 'typeid');
        $mform->setType('typeid', PARAM_INT);

        $launchoptions = array();
        $launchoptions[\core_ltix\constants::LTI_LAUNCH_CONTAINER_EMBED] = get_string('embed', 'core_ltix');
        $launchoptions[\core_ltix\constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS] = get_string('embed_no_blocks', 'core_ltix');
        $launchoptions[\core_ltix\constants::LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW] = get_string('existing_window', 'core_ltix');
        $launchoptions[\core_ltix\constants::LTI_LAUNCH_CONTAINER_WINDOW] = get_string('new_window', 'core_ltix');

        $mform->addElement('select', 'lti_launchcontainer', get_string('default_launch_container', 'core_ltix'), $launchoptions);
        $mform->setDefault('lti_launchcontainer', \core_ltix\constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS);
        $mform->addHelpButton('lti_launchcontainer', 'default_launch_container', 'core_ltix');
        $mform->setType('lti_launchcontainer', PARAM_INT);

        $mform->addElement('advcheckbox', 'lti_contentitem', get_string('contentitem_deeplinking', 'core_ltix'));
        $mform->addHelpButton('lti_contentitem', 'contentitem_deeplinking', 'core_ltix');
        if ($istool) {
            $mform->disabledIf('lti_contentitem', null);
        }

        $mform->addElement('text', 'lti_toolurl_ContentItemSelectionRequest',
            get_string('toolurl_contentitemselectionrequest', 'core_ltix'), array('size' => '64'));
        $mform->setType('lti_toolurl_ContentItemSelectionRequest', PARAM_URL);
        $mform->addHelpButton('lti_toolurl_ContentItemSelectionRequest', 'toolurl_contentitemselectionrequest', 'core_ltix');
        $mform->disabledIf('lti_toolurl_ContentItemSelectionRequest', 'lti_contentitem', 'notchecked');
        if ($istool) {
            $mform->disabledIf('lti_toolurl__ContentItemSelectionRequest', null);
        }

        $mform->addElement('hidden', 'oldicon');
        $mform->setType('oldicon', PARAM_URL);

        $mform->addElement('text', 'lti_icon', get_string('icon_url', 'core_ltix'), array('size' => '64'));
        $mform->setType('lti_icon', PARAM_URL);
        $mform->setAdvanced('lti_icon');
        $mform->addHelpButton('lti_icon', 'icon_url', 'core_ltix');

        $mform->addElement('text', 'lti_secureicon', get_string('secure_icon_url', 'core_ltix'), array('size' => '64'));
        $mform->setType('lti_secureicon', PARAM_URL);
        $mform->setAdvanced('lti_secureicon');
        $mform->addHelpButton('lti_secureicon', 'secure_icon_url', 'core_ltix');

        // Restrict to course categories.
        if (empty($this->_customdata->iscoursetool) || !$this->_customdata->iscoursetool) {
            $mform->addElement('header', 'coursecategory', get_string('restricttocategory', 'core_ltix'));
            $mform->addHelpButton('coursecategory', 'restricttocategory', 'core_ltix');
            $records = $DB->get_records('course_categories', [], 'sortorder, id', 'id,parent,name');
            // Convert array of objects to two dimentional array.
            $tree = $this->lti_build_category_tree(array_map(fn($record) => (array)$record, $records));
            $mform->addElement('html', $OUTPUT->render_from_template('core_ltix/categorynode', ['nodes' => $tree]));
            $mform->addElement('hidden', 'lti_coursecategories');
            $mform->setType('lti_coursecategories', PARAM_TEXT);
        }

        if (!$istool) {
            // Display the lti advantage services.
            $this->get_lti_advantage_services($mform);
        }

        if (!$istool) {
            // Add privacy preferences fieldset where users choose whether to send their data.
            $mform->addElement('header', 'privacy', get_string('privacy', 'core_ltix'));

            $options = array();
            $options[0] = get_string('never', 'core_ltix');
            $options[1] = get_string('always', 'core_ltix');

            $mform->addElement('select', 'lti_sendname', get_string('share_name_admin', 'core_ltix'), $options);
            $mform->setType('lti_sendname', PARAM_INT);
            $mform->setDefault('lti_sendname', '0');
            $mform->addHelpButton('lti_sendname', 'share_name_admin', 'core_ltix');

            $mform->addElement('select', 'lti_sendemailaddr', get_string('share_email_admin', 'core_ltix'), $options);
            $mform->setType('lti_sendemailaddr', PARAM_INT);
            $mform->setDefault('lti_sendemailaddr', '0');
            $mform->addHelpButton('lti_sendemailaddr', 'share_email_admin', 'core_ltix');

            // LTI Extensions.

            // Add grading preferences fieldset where the tool is allowed to return grades.
            $gradeoptions = array();
            $gradeoptions[] = get_string('never', 'core_ltix');
            $gradeoptions[] = get_string('always', 'core_ltix');
            $gradeoptions[] = get_string('delegate_tool', 'core_ltix');

            $mform->addElement('select', 'lti_acceptgrades', get_string('accept_grades_admin', 'core_ltix'), $gradeoptions);
            $mform->setType('lti_acceptgrades', PARAM_INT);
            $mform->setDefault('lti_acceptgrades', '2');
            $mform->addHelpButton('lti_acceptgrades', 'accept_grades_admin', 'core_ltix');

            $mform->addElement('checkbox', 'lti_forcessl', get_string('force_ssl', 'core_ltix'));
            $mform->setType('lti_forcessl', PARAM_BOOL);
            if (!empty($CFG->ltix_forcessl)) {
                $mform->setDefault('lti_forcessl', '1');
                $mform->freeze('lti_forcessl');
            } else if(!empty($CFG->mod_lti_forcessl)) {
                debugging('mod_lti_forcessl is deprecated. Please use ltix_forcessl instead.', DEBUG_DEVELOPER);
                $mform->setDefault('lti_forcessl', '1');
                $mform->freeze('lti_forcessl');
            } else {
                $mform->setDefault('lti_forcessl', '0');
            }
            $mform->addHelpButton('lti_forcessl', 'force_ssl', 'core_ltix');

            if (!empty($this->_customdata->isadmin)) {
                // Add setup parameters fieldset.
                $mform->addElement('header', 'setupoptions', get_string('miscellaneous', 'core_ltix'));

                $options = array(
                    \core_ltix\constants::LTI_DEFAULT_ORGID_SITEID => get_string('siteid', 'core_ltix'),
                    \core_ltix\constants::LTI_DEFAULT_ORGID_SITEHOST => get_string('sitehost', 'core_ltix'),
                );

                // TODO: all these lang strings need to come to core_ltix + check whether we can deprecate them in mod_lti.
                $mform->addElement('select', 'lti_organizationid_default', get_string('organizationid_default', 'core_ltix'),
                    $options);
                $mform->setType('lti_organizationid_default', PARAM_TEXT);
                $mform->setDefault('lti_organizationid_default', \core_ltix\constants::LTI_DEFAULT_ORGID_SITEID);
                $mform->addHelpButton('lti_organizationid_default', 'organizationid_default', 'core_ltix');

                $mform->addElement('text', 'lti_organizationid', get_string('organizationidguid', 'core_ltix'));
                $mform->setType('lti_organizationid', PARAM_TEXT);
                $mform->addHelpButton('lti_organizationid', 'organizationidguid', 'core_ltix');

                $mform->addElement('text', 'lti_organizationurl', get_string('organizationurl', 'core_ltix'));
                $mform->setType('lti_organizationurl', PARAM_URL);
                $mform->addHelpButton('lti_organizationurl', 'organizationurl', 'core_ltix');
            }
        }

        /* Suppress this for now - Chuck
         * mform->addElement('text', 'lti_organizationdescr', get_string('organizationdescr', 'core_ltix'))
         * mform->setType('lti_organizationdescr', PARAM_TEXT)
         * mform->addHelpButton('lti_organizationdescr', 'organizationdescr', 'core_ltix')
         */

        /*
        // Add a hidden element to signal a tool fixing operation after a problematic backup - restore process
        //$mform->addElement('hidden', 'lti_fix');
        */

        $tab = optional_param('tab', '', PARAM_ALPHAEXT);
        $mform->addElement('hidden', 'tab', $tab);
        $mform->setType('tab', PARAM_ALPHAEXT);

        $courseid = optional_param('course', 1, PARAM_INT);
        $mform->addElement('hidden', 'course', $courseid);
        $mform->setType('course', PARAM_INT);

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

    }

    /**
     * Retrieves the data of the submitted form.
     *
     * @return stdClass
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data && !empty($this->_customdata->istool)) {
            // Content item checkbox is disabled in tool settings, so this cannot be edited. Just unset it.
            unset($data->lti_contentitem);
        }
        return $data;
    }

    /**
     * Generates the lti advantage extra configuration adding it to the mform
     *
     * @param MoodleQuickForm $mform
     */
    public function get_lti_advantage_services(&$mform) {
        // For each service add the label and get the array of configuration.
        $services = \core_ltix\helper::get_services();
        $mform->addElement('header', 'services', get_string('services', 'core_ltix'));
        foreach ($services as $service) {
            /** @var \core_ltix\local\ltiservice\service_base $service */
            $service->get_configuration_options($mform);
        }
    }

    /**
     * Validate the form data before we allow them to save the tool type.
     *
     * @param array $data
     * @param array $files
     * @return array Error messages
     */
    public function validation($data, $files) {
        global $CFG;

        $errors = parent::validation($data, $files);

        // LTI2 tools do not contain a ltiversion field.
        if (isset($data['lti_ltiversion']) && $data['lti_ltiversion'] == \core_ltix\constants::LTI_VERSION_1P3) {
            $warning = \core_ltix\oauth_helper::verify_private_key();
            if (!empty($warning)) {
                $errors['lti_ltiversion'] = $warning;
                return $errors;
            }
        }
        return $errors;
    }

    /**
     * Build category tree.
     *
     * @param array $elements
     * @param int $parentid
     * @return array category tree
     */
    public function lti_build_category_tree(array $elements, int $parentid = 0): array {
        $branch = [];

        foreach ($elements as $element) {
            if ($element['parent'] == $parentid) {
                $children = $this->lti_build_category_tree($elements, $element['id']);
                if ($children) {
                    $element['nodes'] = $children;
                    $element['haschildren'] = true;
                } else {
                    $element['nodes'] = null;
                    $element['haschildren'] = false;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    public function definition_after_data() {
        // Add the deprecated "Delegate to teacher" option to the "Share launcher's name" and "Share launcher's email" fields for
        // existing types which are already using this setting value. This ensures that editing the tool type won't result in a
        // change to the existing value. Add the option as a disabled to make this clear. Once changed, it cannot be set again.
        // This isn't supported from 4.3 onward in the creation of new tool types.
        foreach (['lti_sendname', 'lti_sendemailaddr'] as $elementname) {
            if (!empty($this->_form->_defaultValues[$elementname])
                    && $this->_form->_defaultValues[$elementname] == \core_ltix\constants::LTI_SETTING_DELEGATE) {

                $elementarr = array_filter($this->_form->_elements, function ($element) use($elementname) {
                    return !empty($element->_attributes['name']) && $element->_attributes['name'] == $elementname;
                });
                /** @var MoodleQuickForm_select $element */
                $element = array_shift($elementarr);

                $element->addOption(
                    get_string('delegate', 'core_ltix'),
                    \core_ltix\constants::LTI_SETTING_DELEGATE,
                    ['disabled' => 'disabled', 'selected' => 'selected']
                );
            }
        }
    }
}
