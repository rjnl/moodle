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
 * Adds LTI settings links to admin tree.
 *
 * @package   core_ltix
 * @copyright 2024 Godson Ahamba
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Tool configure page, sitting under 'ltix' category.
$toolconfigurepage = new admin_externalpage('ltixtoolconfigure', get_string('manage_external_tools', 'core_ltix'),
    new moodle_url('/ltix/toolconfigure.php'));
$ADMIN->add('ltix', $toolconfigurepage);

// Hidden proxies page.
$proxieslink = new admin_externalpage('ltixtoolproxies', get_string('manage_tool_proxies', 'core_ltix'),
    new moodle_url('/ltix/toolproxies.php'));
$proxieslink->hidden = true;
$ADMIN->add('ltix', $proxieslink);

// Now, add the old 'manage preconfigured tools' settings page but mark it hidden.
// It'll be linked to directly from ltix/toolconfigure.php.
$settings = new admin_settingpage('ltixsettings', new lang_string('manage_tools', 'core_ltix'), ['moodle/site:config'], true);
$ADMIN->add('ltix', $settings);

// Also include any ltixsource plugin settings under this same category.
foreach (core_plugin_manager::instance()->get_plugins_of_type('ltisource') as $plugin) {
    /** @var \core\plugininfo\ltixsource $plugin */
    $plugin->load_settings($ADMIN, 'ltix', $hassiteconfig);
}

if ($ADMIN->fulltree) {
    global $PAGE, $CFG, $USER;

    $configuredtoolshtml = '';
    $pendingtoolshtml = '';
    $rejectedtoolshtml = '';

    $active = get_string('active', 'core_ltix');
    $pending = get_string('pending', 'core_ltix');
    $rejected = get_string('rejected', 'core_ltix');

    // Gather strings used for labels in the inline JS.
    $PAGE->requires->strings_for_js(
        array(
            'typename',
            'baseurl',
            'action',
            'createdon'
        ),
        'core_ltix'
    );

    $types = \core_ltix\helper::filter_get_types(get_site()->id);

    $configuredtools = \core_ltix\helper::filter_tool_types($types, \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED);

    $configuredtoolshtml = \core_ltix\helper::get_tool_table($configuredtools, 'lti_configured');

    $pendingtools = \core_ltix\helper::filter_tool_types($types, \core_ltix\constants::LTI_TOOL_STATE_PENDING);

    $pendingtoolshtml = \core_ltix\helper::get_tool_table($pendingtools, 'lti_pending');

    $rejectedtools = \core_ltix\helper::filter_tool_types($types, \core_ltix\constants::LTI_TOOL_STATE_REJECTED);

    $rejectedtoolshtml = \core_ltix\helper::get_tool_table($rejectedtools, 'lti_rejected');

    $tab = optional_param('tab', '', PARAM_ALPHAEXT);
    $activeselected = '';
    $pendingselected = '';
    $rejectedselected = '';
    switch ($tab) {
        case 'lti_pending':
            $pendingselected = 'class="selected"';
            break;
        case 'lti_rejected':
            $rejectedselected = 'class="selected"';
            break;
        default:
            $activeselected = 'class="selected"';
            break;
    }
    $addtype = get_string('addtype', 'core_ltix');
    $config = get_string('manage_tool_proxies', 'core_ltix');

    $addtypeurl = "{$CFG->wwwroot}/ltix/typessettings.php?action=add&amp;sesskey={$USER->sesskey}";

    $template = <<< EOD
<div id="lti_tabs" class="yui-navset">
    <ul id="lti_tab_heading" class="yui-nav" style="display:none">
        <li {$activeselected}>
            <a href="#tab1">
                <em>$active</em>
            </a>
        </li>
        <li {$pendingselected}>
            <a href="#tab2">
                <em>$pending</em>
            </a>
        </li>
        <li {$rejectedselected}>
            <a href="#tab3">
                <em>$rejected</em>
            </a>
        </li>
    </ul>
    <div class="yui-content">
        <div>
            <div><a style="margin-top:.25em" href="{$addtypeurl}">{$addtype}</a></div>
            $configuredtoolshtml
        </div>
        <div>
            $pendingtoolshtml
        </div>
        <div>
            $rejectedtoolshtml
        </div>
    </div>
</div>

<script type="text/javascript">
//<![CDATA[
    YUI().use('yui2-tabview', 'yui2-datatable', function(Y) {
        //If javascript is disabled, they will just see the three tabs one after another
        var lti_tab_heading = document.getElementById('lti_tab_heading');
        lti_tab_heading.style.display = '';

        new Y.YUI2.widget.TabView('lti_tabs');

        var setupTools = function(id, sort){
            var lti_tools = Y.YUI2.util.Dom.get(id);

            if(lti_tools){
                var dataSource = new Y.YUI2.util.DataSource(lti_tools);

                var configuredColumns = [
                    {key:'name', label: M.util.get_string('typename', 'core_ltix'), sortable: true},
                    {key:'baseURL', label: M.util.get_string('baseurl', 'core_ltix'), sortable: true},
                    {key:'timecreated', label: M.util.get_string('createdon', 'core_ltix'), sortable: true},
                    {key:'action', label: M.util.get_string('action', 'core_ltix')}
                ];

                dataSource.responseType = Y.YUI2.util.DataSource.TYPE_HTMLTABLE;
                dataSource.responseSchema = {
                    fields: [
                        {key:'name'},
                        {key:'baseURL'},
                        {key:'timecreated'},
                        {key:'action'}
                    ]
                };

                new Y.YUI2.widget.DataTable(id + '_container', configuredColumns, dataSource,
                    {
                        sortedBy: sort
                    }
                );
            }
        };

        setupTools('lti_configured_tools', {key:'name', dir:'asc'});
        setupTools('lti_pending_tools', {key:'timecreated', dir:'desc'});
        setupTools('lti_rejected_tools', {key:'timecreated', dir:'desc'});
    });
//]]
</script>
EOD;
    $settings->add(new admin_setting_heading('lti_types', new lang_string('external_tool_types', 'core_ltix') .
        $OUTPUT->help_icon('main_admin', 'core_ltix'), $template));
}
