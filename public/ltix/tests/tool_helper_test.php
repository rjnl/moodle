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

namespace core_ltix;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/ltix/constants.php');

/**
 * Tool helper tests.
 *
 * @package    core_ltix
 * @author     Alex Morris <alex.morris@catalyst.net.nz>
 * @copyright  2023 onwards Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_ltix\tool_helper
 */
class tool_helper_test extends \advanced_testcase {

    /**
     * @covers ::lti_split_parameters()
     *
     * Test the split parameters function
     */
    public function test_split_parameters(): void {
        $this->assertEquals(tool_helper::split_parameters(''), array());
        $this->assertEquals(tool_helper::split_parameters('a=1'), array('a' => '1'));
        $this->assertEquals(tool_helper::split_parameters("a=1\nb=2"), array('a' => '1', 'b' => '2'));
        $this->assertEquals(tool_helper::split_parameters("a=1\n\rb=2"), array('a' => '1', 'b' => '2'));
        $this->assertEquals(tool_helper::split_parameters("a=1\r\nb=2"), array('a' => '1', 'b' => '2'));
    }

    public function test_split_custom_parameters(): void {
        $this->resetAfterTest();

        $tool = new \stdClass();
        $tool->enabledcapability = '';
        $tool->parameter = '';
        $tool->ltiversion = 'LTI-1p0';
        $this->assertEquals(tool_helper::split_custom_parameters(null, $tool, array(), "x=1\ny=2", false),
            array('custom_x' => '1', 'custom_y' => '2'));

        // Check params with caps.
        $this->assertEquals(tool_helper::split_custom_parameters(null, $tool, array(), "X=1", true),
            array('custom_x' => '1', 'custom_X' => '1'));

        // Removed repeat of previous test with a semicolon separator.

        $this->assertEquals(tool_helper::split_custom_parameters(null, $tool, array(), 'Review:Chapter=1.2.56', true),
            array(
                'custom_review_chapter' => '1.2.56',
                'custom_Review:Chapter' => '1.2.56'));

        $this->assertEquals(tool_helper::split_custom_parameters(null, $tool, array(),
            'Complex!@#$^*(){}[]KEY=Complex!@#$^*;(){}[]½Value', true),
            array(
                'custom_complex____________key' => 'Complex!@#$^*;(){}[]½Value',
                'custom_Complex!@#$^*(){}[]KEY' => 'Complex!@#$^*;(){}[]½Value'));

        // Test custom parameter that returns $USER property.
        $user = $this->getDataGenerator()->create_user(array('middlename' => 'SOMETHING'));
        $this->setUser($user);
        $this->assertEquals(array('custom_x' => '1', 'custom_y' => 'SOMETHING'),
            tool_helper::split_custom_parameters(null, $tool, array(), "x=1\ny=\$Person.name.middle", false));
    }

    /**
     * Test convert_content_items().
     */
    public function test_convert_content_items(): void {
        $contentitems = [];
        $contentitems[] = [
            'type' => 'ltiResourceLink',
            'url' => 'http://example.com/messages/launch',
            'title' => 'Test title',
            'text' => 'Test text',
            'iframe' => []
        ];
        $contentitems[] = [
            'type' => 'ltiResourceLink',
            'url' => 'http://example.com/messages/launch2',
            'title' => 'Test title2',
            'text' => 'Test text2',
            'iframe' => [
                'height' => 200,
                'width' => 300
            ],
            'window' => []
        ];
        $contentitems[] = [
            'type' => 'ltiResourceLink',
            'url' => 'http://example.com/messages/launch3',
            'title' => 'Test title3',
            'text' => 'Test text3',
            'window' => [
                'targetName' => 'test-win',
                'height' => 400
            ]
        ];

        $contentitems = json_encode($contentitems);

        $json = tool_helper::convert_content_items($contentitems);

        $jsondecode = json_decode($json);

        $strcontext = '@context';
        $strgraph = '@graph';
        $strtype = '@type';

        $objgraph = new \stdClass();
        $objgraph->url = 'http://example.com/messages/launch';
        $objgraph->title = 'Test title';
        $objgraph->text = 'Test text';
        $objgraph->placementAdvice = new \stdClass();
        $objgraph->placementAdvice->presentationDocumentTarget = 'iframe';
        $objgraph->{$strtype} = 'LtiLinkItem';
        $objgraph->mediaType = 'application\/vnd.ims.lti.v1.ltilink';

        $objgraph2 = new \stdClass();
        $objgraph2->url = 'http://example.com/messages/launch2';
        $objgraph2->title = 'Test title2';
        $objgraph2->text = 'Test text2';
        $objgraph2->placementAdvice = new \stdClass();
        $objgraph2->placementAdvice->presentationDocumentTarget = 'iframe';
        $objgraph2->placementAdvice->displayHeight = 200;
        $objgraph2->placementAdvice->displayWidth = 300;
        $objgraph2->{$strtype} = 'LtiLinkItem';
        $objgraph2->mediaType = 'application\/vnd.ims.lti.v1.ltilink';

        $objgraph3 = new \stdClass();
        $objgraph3->url = 'http://example.com/messages/launch3';
        $objgraph3->title = 'Test title3';
        $objgraph3->text = 'Test text3';
        $objgraph3->placementAdvice = new \stdClass();
        $objgraph3->placementAdvice->presentationDocumentTarget = 'window';
        $objgraph3->placementAdvice->displayHeight = 400;
        $objgraph3->placementAdvice->windowTarget = 'test-win';
        $objgraph3->{$strtype} = 'LtiLinkItem';
        $objgraph3->mediaType = 'application\/vnd.ims.lti.v1.ltilink';

        $expected = new \stdClass();
        $expected->{$strcontext} = 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem';
        $expected->{$strgraph} = [];
        $expected->{$strgraph}[] = $objgraph;
        $expected->{$strgraph}[] = $objgraph2;
        $expected->{$strgraph}[] = $objgraph3;

        $this->assertEquals($expected, $jsondecode);
    }

    /**
     * Test adding a single gradable item through content item.
     */
    public function test_tool_configuration_from_content_item_single_gradable(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $type = new \stdClass();
        $type->name = "Test tool";
        $type->baseurl = "http://example.com";
        $config = new \stdClass();
        $config->lti_acceptgrades = LTI_SETTING_DELEGATE;
        $typeid = types_helper::add_type($type, $config);

        $contentitems = [];
        $contentitems[] = [
            'type' => 'ltiResourceLink',
            'url' => 'http://example.com/messages/launch',
            'title' => 'Test title',
            'lineItem' => [
                'resourceId' => 'r12345',
                'tag' => 'final',
                'scoreMaximum' => 10.0
            ],
            'frame' => []
        ];
        $contentitemsjson13 = json_encode($contentitems);
        $json11 = tool_helper::convert_content_items($contentitemsjson13);

        $config = tool_helper::tool_configuration_from_content_item($typeid,
            'ContentItemSelection',
            $type->ltiversion,
            'ConsumerKey',
            $json11);

        $this->assertEquals($contentitems[0]['url'], $config->toolurl);
        $this->assertEquals(LTI_SETTING_ALWAYS, $config->instructorchoiceacceptgrades);
        $this->assertEquals($contentitems[0]['lineItem']['tag'], $config->lineitemtag);
        $this->assertEquals($contentitems[0]['lineItem']['resourceId'], $config->lineitemresourceid);
        $this->assertEquals($contentitems[0]['lineItem']['scoreMaximum'], $config->grade_modgrade_point);
        $this->assertEquals('', $config->lineitemsubreviewurl);
        $this->assertEquals('', $config->lineitemsubreviewparams);
    }

    /**
     * @covers ::tool_configuration_from_content_item()
     *
     * Test adding a single gradable item through content item with an empty subreview url.
     */
    public function test_tool_configuration_from_content_item_single_gradable_subreview_default_emptyurl(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $type = new \stdClass();
        $type->name = "Test tool";
        $type->baseurl = "http://example.com";
        $config = new \stdClass();
        $config->lti_acceptgrades = LTI_SETTING_DELEGATE;
        $typeid = types_helper::add_type($type, $config);

        $contentitems = [];
        $contentitems[] = [
            'type' => 'ltiResourceLink',
            'url' => 'http://example.com/messages/launch',
            'title' => 'Test title',
            'lineItem' => [
                'resourceId' => 'r12345',
                'tag' => 'final',
                'scoreMaximum' => 10.0,
                'submissionReview' => [
                    'url' => ''
                ]
            ],
            'frame' => []
        ];
        $contentitemsjson13 = json_encode($contentitems);
        $json11 = tool_helper::convert_content_items($contentitemsjson13);

        $config = tool_helper::tool_configuration_from_content_item($typeid,
            'ContentItemSelection',
            $type->ltiversion,
            'ConsumerKey',
            $json11);

        $this->assertEquals('DEFAULT', $config->lineitemsubreviewurl);
        $this->assertEquals('', $config->lineitemsubreviewparams);
    }

    /**
     * @covers ::tool_configuration_from_content_item()
     *
     * Test adding a single gradable item through content item.
     */
    public function test_tool_configuration_from_content_item_single_gradable_subreview_default(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $type = new \stdClass();
        $type->name = "Test tool";
        $type->baseurl = "http://example.com";
        $config = new \stdClass();
        $config->lti_acceptgrades = LTI_SETTING_DELEGATE;
        $typeid = types_helper::add_type($type, $config);

        $contentitems = [];
        $contentitems[] = [
            'type' => 'ltiResourceLink',
            'url' => 'http://example.com/messages/launch',
            'title' => 'Test title',
            'lineItem' => [
                'resourceId' => 'r12345',
                'tag' => 'final',
                'scoreMaximum' => 10.0,
                'submissionReview' => []
            ],
            'frame' => []
        ];
        $contentitemsjson13 = json_encode($contentitems);
        $json11 = tool_helper::convert_content_items($contentitemsjson13);

        $config = tool_helper::tool_configuration_from_content_item($typeid,
            'ContentItemSelection',
            $type->ltiversion,
            'ConsumerKey',
            $json11);

        $this->assertEquals($contentitems[0]['url'], $config->toolurl);
        $this->assertEquals(LTI_SETTING_ALWAYS, $config->instructorchoiceacceptgrades);
        $this->assertEquals($contentitems[0]['lineItem']['tag'], $config->lineitemtag);
        $this->assertEquals($contentitems[0]['lineItem']['resourceId'], $config->lineitemresourceid);
        $this->assertEquals($contentitems[0]['lineItem']['scoreMaximum'], $config->grade_modgrade_point);
        $this->assertEquals('DEFAULT', $config->lineitemsubreviewurl);
        $this->assertEquals('', $config->lineitemsubreviewparams);
    }


    /**
     * Test adding multiple gradable items through content item.
     */
    public function test_tool_configuration_from_content_item_multiple(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $type = new \stdClass();
        $type->name = "Test tool";
        $type->baseurl = "http://example.com";
        $config = new \stdClass();
        $config->lti_acceptgrades = LTI_SETTING_DELEGATE;
        $typeid = types_helper::add_type($type, $config);

        $contentitems = [];
        $contentitems[] = [
            'type' => 'ltiResourceLink',
            'url' => 'http://example.com/messages/launch',
            'title' => 'Test title',
            'text' => 'Test text',
            'icon' => [
                'url' => 'http://lti.example.com/image.jpg',
                'width' => 100
            ],
            'frame' => []
        ];
        $contentitems[] = [
            'type' => 'ltiResourceLink',
            'url' => 'http://example.com/messages/launchgraded',
            'title' => 'Test Graded',
            'lineItem' => [
                'resourceId' => 'r12345',
                'tag' => 'final',
                'scoreMaximum' => 10.0,
                'submissionReview' => [
                    'url' => 'https://testsub.url',
                    'custom' => ['a' => 'b']
                ]
            ],
            'frame' => []
        ];
        $contentitemsjson13 = json_encode($contentitems);
        $json11 = tool_helper::convert_content_items($contentitemsjson13);

        $config = tool_helper::tool_configuration_from_content_item($typeid,
            'ContentItemSelection',
            $type->ltiversion,
            'ConsumerKey',
            $json11);
        $this->assertNotNull($config->multiple);
        $this->assertEquals(2, count( $config->multiple ));
        $this->assertEquals($contentitems[0]['title'], $config->multiple[0]->name);
        $this->assertEquals($contentitems[0]['url'], $config->multiple[0]->toolurl);
        $this->assertEquals(LTI_SETTING_NEVER, $config->multiple[0]->instructorchoiceacceptgrades);
        $this->assertEquals($contentitems[1]['url'], $config->multiple[1]->toolurl);
        $this->assertEquals(LTI_SETTING_ALWAYS, $config->multiple[1]->instructorchoiceacceptgrades);
        $this->assertEquals($contentitems[1]['lineItem']['tag'], $config->multiple[1]->lineitemtag);
        $this->assertEquals($contentitems[1]['lineItem']['resourceId'], $config->multiple[1]->lineitemresourceid);
        $this->assertEquals($contentitems[1]['lineItem']['scoreMaximum'], $config->multiple[1]->grade_modgrade_point);
        $this->assertEquals($contentitems[1]['lineItem']['submissionReview']['url'], $config->multiple[1]->lineitemsubreviewurl);
        $this->assertEquals("a=b", $config->multiple[1]->lineitemsubreviewparams);
    }

    /**
     * Test adding a single non gradable item through content item.
     */
    public function test_tool_configuration_from_content_item_single(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $type = new \stdClass();
        $type->name = "Test tool";
        $type->baseurl = "http://example.com";
        $config = new \stdClass();
        $typeid = types_helper::add_type($type, $config);

        $contentitems = [];
        $contentitems[] = [
            'type' => 'ltiResourceLink',
            'url' => 'http://example.com/messages/launch',
            'title' => 'Test title',
            'text' => 'Test text',
            'icon' => [
                'url' => 'http://lti.example.com/image.jpg',
                'width' => 100
            ],
            'frame' => []
        ];
        $contentitemsjson13 = json_encode($contentitems);
        $json11 = tool_helper::convert_content_items($contentitemsjson13);

        $config = tool_helper::tool_configuration_from_content_item($typeid,
            'ContentItemSelection',
            $type->ltiversion,
            'ConsumerKey',
            $json11);
        $this->assertEquals($contentitems[0]['title'], $config->name);
        $this->assertEquals($contentitems[0]['text'], $config->introeditor['text']);
        $this->assertEquals($contentitems[0]['url'], $config->toolurl);
        $this->assertEquals($contentitems[0]['icon']['url'], $config->icon);
        $this->assertEquals(LTI_SETTING_NEVER, $config->instructorchoiceacceptgrades);

    }

    public function test_ensure_url_is_https(): void {
        $this->assertEquals('https://moodle.org', tool_helper::ensure_url_is_https('http://moodle.org'));
        $this->assertEquals('https://moodle.org', tool_helper::ensure_url_is_https('moodle.org'));
        $this->assertEquals('https://moodle.org', tool_helper::ensure_url_is_https('https://moodle.org'));
    }

    /**
     * Test lti_get_url_thumbprint against various URLs
     */
    public function test_get_url_thumbprint(): void {
        // Note: trailing and double slash are expected right now.  Must evaluate if it must be removed at some point.
        $this->assertEquals('moodle.org/', tool_helper::get_url_thumbprint('http://MOODLE.ORG'));
        $this->assertEquals('moodle.org/', tool_helper::get_url_thumbprint('http://www.moodle.org'));
        $this->assertEquals('moodle.org/', tool_helper::get_url_thumbprint('https://www.moodle.org'));
        $this->assertEquals('moodle.org/', tool_helper::get_url_thumbprint('moodle.org'));
        $this->assertEquals('moodle.org//this/is/moodle', tool_helper::get_url_thumbprint('http://moodle.org/this/is/moodle'));
        $this->assertEquals('moodle.org//this/is/moodle', tool_helper::get_url_thumbprint('https://moodle.org/this/is/moodle'));
        $this->assertEquals('moodle.org//this/is/moodle', tool_helper::get_url_thumbprint('moodle.org/this/is/moodle'));
        $this->assertEquals('moodle.org//this/is/moodle', tool_helper::get_url_thumbprint('moodle.org/this/is/moodle?'));
        $this->assertEquals('moodle.org//this/is/moodle?foo=bar', tool_helper::get_url_thumbprint('moodle.org/this/is/moodle?foo=bar'));
    }

    /**
     * Provider for test_get_best_tool_by_url.
     *
     * @return array of [urlToTest, expectedTool, allTools]
     */
    public static function get_best_tool_by_url_provider(): array {
        $tools = [
            (object) [
                'name' => 'Here',
                'baseurl' => 'https://example.com/i/am/?where=here',
                'tooldomain' => 'example.com',
                'state' => LTI_TOOL_STATE_CONFIGURED,
                'course' => SITEID
            ],
            (object) [
                'name' => 'There',
                'baseurl' => 'https://example.com/i/am/?where=there',
                'tooldomain' => 'example.com',
                'state' => LTI_TOOL_STATE_CONFIGURED,
                'course' => SITEID
            ],
            (object) [
                'name' => 'Not here',
                'baseurl' => 'https://example.com/i/am/?where=not/here',
                'tooldomain' => 'example.com',
                'state' => LTI_TOOL_STATE_CONFIGURED,
                'course' => SITEID
            ],
            (object) [
                'name' => 'Here',
                'baseurl' => 'https://example.com/i/am/',
                'tooldomain' => 'example.com',
                'state' => LTI_TOOL_STATE_CONFIGURED,
                'course' => SITEID
            ],
            (object) [
                'name' => 'Here',
                'baseurl' => 'https://example.com/i/was',
                'tooldomain' => 'example.com',
                'state' => LTI_TOOL_STATE_CONFIGURED,
                'course' => SITEID
            ],
            (object) [
                'name' => 'Here',
                'baseurl' => 'https://badexample.com/i/am/?where=here',
                'tooldomain' => 'badexample.com',
                'state' => LTI_TOOL_STATE_CONFIGURED,
                'course' => SITEID
            ],
        ];

        $data = [
            [
                'url' => $tools[0]->baseurl,
                'expected' => $tools[0],
            ],
            [
                'url' => $tools[1]->baseurl,
                'expected' => $tools[1],
            ],
            [
                'url' => $tools[2]->baseurl,
                'expected' => $tools[2],
            ],
            [
                'url' => $tools[3]->baseurl,
                'expected' => $tools[3],
            ],
            [
                'url' => $tools[4]->baseurl,
                'expected' => $tools[4],
            ],
            [
                'url' => $tools[5]->baseurl,
                'expected' => $tools[5],
            ],
            [
                'url' => 'https://nomatch.com/i/am/',
                'expected' => null
            ],
            [
                'url' => 'https://example.com',
                'expected' => null
            ],
            [
                'url' => 'https://example.com/i/am/?where=unknown',
                'expected' => $tools[3]
            ]
        ];

        // Construct the final array as required by the provider API. Each row
        // of the array contains the URL to test, the expected tool, and
        // the complete list of tools.
        return array_map(function($data) use ($tools) {
            return [$data['url'], $data['expected'], $tools];
        }, $data);
    }

    /**
     * Test get_best_tool_by_url.
     *
     * @dataProvider get_best_tool_by_url_provider
     * @param string $url The URL to test.
     * @param object $expected The expected tool matching the URL.
     * @param array $tools The pool of tools to match the URL with.
     */
    public function test_get_best_tool_by_url($url, $expected, $tools): void {
        $actual = tool_helper::get_best_tool_by_url($url, $tools, null);
        $this->assertSame($expected, $actual);
    }

    /**
     * @covers ::get_tools_by_domain()
     *
     * Test get_tools_by_domain.
     */
    public function test_get_tools_by_domain(): void {
        $this->resetAfterTest();

        /** @var \core_ltix_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        // Create a tool type with good domain.
        $ltigenerator->create_tool_types([
            'name' => 'Test tool 1',
            'description' => 'Good example description',
            'tooldomain' => 'example.com',
            'baseurl' => 'https://example.com/i/am/?where=here',
            'state' => LTI_TOOL_STATE_CONFIGURED
        ]);

        // Create a tool type with bad domain.
        $ltigenerator->create_tool_types([
            'name' => 'Test tool 2',
            'description' => 'Bad example description',
            'tooldomain' => 'badexample.com',
            'baseurl' => 'https://badexample.com/i/am/?where=here',
            'state' => LTI_TOOL_STATE_CONFIGURED
        ]);

        $records = tool_helper::get_tools_by_domain('example.com', LTI_TOOL_STATE_CONFIGURED);
        $this->assertCount(1, $records);
        $this->assertEmpty(array_diff(
            ['https://example.com/i/am/?where=here'],
            array_column($records, 'baseurl')
        ));
    }

    /**
     * @covers ::get_tools_by_domain()
     *
     * Test test_get_tools_by_domain_restrict_types_category.
     */
    public function test_get_tools_by_domain_restrict_types_category(): void {
        $this->resetAfterTest();

        $coursecat1 = $this->getDataGenerator()->create_category();
        $coursecat2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(['category' => $coursecat1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $coursecat2->id]);

        /** @var \core_ltix_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');

        // Create a tool type with domain restricting to a category1.
        $ltigenerator->create_tool_types([
            'name' => 'Test tool 1',
            'description' => 'Good example description',
            'tooldomain' => 'exampleone.com',
            'baseurl' => 'https://exampleone.com/tool/1',
            'state' => LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat1->id
        ]);

        // Create another tool type using the same domain, restricted to category2.
        $ltigenerator->create_tool_types([
            'name' => 'Test tool 1',
            'description' => 'Good example description',
            'tooldomain' => 'exampleone.com',
            'baseurl' => 'https://exampleone.com/tool/2',
            'state' => LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat2->id
        ]);

        // Create a tool type with domain restricting to a category2.
        $ltigenerator->create_tool_types([
            'name' => 'Test tool 2',
            'description' => 'Good example description',
            'tooldomain' => 'exampletwo.com',
            'baseurl' => 'https://exampletwo.com/tool/3',
            'state' => LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat2->id
        ]);

        // Get tool types for domain 'exampleone' in course 1 and verify only the one result under course category 1 is included.
        $records = tool_helper::get_tools_by_domain('exampleone.com', LTI_TOOL_STATE_CONFIGURED, $course1->id);
        $this->assertCount(1, $records);
        $this->assertEmpty(array_diff(
            ['https://exampleone.com/tool/1'],
            array_column($records, 'baseurl')
        ));

        // Get tool types for domain 'exampleone' in course 2 and verify only the one result under course category 2 is included.
        $records = tool_helper::get_tools_by_domain('exampleone.com', LTI_TOOL_STATE_CONFIGURED, $course2->id);
        $this->assertCount(1, $records);
        $this->assertEmpty(array_diff(
            ['https://exampleone.com/tool/2'],
            array_column($records, 'baseurl')
        ));

        // Get tool types for domain 'exampletwo' in course 1 and verify that no results are found.
        $records = tool_helper::get_tools_by_domain('exampletwo.com', LTI_TOOL_STATE_CONFIGURED, $course1->id);
        $this->assertCount(0, $records);
    }

    public function test_get_course_history(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $parentparentcourse = $this->getDataGenerator()->create_course();
        $parentcourse = $this->getDataGenerator()->create_course();
        $parentcourse->originalcourseid = $parentparentcourse->id;
        $DB->update_record('course', $parentcourse);
        $course = $this->getDataGenerator()->create_course();
        $course->originalcourseid = $parentcourse->id;
        $DB->update_record('course', $course);
        $this->assertEquals(tool_helper::get_course_history($parentparentcourse), []);
        $this->assertEquals(tool_helper::get_course_history($parentcourse), [$parentparentcourse->id]);
        $this->assertEquals(tool_helper::get_course_history($course), [$parentcourse->id, $parentparentcourse->id]);
        $course->originalcourseid = 38903;
        $DB->update_record('course', $course);
        $this->assertEquals(tool_helper::get_course_history($course), [38903]);
    }

    /**
     * Verify that empty curl responses lead to the proper moodle_exception, not to XML ValueError.
     *
     * @covers ::load_cartridge()
     */
    public function test_empty_response_load_cartridge(): void {
        // Mock the curl response to empty string, this is hardly
        // reproducible in real life (only Windows + GHA).
        \curl::mock_response('');

        $this->expectException(\moodle_exception::class);
        tool_helper::load_cartridge('http://example.com/mocked/empty/response', []);
    }

}
