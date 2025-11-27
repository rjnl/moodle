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

use core\context\course;
use core_ltix\constants;
use core_ltix\local\placement\service\resource_link_manager;

/**
 * Test class for \core_ltix\local\placement\placement_service.
 *
 * @covers \core_ltix\local\placement\placement_service
 * @package    core_ltix
 * @copyright  2025 Muhammad Arnaldo <muhammad.arnaldo@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class placement_service_test extends \advanced_testcase {
    /**
     * Test getting launch container for a link.
     *
     * @dataProvider get_launch_container_provider
     * @param int|null $toollaunchcontainer Launch container from tool type
     * @param int|null $linklaunchcontainer Launch container from link
     * @param int|null $expected Expected result
     * @param bool $mobile If test should simulate mobile device
     */
    public function test_get_launch_container_for_link(
        ?int $toollaunchcontainer,
        ?int $linklaunchcontainer,
        ?int $expected,
        bool $mobile = false
    ): void {
        $this->resetAfterTest();

        if ($mobile) {
            $this->pretend_to_be_mobile_device();
        }

        // Create a site tool type.
        /** @var \core_ltix_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');
        $typeid = $ltigenerator->create_tool_types([
            'name' => 'Test Tool',
            'baseurl' => 'http://example.com/lti',
            'coursevisible' => constants::LTI_COURSEVISIBLE_PRECONFIGURED,
            'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
            'lti_launchcontainer' => $toollaunchcontainer,
        ]);
        // Create a placement for the site tool.
        $placementtype = $ltigenerator->create_placement_type(
            ['component' => 'core_ltix', 'placementtype' => 'core_ltix:myplacement']
        );
        $ltigenerator->create_tool_placements([
            'toolid' => $typeid,
            'placementtypeid' => $placementtype->id,
            'config_default_usage' => 'enabled',
            'config_supports_deep_linking' => 0,
        ]);
        // Create a resource link for the placement.
        $course = $this->getDataGenerator()->create_course();
        $linkmanager = resource_link_manager::create(
            $placementtype->type,
            $placementtype->component,
            course::instance($course->id)
        );
        $link = $linkmanager->create_resource_link(
            toolid: $typeid,
            itemid: 123456, // Arbitrary value, not important for this test.
            url: new \core\url('http://lms.example.com/link'),
            title: 'Link title',
            launchcontainer: $linklaunchcontainer ?? null,
        );

        $launchcontainer = placement_service::get_launch_container_for_link($link);
        $this->assertEquals($expected, $launchcontainer);
    }

    /**
     * Data provider for test_get_launch_container_for_link.
     */
    public static function get_launch_container_provider(): array {
        return [
            'Use tool launch container when link launch container is default' => [
                'toollaunchcontainer' => constants::LTI_LAUNCH_CONTAINER_WINDOW,
                'linklaunchcontainer' => constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                'expected' => constants::LTI_LAUNCH_CONTAINER_WINDOW,
            ],
            'Use link launch container when tool launch container is default' => [
                'toollaunchcontainer' => constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                'linklaunchcontainer' => constants::LTI_LAUNCH_CONTAINER_WINDOW,
                'expected' => constants::LTI_LAUNCH_CONTAINER_WINDOW,
            ],
            'Use embed no blocks when both set to default' => [
                'toollaunchcontainer' => constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                'linklaunchcontainer' => constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                'expected' => constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
            ],
            'Use link launch container when tool launch container is empty' => [
                'toollaunchcontainer' => null,
                'linklaunchcontainer' => constants::LTI_LAUNCH_CONTAINER_WINDOW,
                'expected' => constants::LTI_LAUNCH_CONTAINER_WINDOW,
            ],
            'Use tool launch container when link launch container is empty' => [
                'toollaunchcontainer' => constants::LTI_LAUNCH_CONTAINER_WINDOW,
                'linklaunchcontainer' => null,
                'expected' => constants::LTI_LAUNCH_CONTAINER_WINDOW,
            ],
            'Use embed no blocks when both containers are empty' => [
                'toollaunchcontainer' => null,
                'linklaunchcontainer' => null,
                'expected' => constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
            ],
            'Mobile device should return replace window' => [
                'toollaunchcontainer' => constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                'linklaunchcontainer' => constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
                'expected' => constants::LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW,
                'mobile' => true,
            ],
        ];
    }

    /**
     * Helper method to simulate mobile device.
     */
    private function pretend_to_be_mobile_device(): void {
        \core_useragent::instance(true, 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)');
    }
}
