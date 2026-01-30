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

use core_ltix\local\lticore\models\resource_link;

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

        // Verify that no resource link exists for the given itemid and placement type.
        $resourcelink = resource_link_manager::get_resource_link_by_item($args['itemid'], $args['placementtype']);
        $this->assertNull($resourcelink);

        // If an exception is expected, verify the exception.
        if ($expectedexception) {
            $this->expectException(\coding_exception::class);
            $this->expectExceptionMessage($expectedexception);
        }

        // Create a new resource link for the given placement type.
        $resourcelink = resource_link_manager::create_resource_link(... array_merge(['toolid' => $toolid], $args));

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
        global $SITE;

        return [
            'Invalid argument provided: placement type' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_PENDING,
                ],
                [
                    'default_usage' => 'enabled'
                ],
                [
                    'placementtype' => 'core_ltix:invalidplacement',
                    'component' => 'core_ltix',
                    'context' => \core\context\course::instance($SITE->id),
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                ],
                [],
                'Invalid placement type.',
            ],
            'Invalid argument provided: component' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_PENDING,
                ],
                [
                    'default_usage' => 'enabled'
                ],
                [
                    'placementtype' => 'core_ltix:validplacement',
                    'component' => 'core_message',
                    'context' => \core\context\course::instance($SITE->id),
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                ],
                [],
                'Invalid component.',
            ],
            'Invalid argument provided: context' => [
                [
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_PENDING,
                ],
                [
                    'default_usage' => 'enabled'
                ],
                [
                    'placementtype' => 'core_ltix:validplacement',
                    'component' => 'core_ltix',
                    'context' => \core\context\system::instance(),
                    'itemid' => 1,
                    'url' => 'http://example.com/tool/1/resource/1',
                    'title' => 'Resource title',
                ],
                [],
                'Invalid context.',
            ],
            'Legacy tool (manually configured), placement disabled, only required arguments provided' => [
                null,
                [
                    'default_usage' => 'disabled'
                ],
                [
                    'placementtype' => 'core_ltix:validplacement',
                    'component' => 'core_ltix',
                    'context' => \core\context\course::instance($SITE->id),
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
                    'placementtype' => 'core_ltix:validplacement',
                    'component' => 'core_ltix',
                    'context' => \core\context\course::instance($SITE->id),
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
                    'placementtype' => 'core_ltix:validplacement',
                    'component' => 'core_ltix',
                    'context' => \core\context\course::instance($SITE->id),
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
                    'placementtype' => 'core_ltix:validplacement',
                    'component' => 'core_ltix',
                    'context' => \core\context\course::instance($SITE->id),
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
                    'placementtype' => 'core_ltix:validplacement',
                    'component' => 'core_ltix',
                    'context' => \core\context\course::instance($SITE->id),
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
                    'placementtype' => 'core_ltix:validplacement',
                    'component' => 'core_ltix',
                    'context' => \core\context\course::instance($SITE->id),
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
     * Test the method get_resource_link_by_id().
     *
     * @return void
     */
    public function test_get_resource_link_by_id(): void {
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

        // Create 2 resource links.
        $resourcelink1 = resource_link_manager::create_resource_link('core_ltix:validplacement', 'core_ltix',
            \core\context\course::instance($SITE->id), $toolid, 1, 'http://example.com/tool/1/resource/1', 'Resource title 1');
        $resourcelink1id = $resourcelink1->get('id');

        $resourcelink2 = resource_link_manager::create_resource_link('core_ltix:validplacement', 'core_ltix',
            \core\context\course::instance($SITE->id), $toolid, 2, 'http://example.com/tool/1/resource/2', 'Resource title 2');
        $resourcelink2id = $resourcelink2->get('id');

        // Get resource link 1 by its ID.
        $returnedresourcelink = resource_link_manager::get_resource_link_by_id($resourcelink1id);
        // Verify that the returned link is resource link 1.
        $this->assertEquals($resourcelink1id, $returnedresourcelink->get('id'));
        $this->assertEquals('http://example.com/tool/1/resource/1', $returnedresourcelink->get('url'));
        $this->assertEquals('Resource title 1', $returnedresourcelink->get('title'));

        // Get resource link 2 by its ID.
        $returnedresourcelink = resource_link_manager::get_resource_link_by_id($resourcelink2id);
        // Verify that the returned link is resource link 2.
        $this->assertEquals($resourcelink2id, $returnedresourcelink->get('id'));
        $this->assertEquals('http://example.com/tool/1/resource/2', $returnedresourcelink->get('url'));
        $this->assertEquals('Resource title 2', $returnedresourcelink->get('title'));

        // Attempt to get a resource link using the non-existent ID.
        $returnedresourcelink = resource_link_manager::get_resource_link_by_id(0);
        // Verify that no resource link is returned.
        $this->assertNull($returnedresourcelink);
    }

    /**
     * Test the method get_resource_link_by_item().
     *
     * @param int $itemid The item ID of the resource link to retrieve.
     * @param string $itemtype The type of the resource link to retrieve.
     * @param bool $expectsresourcelink Whether the method call is expected to return a resource link.
     * @return void
     * @dataProvider get_resource_link_by_item_provider
     */
    public function test_get_resource_link_by_item(int $itemid, string $itemtype, bool $expectsresourcelink): void {
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

        // Create a resource link.
        resource_link_manager::create_resource_link('core_ltix:validplacement', 'core_ltix',
            \core\context\course::instance($SITE->id), $toolid, 1, 'http://example.com/tool/1/resource/1', 'Resource title');

        // Get the resource link.
        $resourcelink = resource_link_manager::get_resource_link_by_item($itemid, $itemtype);

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
     * Data provider for test_get_resource_link_by_item().
     *
     * @return array
     */
    public static function get_resource_link_by_item_provider(): array {
        return [
            'Existing resource link' => [
                1,
                'core_ltix:validplacement',
                true,
            ],
            'Non-existing resource link: invalid itemid' => [
                2,
                'core_ltix:validplacement',
                false,
            ],
            'Non-existing resource link: invalid itemtype' => [
                2,
                'core_ltix:invalidplacement',
                false,
            ],
        ];
    }

    /**
     * Test the method update_resource_link().
     *
     * @param array $updatedata The array containing data to update.
     * @param bool $expectedreturn The expected return value from the method call.
     * @param array $expectedpropertyvalues The array containing the expected values for the properties of the resource link
     *                                      after the update.
     * @return void
     * @dataProvider update_resource_link_provider
     */
    public function test_update_resource_link(array $updatedata, bool $expectedreturn, array $expectedpropertyvalues): void {
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

        // Create a new resource link for the given placement type.
        $resourcelink = resource_link_manager::create_resource_link('core_ltix:validplacement', 'core_ltix',
            \core\context\course::instance($SITE->id), $toolid, 1, 'http://example.com/tool/1/resource/1',
            'Resource title');

        // Update the resource link.
        $result = resource_link_manager::update_resource_link($resourcelink, $updatedata);

        // Verify the return value.
        $this->assertEquals($expectedreturn, $result);

        // Now, verify the changes.
        $resourcelink = resource_link_manager::get_resource_link_by_id($resourcelink->get('id'));

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
            'Attempting to update disallowed resource link properties' => [
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
     * @return void
     */
    public function test_delete_resource_link(): void {
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

        // Create a resource link.
        $resourcelink = resource_link_manager::create_resource_link('core_ltix:validplacement', 'core_ltix',
            \core\context\course::instance($SITE->id), $toolid, 1, 'http://example.com/tool/1/resource/1',
            'Resource title');

        // Delete the resource link.
        $result = resource_link_manager::delete_resource_link($resourcelink);

        // Verify the return value.
        $this->assertEquals(true, $result);
        // Try to get the resource link.
        $resourcelink = resource_link_manager::get_resource_link_by_id($resourcelink->get('id'));
        // Ensure that resource link no longer exists.
        $this->assertNull($resourcelink);
    }
}
