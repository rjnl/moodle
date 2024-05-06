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
 * Data generator class for core_ltix.
 *
 * @package    core_ltix
 * @category   test
 * @copyright  Copyright (c) 2012 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @author     Mark Nielsen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_ltix_generator extends testing_module_generator {

    /**
     * Create a tool proxy.
     *
     * @param array $config
     */
    public function create_tool_proxies(array $config) {
        if (!isset($config['capabilityoffered'])) {
            $config['capabilityoffered'] = '';
        }
        if (!isset($config['serviceoffered'])) {
            $config['serviceoffered'] = '';
        }
        \core_ltix\helper::add_tool_proxy((object) $config);
    }

    /**
     * Split type creation data into 'type' and 'config' components, based on input array key prefixes.
     *
     * The $data array contains both the type data and config data that will be passed to lti_add_type(). This must be split into
     * two params (type, config) based on the array key prefixes ({@see lti_add_type()} for how the two params are handled):
     * - NO prefix: denotes 'type' data.
     * - 'lti_' prefix: denotes 'config' data.
     * - 'ltiservice_' prefix: denotes 'config' data, specifically config for service plugins.
     *
     * @param array $data array of type and config data containing prefixed keys.
     * @return array containing separated objects for type and config data. E.g. ['type' = stdClass, 'config' => stdClass]
     */
    protected function get_type_and_config_from_data(array $data): array {
        // Grab any non-prefixed fields; these are the type fields. The rest is considered config.
        $type = array_filter(
            $data,
            fn($val, $key) => !str_contains($key, 'lti_') && !str_contains($key, 'ltiservice_')
                && !str_contains($key, 'ltixservice_'),
            ARRAY_FILTER_USE_BOTH
        );
        $config = array_diff_key($data, $type);

        return ['type' => (object) $type, 'config' => (object) $config];
    }

    /**
     * Create a tool type.
     *
     * @param array $data
     * @return int ID of created tool
     */
    public function create_tool_types(array $data): int {
        global $CFG;
        require_once($CFG->dirroot . '/ltix/constants.php');

        if (!isset($data['baseurl'])) {
            throw new coding_exception('Must specify baseurl when creating a LTI tool type.');
        }
        $data['baseurl'] = (new moodle_url($data['baseurl']))->out(false); // Permits relative URLs in behat features.

        // Sensible defaults permitting the tool type to be used in a launch.
        $data['lti_acceptgrades'] = $data['lti_acceptgrades'] ?? LTI_SETTING_ALWAYS;
        $data['lti_sendname'] = $data['lti_sendname'] ?? LTI_SETTING_ALWAYS;
        $data['lti_sendemailaddr'] = $data['lti_sendname'] ?? LTI_SETTING_ALWAYS;
        $data['lti_launchcontainer'] = $data['lti_launchcontainer'] ?? LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS;

        ['type' => $type, 'config' => $config] = $this->get_type_and_config_from_data($data);

        return \core_ltix\helper::add_type(type: $type, config: $config);
    }

    /**
     * Create a course tool type.
     *
     * @param array $type the type info.
     * @return int ID of created tool.
     * @throws coding_exception if any required fields are missing.
     */
    public function create_course_tool_types(array $type): int {
        global $SITE, $CFG;
        require_once($CFG->dirroot . '/ltix/constants.php');

        if (!isset($type['baseurl'])) {
            throw new coding_exception('Must specify baseurl when creating a course tool type.');
        }
        if (!isset($type['course']) || $type['course'] == $SITE->id) {
            throw new coding_exception('Must specify a non-site course when creating a course tool type.');
        }

        $type['baseurl'] = (new moodle_url($type['baseurl']))->out(false); // Permits relative URLs in behat features.
        $type['coursevisible'] = $type['coursevisible'] ?? LTI_COURSEVISIBLE_ACTIVITYCHOOSER;
        $type['state'] = LTI_TOOL_STATE_CONFIGURED; // The default for course tools.

        // Sensible defaults permitting the tool type to be used in a launch.
        $type['lti_acceptgrades'] = $type['lti_acceptgrades'] ?? LTI_SETTING_ALWAYS;
        $type['lti_sendname'] = $type['lti_sendname'] ?? LTI_SETTING_ALWAYS;
        $type['lti_sendemailaddr'] = $type['lti_sendemailaddr'] ?? LTI_SETTING_ALWAYS;
        $type['lti_coursevisible'] = $type['coursevisible'] ?? LTI_COURSEVISIBLE_ACTIVITYCHOOSER;
        $type['lti_launchcontainer'] = $type['lti_launchcontainer'] ?? LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS;

        // Required for cartridge processing support.
        $type['lti_toolurl'] = $type['baseurl'];
        $type['lti_description'] = $type['description'] ?? '';
        $type['lti_icon'] = $type['icon'] ?? '';
        $type['lti_secureicon'] = $type['secureicon'] ?? '';
        if (!empty($type['name'])) {
            $type['lti_typename'] = $type['name'];
        }

        ['type' => $type, 'config' => $config] = $this->get_type_and_config_from_data($type);

        \core_ltix\helper::load_type_if_cartridge($config);
        return \core_ltix\helper::add_type(type: $type, config: $config);
    }

}
