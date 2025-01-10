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
 * OAuth helper tests.
 *
 * @package    core_ltix
 * @author     Alex Morris <alex.morris@catalyst.net.nz>
 * @copyright  2023 onwards Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \core_ltix\oauth_helper
 */
final class oauth_helper_test extends lti_testcase {

    /**
     * Test lti_get_jwt_message_type_mapping().
     */
    public function test_get_jwt_message_type_mapping(): void {
        $mapping = [
            'basic-lti-launch-request' => 'LtiResourceLinkRequest',
            'ContentItemSelectionRequest' => 'LtiDeepLinkingRequest',
            'LtiDeepLinkingResponse' => 'ContentItemSelection',
            'LtiSubmissionReviewRequest' => 'LtiSubmissionReviewRequest'
        ];

        $this->assertEquals($mapping, oauth_helper::get_jwt_message_type_mapping());
    }

    /**
     * Test lti_get_jwt_claim_mapping()
     */
    public function test_get_jwt_claim_mapping(): void {
        $mapping = [
            'accept_copy_advice' => [
                'suffix' => 'dl',
                'group' => 'deep_linking_settings',
                'claim' => 'accept_copy_advice',
                'isarray' => false,
                'type' => 'boolean'
            ],
            'accept_media_types' => [
                'suffix' => 'dl',
                'group' => 'deep_linking_settings',
                'claim' => 'accept_media_types',
                'isarray' => true
            ],
            'accept_multiple' => [
                'suffix' => 'dl',
                'group' => 'deep_linking_settings',
                'claim' => 'accept_multiple',
                'isarray' => false,
                'type' => 'boolean'
            ],
            'accept_presentation_document_targets' => [
                'suffix' => 'dl',
                'group' => 'deep_linking_settings',
                'claim' => 'accept_presentation_document_targets',
                'isarray' => true
            ],
            'accept_types' => [
                'suffix' => 'dl',
                'group' => 'deep_linking_settings',
                'claim' => 'accept_types',
                'isarray' => true
            ],
            'accept_unsigned' => [
                'suffix' => 'dl',
                'group' => 'deep_linking_settings',
                'claim' => 'accept_unsigned',
                'isarray' => false,
                'type' => 'boolean'
            ],
            'auto_create' => [
                'suffix' => 'dl',
                'group' => 'deep_linking_settings',
                'claim' => 'auto_create',
                'isarray' => false,
                'type' => 'boolean'
            ],
            'can_confirm' => [
                'suffix' => 'dl',
                'group' => 'deep_linking_settings',
                'claim' => 'can_confirm',
                'isarray' => false,
                'type' => 'boolean'
            ],
            'content_item_return_url' => [
                'suffix' => 'dl',
                'group' => 'deep_linking_settings',
                'claim' => 'deep_link_return_url',
                'isarray' => false
            ],
            'content_items' => [
                'suffix' => 'dl',
                'group' => '',
                'claim' => 'content_items',
                'isarray' => true
            ],
            'data' => [
                'suffix' => 'dl',
                'group' => 'deep_linking_settings',
                'claim' => 'data',
                'isarray' => false
            ],
            'text' => [
                'suffix' => 'dl',
                'group' => 'deep_linking_settings',
                'claim' => 'text',
                'isarray' => false
            ],
            'title' => [
                'suffix' => 'dl',
                'group' => 'deep_linking_settings',
                'claim' => 'title',
                'isarray' => false
            ],
            'lti_msg' => [
                'suffix' => 'dl',
                'group' => '',
                'claim' => 'msg',
                'isarray' => false
            ],
            'lti_log' => [
                'suffix' => 'dl',
                'group' => '',
                'claim' => 'log',
                'isarray' => false
            ],
            'lti_errormsg' => [
                'suffix' => 'dl',
                'group' => '',
                'claim' => 'errormsg',
                'isarray' => false
            ],
            'lti_errorlog' => [
                'suffix' => 'dl',
                'group' => '',
                'claim' => 'errorlog',
                'isarray' => false
            ],
            'context_id' => [
                'suffix' => '',
                'group' => 'context',
                'claim' => 'id',
                'isarray' => false
            ],
            'context_label' => [
                'suffix' => '',
                'group' => 'context',
                'claim' => 'label',
                'isarray' => false
            ],
            'context_title' => [
                'suffix' => '',
                'group' => 'context',
                'claim' => 'title',
                'isarray' => false
            ],
            'context_type' => [
                'suffix' => '',
                'group' => 'context',
                'claim' => 'type',
                'isarray' => true
            ],
            'lis_course_offering_sourcedid' => [
                'suffix' => '',
                'group' => 'lis',
                'claim' => 'course_offering_sourcedid',
                'isarray' => false
            ],
            'lis_course_section_sourcedid' => [
                'suffix' => '',
                'group' => 'lis',
                'claim' => 'course_section_sourcedid',
                'isarray' => false
            ],
            'launch_presentation_css_url' => [
                'suffix' => '',
                'group' => 'launch_presentation',
                'claim' => 'css_url',
                'isarray' => false
            ],
            'launch_presentation_document_target' => [
                'suffix' => '',
                'group' => 'launch_presentation',
                'claim' => 'document_target',
                'isarray' => false
            ],
            'launch_presentation_height' => [
                'suffix' => '',
                'group' => 'launch_presentation',
                'claim' => 'height',
                'isarray' => false
            ],
            'launch_presentation_locale' => [
                'suffix' => '',
                'group' => 'launch_presentation',
                'claim' => 'locale',
                'isarray' => false
            ],
            'launch_presentation_return_url' => [
                'suffix' => '',
                'group' => 'launch_presentation',
                'claim' => 'return_url',
                'isarray' => false
            ],
            'launch_presentation_width' => [
                'suffix' => '',
                'group' => 'launch_presentation',
                'claim' => 'width',
                'isarray' => false
            ],
            'lis_person_contact_email_primary' => [
                'suffix' => '',
                'group' => null,
                'claim' => 'email',
                'isarray' => false
            ],
            'lis_person_name_family' => [
                'suffix' => '',
                'group' => null,
                'claim' => 'family_name',
                'isarray' => false
            ],
            'lis_person_name_full' => [
                'suffix' => '',
                'group' => null,
                'claim' => 'name',
                'isarray' => false
            ],
            'lis_person_name_given' => [
                'suffix' => '',
                'group' => null,
                'claim' => 'given_name',
                'isarray' => false
            ],
            'lis_person_sourcedid' => [
                'suffix' => '',
                'group' => 'lis',
                'claim' => 'person_sourcedid',
                'isarray' => false
            ],
            'user_id' => [
                'suffix' => '',
                'group' => null,
                'claim' => 'sub',
                'isarray' => false
            ],
            'user_image' => [
                'suffix' => '',
                'group' => null,
                'claim' => 'picture',
                'isarray' => false
            ],
            'roles' => [
                'suffix' => '',
                'group' => '',
                'claim' => 'roles',
                'isarray' => true
            ],
            'role_scope_mentor' => [
                'suffix' => '',
                'group' => '',
                'claim' => 'role_scope_mentor',
                'isarray' => false
            ],
            'deployment_id' => [
                'suffix' => '',
                'group' => '',
                'claim' => 'deployment_id',
                'isarray' => false
            ],
            'lti_message_type' => [
                'suffix' => '',
                'group' => '',
                'claim' => 'message_type',
                'isarray' => false
            ],
            'lti_version' => [
                'suffix' => '',
                'group' => '',
                'claim' => 'version',
                'isarray' => false
            ],
            'resource_link_description' => [
                'suffix' => '',
                'group' => 'resource_link',
                'claim' => 'description',
                'isarray' => false
            ],
            'resource_link_id' => [
                'suffix' => '',
                'group' => 'resource_link',
                'claim' => 'id',
                'isarray' => false
            ],
            'resource_link_title' => [
                'suffix' => '',
                'group' => 'resource_link',
                'claim' => 'title',
                'isarray' => false
            ],
            'tool_consumer_info_product_family_code' => [
                'suffix' => '',
                'group' => 'tool_platform',
                'claim' => 'product_family_code',
                'isarray' => false
            ],
            'tool_consumer_info_version' => [
                'suffix' => '',
                'group' => 'tool_platform',
                'claim' => 'version',
                'isarray' => false
            ],
            'tool_consumer_instance_contact_email' => [
                'suffix' => '',
                'group' => 'tool_platform',
                'claim' => 'contact_email',
                'isarray' => false
            ],
            'tool_consumer_instance_description' => [
                'suffix' => '',
                'group' => 'tool_platform',
                'claim' => 'description',
                'isarray' => false
            ],
            'tool_consumer_instance_guid' => [
                'suffix' => '',
                'group' => 'tool_platform',
                'claim' => 'guid',
                'isarray' => false
            ],
            'tool_consumer_instance_name' => [
                'suffix' => '',
                'group' => 'tool_platform',
                'claim' => 'name',
                'isarray' => false
            ],
            'tool_consumer_instance_url' => [
                'suffix' => '',
                'group' => 'tool_platform',
                'claim' => 'url',
                'isarray' => false
            ],
            'custom_context_memberships_v2_url' => [
                'suffix' => 'nrps',
                'group' => 'namesroleservice',
                'claim' => 'context_memberships_url',
                'isarray' => false
            ],
            'custom_context_memberships_versions' => [
                'suffix' => 'nrps',
                'group' => 'namesroleservice',
                'claim' => 'service_versions',
                'isarray' => true
            ],
            'custom_gradebookservices_scope' => [
                'suffix' => 'ags',
                'group' => 'endpoint',
                'claim' => 'scope',
                'isarray' => true
            ],
            'custom_lineitems_url' => [
                'suffix' => 'ags',
                'group' => 'endpoint',
                'claim' => 'lineitems',
                'isarray' => false
            ],
            'custom_lineitem_url' => [
                'suffix' => 'ags',
                'group' => 'endpoint',
                'claim' => 'lineitem',
                'isarray' => false
            ],
            'custom_results_url' => [
                'suffix' => 'ags',
                'group' => 'endpoint',
                'claim' => 'results',
                'isarray' => false
            ],
            'custom_result_url' => [
                'suffix' => 'ags',
                'group' => 'endpoint',
                'claim' => 'result',
                'isarray' => false
            ],
            'custom_scores_url' => [
                'suffix' => 'ags',
                'group' => 'endpoint',
                'claim' => 'scores',
                'isarray' => false
            ],
            'custom_score_url' => [
                'suffix' => 'ags',
                'group' => 'endpoint',
                'claim' => 'score',
                'isarray' => false
            ],
            'lis_outcome_service_url' => [
                'suffix' => 'bo',
                'group' => 'basicoutcome',
                'claim' => 'lis_outcome_service_url',
                'isarray' => false
            ],
            'lis_result_sourcedid' => [
                'suffix' => 'bo',
                'group' => 'basicoutcome',
                'claim' => 'lis_result_sourcedid',
                'isarray' => false
            ],
            'for_user_id' => [
                'suffix' => '',
                'group' => 'for_user',
                'claim' => 'user_id',
                'isarray' => false
            ],
        ];
        $actual = oauth_helper::get_jwt_claim_mapping();
        $this->assertEquals($mapping, $actual);
    }

    /**
     * Test verify_jwt_signature().
     */
    public function test_verify_jwt_signature(): void {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a tool type, associated with that proxy.
        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $config = new \stdClass();
        $config->lti_publickey = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnzyis1ZjfNB0bBgKFMSv
vkTtwlvBsaJq7S5wA+kzeVOVpVWwkWdVha4s38XM/pa/yr47av7+z3VTmvDRyAHc
aT92whREFpLv9cj5lTeJSibyr/Mrm/YtjCZVWgaOYIhwrXwKLqPr/11inWsAkfIy
tvHWTxZYEcXLgAXFuUuaS3uF9gEiNQwzGTU1v0FqkqTBr4B8nW3HCN47XUu0t8Y0
e+lf4s4OxQawWD79J9/5d3Ry0vbV3Am1FtGJiJvOwRsIfVChDpYStTcHTCMqtvWb
V6L11BWkpzGXSW4Hv43qa+GSYOD2QU68Mb59oSk2OB+BtOLpJofmbGEGgvmwyCI9
MwIDAQAB
-----END PUBLIC KEY-----';

        $config->lti_keytype = LTI_RSA_KEY;

        $typeid = helper::add_type($type, $config);

        oauth_helper::verify_jwt_signature($typeid, '', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4g' .
            'RG9lIiwiYWRtaW4iOnRydWUsImlhdCI6MTUxNjIzOTAyMn0.POstGetfAytaZS82wHcjoTyoqhMyxXiWdR7Nn7A29DNSl0EiXLdwJ6xC6AfgZWF1bOs' .
            'S_TuYI3OG85AmiExREkrS6tDfTQ2B3WXlrr-wp5AokiRbz3_oB4OxG-W9KcEEbDRcZc0nH3L7LzYptiy1PtAylQGxHTWZXtGz4ht0bAecBgmpdgXMgu' .
            'EIcoqPJ1n3pIWk_dUZegpqx0Lka21H6XxUTxiy8OcaarA8zdnPUnV6AmNP3ecFawIFYdvJB_cm-GvpCSbr8G8y_Mllj8f4x9nBH8pQux89_6gUY618iY' .
            'v7tuPWBFfEbLxtF2pZS6YC1aSfLQxeNe8djT9YjpvRZA');
    }

    /**
     * Test lti_verify_jwt_signature_jwk().
     */
    public function test_verify_jwt_signature_jwk(): void {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a tool type, associated with that proxy.
        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $config = new \stdClass();
        $config->lti_publickeyset = $this->getExternalTestFileUrl('/lti_keyset.json');

        $config->lti_keytype = LTI_JWK_KEYSET;

        $typeid = helper::add_type($type, $config);

        $jwt = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6IjU3YzExNzdkMmQ1M2EwMjFjNzM';
        $jwt .= '3NTY0OTFjMTM3YjE3In0.eyJpc3MiOiJnclJvbkd3RTd1WjRwZ28iLCJzdWIiOiJnclJvb';
        $jwt .= 'kd3RTd1WjRwZ28iLCJhdWQiOiJodHRwOi8vbG9jYWxob3N0L21vb2RsZS9tb2QvbHRpL3R';
        $jwt .= 'va2VuLnBocCIsImp0aSI6IjFlMUJPVEczVFJjbFdUem00dERsMGc9PSIsImlhdCI6MTU4M';
        $jwt .= 'Dg1NTUwNX0.Lowhc9ovNAXRb2rkAnv1oozDXlRD54Mz2JS1i8Zx4yGWQzmXzam-La19_g0';
        $jwt .= 'CTnwlKM6gxaInnRKFRAcwhJVcWec389liLAjMbna6d6iTWYTZr7q_4BIe3CT_oTMWASGta';
        $jwt .= 'Paaq53ch1rO4YdueEtmtd1K47ibo4Lhu1jmP_icc3lxjfnqiv4vIYdy7W2JQEzpk1ImuQr';
        $jwt .= 'AlO1xR3fZ6bgcJhVIaw5xoaZD3ZgEjuZOQXMkywv1bL-mL17RX336CzHd8rYZg82QXrBzb';
        $jwt .= 'NWzAlaZxv9VSug8t6mORvM6TkYYWjqEBKemgkD5rNh1BHrPcjWP7vy2Jz7YMjLsmuvDuLK';
        $jwt .= '_PHYIKL--s4gcXWoYmOu1vj-SgoPczTJPoiBD35hAKqVHy5ggHaYHBy95_bbcFd8H1smHw';
        $jwt .= 'pejrAFj1QAwGyTISLzUm08oq7Ak0tSxRKKXw4lpZAka1MmYxO3tJ_3-MXw6Bwz12bNgitJ';
        $jwt .= 'lQd6n3kkGLCJAmANeRkPsH6eZVwF0n2cjh2O1JAwyNcMD2vs4I8ftM1EqqoE2M3r6kt3AC';
        $jwt .= 'EscmqzizI3j80USBCLUUb1UTsfJb2g7oyApJAp-13Q3InR3QyvWO8unG5VraFE7IL5I28h';
        $jwt .= 'MkQAHuCI90DFmXB4leflAu7wNlIK_U8xkGl8X8Mnv6MWgg94Ki8jgIq_kA85JAqI';

        oauth_helper::verify_jwt_signature($typeid, '', $jwt);
    }

    /**
     * Test verify_jwt_signature().
     */
    public function test_verify_jwt_signature_no_consumer_key(): void {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a tool type, associated with that proxy.
        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->clientid = 'consumerkey';
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $config = new \stdClass();
        $typeid = helper::add_type($type, $config);

        $this->expectExceptionMessage(get_string('errorincorrectconsumerkey', 'core_ltix'));
        oauth_helper::verify_jwt_signature($typeid, '', '');
    }

    /**
     * Test verify_jwt_signature().
     */
    public function test_verify_jwt_signature_no_public_key(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a tool type, associated with that proxy.
        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->clientid = 'consumerkey';
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $config = new \stdClass();
        $config->lti_keytype = LTI_RSA_KEY;
        $typeid = helper::add_type($type, $config);

        $this->expectExceptionMessage('No public key configured');
        oauth_helper::verify_jwt_signature($typeid, 'consumerkey', '');
    }

    /**
     * Test sign_jwt().
     */
    public function test_sign_jwt(): void {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a tool type, associated with that proxy.
        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->clientid = 'consumerkey';
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $config = new \stdClass();
        $typeid = helper::add_type($type, $config);

        $params = [];
        $params['roles'] = 'urn:lti:role:ims/lis/testrole,' .
            'urn:lti:instrole:ims/lis/testinstrole,' .
            'urn:lti:sysrole:ims/lis/testsysrole,' .
            'hi';
        $params['accept_copy_advice'] = [
            'suffix' => 'dl',
            'group' => 'deep_linking_settings',
            'claim' => 'accept_copy_advice',
            'isarray' => false
        ];
        $params['lis_result_sourcedid'] = [
            'suffix' => 'bos',
            'group' => 'basicoutcomesservice',
            'claim' => 'lis_result_sourcedid',
            'isarray' => false
        ];
        $endpoint = 'https://www.example.com/moodle';
        $oauthconsumerkey = 'consumerkey';
        $nonce = '';

        $jwt = oauth_helper::sign_jwt($params, $endpoint, $oauthconsumerkey, $typeid, $nonce);

        $this->assertArrayHasKey('id_token', $jwt);
        $this->assertNotEmpty($jwt['id_token']);
    }

    /**
     * Test convert_from_jwt()
     */
    public function test_convert_from_jwt(): void {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a tool type, associated with that proxy.
        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->clientid = 'sso.example.com';
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $config = new \stdClass();
        $config->lti_publickey = '-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnzyis1ZjfNB0bBgKFMSv
vkTtwlvBsaJq7S5wA+kzeVOVpVWwkWdVha4s38XM/pa/yr47av7+z3VTmvDRyAHc
aT92whREFpLv9cj5lTeJSibyr/Mrm/YtjCZVWgaOYIhwrXwKLqPr/11inWsAkfIy
tvHWTxZYEcXLgAXFuUuaS3uF9gEiNQwzGTU1v0FqkqTBr4B8nW3HCN47XUu0t8Y0
e+lf4s4OxQawWD79J9/5d3Ry0vbV3Am1FtGJiJvOwRsIfVChDpYStTcHTCMqtvWb
V6L11BWkpzGXSW4Hv43qa+GSYOD2QU68Mb59oSk2OB+BtOLpJofmbGEGgvmwyCI9
MwIDAQAB
-----END PUBLIC KEY-----';
        $config->lti_keytype = LTI_RSA_KEY;

        $typeid = helper::add_type($type, $config);

        $params = oauth_helper::convert_from_jwt($typeid, 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwib' .
            'mFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWUsImlhdCI6MTUxNjIzOTAyMiwiaXNzIjoic3NvLmV4YW1wbGUuY29tIn0.XURVvEb5ueAvFsn-S9EB' .
            'BSfKbsgUzfRQqmJ6evlrYdx7sXWoZXw1nYjaLTg-mawvBr7MVvrdG9qh6oN8OfkQ7bfMwiz4tjBMJ4B4q_sig5BDYIKwMNjZL5GGCBs89FQrgqZBhxw' .
            '3exTjPBEn69__w40o0AhCsBohPMh0ZsAyHug5dhm8vIuOP667repUJzM8uKCD6L4bEL6vQE8EwU6WQOmfJ2SDmRs-1pFkiaFd6hmPn6AVX7ETtzQmlT' .
            'X-nXe9weQjU1lH4AQG2Yfnn-7lS94bt6E76Zt-XndP3IY7W48EpnRfUK9Ff1fZlomT4MPahdNP1eP8gT2iMz7vYpCfmA');

        $this->assertEquals('sso.example.com', $params['oauth_consumer_key']);
        $this->assertEquals('John Doe', $params['lis_person_name_full']);
    }

    /**
     * Test new_access_token().
     */
    public function test_new_access_token(): void {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a tool type, associated with that proxy.
        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->clientid = "Test client ID";
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $config = new \stdClass();

        $typeid = helper::add_type($type, $config);

        $scopes = ['lti_some_scope', 'lti_another_scope'];

        helper::new_access_token($typeid, $scopes);

        $token = $DB->get_records('lti_access_tokens');
        $this->assertEquals(1, count($token));

        $token = reset($token);

        $this->assertEquals($typeid, $token->typeid);
        $this->assertEquals(json_encode(array_values($scopes)), $token->scope);
        $this->assertEquals($token->timecreated + LTI_ACCESS_TOKEN_LIFE, $token->validuntil);
        $this->assertNull($token->lastaccess);
    }

    /**
     * Test verify_jwt_signature().
     */
    public function test_verify_jwt_signature_with_lti2(): void {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a tool proxy.
        $proxy = $this->generate_tool_proxy('Test proxy', $this->getExternalTestFileUrl('/test.html'));

        // Create a tool type, associated with that proxy.
        $type = new \stdClass();
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->name = "Test tool";
        $type->description = "Example description";
        $type->toolproxyid = $proxy->id;
        $type->baseurl = $this->getExternalTestFileUrl('/test.html');

        $data = new \stdClass();
        $data->lti_contentitem = true;

        $typeid = helper::add_type($type, $data);

        $this->expectExceptionMessage('JWT security not supported with LTI 2');
        oauth_helper::verify_jwt_signature($typeid, '', '');
    }

}
