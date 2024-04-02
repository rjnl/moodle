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

/**
 * This file contains unit tests for (some of) lti/locallib.php
 *
 * @package    mod_lti
 * @category   phpunit
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Charles Severance csev@unmich.edu
 * @author     Marc Alier (marc.alier@upc.edu)
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_lti;

use mod_lti_external;
use mod_lti_testcase;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/mod/lti/locallib.php');
require_once($CFG->dirroot . '/mod/lti/servicelib.php');
require_once($CFG->dirroot . '/mod/lti/tests/mod_lti_testcase.php');

/**
 * Local library tests
 *
 * @package    mod_lti
 * @copyright  Copyright (c) 2012 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class locallib_test extends mod_lti_testcase {

    /**
     * This test has been disabled because the test-tool is
     * being moved and probably it won't work anymore for this.
     * We should be testing here local stuff only and leave
     * outside-checks to the conformance tests. MDL-30347
     */
    public function disabled_test_sign_parameters() {
        $correct = array ( 'context_id' => '12345', 'context_label' => 'SI124', 'context_title' => 'Social Computing',
            'ext_submit' => 'Click Me', 'lti_message_type' => 'basic-lti-launch-request', 'lti_version' => 'LTI-1p0',
            'oauth_consumer_key' => 'lmsng.school.edu', 'oauth_nonce' => '47458148e33a8f9dafb888c3684cf476',
            'oauth_signature' => 'qWgaBIezihCbeHgcwUy14tZcyDQ=', 'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => '1307141660', 'oauth_version' => '1.0', 'resource_link_id' => '123',
            'resource_link_title' => 'Weekly Blog', 'roles' => 'Learner', 'tool_consumer_instance_guid' => 'lmsng.school.edu',
            'user_id' => '789');

        $requestparams = array('resource_link_id' => '123', 'resource_link_title' => 'Weekly Blog', 'user_id' => '789',
            'roles' => 'Learner', 'context_id' => '12345', 'context_label' => 'SI124', 'context_title' => 'Social Computing');

        $parms = \core_ltix\oauth_helper::sign_parameters($requestparams, 'http://www.imsglobal.org/developer/LTI/tool.php', 'POST',
            'lmsng.school.edu', 'secret', 'Click Me', 'lmsng.school.edu' /*, $org_desc*/);
        $this->assertTrue(isset($parms['oauth_nonce']));
        $this->assertTrue(isset($parms['oauth_signature']));
        $this->assertTrue(isset($parms['oauth_timestamp']));

        // Those things that are hard to mock.
        $correct['oauth_nonce'] = $parms['oauth_nonce'];
        $correct['oauth_signature'] = $parms['oauth_signature'];
        $correct['oauth_timestamp'] = $parms['oauth_timestamp'];
        ksort($parms);
        ksort($correct);
        $this->assertEquals($parms, $correct);
    }

    /**
     * This test has been disabled because, since its creation,
     * the sourceId generation has changed and surely this is outdated.
     * Some day these should be replaced by proper tests, but until then
     * conformance tests say this is working. MDL-30347
     */
    public function disabled_test_parse_grade_replace_message() {
        $message = '
            <imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
              <imsx_POXHeader>
                <imsx_POXRequestHeaderInfo>
                  <imsx_version>V1.0</imsx_version>
                  <imsx_messageIdentifier>999998123</imsx_messageIdentifier>
                </imsx_POXRequestHeaderInfo>
              </imsx_POXHeader>
              <imsx_POXBody>
                <replaceResultRequest>
                  <resultRecord>
                    <sourcedGUID>
                      <sourcedId>' .
            '{&quot;data&quot;:{&quot;instanceid&quot;:&quot;2&quot;,&quot;userid&quot;:&quot;2&quot;},&quot;hash&quot;:' .
            '&quot;0b5078feab59b9938c333ceaae21d8e003a7b295e43cdf55338445254421076b&quot;}' .
                      '</sourcedId>
                    </sourcedGUID>
                    <result>
                      <resultScore>
                        <language>en-us</language>
                        <textString>0.92</textString>
                      </resultScore>
                    </result>
                  </resultRecord>
                </replaceResultRequest>
              </imsx_POXBody>
            </imsx_POXEnvelopeRequest>
';

        $parsed = lti_parse_grade_replace_message(new SimpleXMLElement($message));

        $this->assertEquals($parsed->userid, '2');
        $this->assertEquals($parsed->instanceid, '2');
        $this->assertEquals($parsed->sourcedidhash, '0b5078feab59b9938c333ceaae21d8e003a7b295e43cdf55338445254421076b');

        $ltiinstance = (object)array('servicesalt' => '4e5fcc06de1d58.44963230');

        lti_verify_sourcedid($ltiinstance, $parsed);
    }

    /*
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
     * Tests for lti_build_content_item_selection_request().
     */
    public function test_lti_build_content_item_selection_request(): void {
        $this->resetAfterTest();

        $this->setAdminUser();
        // Create a tool proxy.
        $proxy = mod_lti_external::create_tool_proxy('Test proxy', $this->getExternalTestFileUrl('/test.html'), array(), array());

        // Create a tool type, associated with that proxy.
        $type = new \stdClass();
        $data = new \stdClass();
        $data->lti_contentitem = true;
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->toolproxyid = $proxy->id;
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $typeid = \core_ltix\helper::add_type($type, $data);

        $typeconfig = \core_ltix\helper::get_type_config($typeid);

        $course = $this->getDataGenerator()->create_course();
        $returnurl = new \moodle_url('/');

        // Default parameters.
        $result = \core_ltix\helper::build_content_item_selection_request($typeid, $course, $returnurl);
        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result->params);
        $this->assertNotEmpty($result->url);
        $params = $result->params;
        $url = $result->url;
        $this->assertEquals($typeconfig['toolurl'], $url);
        $this->assertEquals('ContentItemSelectionRequest', $params['lti_message_type']);
        $this->assertEquals(LTI_VERSION_1, $params['lti_version']);
        $this->assertEquals('application/vnd.ims.lti.v1.ltilink', $params['accept_media_types']);
        $this->assertEquals('frame,iframe,window', $params['accept_presentation_document_targets']);
        $this->assertEquals($returnurl->out(false), $params['content_item_return_url']);
        $this->assertEquals('false', $params['accept_unsigned']);
        $this->assertEquals('true', $params['accept_multiple']);
        $this->assertEquals('false', $params['accept_copy_advice']);
        $this->assertEquals('false', $params['auto_create']);
        $this->assertEquals($type->name, $params['title']);
        $this->assertFalse(isset($params['resource_link_id']));
        $this->assertFalse(isset($params['resource_link_title']));
        $this->assertFalse(isset($params['resource_link_description']));
        $this->assertFalse(isset($params['launch_presentation_return_url']));
        $this->assertFalse(isset($params['lis_result_sourcedid']));
        $this->assertEquals($params['tool_consumer_instance_guid'], 'www.example.com');

        // Custom parameters.
        $title = 'My custom title';
        $text = 'This is the tool description';
        $mediatypes = ['image/*', 'video/*'];
        $targets = ['embed', 'iframe'];
        $result = \core_ltix\helper::build_content_item_selection_request($typeid, $course, $returnurl, $title, $text, $mediatypes, $targets,
            true, true, true, true, true);
        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result->params);
        $this->assertNotEmpty($result->url);
        $params = $result->params;
        $this->assertEquals(implode(',', $mediatypes), $params['accept_media_types']);
        $this->assertEquals(implode(',', $targets), $params['accept_presentation_document_targets']);
        $this->assertEquals('true', $params['accept_unsigned']);
        $this->assertEquals('true', $params['accept_multiple']);
        $this->assertEquals('true', $params['accept_copy_advice']);
        $this->assertEquals('true', $params['auto_create']);
        $this->assertEquals($title, $params['title']);
        $this->assertEquals($text, $params['text']);

        // Invalid flag values.
        $result = \core_ltix\helper::build_content_item_selection_request($typeid, $course, $returnurl, $title, $text, $mediatypes, $targets,
            'aa', -1, 0, 1, 0xabc);
        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result->params);
        $this->assertNotEmpty($result->url);
        $params = $result->params;
        $this->assertEquals(implode(',', $mediatypes), $params['accept_media_types']);
        $this->assertEquals(implode(',', $targets), $params['accept_presentation_document_targets']);
        $this->assertEquals('false', $params['accept_unsigned']);
        $this->assertEquals('false', $params['accept_multiple']);
        $this->assertEquals('false', $params['accept_copy_advice']);
        $this->assertEquals('false', $params['auto_create']);
        $this->assertEquals($title, $params['title']);
        $this->assertEquals($text, $params['text']);
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
     * @covers core_ltix\helper::get_launch_data()
     *
     * Test for_user is passed as parameter when specified.
     */
    public function test_lti_get_launch_data_with_for_user(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $config = new \stdClass();
        $config->lti_organizationid = '';
        $course = $this->getDataGenerator()->create_course();
        $type = $this->create_type($config);
        $link = $this->create_instance($type, $course);
        $launchdata = \core_ltix\helper::get_launch_data($link, '', '', 345);
        $this->assertEquals($launchdata[1]['lti_message_type'], 'basic-lti-launch-request');
        $this->assertEquals($launchdata[1]['for_user_id'], 345);
    }

    /**
     * Test default orgid is host if not specified in config (tool installed in earlier version of Moodle).
     */
    public function test_lti_get_launch_data_default_organizationid_unset_usehost(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $config = new \stdClass();
        $config->lti_organizationid = '';
        $course = $this->getDataGenerator()->create_course();
        $type = $this->create_type($config);
        $link = $this->create_instance($type, $course);
        $launchdata = \core_ltix\helper::get_launch_data($link);
        $this->assertEquals($launchdata[1]['tool_consumer_instance_guid'], 'www.example.com');
    }

    /**
     * Test default org id is set to host when config is usehost.
     */
    public function test_lti_get_launch_data_default_organizationid_set_usehost(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $config = new \stdClass();
        $config->lti_organizationid = '';
        $config->lti_organizationid_default = LTI_DEFAULT_ORGID_SITEHOST;
        $course = $this->getDataGenerator()->create_course();
        $type = $this->create_type($config);
        $link = $this->create_instance($type, $course);
        $launchdata = \core_ltix\helper::get_launch_data($link);
        $this->assertEquals($launchdata[1]['tool_consumer_instance_guid'], 'www.example.com');
    }

    /**
     * Test default org id is set to site id when config is usesiteid.
     */
    public function test_lti_get_launch_data_default_organizationid_set_usesiteid(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $config = new \stdClass();
        $config->lti_organizationid = '';
        $config->lti_organizationid_default = LTI_DEFAULT_ORGID_SITEID;
        $course = $this->getDataGenerator()->create_course();
        $type = $this->create_type($config);
        $link = $this->create_instance($type, $course);
        $launchdata = \core_ltix\helper::get_launch_data($link);
        $this->assertEquals($launchdata[1]['tool_consumer_instance_guid'], md5(get_site_identifier()));
    }

    /**
     * Test orgid can be overridden in which case default is ignored.
     */
    public function test_lti_get_launch_data_default_organizationid_orgid_override(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $config = new \stdClass();
        $config->lti_organizationid = 'overridden!';
        $config->lti_organizationid_default = LTI_DEFAULT_ORGID_SITEID;
        $course = $this->getDataGenerator()->create_course();
        $type = $this->create_type($config);
        $link = $this->create_instance($type, $course);
        $launchdata = \core_ltix\helper::get_launch_data($link);
        $this->assertEquals($launchdata[1]['tool_consumer_instance_guid'], 'overridden!');
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

    /**
     * Create an LTI Tool.
     *
     * @param object $config tool config.
     *
     * @return object tool.
     */
    private function create_type(object $config) {
        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->clientid = "Test client ID";
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $configbase = new \stdClass();
        $configbase->lti_acceptgrades = LTI_SETTING_NEVER;
        $configbase->lti_sendname = LTI_SETTING_NEVER;
        $configbase->lti_sendemailaddr = LTI_SETTING_NEVER;
        $mergedconfig = (object) array_merge( (array) $configbase, (array) $config);
        $typeid = \core_ltix\helper::add_type($type, $mergedconfig);
        return \core_ltix\helper::get_type($typeid);
    }

    /**
     * Create an LTI Instance for the tool in a given course.
     *
     * @param object $type tool for which an instance should be added.
     * @param object $course course where the instance should be added.
     *
     * @return object instance.
     */
    private function create_instance(object $type, object $course) {
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_lti');
        return $generator->create_instance(array('course' => $course->id,
                  'toolurl' => $type->baseurl,
                  'typeid' => $type->id
                  ), array());
    }

    /**
     * Generate a number of LTI tool types and proxies.
     *
     * @param int $toolandproxycount How many tool types and associated proxies to create. E.g. Value of 10 will create 10 types
     * and 10 proxies.
     * @param int $orphanproxycount How many orphaned proxies to create.
     * @return array[]
     */
    private function generate_tool_types_and_proxies(int $toolandproxycount = 0, int $orphanproxycount = 0) {
        $proxies = [];
        $types = [];
        for ($i = 0; $i < $toolandproxycount; $i++) {
            $proxies[$i] = $this->generate_tool_proxy($i);
            $types[$i] = $this->generate_tool_type($i, $proxies[$i]->id);

        }
        for ($i = $toolandproxycount; $i < ($toolandproxycount + $orphanproxycount); $i++) {
            $proxies[$i] = $this->generate_tool_proxy($i);
        }

        return ['proxies' => $proxies, 'types' => $types];
    }

    /**
     * Test for \core_ltix\helper::get_lti_types_by_course.
     *
     * Note: This includes verification of the broken legacy behaviour in which the inclusion of course and site tools could be
     * controlled independently, based on the capabilities 'mod/lti:addmanualinstance' (to include course tools) and
     * 'mod/lti:addpreconfiguredinstance' (to include site tools). This behaviour is deprecated in 4.3 and all preconfigured tools
     * are controlled by the single capability 'mod/lti:addpreconfiguredinstance'.
     *
     * @covers \core_ltix\helper::get_lti_types_by_course()
     * @return void
     */
    public function test_lti_get_lti_types_by_course(): void {
        $this->resetAfterTest();

        global $DB;
        $coursecat1 = $this->getDataGenerator()->create_category();
        $coursecat2 = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $coursecat1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $coursecat2->id]);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $teacher2 = $this->getDataGenerator()->create_and_enrol($course2, 'editingteacher');

        // Create the following tool types for testing:
        // - Site tool configured as "Do not show" (LTI_COURSEVISIBLE_NO).
        // - Site tool configured as "Show as a preconfigured tool only" (LTI_COURSEVISIBLE_PRECONFIGURED).
        // - Site tool configured as "Show as a preconfigured tool and in the activity chooser" (LTI_COURSEVISIBLE_ACTIVITYCHOOSER).
        // - Course tool which, by default, is configured as LTI_COURSEVISIBLE_ACTIVITYCHOOSER).
        // - Site tool configured to "Show as a preconfigured tool and in the activity chooser" but restricted to a category.

        /** @var \mod_lti_generator $ltigenerator */
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('mod_lti');
        $ltigenerator->create_tool_types([
            'name' => 'site tool do not show',
            'baseurl' => 'http://example.com/tool/1',
            'coursevisible' => LTI_COURSEVISIBLE_NO,
            'state' => LTI_TOOL_STATE_CONFIGURED
        ]);
        $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured only',
            'baseurl' => 'http://example.com/tool/2',
            'coursevisible' => LTI_COURSEVISIBLE_PRECONFIGURED,
            'state' => LTI_TOOL_STATE_CONFIGURED
        ]);
        $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured and activity chooser',
            'baseurl' => 'http://example.com/tool/3',
            'coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER,
            'state' => LTI_TOOL_STATE_CONFIGURED
        ]);
        $ltigenerator->create_course_tool_types([
            'name' => 'course tool preconfigured and activity chooser',
            'baseurl' => 'http://example.com/tool/4',
            'course' => $course->id
        ]);
        $ltigenerator->create_tool_types([
            'name' => 'site tool preconfigured and activity chooser, restricted to category 2',
            'baseurl' => 'http://example.com/tool/5',
            'coursevisible' => LTI_COURSEVISIBLE_ACTIVITYCHOOSER,
            'state' => LTI_TOOL_STATE_CONFIGURED,
            'lti_coursecategories' => $coursecat2->id
        ]);

        $this->setUser($teacher); // Important: this deprecated method depends on the global user for cap checks.

        // Request using the default 'coursevisible' param will include all tools except the one configured as "Do not show".
        $coursetooltypes = \core_ltix\helper::get_lti_types_by_course($course->id, $teacher->id);
        $this->assertCount(3, $coursetooltypes);
        $this->assertEmpty(array_diff(
            ['http://example.com/tool/2', 'http://example.com/tool/3', 'http://example.com/tool/4'],
            array_column($coursetooltypes, 'baseurl')
        ));

        // Request for only those tools configured to show in the activity chooser for the teacher.
        $coursetooltypes = \core_ltix\helper::get_lti_types_by_course($course->id, $teacher->id, [LTI_COURSEVISIBLE_ACTIVITYCHOOSER]);
        $this->assertCount(2, $coursetooltypes);
        $this->assertEmpty(array_diff(
            ['http://example.com/tool/3', 'http://example.com/tool/4'],
            array_column($coursetooltypes, 'baseurl')
        ));

        // Request for only those tools configured to show as a preconfigured tool for the teacher.
        $coursetooltypes = \core_ltix\helper::get_lti_types_by_course($course->id, $teacher->id, [LTI_COURSEVISIBLE_PRECONFIGURED]);
        $this->assertCount(1, $coursetooltypes);
        $this->assertEmpty(array_diff(
            ['http://example.com/tool/2'],
            array_column($coursetooltypes, 'baseurl')
        ));

        // Request for teacher2 in course2 (course category 2).
        $this->setUser($teacher2);
        $coursetooltypes = \core_ltix\helper::get_lti_types_by_course($course2->id, $teacher2->id);
        $this->assertCount(3, $coursetooltypes);
        $this->assertEmpty(array_diff(
            ['http://example.com/tool/2', 'http://example.com/tool/3', 'http://example.com/tool/5'],
            array_column($coursetooltypes, 'baseurl')
        ));

        // Request for a teacher who cannot use preconfigured tools in the course.
        // No tools are available.
        $this->setUser($teacher);
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        assign_capability('mod/lti:addpreconfiguredinstance', CAP_PROHIBIT, $teacherrole->id,
            \core\context\course::instance($course->id));
        $coursetooltypes = \core_ltix\helper::get_lti_types_by_course($course->id, $teacher->id);
        $this->assertCount(0, $coursetooltypes);
        $this->unassignUserCapability('mod/lti:addpreconfiguredinstance', (\core\context\course::instance($course->id))->id,
            $teacherrole->id);
    }
}
