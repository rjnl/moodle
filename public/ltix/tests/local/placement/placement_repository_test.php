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

namespace core_ltix\local\placement;

use core_ltix\constants;

/**
 * Placements repository tests.
 *
 * @covers     \core_ltix\local\placement\placement_repository
 * @package    core_ltix
 * @copyright  2025 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class placement_repository_test extends \advanced_testcase {

    /**
     * Tests the method is_placement_enabled_for_tool_in_course().
     *
     * @param array $toolsettings The array containing tool related configuration settings.
     * @param array $placementsettings The array containing placement related configuration settings.
     * @param string $placementtypearg The placement type argument used when calling the method.
     * @param bool $expectedreturn The expected return value from the method call.
     * @param string|null $expectedexception The expected exception message, if applicable.
     * @return void
     * @dataProvider is_placement_enabled_for_tool_in_course_provider
     */
    public function test_is_placement_enabled_for_tool_in_course(array $toolsettings, array $placementsettings,
            string $placementtypearg, bool $expectedreturn, ?string $expectedexception = null): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $context = \core\context\course::instance($course->id);

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        // Create a tool.
        $toolid = $ltigenerator->create_tool_types([
            'name' => 'Example tool',
            'baseurl' => 'http://example.com/tool/1',
            'lti_coursevisible' => $toolsettings['coursevisible'],
            'state' => $toolsettings['state']
        ]);

        // Create a placement type.
        $placementtype = $ltigenerator->create_placement_type([
            'component' => 'core_ltix',
            'placementtype' => 'core_ltix:validplacement'
        ]);

        // Create a placement.
        $placement = $ltigenerator->create_tool_placements([
            'toolid' => $toolid,
            'placementtypeid' => $placementtype->id,
            'config_default_usage' => $placementsettings['default_usage'],
            'config_supports_deep_linking' => 0,
        ]);

        // Override the state of the placement in the course as defined in the data provider.
        if (!is_null($placementsettings['course_usage_override'])) {
            $ltigenerator->create_placement_status_in_context($placement->id, $placementsettings['course_usage_override'],
                $context->id);
        }

        // If an exception is expected, verify the exception.
        if ($expectedexception) {
            $this->expectExceptionMessage($expectedexception);
        }

        $result = placement_repository::is_placement_enabled_for_tool_in_course($placementtypearg, $toolid, $course->id);

        // Verify the return.
        $this->assertEquals($expectedreturn, $result);
    }

    /**
     * Data provider for test_is_placement_enabled_for_tool_in_course().
     *
     * @return array
     */
    public static function is_placement_enabled_for_tool_in_course_provider(): array {
        return [
            'Invalid placement type provided' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                ],
                [
                    'default_usage' => 'enabled',
                    'course_usage_override' => null,
                ],
                'core_ltix:invalidplacement',
                false,
                'Invalid placement type.',
            ],
            'Tool is not visible in course' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_NO,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                ],
                [
                    'default_usage' => 'enabled',
                    'course_usage_override' => null,
                ],
                'core_ltix:validplacement',
                false,
            ],
            'Tool is pending' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_PENDING,
                ],
                [
                    'default_usage' => 'enabled',
                    'course_usage_override' => null,
                ],
                'core_ltix:validplacement',
                false,
            ],
            'Default placement type state is set to "enabled", without a course override' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                ],
                [
                    'default_usage' => 'enabled',
                    'course_usage_override' => null,
                ],
                'core_ltix:validplacement',
                true,
            ],
            'Default placement type state is set to "disabled", without a course override' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                ],
                [
                    'default_usage' => 'disabled',
                    'course_usage_override' => null,
                ],
                'core_ltix:validplacement',
                false,
            ],
            'Default placement type state is set to "enabled", with a course override "enabled"' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                ],
                [
                    'default_usage' => 'enabled',
                    'course_usage_override' => placement_status::ENABLED,
                ],
                'core_ltix:validplacement',
                true,
            ],
            'Default placement type state is set to "enabled", with a course override "disabled"' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                ],
                [
                    'default_usage' => 'enabled',
                    'course_usage_override' => placement_status::DISABLED,
                ],
                'core_ltix:validplacement',
                false,
            ],
            'Default placement type state is set to "disabled", with a course override "enabled"' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                ],
                [
                    'default_usage' => 'disabled',
                    'course_usage_override' => placement_status::ENABLED,
                ],
                'core_ltix:validplacement',
                true,
            ],
            'Default placement type state is set to "disabled", with a course override "disabled"' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                ],
                [
                    'default_usage' => 'disabled',
                    'course_usage_override' => placement_status::DISABLED,
                ],
                'core_ltix:validplacement',
                false,
            ],
        ];
    }

    /**
     * Test getting a list of tools with an enabled placement in the course context.
     *
     * @param int $toolcoursevisible
     * @param int $toolstate
     * @param string $placementdefault
     * @param bool $placementoverride
     * @param int $expectedcount
     * @return void
     * @dataProvider get_placement_overrides_provider
     */
    public function test_get_tools_with_enabled_placement_in_course(
        $toolcoursevisible,
        $toolstate,
        $placementdefault,
        $placementoverride,
        $expectedcount,
    ): void {
        $this->resetAfterTest();

        /** @var \core_ltix_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        $course = $this->getDataGenerator()->create_course();
        $context = \core\context\course::instance($course->id);

        $toolid = $ltigenerator->create_tool_types([
            'name' => 'Example tool',
            'baseurl' => 'http://example.com/tool/1',
            'lti_coursevisible' => $toolcoursevisible,
            'state' => $toolstate
        ]);

        // Create a couple of placement types with associated config.
        $placementtype1 = $ltigenerator->create_placement_type(
            ['component' => 'core_ltix', 'placementtype' => 'core_ltix:myplacement']
        );
        $placementtype2 = $ltigenerator->create_placement_type(
            ['component' => 'core_ltix', 'placementtype' => 'core_ltix:anotherplacement']
        );

        // Create placements for each types
        $placement1 = $ltigenerator->create_tool_placements([
            'toolid' => $toolid,
            'placementtypeid' => $placementtype1->id,
            'config_default_usage' => $placementdefault,
            'config_supports_deep_linking' => 0,
        ]);
        $placement2 = $ltigenerator->create_tool_placements([
            'toolid' => $toolid,
            'placementtypeid' => $placementtype2->id,
            'config_default_usage' => $placementdefault,
            'config_supports_deep_linking' => 0,
        ]);

        // Overrides placements with the value from data provider
        if ($placementoverride !== null) {
            $ltigenerator->create_placement_status_in_context($placement1->id, $placementoverride, $context->id);
            $ltigenerator->create_placement_status_in_context($placement2->id, $placementoverride, $context->id);
        }

        $type1tools = placement_repository::get_tools_with_enabled_placement_in_course($placementtype1->type, $course->id);
        $type2tools = placement_repository::get_tools_with_enabled_placement_in_course($placementtype2->type, $course->id);

        $this->assertCount($expectedcount, $type1tools);
        $this->assertCount($expectedcount, $type2tools);

        if ($expectedcount > 0) {
            $this->assertSame('Example tool', $type1tools[$toolid]->name);
            $this->assertSame('Example tool', $type2tools[$toolid]->name);
        }
    }

    /**
     * Data provider for testing get_tools_with_enabled_placement_in_course.
     *
     * @return array[] the test case data.
     */
    public static function get_placement_overrides_provider(): array {
        return [
            'Default YES, Override NULL' => [
                'toolcoursevisible' => constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                'toolstate' => constants::LTI_TOOL_STATE_CONFIGURED,
                'placementdefault' => 'enabled',
                'placementoverride' => null,
                'expectedcount' => 1,
            ],
            'Default YES, Override YES' => [
                'toolcoursevisible' => constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                'toolstate' => constants::LTI_TOOL_STATE_CONFIGURED,
                'placementdefault' => 'enabled',
                'placementoverride' => placement_status::ENABLED,
                'expectedcount' => 1,
            ],
            'Default YES, Override NO' => [
                'toolcoursevisible' => constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                'toolstate' => constants::LTI_TOOL_STATE_CONFIGURED,
                'placementdefault' => 'enabled',
                'placementoverride' => placement_status::DISABLED,
                'expectedcount' => 0,
            ],
            'Default NO, Override NULL' => [
                'toolcoursevisible' => constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                'toolstate' => constants::LTI_TOOL_STATE_CONFIGURED,
                'placementdefault' => 'disabled',
                'placementoverride' => null,
                'expectedcount' => 0,
            ],
            'Default NO, Override YES' => [
                'toolcoursevisible' => constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                'toolstate' => constants::LTI_TOOL_STATE_CONFIGURED,
                'placementdefault' => 'disabled',
                'placementoverride' => placement_status::ENABLED,
                'expectedcount' => 1,
            ],
            'Default NO, Override NO' => [
                'toolcoursevisible' => constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                'toolstate' => constants::LTI_TOOL_STATE_CONFIGURED,
                'placementdefault' => 'disabled',
                'placementoverride' => placement_status::DISABLED,
                'expectedcount' => 0,
            ],
            'Tool is hidden' => [
                'toolcoursevisible' => constants::LTI_COURSEVISIBLE_NO,
                'toolstate' => constants::LTI_TOOL_STATE_CONFIGURED,
                'placementdefault' => 'enabled',
                'placementoverride' => null,
                'expectedcount' => 0,
            ],
            'Tool is pending' => [
                'toolcoursevisible' => constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                'toolstate' => constants::LTI_TOOL_STATE_PENDING,
                'placementdefault' => 'enabled',
                'placementoverride' => null,
                'expectedcount' => 0,
            ],
        ];
    }

    /**
     * Test get_placement_status_for_tool.
     *
     * @param string $placementdefault the default usage for the placement.
     * @param \core_ltix\local\placement\placement_status|null $placementoverride the override status for the placement, or null.
     * @param int $expectedstatus the expected status of the placement.
     * @return void
     * @covers ::get_placement_status_for_tool
     * @dataProvider get_placement_status_provider
     */
    public function test_get_placement_status_for_tool(
        string $placementdefault,
        ?\core_ltix\local\placement\placement_status $placementoverride,
        int $expectedstatus,
    ): void {
        $this->resetAfterTest();

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        $course = $this->getDataGenerator()->create_course();
        $context = \core\context\course::instance($course->id);

        $toolid = $ltigenerator->create_tool_types([
            'name' => 'Example tool',
            'baseurl' => 'http://example.com/tool/1',
            'lti_coursevisible' => constants::LTI_COURSEVISIBLE_PRECONFIGURED,
            'state' => constants::LTI_TOOL_STATE_CONFIGURED,
        ]);

        // Create a couple of placement types with associated config.
        $placementtype1 = $ltigenerator->create_placement_type(
            ['component' => 'core_ltix', 'placementtype' => 'core_ltix:myplacement']
        );
        $placementtype2 = $ltigenerator->create_placement_type(
            ['component' => 'core_ltix', 'placementtype' => 'core_ltix:anotherplacement']
        );

        // Create placements for each types.
        $placement1 = $ltigenerator->create_tool_placements([
            'toolid' => $toolid,
            'placementtypeid' => $placementtype1->id,
            'config_default_usage' => $placementdefault,
            'config_text' => 'Some text 1',
        ]);
        $placement2 = $ltigenerator->create_tool_placements([
            'toolid' => $toolid,
            'placementtypeid' => $placementtype2->id,
            'config_default_usage' => $placementdefault,
            'config_text' => 'Some text 2',
        ]);

        // Override placements with the value from data provider.
        if ($placementoverride !== null) {
            $ltigenerator->create_placement_status_in_context($placement1->id, $placementoverride, $context->id);
            $ltigenerator->create_placement_status_in_context($placement2->id, $placementoverride, $context->id);
        }

        // Call the method being tested.
        $statusrecords = placement_repository::get_placement_status_for_tool($toolid, $course->id);

        foreach ($statusrecords as $record) {
            $this->assertEquals($expectedstatus, $record->status);
        }
    }

    /**
     * Data provider for testing get_placement_status_for_tool.
     *
     * @return array[] the test case data.
     */
    public static function get_placement_status_provider(): array {
        return [
            'Default YES, Override NULL' => [
                'placementdefault' => 'enabled',
                'placementoverride' => null,
                'expectedstatus' => 1,
            ],
            'Default YES, Override ENABLED' => [
                'placementdefault' => 'enabled',
                'placementoverride' => placement_status::ENABLED,
                'expectedstatus' => 1,
            ],
            'Default YES, Override DISABLED' => [
                'placementdefault' => 'enabled',
                'placementoverride' => placement_status::DISABLED,
                'expectedstatus' => 0,
            ],
            'Default NO, Override NULL' => [
                'placementdefault' => 'disabled',
                'placementoverride' => null,
                'expectedstatus' => 0,
            ],
            'Default NO, Override ENABLED' => [
                'placementdefault' => 'disabled',
                'placementoverride' => placement_status::ENABLED,
                'expectedstatus' => 1,
            ],
            'Default NO, Override DISABLED' => [
                'placementdefault' => 'disabled',
                'placementoverride' => placement_status::DISABLED,
                'expectedstatus' => 0,
            ],
        ];
    }

    /**
     * Test get_placement_config_by_placement_type() with data provider.
     *
     * @covers ::get_placement_config_by_placement_type
     * @dataProvider get_placement_config_by_placement_type_provider
     * @param array $placementconfig The configuration data and the expected value for the placement.
     * @param array $expected The expected configuration data to be returned.
     * @param string $placementtypestr The placement type string to be used for the test.
     * @param bool $expectexception Whether to expect an exception to be thrown.
     * @return void
     */
    public function test_get_placement_config_by_placement_type(
        array $placementconfig,
        array $expected,
        string $placementtypestr,
        bool $expectexception
    ): void {
        $this->resetAfterTest();

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        $toolid = $ltigenerator->create_tool_types([
            'name' => 'Test tool',
            'baseurl' => 'http://example.com/tool',
            'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
        ]);

        $placementtype = $ltigenerator->create_placement_type([
            'component' => 'core_ltix',
            'placementtype' => 'core_ltix:myplacement',
        ]);

        // Only create tool placement if placement type string is valid.
        if ($placementtypestr) {
            $data = [
                'toolid' => $toolid,
                'placementtypeid' => $placementtype->id,
            ];
            if ($placementconfig !== []) {
                foreach ($placementconfig as $key => $value) {
                    $data['config_' . $key] = $value;
                }
            }
            $ltigenerator->create_tool_placements($data);
        }

        if ($expectexception) {
            $this->expectException(\coding_exception::class);
            placement_repository::get_placement_config_by_placement_type($toolid, $placementtypestr);
        } else {
            $config = placement_repository::get_placement_config_by_placement_type($toolid, $placementtypestr);

            // Test each expected property exists and matches.
            foreach ($expected as $key => $value) {
                $this->assertEquals($value, $config->$key);
            }

            // Test no unexpected properties exist.
            $configarray = (array)$config;
            $this->assertCount(count($expected), $configarray);
        }
    }

    /**
     * Data provider for testing get_placement_config_by_placement_type().
     *
     * @return array[] the test case data.
     */
    public static function get_placement_config_by_placement_type_provider(): array {
        return [
            'Valid config returned' => [
                [
                    'default_usage' => 'disabled',
                    'deep_linking_url' => 'http://deeplink.example.com',
                    'icon_url' => 'https://icon.example.com',
                ],
                [
                    'default_usage' => 'disabled',
                    'deep_linking_url' => 'http://deeplink.example.com',
                    'icon_url' => 'https://icon.example.com',
                ],
                'core_ltix:myplacement',
                false,
            ],
            'Valid placement type with no config filled' => [
                [],
                [
                    'default_usage' => 'enabled', // Set to enabled, if not set.
                ],
                'core_ltix:myplacement',
                false,
            ],
            'Invalid placement type string' => [
                [
                    'default_usage' => 'enabled',
                    'deep_linking_url' => 'http://deeplink.example.com',
                    'icon_url' => 'https://icon.example.com',
                ],
                [],
                'invalid placement type',
                true,
            ],
            'Nonexistent placement type' => [
                [
                    'default_usage' => 'enabled',
                    'deep_linking_url' => 'http://deeplink.example.com',
                    'icon_url' => 'https://icon.example.com',
                ],
                [],
                'nonexistent:type',
                true,
            ],
        ];
    }

    /**
     * Test the load_placement_config() helper function.
     *
     * @covers ::test_load_placement_config
     * @dataProvider load_placement_config_provider
     * @param array $placementsdata Data used for pre-creating tool placements and respective configs.
     * @param object $expected The expected result from the method call.
     * @return void
     */
    public function test_load_placement_config(array $placementsdata, object $expected): void {

        $this->resetAfterTest();

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        foreach ($placementsdata as $placementdata) {
            $data = [
                'toolid' => 1,
                'placementtypeid' => $placementdata['placementtypeid'],
            ];

            foreach ($placementdata['configdata'] as $configname => $configvalue) {
                $data["config_{$configname}"] = $configvalue;
            }

            $ltigenerator->create_tool_placements($data);
        }

        $result = placement_repository::load_placement_config(1);

        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for testing load_placement_config.
     *
     * @return array[] the test case data.
     */
    public static function load_placement_config_provider(): array {
        return [
            'Single tool placement with configuration' =>
                [
                    [
                        [
                            'placementtypeid' => 1,
                            'configdata' => [
                                'deep_linking_url' => 'http://deeplink.example.com',
                                'icon_url' => 'https://icon.example.com',
                            ],
                        ],
                    ],
                    (object)[
                        'toolplacements' => [1],
                        'deep_linking_url_placementconfig1' => 'http://deeplink.example.com',
                        'icon_url_placementconfig1' => 'https://icon.example.com',
                        // This is a configuration option set by the create_tool_placements() generator function.
                        'default_usage_placementconfig1' => 'enabled',
                    ],
                ],
            'Multiple tool placements with configuration' =>
                [
                    [
                        [
                            'placementtypeid' => 1,
                            'configdata' => [
                                'deep_linking_url' => 'http://deeplink.example.com',
                                'icon_url' => 'https://icon.example.com',
                            ],
                        ],
                        [
                            'placementtypeid' => 2,
                            'configdata' => [
                                'default_usage' => 'enabled',
                                'resource_linking_url' => 'http://resourcelink.example.com',
                                'icon_url' => 'https://icon2.example.com',
                                'text' => 'Example text',
                            ],
                        ],
                        [
                            'placementtypeid' => 3,
                            'configdata' => [
                                'default_usage' => 'disabled',
                                'deep_linking_url' => 'http://deeplink3.example.com',
                                'resource_linking_url' => 'http://resourcelink3.example.com',
                                'icon_url' => 'https://icon3.example.com',
                                'text' => 'Example text 3',
                            ],
                        ],
                    ],
                    (object)[
                        'toolplacements' => [1, 2, 3],
                        'deep_linking_url_placementconfig1' => 'http://deeplink.example.com',
                        'icon_url_placementconfig1' => 'https://icon.example.com',
                        'default_usage_placementconfig1' => 'enabled', // Set by create_tool_placements() generator function.
                        'resource_linking_url_placementconfig2' => 'http://resourcelink.example.com',
                        'icon_url_placementconfig2' => 'https://icon2.example.com',
                        'text_placementconfig2' => 'Example text',
                        'default_usage_placementconfig2' => 'enabled',
                        'deep_linking_url_placementconfig3' => 'http://deeplink3.example.com',
                        'resource_linking_url_placementconfig3' => 'http://resourcelink3.example.com',
                        'icon_url_placementconfig3' => 'https://icon3.example.com',
                        'text_placementconfig3' => 'Example text 3',
                        'default_usage_placementconfig3' => 'disabled',
                    ],
                ],
            'No tool placements' =>
                [
                    [],
                    (object)[
                        'toolplacements' => [],
                    ],
                ],
        ];
    }

    /**
     * Test the insert_or_update_placement_config() helper function.
     *
     * @covers ::insert_or_update_placement_config
     * @dataProvider insert_or_update_placement_config_provider
     * @param string|null $existingvalue The pre-existing value for the placement config, or null if not pre-existing
     *                                   (config won't be pre-created).
     * @param string $newvalue The new value to be added to the placement config.
     * @param string $expectedvalue The expected value for placement config after addition.
     * @return void
     */
    public function test_insert_or_update_placement_config(?string $existingvalue, string $newvalue, string $expectedvalue): void {
        global $DB;

        $this->resetAfterTest();

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('mod_lti');

        $placementdata = [
            'toolid' => 1,
            'placementtypeid' => $DB->get_field('lti_placement_type', 'id', ['type' => 'mod_lti:activityplacement']),
        ];

        if (!empty($existingvalue)) {
            $placementdata["config_testconfig"] = $existingvalue;
        }

        // Create a dummy placement record to reference.
        $placement = $ltigenerator->create_tool_placements($placementdata);

        $newconfig = [
            'placementid' => $placement->id,
            'name' => 'testconfig',
            'value' => $newvalue,
        ];

        placement_repository::insert_or_update_placement_config((object)$newconfig);

        // Fetch the config and verify the returned value.
        $configrecord = $DB->get_records('lti_placement_config', [
            'placementid' => $placement->id,
            'name' => 'testconfig',
        ]);

        $this->assertCount(1, $configrecord);
        $this->assertEquals($expectedvalue, reset($configrecord)->value);
    }

    /**
     * Data provider for testing insert_or_update_placement_config().
     *
     * @return array[] the test case data.
     */
    public static function insert_or_update_placement_config_provider(): array {
        return [
            'Tool placement without a pre-existing placement config' =>
                [
                    null,
                    'foo',
                    'foo',
                ],
            'Tool placement with a pre-exising placement config' =>
                [
                    'foo',
                    'bar',
                    'bar',
                ],
        ];
    }

    /**
     * Test the delete_tool_placements() helper function.
     *
     * @covers ::delete_tool_placements
     * @dataProvider delete_tool_placements_provider
     * @param array $placementsdata Data used for pre-creating tool placements and respective configs.
     * @param array $placementtypeids An array of placement type IDs associated with the tool placements to delete.
     */
    public function test_delete_tool_placements(array $placementsdata, array $placementtypeids): void {
        global $DB;

        $this->resetAfterTest();

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        // Array storing the placement IDs of placements to be deleted, for easier validation post-deletion.
        $placementstodelete = [];
        // Array storing the placement IDs of placements that should not be deleted, for easier validation after deletion.
        $remainingplacements = [];

        foreach ($placementsdata as $placementdata) {
            $data = [
                'toolid' => 1,
                'placementtypeid' => $placementdata['placementtypeid'],
            ];

            foreach ($placementdata['configdata'] as $configname => $configvalue) {
                $data["config_{$configname}"] = $configvalue;
            }

            $placement = $ltigenerator->create_tool_placements($data);

            $ltigenerator->create_placement_status_in_context($placement->id, $placementdata['statusoverride'],
                \core\context\system::instance()->id);

            // If the created placement is supposed to be deleted later, include its ID to the $placementstodelete array.
            if (empty($placementtypeids) || in_array($placementdata['placementtypeid'], $placementtypeids)) {
                $placementstodelete[] = $placement->id;
            } else { // Otherwise include its ID to the $remainingplacements array.
                $remainingplacements[] = $placement->id;
            }
        }

        placement_repository::delete_tool_placements(1, $placementtypeids);

        // Verify that the placements data has been successfully removed.
        foreach ($placementstodelete as $placementid) {
            $placement = $DB->get_record('lti_placement', ['id' => $placementid]);
            $this->assertFalse($placement);
            $placementconfigs = $DB->get_records('lti_placement_config', ['placementid' => $placementid]);
            $this->assertEmpty($placementconfigs);
            $placementstatusoverrides = $DB->get_records('lti_placement_status', ['placementid' => $placementid]);
            $this->assertEmpty($placementstatusoverrides);
        }

        // Verify that the placements data that should not have been removed is still present.
        foreach ($remainingplacements as $placementid) {
            $placement = $DB->get_record('lti_placement', ['id' => $placementid]);
            $this->assertIsObject($placement);
            $placementconfigs = $DB->get_records('lti_placement_config', ['placementid' => $placementid]);
            $this->assertNotEmpty($placementconfigs);
            $placementstatusoverrides = $DB->get_records('lti_placement_status', ['placementid' => $placementid]);
            $this->assertNotEmpty($placementstatusoverrides);
        }
    }

    /**
     * Data provider for testing delete_tool_placements().
     *
     * @return array[] the test case data.
     */
    public static function delete_tool_placements_provider(): array {
        return [
            'Delete all existing tool placements (multiple available) and config without passing placement type IDs' =>
                [
                    [
                        [
                            'placementtypeid' => 1,
                            'configdata' => [
                                'deep_linking_url' => 'http://deeplink.example.com',
                                'icon_url' => 'https://icon.example.com',
                            ],
                            'statusoverride' => placement_status::DISABLED
                        ],
                        [
                            'placementtypeid' => 2,
                            'configdata' => [
                                'resource_linking_url' => 'http://resourcelink.example.com',
                                'icon_url' => 'https://icon2.example.com',
                                'text' => 'Example text',
                            ],
                            'statusoverride' => placement_status::DISABLED
                        ],
                        [
                            'placementtypeid' => 3,
                            'configdata' => [
                                'deep_linking_url' => 'http://deeplink3.example.com',
                                'resource_linking_url' => 'http://resourcelink3.example.com',
                                'icon_url' => 'https://icon3.example.com',
                                'text' => 'Example text 3',
                            ],
                            'statusoverride' => placement_status::ENABLED
                        ],
                    ],
                    [],
                ],
            'Delete all existing tool placements (one available) and data by passing placement type ID' =>
                [
                    [
                        [
                            'placementtypeid' => 1,
                            'configdata' => [
                                'deep_linking_url' => 'http://deeplink.example.com',
                                'icon_url' => 'https://icon.example.com',
                            ],
                            'statusoverride' => placement_status::ENABLED
                        ],
                    ],
                    [1],
                ],
            'Delete all existing tool placements (multiple available) and config by passing placement type IDs' =>
                [
                    [
                        [
                            'placementtypeid' => 1,
                            'configdata' => [
                                'deep_linking_url' => 'http://deeplink.example.com',
                                'icon_url' => 'https://icon.example.com',
                            ],
                            'statusoverride' => placement_status::DISABLED
                        ],
                        [
                            'placementtypeid' => 2,
                            'configdata' => [
                                'resource_linking_url' => 'http://resourcelink.example.com',
                                'icon_url' => 'https://icon2.example.com',
                                'text' => 'Example text',
                            ],
                            'statusoverride' => placement_status::DISABLED
                        ],
                        [
                            'placementtypeid' => 3,
                            'configdata' => [
                                'default_usage' => 'enabled',
                                'deep_linking_url' => 'http://deeplink3.example.com',
                                'resource_linking_url' => 'http://resourcelink3.example.com',
                                'icon_url' => 'https://icon3.example.com',
                                'text' => 'Example text 3',
                            ],
                            'statusoverride' => placement_status::ENABLED
                        ],
                    ],
                    [1, 2, 3],
                ],
            'Delete only a few of the existing tool placements and config by passing placement type IDs' =>
                [
                    [
                        [
                            'placementtypeid' => 1,
                            'configdata' => [
                                'deep_linking_url' => 'http://deeplink.example.com',
                                'icon_url' => 'https://icon.example.com',
                            ],
                            'statusoverride' => placement_status::ENABLED
                        ],
                        [
                            'placementtypeid' => 2,
                            'configdata' => [
                                'resource_linking_url' => 'http://resourcelink.example.com',
                                'icon_url' => 'https://icon2.example.com',
                                'text' => 'Example text',
                            ],
                            'statusoverride' => placement_status::DISABLED
                        ],
                        [
                            'placementtypeid' => 3,
                            'configdata' => [
                                'default_usage' => 'disabled',
                                'deep_linking_url' => 'http://deeplink3.example.com',
                                'resource_linking_url' => 'http://resourcelink3.example.com',
                                'icon_url' => 'https://icon3.example.com',
                                'text' => 'Example text 3',
                            ],
                            'statusoverride' => placement_status::ENABLED
                        ],
                    ],
                    [1, 3],
                ],
            'Attempt deleting non-existing tool placements' =>
                [
                    [
                        [
                            'placementtypeid' => 1,
                            'configdata' => [
                                'deep_linking_url' => 'http://deeplink.example.com',
                                'icon_url' => 'https://icon.example.com',
                            ],
                            'statusoverride' => placement_status::ENABLED
                        ],
                    ],
                    [2],
                ],
        ];
    }
}
