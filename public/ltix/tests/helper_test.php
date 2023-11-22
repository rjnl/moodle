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
require_once($CFG->dirroot . '/ltix/tests/lti_testcase.php');

/**
 * Tool helper tests.
 *
 * @package    core_ltix
 * @author     Alex Morris <alex.morris@catalyst.net.nz>
 * @copyright  2023 onwards Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_ltix\tool_helper
 */
class helper_test extends lti_testcase {

    /**
     * @covers ::lti_split_parameters()
     *
     * Test the split parameters function
     */
    public function test_split_parameters(): void {
        $this->assertEquals(helper::split_parameters(''), array());
        $this->assertEquals(helper::split_parameters('a=1'), array('a' => '1'));
        $this->assertEquals(helper::split_parameters("a=1\nb=2"), array('a' => '1', 'b' => '2'));
        $this->assertEquals(helper::split_parameters("a=1\n\rb=2"), array('a' => '1', 'b' => '2'));
        $this->assertEquals(helper::split_parameters("a=1\r\nb=2"), array('a' => '1', 'b' => '2'));
    }

    public function test_split_custom_parameters(): void {
        $this->resetAfterTest();

        $tool = new \stdClass();
        $tool->enabledcapability = '';
        $tool->parameter = '';
        $tool->ltiversion = 'LTI-1p0';
        $this->assertEquals(helper::split_custom_parameters(null, $tool, array(), "x=1\ny=2", false),
            array('custom_x' => '1', 'custom_y' => '2'));

        // Check params with caps.
        $this->assertEquals(helper::split_custom_parameters(null, $tool, array(), "X=1", true),
            array('custom_x' => '1', 'custom_X' => '1'));

        // Removed repeat of previous test with a semicolon separator.

        $this->assertEquals(helper::split_custom_parameters(null, $tool, array(), 'Review:Chapter=1.2.56', true),
            array(
                'custom_review_chapter' => '1.2.56',
                'custom_Review:Chapter' => '1.2.56'));

        $this->assertEquals(helper::split_custom_parameters(null, $tool, array(),
            'Complex!@#$^*(){}[]KEY=Complex!@#$^*;(){}[]½Value', true),
            array(
                'custom_complex____________key' => 'Complex!@#$^*;(){}[]½Value',
                'custom_Complex!@#$^*(){}[]KEY' => 'Complex!@#$^*;(){}[]½Value'));

        // Test custom parameter that returns $USER property.
        $user = $this->getDataGenerator()->create_user(array('middlename' => 'SOMETHING'));
        $this->setUser($user);
        $this->assertEquals(array('custom_x' => '1', 'custom_y' => 'SOMETHING'),
            helper::split_custom_parameters(null, $tool, array(), "x=1\ny=\$Person.name.middle", false));
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

        $json = helper::convert_content_items($contentitems);

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
        $typeid = helper::add_type($type, $config);

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
        $json11 = helper::convert_content_items($contentitemsjson13);

        $config = helper::tool_configuration_from_content_item($typeid,
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
        $typeid = helper::add_type($type, $config);

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
        $json11 = helper::convert_content_items($contentitemsjson13);

        $config = helper::tool_configuration_from_content_item($typeid,
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
        $typeid = helper::add_type($type, $config);

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
        $json11 = helper::convert_content_items($contentitemsjson13);

        $config = helper::tool_configuration_from_content_item($typeid,
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
        $typeid = helper::add_type($type, $config);

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
        $json11 = helper::convert_content_items($contentitemsjson13);

        $config = helper::tool_configuration_from_content_item($typeid,
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
        $typeid = helper::add_type($type, $config);

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
        $json11 = helper::convert_content_items($contentitemsjson13);

        $config = helper::tool_configuration_from_content_item($typeid,
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
        $this->assertEquals('https://moodle.org', helper::ensure_url_is_https('http://moodle.org'));
        $this->assertEquals('https://moodle.org', helper::ensure_url_is_https('moodle.org'));
        $this->assertEquals('https://moodle.org', helper::ensure_url_is_https('https://moodle.org'));
    }

    /**
     * Test lti_get_url_thumbprint against various URLs
     */
    public function test_get_url_thumbprint(): void {
        // Note: trailing and double slash are expected right now.  Must evaluate if it must be removed at some point.
        $this->assertEquals('moodle.org/', helper::get_url_thumbprint('http://MOODLE.ORG'));
        $this->assertEquals('moodle.org/', helper::get_url_thumbprint('http://www.moodle.org'));
        $this->assertEquals('moodle.org/', helper::get_url_thumbprint('https://www.moodle.org'));
        $this->assertEquals('moodle.org/', helper::get_url_thumbprint('moodle.org'));
        $this->assertEquals('moodle.org//this/is/moodle', helper::get_url_thumbprint('http://moodle.org/this/is/moodle'));
        $this->assertEquals('moodle.org//this/is/moodle', helper::get_url_thumbprint('https://moodle.org/this/is/moodle'));
        $this->assertEquals('moodle.org//this/is/moodle', helper::get_url_thumbprint('moodle.org/this/is/moodle'));
        $this->assertEquals('moodle.org//this/is/moodle', helper::get_url_thumbprint('moodle.org/this/is/moodle?'));
        $this->assertEquals('moodle.org//this/is/moodle?foo=bar', helper::get_url_thumbprint('moodle.org/this/is/moodle?foo=bar'));
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
        $actual = helper::get_best_tool_by_url($url, $tools, null);
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

        $records = helper::get_tools_by_domain('example.com', LTI_TOOL_STATE_CONFIGURED);
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
        $records = helper::get_tools_by_domain('exampleone.com', LTI_TOOL_STATE_CONFIGURED, $course1->id);
        $this->assertCount(1, $records);
        $this->assertEmpty(array_diff(
            ['https://exampleone.com/tool/1'],
            array_column($records, 'baseurl')
        ));

        // Get tool types for domain 'exampleone' in course 2 and verify only the one result under course category 2 is included.
        $records = helper::get_tools_by_domain('exampleone.com', LTI_TOOL_STATE_CONFIGURED, $course2->id);
        $this->assertCount(1, $records);
        $this->assertEmpty(array_diff(
            ['https://exampleone.com/tool/2'],
            array_column($records, 'baseurl')
        ));

        // Get tool types for domain 'exampletwo' in course 1 and verify that no results are found.
        $records = helper::get_tools_by_domain('exampletwo.com', LTI_TOOL_STATE_CONFIGURED, $course1->id);
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
        $this->assertEquals(helper::get_course_history($parentparentcourse), []);
        $this->assertEquals(helper::get_course_history($parentcourse), [$parentparentcourse->id]);
        $this->assertEquals(helper::get_course_history($course), [$parentcourse->id, $parentparentcourse->id]);
        $course->originalcourseid = 38903;
        $DB->update_record('course', $course);
        $this->assertEquals(helper::get_course_history($course), [38903]);
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
        helper::load_cartridge('http://example.com/mocked/empty/response', []);
    }

    /**
     * Test fetching tool types for a given course and user.
     *
     * @covers ::override_type_showinactivitychooser
     * @return void.
     */
    public function test_override_type_showinactivitychooser(): void {
        $this->resetAfterTest();

        global $DB;
        $coursecat1 = $this->getDataGenerator()->create_category();
        $coursecat2 = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $coursecat1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $coursecat2->id]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $teacher2 = $this->getDataGenerator()->create_and_enrol($course2, 'editingteacher');
        $context =  \core\context\course::instance($course->id);

        $this->setUser($teacher);

        /*
            Create the following tool types for testing:
            | tooltype | coursevisible                     | restrictedtocategory |
            | site     | LTI_COURSEVISIBLE_NO              |                      |
            | site     | LTI_COURSEVISIBLE_PRECONFIGURED   |                      |
            | site     | LTI_COURSEVISIBLE_ACTIVITYCHOOSER | yes                  |
            | site     | LTI_COURSEVISIBLE_ACTIVITYCHOOSER | yes                  |
            | course   | LTI_COURSEVISIBLE_ACTIVITYCHOOSER |                      |
        */

        /** @var \core_ltix_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');
        $tool1id = $ltigenerator->create_tool_types([
            'name' => 'site tool do not show',
            'baseurl' => 'http://example.com/tool/1',
            'coursevisible' => LTI_COURSEVISIBLE_NO,
            'state' => LTI_TOOL_STATE_CONFIGURED
        ]);
        $tool2id = $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured only',
            'baseurl' => 'http://example.com/tool/2',
            'coursevisible' => LTI_COURSEVISIBLE_PRECONFIGURED,
            'state' => LTI_TOOL_STATE_CONFIGURED
        ]);
        $tool3id = $ltigenerator->create_course_tool_types([
            'name' => 'course tool preconfigured and activity chooser',
            'baseurl' => 'http://example.com/tool/3',
            'course' => $course->id
        ]);
        $tool4id = $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured and activity chooser, restricted to category 2',
            'baseurl' => 'http://example.com/tool/4',
            'coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER,
            'state' => LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat2->id
        ]);
        $tool5id = $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured and activity chooser, restricted to category 1',
            'baseurl' => 'http://example.com/tool/5',
            'coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER,
            'state' => LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat1->id
        ]);

        // LTI_COURSEVISIBLE_NO can't be updated.
        $result = helper::override_type_showinactivitychooser($tool1id, $course->id, $context, true);
        $this->assertFalse($result);

        // Tool not exist.
        $result = helper::override_type_showinactivitychooser($tool5id + 1, $course->id, $context, false);
        $this->assertFalse($result);

        $result = helper::override_type_showinactivitychooser($tool2id, $course->id, $context, true);
        $this->assertTrue($result);
        $coursevisibleoverriden = $DB->get_field('lti_coursevisible', 'coursevisible',
            ['typeid' => $tool2id, 'courseid' => $course->id]);
        $this->assertEquals(LTI_COURSEVISIBLE_ACTIVITYCHOOSER, $coursevisibleoverriden);

        $result = helper::override_type_showinactivitychooser($tool3id, $course->id, $context, false);
        $this->assertTrue($result);
        $coursevisible = $DB->get_field('lti_types', 'coursevisible', ['id' => $tool3id]);
        $this->assertEquals(LTI_COURSEVISIBLE_PRECONFIGURED, $coursevisible);

        // Restricted category no allowed.
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage('You are not allowed to change this setting for this tool.');
        helper::override_type_showinactivitychooser($tool4id, $course->id, $context, false);

        // Restricted category allowed.
        $result = helper::override_type_showinactivitychooser($tool5id, $course->id, $context, false);
        $this->assertTrue($result);
        $coursevisibleoverriden = $DB->get_field('lti_coursevisible', 'coursevisible',
            ['typeid' => $tool5id, 'courseid' => $course->id]);
        $this->assertEquals(LTI_COURSEVISIBLE_PRECONFIGURED, $coursevisibleoverriden);

        $this->setUser($teacher2);
        $this->expectException(\required_capability_exception::class);
        helper::override_type_showinactivitychooser($tool5id, $course->id, $context, false);
    }

    /**
     * Tests prepare_type_for_save's handling of the "Force SSL" configuration.
     */
    public function test_prepare_type_for_save_forcessl(): void {
        $type = new \stdClass();
        $config = new \stdClass();

        // Try when the forcessl config property is not set.
        helper::prepare_type_for_save($type, $config);
        $this->assertObjectHasAttribute('lti_forcessl', $config);
        $this->assertEquals(0, $config->lti_forcessl);
        $this->assertEquals(0, $type->forcessl);

        // Try when forcessl config property is set.
        $config->lti_forcessl = 1;
        helper::prepare_type_for_save($type, $config);
        $this->assertObjectHasAttribute('lti_forcessl', $config);
        $this->assertEquals(1, $config->lti_forcessl);
        $this->assertEquals(1, $type->forcessl);

        // Try when forcessl config property is set to 0.
        $config->lti_forcessl = 0;
        helper::prepare_type_for_save($type, $config);
        $this->assertObjectHasAttribute('lti_forcessl', $config);
        $this->assertEquals(0, $config->lti_forcessl);
        $this->assertEquals(0, $type->forcessl);
    }

    /**
     * Tests load_type_from_cartridge and lti_load_type_if_cartridge
     */
    public function test_load_type_from_cartridge(): void {
        $type = new \stdClass();
        $type->lti_toolurl = $this->getExternalTestFileUrl('/ims_cartridge_basic_lti_link.xml');

        helper::load_type_if_cartridge($type);

        $this->assertEquals('Example tool', $type->lti_typename);
        $this->assertEquals('Example tool description', $type->lti_description);
        $this->assertEquals('http://www.example.com/lti/provider.php', $type->lti_toolurl);
        $this->assertEquals('http://download.moodle.org/unittest/test.jpg', $type->lti_icon);
        $this->assertEquals('https://download.moodle.org/unittest/test.jpg', $type->lti_secureicon);
    }

    /**
     * Test get_lti_types_and_proxies with no limit or offset.
     */
    public function test_get_lti_types_and_proxies_with_no_limit(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->generate_tool_types_and_proxies(10);
        list($proxies, $types) = helper::get_lti_types_and_proxies();

        $this->assertCount(10, $proxies);
        $this->assertCount(10, $types);
    }

    /**
     * Test get_lti_types_and_proxies with limits.
     */
    public function test_get_lti_types_and_proxies_with_limit(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->generate_tool_types_and_proxies(10);

        // Get the middle 10 data sets (of 20 total).
        list($proxies, $types) = helper::get_lti_types_and_proxies(10, 5);

        $this->assertCount(5, $proxies);
        $this->assertCount(5, $types);

        // Get the last 5 data sets with large limit (of 20 total).
        list($proxies, $types) = helper::get_lti_types_and_proxies(50, 15);

        $this->assertCount(0, $proxies);
        $this->assertCount(5, $types);

        // Get the last 13 data sets with large limit (of 20 total).
        list($proxies, $types) = helper::get_lti_types_and_proxies(50, 7);

        $this->assertCount(3, $proxies);
        $this->assertCount(10, $types);
    }

    /**
     * Test get_lti_types_and_proxies with limits and only fetching orphaned proxies.
     */
    public function test_get_lti_types_and_proxies_with_limit_and_orphaned_proxies(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->generate_tool_types_and_proxies(10, 5);

        // Get the first 10 data sets (of 15 total).
        list($proxies, $types) = helper::get_lti_types_and_proxies(10, 0, true);

        $this->assertCount(5, $proxies);
        $this->assertCount(5, $types);

        // Get the middle 10 data sets with large limit (of 15 total).
        list($proxies, $types) = helper::get_lti_types_and_proxies(10, 2, true);

        $this->assertCount(3, $proxies);
        $this->assertCount(7, $types);

        // Get the last 5 data sets with large limit (of 15 total).
        list($proxies, $types) = helper::get_lti_types_and_proxies(50, 10, true);

        $this->assertCount(0, $proxies);
        $this->assertCount(5, $types);
    }

    /**
     * Test get_lti_types_and_proxies_count.
     */
    public function test_get_lti_types_and_proxies_count_with_no_filters(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->generate_tool_types_and_proxies(10, 5);

        $totalcount = helper::get_lti_types_and_proxies_count();
        $this->assertEquals(25, $totalcount); // 10 types, 15 proxies.
    }

    /**
     * Test get_lti_types_and_proxies_count only counting orphaned proxies.
     */
    public function test_get_lti_types_and_proxies_count_with_only_orphaned_proxies(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->generate_tool_types_and_proxies(10, 5);

        $orphanedcount = helper::get_lti_types_and_proxies_count(true);
        $this->assertEquals(15, $orphanedcount); // 10 types, 5 proxies.
    }

    /**
     * Test get_lti_types_and_proxies_count only matching tool type with toolproxyid.
     */
    public function test_get_lti_types_and_proxies_count_type_with_proxyid(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        ['proxies' => $proxies, 'types' => $types] = $this->generate_tool_types_and_proxies(10, 5);

        $countwithproxyid = helper::get_lti_types_and_proxies_count(false, $proxies[0]->id);
        $this->assertEquals(16, $countwithproxyid); // 1 type, 15 proxies.
    }

    /**
     * Verify that lti_build_request does handle resource_link_id as expected
     */
    public function test_lti_buid_request_resource_link_id(): void {
        $this->resetAfterTest();

        self::setUser($this->getDataGenerator()->create_user());
        $course   = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('lti', array(
            'intro'       => "<p>This</p>\nhas\r\n<p>some</p>\nnew\n\rlines",
            'introformat' => FORMAT_HTML,
            'course'      => $course->id,
        ));

        $typeconfig = array(
            'acceptgrades'     => 1,
            'forcessl'         => 0,
            'sendname'         => 2,
            'sendemailaddr'    => 2,
            'customparameters' => '',
        );

        // Normal call, we expect $instance->id to be used as resource_link_id.
        $params = \core_ltix\helper::build_request($instance, $typeconfig, $course, null);
        $this->assertSame($instance->id, $params['resource_link_id']);

        // If there is a resource_link_id set, it gets precedence.
        $instance->resource_link_id = $instance->id + 99;
        $params = \core_ltix\helper::build_request($instance, $typeconfig, $course, null);
        $this->assertSame($instance->resource_link_id, $params['resource_link_id']);

        // With none set, resource_link_id is not set either.
        unset($instance->id);
        unset($instance->resource_link_id);
        $params = \core_ltix\helper::build_request($instance, $typeconfig, $course, null);
        $this->assertArrayNotHasKey('resource_link_id', $params);
    }

    /**
     * Test lti_build_request's resource_link_description and ensure
     * that the newlines in the description are correct.
     */
    public function test_lti_build_request_description(): void {
        $this->resetAfterTest();

        self::setUser($this->getDataGenerator()->create_user());
        $course   = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('lti', array(
            'intro'       => "<p>This</p>\nhas\r\n<p>some</p>\nnew\n\rlines",
            'introformat' => FORMAT_HTML,
            'course'      => $course->id,
        ));

        $typeconfig = array(
            'acceptgrades'     => 1,
            'forcessl'         => 0,
            'sendname'         => 2,
            'sendemailaddr'    => 2,
            'customparameters' => '',
        );

        $params = \core_ltix\helper::build_request($instance, $typeconfig, $course, null);

        $ncount = substr_count($params['resource_link_description'], "\n");
        $this->assertGreaterThan(0, $ncount);

        $rcount = substr_count($params['resource_link_description'], "\r");
        $this->assertGreaterThan(0, $rcount);

        $this->assertEquals($ncount, $rcount, 'The number of \n characters should be the same as the number of \r characters');

        $rncount = substr_count($params['resource_link_description'], "\r\n");
        $this->assertGreaterThan(0, $rncount);

        $this->assertEquals($ncount, $rncount, 'All newline characters should be a combination of \r\n');
    }

    /**
     * Tests lti_load_tool_from_cartridge and lti_load_tool_if_cartridge
     */
    public function test_lti_load_tool_from_cartridge(): void {
        $lti = new \stdClass();
        $lti->toolurl = $this->getExternalTestFileUrl('/ims_cartridge_basic_lti_link.xml');

        \core_ltix\helper::load_tool_if_cartridge($lti);

        $this->assertEquals('Example tool', $lti->name);
        $this->assertEquals('Example tool description', $lti->intro);
        $this->assertEquals('http://www.example.com/lti/provider.php', $lti->toolurl);
        $this->assertEquals('https://www.example.com/lti/provider.php', $lti->securetoolurl);
        $this->assertEquals('http://download.moodle.org/unittest/test.jpg', $lti->icon);
        $this->assertEquals('https://download.moodle.org/unittest/test.jpg', $lti->secureicon);
    }
    /**
     * Test for lti_build_content_item_selection_request() with nonexistent tool type ID parameter.
     */
    public function test_lti_build_content_item_selection_request_invalid_tooltype(): void {
        $this->resetAfterTest();

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $returnurl = new \moodle_url('/');

        // Should throw Exception on non-existent tool type.
        $this->expectException('moodle_exception');
        \core_ltix\helper::build_content_item_selection_request(1, $course, $returnurl);
    }

    /**
     * Test for lti_build_content_item_selection_request() with invalid media types parameter.
     */
    public function test_lti_build_content_item_selection_request_invalid_mediatypes(): void {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a tool type, associated with that proxy.
        $type = new \stdClass();
        $data = new \stdClass();
        $data->lti_contentitem = true;
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $typeid = \core_ltix\helper::add_type($type, $data);
        $course = $this->getDataGenerator()->create_course();
        $returnurl = new \moodle_url('/');

        // Should throw coding_exception on non-array media types.
        $mediatypes = 'image/*,video/*';
        $this->expectException('coding_exception');
        \core_ltix\helper::build_content_item_selection_request($typeid, $course, $returnurl, '', '', $mediatypes);
    }

    /**
     * Test for lti_build_content_item_selection_request() with invalid presentation targets parameter.
     */
    public function test_lti_build_content_item_selection_request_invalid_presentationtargets(): void {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a tool type, associated with that proxy.
        $type = new \stdClass();
        $data = new \stdClass();
        $data->lti_contentitem = true;
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $typeid = \core_ltix\helper::add_type($type, $data);
        $course = $this->getDataGenerator()->create_course();
        $returnurl = new \moodle_url('/');

        // Should throw coding_exception on non-array presentation targets.
        $targets = 'frame,iframe';
        $this->expectException('coding_exception');
        \core_ltix\helper::build_content_item_selection_request($typeid, $course, $returnurl, '', '', [], $targets);
    }

    /**
     * Test lti_build_standard_message().
     */
    public function test_lti_build_standard_message_institution_name_set(): void {
        global $CFG;

        $this->resetAfterTest();

        $CFG->mod_lti_institution_name = 'some institution name lols';

        $course   = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('lti',
            [
                'course' => $course->id,
            ]
        );

        $message = \core_ltix\helper::build_standard_message($instance, '2', LTI_VERSION_1);

        $this->assertEquals('moodle-2', $message['ext_lms']);
        $this->assertEquals('moodle', $message['tool_consumer_info_product_family_code']);
        $this->assertEquals(LTI_VERSION_1, $message['lti_version']);
        $this->assertEquals('basic-lti-launch-request', $message['lti_message_type']);
        $this->assertEquals('2', $message['tool_consumer_instance_guid']);
        $this->assertEquals('some institution name lols', $message['tool_consumer_instance_name']);
        $this->assertEquals('PHPUnit test site', $message['tool_consumer_instance_description']);
    }

    /**
     * Test lti_build_standard_message().
     */
    public function test_lti_build_standard_message_institution_name_not_set(): void {
        $this->resetAfterTest();

        $course   = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('lti',
            [
                'course' => $course->id,
            ]
        );

        $message = \core_ltix\helper::build_standard_message($instance, '2', LTI_VERSION_2);

        $this->assertEquals('moodle-2', $message['ext_lms']);
        $this->assertEquals('moodle', $message['tool_consumer_info_product_family_code']);
        $this->assertEquals(LTI_VERSION_2, $message['lti_version']);
        $this->assertEquals('basic-lti-launch-request', $message['lti_message_type']);
        $this->assertEquals('2', $message['tool_consumer_instance_guid']);
        $this->assertEquals('phpunit', $message['tool_consumer_instance_name']);
        $this->assertEquals('PHPUnit test site', $message['tool_consumer_instance_description']);
    }

    /**
     * Test lti_get_permitted_service_scopes().
     */
    public function test_lti_get_permitted_service_scopes(): void {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a tool type, associated with that proxy.
        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $typeconfig = new \stdClass();
        $typeconfig->lti_acceptgrades = true;

        $typeid = \core_ltix\helper::add_type($type, $typeconfig);

        $tool = \core_ltix\helper::get_type($typeid);

        $config = \core_ltix\helper::get_type_config($typeid);
        $permittedscopes = \core_ltix\helper::get_permitted_service_scopes($tool, $config);

        $expected = [
            'https://purl.imsglobal.org/spec/lti-bo/scope/basicoutcome'
        ];
        $this->assertEquals($expected, $permittedscopes);
    }

    /**
     * Test lti_build_login_request().
     */
    public function test_lti_build_login_request(): void {
        global $USER, $CFG;

        $this->resetAfterTest();

        $USER->id = 123456789;

        $course   = $this->getDataGenerator()->create_course();
        $instance = $this->getDataGenerator()->create_module('lti',
            [
                'course' => $course->id,
            ]
        );

        $config = new \stdClass();
        $config->lti_clientid = 'some-client-id';
        $config->typeid = 'some-type-id';
        $config->lti_toolurl = 'some-lti-tool-url';

        $request = \core_ltix\helper::build_login_request($course->id, $instance->cmid, $instance, $config, 'basic-lti-launch-request');
        $this->assertEquals($CFG->wwwroot, $request['iss']);
        $this->assertEquals('http://some-lti-tool-url', $request['target_link_uri']);
        $this->assertEquals(123456789, $request['login_hint']);
        $this->assertTrue(strpos($request['lti_message_hint'], "\"cmid\":{$instance->cmid}") > 0);
        $this->assertTrue(strpos($request['lti_message_hint'],  "\"launchid\":\"ltilaunch{$instance->id}_") > 0);
        $this->assertEquals('some-client-id', $request['client_id']);
        $this->assertEquals('some-type-id', $request['lti_deployment_id']);
    }


    /**
     * Test the lti_get_ims_role helper function.
     *
     * @dataProvider lti_get_ims_role_provider
     * @covers ::lti_get_ims_role()
     *
     * @param bool $islti2 whether the method is called with LTI 2.0 role names or not.
     * @param string $rolename the name of the role (student, teacher, admin)
     * @param null|string $switchedto the role to switch to, or false if not using the 'switch to' functionality.
     * @param string $expected the expected role name.
     */
    public function test_lti_get_ims_role(bool $islti2, string $rolename, ?string $switchedto, string $expected): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $rolename == 'admin' ? get_admin() : $this->getDataGenerator()->create_and_enrol($course, $rolename);

        if ($switchedto) {
            $this->setUser($user);
            $role = $DB->get_record('role', array('shortname' => $switchedto));
            role_switch($role->id, \context_course::instance($course->id));
        }

        $this->assertEquals($expected, \core_ltix\helper::get_ims_role($user, 0, $course->id, $islti2));
    }

    /**
     * Data provider for testing lti_get_ims_role.
     *
     * @return array[] the test case data.
     */
    public static function lti_get_ims_role_provider(): array {
        return [
            'Student, LTI 1.1, no role switch' => [
                'islti2' => false,
                'rolename' => 'student',
                'switchedto' => null,
                'expected' => 'Learner'
            ],
            'Student, LTI 2.0, no role switch' => [
                'islti2' => true,
                'rolename' => 'student',
                'switchedto' => null,
                'expected' => 'Learner'
            ],
            'Teacher, LTI 1.1, no role switch' => [
                'islti2' => false,
                'rolename' => 'editingteacher',
                'switchedto' => null,
                'expected' => 'Instructor'
            ],
            'Teacher, LTI 2.0, no role switch' => [
                'islti2' => true,
                'rolename' => 'editingteacher',
                'switchedto' => null,
                'expected' => 'Instructor'
            ],
            'Admin, LTI 1.1, no role switch' => [
                'islti2' => false,
                'rolename' => 'admin',
                'switchedto' => null,
                'expected' => 'Instructor,urn:lti:sysrole:ims/lis/Administrator,urn:lti:instrole:ims/lis/Administrator'
            ],
            'Admin, LTI 2.0, no role switch' => [
                'islti2' => true,
                'rolename' => 'admin',
                'switchedto' => null,
                'expected' => 'Instructor,http://purl.imsglobal.org/vocab/lis/v2/person#Administrator'
            ],
            'Admin, LTI 1.1, role switch student' => [
                'islti2' => false,
                'rolename' => 'admin',
                'switchedto' => 'student',
                'expected' => 'Learner'
            ],
            'Admin, LTI 2.0, role switch student' => [
                'islti2' => true,
                'rolename' => 'admin',
                'switchedto' => 'student',
                'expected' => 'Learner'
            ],
            'Admin, LTI 1.1, role switch teacher' => [
                'islti2' => false,
                'rolename' => 'admin',
                'switchedto' => 'editingteacher',
                'expected' => 'Instructor'
            ],
            'Admin, LTI 2.0, role switch teacher' => [
                'islti2' => true,
                'rolename' => 'admin',
                'switchedto' => 'editingteacher',
                'expected' => 'Instructor'
            ],
        ];
    }

}
