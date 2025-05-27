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
}
