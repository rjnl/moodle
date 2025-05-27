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

namespace core_ltix\local\placement\service;

use core\context;
use core_ltix\local\lticore\models\resource_link;
use core_ltix\local\placement\service\resource_link_manager;

/**
 * Resource link manager service tests.
 *
 * @covers     \core_ltix\local\placement\service\resource_link_manager
 * @package    core_ltix
 * @copyright  2025 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class resource_link_manager_test extends \advanced_testcase {

    /**
     * Test the factory method create().
     *
     * @param string $placementtypearg The placement type argument used when calling the method.
     * @param string $componentarg The component argument used when calling the method.
     * @param context $contextarg The context argument used when calling the method.
     * @param string|null $expectedexception The expected exception message, if applicable.
     * @return void
     * @dataProvider create_provider
     */
    public function test_create(string $placementtypearg, string $componentarg, context $contextarg,
            ?string $expectedexception = null): void {
        $this->resetAfterTest();

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        // Create a placement type.
        $ltigenerator->create_placement_type([
            'component' => 'core_ltix',
            'placementtype' => 'core_ltix:validplacement'
        ]);

        // If an exception is expected, verify the exception.
        if ($expectedexception) {
            $this->expectException(\coding_exception::class);
            $this->expectExceptionMessage($expectedexception);
        }

        $result = resource_link_manager::create($placementtypearg, $componentarg, $contextarg);
        // Verify that a resource_link_manager object is returned.
        $this->assertInstanceOf(resource_link_manager::class, $result);
    }

    /**
     * Data provider for test_create().
     *
     * @return array
     */
    public static function create_provider(): array {
        global $SITE;

        return [
            'Invalid argument provided: placement type' => [
                'core_ltix:invalidplacement',
                'core_ltix',
                \core\context\course::instance($SITE->id),
                'Invalid placement type.',
            ],
            'Invalid argument provided: component' => [
                'core_ltix:validplacement',
                'core_message',
                \core\context\course::instance($SITE->id),
                'Invalid component.',
            ],
            'Invalid argument provided: context' => [
                'core_ltix:validplacement',
                'core_ltix',
                \core\context\system::instance(),
                'Invalid context.',
            ],
        ];
    }

    /**
     * Test the method create_resource_link().
     *
     * @param array|null $toolsettings The array containing tool related configuration settings, or null there is no
     *                                 requirement for tool creation in the test.
     * @param array $placementsettings The array containing placement related configuration settings.
     * @param array $args The array containing the arguments used when calling the method.
     * @param array $expectedpropertyvalues The array containing the expected values for the properties of the created
     *                                      resource link.
     * @param string|null $expectedexception The expected exception message, if applicable.
     * @return void
     * @dataProvider create_resource_link_provider
     */
    public function test_create_resource_link(?array $toolsettings, array $placementsettings, array $args,
            array $expectedpropertyvalues, ?string $expectedexception = null): void {
        global $SITE;

        $this->resetAfterTest();

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        // Set the tool ID. If tool configuration settings are provided in the data provider, a new tool is created and
        // its ID is used. Otherwise, the tool ID is set to 0 (simulating a legacy tool, for example).
        $toolid = $toolsettings ? $ltigenerator->create_tool_types([
            'name' => 'Example tool',
            'baseurl' => 'http://example.com/tool/1',
            'lti_coursevisible' => $toolsettings['coursevisible'],
            'state' => $toolsettings['state']
        ]): 0;

        // Create a placement type.
        $placementtype = $ltigenerator->create_placement_type([
            'component' => 'core_ltix',
            'placementtype' => 'core_ltix:validplacement'
        ]);

        // Create a placement.
        $ltigenerator->create_tool_placements([
            'toolid' => $toolid,
            'placementtypeid' => $placementtype->id,
            'config_default_usage' => $placementsettings['default_usage'],
            'config_supports_deep_linking' => 0,
        ]);

        $resourcelinkmanager = resource_link_manager::create('core_ltix:validplacement', 'core_ltix',
            \core\context\course::instance($SITE->id));

        // Verify that no resource link exists for the given itemid and placement type.
        $resourcelink = $resourcelinkmanager->get_resource_link($args['itemid']);
        $this->assertNull($resourcelink);

        // If an exception is expected, verify the exception.
        if ($expectedexception) {
            $this->expectException(\coding_exception::class);
            $this->expectExceptionMessage($expectedexception);
        }

        // Create a new resource link for the given placement type.
        $resourcelink = $resourcelinkmanager->create_resource_link($toolid, ...$args);

        // Now, verify the return.
        $this->assertInstanceOf(resource_link::class, $resourcelink);
        $expectedpropertyvalues += [
            'typeid' => $toolid,
            'contextid' => \core\context\course::instance($SITE->id)->id,
        ];

        foreach ($expectedpropertyvalues as $name => $value) {
            $this->assertEquals($value, $resourcelink->get($name));
        }
    }

    /**
     * Data provider for test_create_resource_link().
     *
     * @return array
     */
    public static function create_resource_link_provider(): array {
        return [
            'Legacy tool (manually configured), placement disabled, only required arguments provided' => [
                null,
                [
                    'default_usage' => 'disabled'
                ],
                [
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                ],
                [
                    'component' => 'core_ltix',
                    'itemtype' => 'core_ltix:validplacement',
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                    'text' => null,
                    'textformat' => FORMAT_MOODLE,
                    'gradable' => false,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                    'customparams' => null,
                    'icon' => null,
                    'servicesalt' => null,
                ],
            ],
            'Tool is pending, placement enabled, only required arguments provided' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_PENDING,
                ],
                [
                    'default_usage' => 'enabled'
                ],
                [
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                ],
                [
                    'component' => 'core_ltix',
                    'itemtype' => 'core_ltix:validplacement',
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                    'text' => null,
                    'textformat' => FORMAT_MOODLE,
                    'gradable' => false,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                    'customparams' => null,
                    'icon' => null,
                    'servicesalt' => null,
                ],
                'The resource link cannot be created for the specified placement in the given tool.',
            ],
            'Tool is not visible in course, placement enabled, only required arguments provided' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_NO,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_PENDING,
                ],
                [
                    'default_usage' => 'enabled'
                ],
                [
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                ],
                [
                    'component' => 'core_ltix',
                    'itemtype' => 'core_ltix:validplacement',
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                    'text' => null,
                    'textformat' => FORMAT_MOODLE,
                    'gradable' => false,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                    'customparams' => null,
                    'icon' => null,
                    'servicesalt' => null,
                ],
                'The resource link cannot be created for the specified placement in the given tool.',
            ],
            'Valid tool, placement disabled, only required arguments provided' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                ],
                [
                    'default_usage' => 'disabled'
                ],
                [
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                ],
                [
                    'component' => 'core_ltix',
                    'itemtype' => 'core_ltix:validplacement',
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                    'text' => null,
                    'textformat' => FORMAT_MOODLE,
                    'gradable' => false,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                    'customparams' => null,
                    'icon' => null,
                    'servicesalt' => null,
                ],
                'The resource link cannot be created for the specified placement in the given tool.',
            ],
            'Valid tool, placement enabled, only required arguments provided' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                ],
                [
                    'default_usage' => 'enabled'
                ],
                [
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                ],
                [
                    'component' => 'core_ltix',
                    'itemtype' => 'core_ltix:validplacement',
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                    'text' => null,
                    'textformat' => FORMAT_MOODLE,
                    'gradable' => false,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                    'customparams' => null,
                    'icon' => null,
                    'servicesalt' => null,
                ],
            ],
            'Valid tool, placement enabled, all arguments provided' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                ],
                [
                    'default_usage' => 'enabled'
                ],
                [
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                    'text' => '<p>Resource description</p>',
                    'textformat' => FORMAT_HTML,
                    'gradable' => true,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_WINDOW,
                    'customparams' => 'id=1',
                    'icon' => 'http://example.com/tool/1/resource/1/icon/image.png',
                    'servicesalt' => 'abcdef',
                ],
                [
                    'component' => 'core_ltix',
                    'itemtype' => 'core_ltix:validplacement',
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                    'text' => '<p>Resource description</p>',
                    'textformat' => FORMAT_HTML,
                    'gradable' => true,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_WINDOW,
                    'customparams' => 'id=1',
                    'icon' => 'http://example.com/tool/1/resource/1/icon/image.png',
                    'servicesalt' => 'abcdef',
                ],
            ],
        ];
    }

    /**
     * Test the method get_resource_link().
     *
     * @param int $itemid The item ID of the resource link to retrieve.
     * @param bool $expectsresourcelink Whether the method call is expected to return a resource link.
     * @return void
     * @dataProvider get_resource_link_provider
     */
    public function test_get_resource_link(int $itemid, bool $expectsresourcelink): void {
        global $SITE;

        $this->resetAfterTest();

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        // Create a tool.
        $toolid = $ltigenerator->create_tool_types([
            'name' => 'Example tool',
            'baseurl' => 'http://example.com/tool/1',
            'lti_coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
            'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED
        ]);

        // Create a placement type.
        $placementtype = $ltigenerator->create_placement_type([
            'component' => 'core_ltix',
            'placementtype' => 'core_ltix:validplacement'
        ]);

        // Create a placement.
        $ltigenerator->create_tool_placements([
            'toolid' => $toolid,
            'placementtypeid' => $placementtype->id,
            'config_default_usage' => 'enabled',
            'config_supports_deep_linking' => 0,
        ]);

        $resourcelinkmanager = resource_link_manager::create('core_ltix:validplacement', 'core_ltix',
            \core\context\course::instance($SITE->id));

        // Create a resource link.
        $resourcelinkmanager->create_resource_link($toolid, 1, 'http://example.com/tool/1/resource/1',
            'Resource title');

        // Get the resource link.
        $resourcelink = $resourcelinkmanager->get_resource_link($itemid);

        if ($expectsresourcelink) { // If a resource link is expected to be returned.
            // Ensure the correctness of the returned data.
            $this->assertInstanceOf(resource_link::class, $resourcelink);
            $this->assertEquals(1, $resourcelink->get('itemid'));
            $this->assertEquals('core_ltix:validplacement', $resourcelink->get('itemtype'));
            $this->assertEquals('http://example.com/tool/1/resource/1', $resourcelink->get('url'));
            $this->assertEquals('Resource title', $resourcelink->get('title'));
        } else { // Otherwise, ensure that no resource link is returned.
            $this->assertNull($resourcelink);
        }
    }

    /**
     * Data provider for test_get_resource_link().
     *
     * @return array
     */
    public static function get_resource_link_provider(): array {
        return [
            'Existing resource link' => [
                1,
                true,
            ],
            'Non-existing resource link' => [
                2,
                false,
            ],
        ];
    }

    /**
     * Test the method update_resource_link().
     *
     * @param int $itemid The item ID of the resource link to update.
     * @param array $updatedata The array containing data to update.
     * @param bool $expectedreturn The expected return value from the method call.
     * @param array $expectedpropertyvalues The array containing the expected values for the properties of the resource link
     *                                      after the update.
     * @return void
     * @dataProvider update_resource_link_provider
     */
    public function test_update_resource_link(int $itemid, array $updatedata, bool $expectedreturn,
            array $expectedpropertyvalues): void {
        global $SITE;

        $this->resetAfterTest();

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        // Create a tool.
        $toolid =  $ltigenerator->create_tool_types([
            'name' => 'Example tool',
            'baseurl' => 'http://example.com/tool/1',
            'lti_coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
            'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED
        ]);

        // Create a placement type.
        $placementtype = $ltigenerator->create_placement_type([
            'component' => 'core_ltix',
            'placementtype' => 'core_ltix:validplacement'
        ]);

        // Create a placement.
        $ltigenerator->create_tool_placements([
            'toolid' => $toolid,
            'placementtypeid' => $placementtype->id,
            'config_default_usage' => 'enabled',
            'config_supports_deep_linking' => 0,
        ]);

        $resourcelinkmanager = resource_link_manager::create('core_ltix:validplacement', 'core_ltix',
            \core\context\course::instance($SITE->id));

        // Create a new resource link for the given placement type.
        $resourcelinkmanager->create_resource_link($toolid, 1, 'http://example.com/tool/1/resource/1',
            'Resource title');

        // Update the resource link.
        $result = $resourcelinkmanager->update_resource_link($itemid, $updatedata);

        // Verify the return value.
        $this->assertEquals($expectedreturn, $result);

        // Now, verify the changes.
        $resourcelink = $resourcelinkmanager->get_resource_link(1);

        $this->assertInstanceOf(resource_link::class, $resourcelink);
        $expectedpropertyvalues += [
            'typeid' => $toolid,
            'contextid' => \core\context\course::instance($SITE->id)->id,
        ];

        foreach ($expectedpropertyvalues as $name => $value) {
            $this->assertEquals($value, $resourcelink->get($name));
        }
    }

    /**
     * Data provider for update_resource_link().
     *
     * @return array
     */
    public static function update_resource_link_provider(): array {
        return [
            'No update data provided' => [
                1,
                [],
                false,
                [
                    'component' => 'core_ltix',
                    'itemtype' => 'core_ltix:validplacement',
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                    'text' => null,
                    'textformat' => FORMAT_MOODLE,
                    'gradable' => false,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                    'customparams' => null,
                    'icon' => null,
                    'servicesalt' => null,
                ],
            ],
            'Attempting to update a non-existent resource link.' => [
                2,
                [
                    'title' => 'Resource title (updated)',
                    'text' => '<p>Resource description (updated)</p>',
                    'textformat' => FORMAT_HTML,
                    'gradable' => true,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_WINDOW,
                    'customparams' => 'id=1',
                    'icon' => 'http://example.com/tool/1/resource/1/icon/image.png',
                ],
                false,
                [
                    'component' => 'core_ltix',
                    'itemtype' => 'core_ltix:validplacement',
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                    'text' => null,
                    'textformat' => FORMAT_MOODLE,
                    'gradable' => false,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                    'customparams' => null,
                    'icon' => null,
                    'servicesalt' => null,
                ],
            ],
            'Attempting to update disallowed resource link properties' => [
                1,
                [
                    'typeid' => 10,
                    'component' => 'core_navigation',
                    'itemtype' => 'core_navigation:invalidplacement',
                    'itemid' => 30,
                    'servicesalt' => 'abcdef'
                ],
                false,
                [
                    'component' => 'core_ltix',
                    'itemtype' => 'core_ltix:validplacement',
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                    'text' => null,
                    'textformat' => FORMAT_MOODLE,
                    'gradable' => false,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                    'customparams' => null,
                    'icon' => null,
                    'servicesalt' => null,
                ],
            ],
            'Attempting to update allowed resource link properties' => [
                1,
                [
                    'url' => 'http://example.com/tool/1/resource/10',
                    'title' => 'Resource title (updated)',
                    'text' => '<p>Resource description (updated)</p>',
                    'textformat' => FORMAT_HTML,
                    'gradable' => true,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_WINDOW,
                    'customparams' => 'id=1',
                    'icon' => 'http://example.com/tool/1/resource/1/icon/image.png',
                ],
                true,
                [
                    'component' => 'core_ltix',
                    'itemtype' => 'core_ltix:validplacement',
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/10',
                    'title' => 'Resource title (updated)',
                    'text' => '<p>Resource description (updated)</p>',
                    'textformat' => FORMAT_HTML,
                    'gradable' => true,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_WINDOW,
                    'customparams' => 'id=1',
                    'icon' => 'http://example.com/tool/1/resource/1/icon/image.png',
                    'servicesalt' => null,
                ],
            ],
        ];
    }

    /**
     * Test the method delete_resource_link().
     *
     * @param int $itemid The item ID of the resource link to delete.
     * @param bool $expectedreturn The expected return value from the method call.
     * @return void
     * @dataProvider delete_resource_link_provider
     */
    public function test_delete_resource_link(int $itemid, bool $expectedreturn): void {
        global $SITE;

        $this->resetAfterTest();

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        // Create a tool.
        $toolid = $ltigenerator->create_tool_types([
            'name' => 'Example tool',
            'baseurl' => 'http://example.com/tool/1',
            'lti_coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
            'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED
        ]);

        $placementtype = $ltigenerator->create_placement_type([
            'component' => 'core_ltix',
            'placementtype' => 'core_ltix:validplacement'
        ]);

        // Create a placement.
        $ltigenerator->create_tool_placements([
            'toolid' => $toolid,
            'placementtypeid' => $placementtype->id,
            'config_default_usage' => 'enabled',
            'config_supports_deep_linking' => 0,
        ]);

        $resourcelinkmanager = resource_link_manager::create('core_ltix:validplacement', 'core_ltix',
            \core\context\course::instance($SITE->id));

        // Create a resource link.
        $resourcelinkmanager->create_resource_link($toolid, 1, 'http://example.com/tool/1/resource/1',
            'Resource title');

        // Delete the resource link.
        $result = $resourcelinkmanager->delete_resource_link($itemid);

        // Verify the return value.
        $this->assertEquals($expectedreturn, $result);

        // Try to get the resource link.
        $resourcelink = $resourcelinkmanager->get_resource_link(1);

        if ($expectedreturn) { // If the resource link is expected to be deleted.
            // Ensure that the resource link no longer exists.
            $this->assertNull($resourcelink);
        } else { // If the resource link is expected not to be deleted.
            // Ensure that the resource link still exists.
            $this->assertInstanceOf(resource_link::class, $resourcelink);
        }
    }

    /**
     * Data provider for test_delete_resource_link().
     *
     * @return array
     */
    public static function delete_resource_link_provider(): array {
        return [
            'Existing resource link' => [
                1,
                true,
            ],
            'Non-existing resource link' => [
                2,
                false,
            ],
        ];
    }
}
