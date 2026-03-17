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

namespace core_ltix\local\lticore\message\payload;

use core_ltix\oauth_helper;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering lti_1px_payload_converter.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(lti_1px_payload_converter::class)]
final class lti_1px_payload_converter_test extends \basic_testcase {

    /**
     * Test v1p1->v1p3 conversion.
     *
     * @param array $params the params to convert.
     * @param array $expected the expected array of claims.
     * @return void
     */
    #[DataProvider('params_to_claims_provider')]
    public function test_params_to_claims(array $params, array $expected): void {
        $converted = (new lti_1px_payload_converter(new lis_vocab_converter()))->params_to_claims($params);
        $this->assertEquals($expected, $converted);
    }

    /**
     * Provider for testing params_to_claims().
     *
     * @return array the test data.
     */
    public static function params_to_claims_provider(): array {
        return [
            'Example based on payload for a Lti Resource Link Launch, using v2 context types and roles' => [
                'params' => [
                    'user_id' => '2',
                    'lis_person_sourcedid' => 'abc2f1',
                    'roles' => 'Instructor,Instructor/ExternalInstructor,http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator,http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
                    'context_id' => '2',
                    'context_label' => 'Test course 1',
                    'context_title' => 'Test course 1',
                    'lti_message_type' => 'LtiResourceLinkRequest',
                    'resource_link_title' => 'assignment 1',
                    'resource_link_description' => '',
                    'resource_link_id' => '2',
                    'context_type' => 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection',
                    'lis_course_section_sourcedid' => '',
                    'lis_result_sourcedid' => '{"data":{"instanceid":"2","userid":"2","typeid":"3","launchid":1131988845},"hash":"03a9c7771318f0986ad07452910b727440f5dc704755e5313f138cd5a3c6a0f8"}',
                    'lis_outcome_service_url' => 'https://lms.example.com/lti/service',
                    'lis_person_name_given' => 'Admin',
                    'lis_person_name_family' => 'User',
                    'lis_person_name_full' => 'Admin User',
                    'ext_user_username' => 'admin',
                    'lis_person_contact_email_primary' => 'admin@example.com',
                    'launch_presentation_locale' => 'en',
                    'ext_lms' => 'moodle-2',
                    'tool_consumer_info_product_family_code' => 'moodle',
                    'tool_consumer_info_version' => '2025041401.07',
                    'lti_version' => '1.3.0',
                    'tool_consumer_instance_guid' => '3edd2c9e4820ec0ac5d875bc636a70cd',
                    'tool_consumer_instance_name' => 's500',
                    'tool_consumer_instance_description' => 'Stable 500 PostgreSQL',
                    'custom_id' => 'eab7d550-6b11-48e1-8e09-44adc609af76',
                    'launch_presentation_document_target' => 'iframe',
                    'launch_presentation_return_url' => 'https://lms.example.com/lti/return',
                    'custom_gradebookservices_scope' => 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly,https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly,https://purl.imsglobal.org/spec/lti-ags/scope/score,https://purl.imsglobal.org/spec/lti-ags/scope/lineitem',
                    'custom_lineitems_url' => 'https://lms.example.com/lti/lineitems/2',
                    'custom_lineitem_url' => 'https://lms.example.com/lti/lineitems/2/lineitem',
                    'custom_context_memberships_url' => 'https://lms.example.com/lti/service/memberships/3/',
                    'custom_context_memberships_v2_url' => 'https://lms.example.com/lti/service/memberships/3/',
                    'custom_context_memberships_versions' => '1.0,2.0',
                ],
                'expected' => [
                    'sub' => '2',
                    'https://purl.imsglobal.org/spec/lti/claim/lis' => [
                        'person_sourcedid' => 'abc2f1',
                        'course_section_sourcedid' => '',
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/roles' => [
                        'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
                        'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#ExternalInstructor',
                        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator',
                        'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/context' => [
                        'id' => '2',
                        'label' => 'Test course 1',
                        'title' => 'Test course 1',
                        'type' => [
                            'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection',
                        ],
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest',
                    'https://purl.imsglobal.org/spec/lti/claim/resource_link' => [
                        'title' => 'assignment 1',
                        'description' => '',
                        'id' => '2',
                    ],
                    'https://purl.imsglobal.org/spec/lti-bo/claim/basicoutcome' => [
                        'lis_result_sourcedid' => '{"data":{"instanceid":"2","userid":"2","typeid":"3","launchid":1131988845},"hash":"03a9c7771318f0986ad07452910b727440f5dc704755e5313f138cd5a3c6a0f8"}',
                        'lis_outcome_service_url' => 'https://lms.example.com/lti/service',
                    ],
                    'given_name' => 'Admin',
                    'family_name' => 'User',
                    'name' => 'Admin User',
                    'https://purl.imsglobal.org/spec/lti/claim/ext' => [
                        'user_username' => 'admin',
                        'lms' => 'moodle-2',
                    ],
                    'email' => 'admin@example.com',
                    'https://purl.imsglobal.org/spec/lti/claim/launch_presentation' => [
                        'locale' => 'en',
                        'document_target' => 'iframe',
                        'return_url' => 'https://lms.example.com/lti/return',
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/tool_platform' => [
                        'product_family_code' => 'moodle',
                        'version' => '2025041401.07',
                        'guid' => '3edd2c9e4820ec0ac5d875bc636a70cd',
                        'name' => 's500',
                        'description' => 'Stable 500 PostgreSQL',
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
                    'https://purl.imsglobal.org/spec/lti/claim/custom' => [
                        'id' => 'eab7d550-6b11-48e1-8e09-44adc609af76',
                        'context_memberships_url' => 'https://lms.example.com/lti/service/memberships/3/',
                    ],
                    'https://purl.imsglobal.org/spec/lti-ags/claim/endpoint' => [
                        'scope' => [
                            'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem',
                            'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly',
                            'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly',
                            'https://purl.imsglobal.org/spec/lti-ags/scope/score',
                        ],
                        'lineitems' => 'https://lms.example.com/lti/lineitems/2',
                        'lineitem' => 'https://lms.example.com/lti/lineitems/2/lineitem',
                    ],
                    'https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice' => [
                        'context_memberships_url' => 'https://lms.example.com/lti/service/memberships/3/',
                        'service_versions' => [
                            '1.0',
                            '2.0',
                        ],
                    ],
                ],
            ],
            'Example showing the handling of non-mappable params' => [
                'params' => [
                    'user_id' => '2',
                    'oauth_callback' => 'example',
                ],
                'expected' => [
                    'sub' => '2',
                ],
            ],
            'Example showing the handling of empty params' => [
                'params' => [],
                'expected' => [],
            ],
            'Example showing that special characters are preserved' => [
                'params' => [
                    'user_id' => '7',
                    'context_label' => 'Cours de Français',
                    'resource_link_title' => 'Assignation #1 & #2',
                    'lis_person_name_given' => 'François',
                    'lis_person_contact_email_primary' => 'françois@example.com',
                ],
                'expected' => [
                    'sub' => '7',
                    'https://purl.imsglobal.org/spec/lti/claim/context' => [
                        'label' => 'Cours de Français',
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/resource_link' => [
                        'title' => 'Assignation #1 & #2',
                    ],
                    'given_name' => 'François',
                    'email' => 'françois@example.com',
                ],
            ],
            'Example showing that vocabulary conversion to v2 message type, context type and roles takes place' => [
                'params' => [
                    'user_id' => '7',
                    'roles' => 'Instructor,urn:lti:sysrole:ims/lis/Administrator,urn:lti:instrole:ims/lis/Administrator',
                    'context_type' => 'CourseSection',
                    'lti_message_type' => 'basic-lti-launch-request',
                ],
                'expected' => [
                    'sub' => '7',
                    'https://purl.imsglobal.org/spec/lti/claim/roles' => [
                        'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
                        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator',
                        'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/context' => [
                        'type' => [
                            'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection',
                        ]
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest',
                ]
            ],
            'Example showing that boolean string params are converted to boolean type claims' => [
                'params' => [
                    'accept_copy_advice' => 'true',
                    'accept_multiple' => 'false',
                ],
                'expected' => [
                    'https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings' => [
                        'accept_copy_advice' => true,
                        'accept_multiple' => false,
                    ],
                ],
            ],
            'Example showing that an unrecognised lti_message_type param is passed through unchanged' => [
                'params' => [
                    'lti_message_type' => 'SomeUnrecognisedMessageType',
                ],
                'expected' => [
                    'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'SomeUnrecognisedMessageType',
                ],
            ],
        ];
    }

    /**
     * Test v1p3->v1p1 conversion.
     *
     * @param array $claims the input claims array.
     * @param array $expected the expected params array.
     * @return void
     */
    #[DataProvider('claims_to_params_provider')]
    public function test_claims_to_params(array $claims, array $expected): void {
        $legacypayload = (new lti_1px_payload_converter(new lis_vocab_converter()))->claims_to_params($claims);
        $this->assertEquals($expected, $legacypayload);
    }

    /**
     * Provider for testing claims_to_params().
     *
     * @return array the test data.
     */
    public static function claims_to_params_provider(): array {
        return [
            'Sample, based on payload required for a deep link launch, all claims mappable' => [
                'claims' => [
                    'sub' => '2',
                    'https://purl.imsglobal.org/spec/lti/claim/lis' => [
                        'person_sourcedid' => 'USERIDNUM-456',
                        'course_section_sourcedid' => 'COURSEIDNUM-678',
                    ],
                    // Note: array type claims are sorted during conversion, so for comparison to work, this must be a sorted array.
                    'https://purl.imsglobal.org/spec/lti/claim/roles' => [
                        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator',
                        'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
                        'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#PrimaryInstructor',
                        'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/context' => [
                        'id' => '2',
                        'label' => 'LTI consumer course',
                        'title' => 'LTI consumer course',
                        'type' => [
                            'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection',
                        ],
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiDeepLinkingRequest',
                    'given_name' => 'Admin',
                    'family_name' => 'User',
                    'name' => 'Admin User',
                    'https://purl.imsglobal.org/spec/lti/claim/ext' => [
                        'user_username' => 'admin',
                        'lms' => 'moodle-2',
                    ],
                    'email' => 'admin@example.com',
                    'https://purl.imsglobal.org/spec/lti/claim/launch_presentation' => [
                        'locale' => 'en',
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/tool_platform' => [
                        'product_family_code' => 'moodle',
                        'version' => '2025010900.01',
                        'guid' => '3d2743ebb0a54eb313577143d6c983b0',
                        'name' => 'ltiplatform',
                        'description' => 'Stable Main PostgreSQL',
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
                    'https://purl.imsglobal.org/spec/lti/claim/custom' => [
                        'cat' => 'DONTCHANGE',
                        'idnumber' => '456',
                        'usertimezone' => 'Australia/Perth',
                        'context_memberships_url' => 'https://lms.example.com/lti/service/memberships?ctx=44',
                    ],
                    'https://purl.imsglobal.org/spec/lti-ags/claim/endpoint' => [
                        'scope' => [
                            'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem',
                            'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly',
                            'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly',
                            'https://purl.imsglobal.org/spec/lti-ags/scope/score',
                        ],
                        'lineitems' => 'https://lms.example.com/lti/service/lineitems/2',
                    ],
                    'https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice' => [
                        'context_memberships_url' => 'https://lms.example.com/lti/service/memberships?ctx=44',
                        'service_versions' => [
                            '1.0',
                            '2.0',
                        ],
                    ],
                    'https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings' => [
                        'accept_types' => [
                            'ltiResourceLink',
                        ],
                        'accept_presentation_document_targets' => [
                            'frame',
                            'iframe',
                            'window',
                        ],
                        'accept_copy_advice' => false,
                        'accept_multiple' => true,
                        'accept_unsigned' => false,
                        'auto_create' => false,
                        'can_confirm' => false,
                        'deep_link_return_url' => 'https://lms.example.com/deeplinkreturn',
                        'title' => 'Default link title',
                        'text' => 'Default link description',
                    ],
                ],
                'expected' => [
                    'user_id' => '2',
                    'lis_person_sourcedid' => 'USERIDNUM-456',
                    'lis_course_section_sourcedid' => 'COURSEIDNUM-678',
                    'roles' => 'urn:lti:instrole:ims/lis/Administrator,urn:lti:role:ims/lis/Instructor,urn:lti:role:ims/lis/Instructor/PrimaryInstructor,urn:lti:sysrole:ims/lis/Administrator',
                    'context_id' => '2',
                    'context_label' => 'LTI consumer course',
                    'context_title' => 'LTI consumer course',
                    'context_type' => 'urn:lti:context-type:ims/lis/CourseSection',
                    'lti_message_type' => 'ContentItemSelectionRequest',
                    'lis_person_name_given' => 'Admin',
                    'lis_person_name_family' => 'User',
                    'lis_person_name_full' => 'Admin User',
                    'ext_user_username' => 'admin',
                    'ext_lms' => 'moodle-2',
                    'lis_person_contact_email_primary' => 'admin@example.com',
                    'launch_presentation_locale' => 'en',
                    'tool_consumer_info_product_family_code' => 'moodle',
                    'tool_consumer_info_version' => '2025010900.01',
                    'tool_consumer_instance_guid' => '3d2743ebb0a54eb313577143d6c983b0',
                    'tool_consumer_instance_name' => 'ltiplatform',
                    'tool_consumer_instance_description' => 'Stable Main PostgreSQL',
                    'lti_version' => '1.3.0',
                    'custom_cat' => 'DONTCHANGE',
                    'custom_idnumber' => '456',
                    'custom_usertimezone' => 'Australia/Perth',
                    'custom_context_memberships_url' => 'https://lms.example.com/lti/service/memberships?ctx=44',
                    'custom_gradebookservices_scope' => 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem,https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly,https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly,https://purl.imsglobal.org/spec/lti-ags/scope/score',
                    'custom_lineitems_url' => 'https://lms.example.com/lti/service/lineitems/2',
                    'custom_context_memberships_v2_url' => 'https://lms.example.com/lti/service/memberships?ctx=44',
                    'custom_context_memberships_versions' => '1.0,2.0',
                    'accept_types' => 'ltiResourceLink',
                    'accept_presentation_document_targets' => 'frame,iframe,window',
                    'accept_copy_advice' => 'false',
                    'accept_multiple' => 'true',
                    'accept_unsigned' => 'false',
                    'auto_create' => 'false',
                    'can_confirm' => 'false',
                    'content_item_return_url' => 'https://lms.example.com/deeplinkreturn',
                    'title' => 'Default link title',
                    'text' => 'Default link description',
                ]
            ],
            'Example showing handling of non-mappable claims' => [
                'claims' => [
                    // Mappable
                    'sub' => '2',
                    'https://purl.imsglobal.org/spec/lti/claim/lis' => [
                        'person_sourcedid' => 'USERIDNUM-456',
                        'course_section_sourcedid' => 'COURSEIDNUM-678',
                    ],
                    // Non-mappable claims will not be present in v1p1 output.
                    'baboon' => 'baboon',
                ],
                'expected' => [
                    'user_id' => '2',
                    'lis_person_sourcedid' => 'USERIDNUM-456',
                    'lis_course_section_sourcedid' => 'COURSEIDNUM-678',
                ]
            ],
            'Example showing the handling of empty params' => [
                'claims' => [],
                'expected' => [],
            ],
            'Example showing that special characters are preserved' => [
                'claims' => [
                    'sub' => '2',
                    'given_name' => 'François',
                    'email' => 'françois@example.com',
                    'https://purl.imsglobal.org/spec/lti/claim/context' => [
                        'id' => '2',
                        'label' => 'Cours de Français',
                        'title' => 'Cours de Français',
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/resource_link' => [
                        'title' => 'Assignation #1 & #2',
                    ],
                ],
                'expected' => [
                    'user_id' => '2',
                    'context_id' => '2',
                    'context_label' => 'Cours de Français',
                    'context_title' => 'Cours de Français',
                    'lis_person_name_given' => 'François',
                    'lis_person_contact_email_primary' => 'françois@example.com',
                    'resource_link_title' => 'Assignation #1 & #2',
                ],
            ],
            'Example showing that vocabulary conversion to v1 message type, context type and roles takes place' => [
                'claims' => [
                    'sub' => '7',
                    'https://purl.imsglobal.org/spec/lti/claim/roles' => [
                        'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
                        'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator',
                        'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/context' => [
                        'type' => [
                            'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection',
                        ]
                    ],
                    'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest',
                ],
                'expected' => [
                    'user_id' => '7',
                    'roles' => 'urn:lti:role:ims/lis/Instructor,urn:lti:instrole:ims/lis/Administrator,urn:lti:sysrole:ims/lis/Administrator',
                    'context_type' => 'urn:lti:context-type:ims/lis/CourseSection',
                    'lti_message_type' => 'basic-lti-launch-request',
                ]
            ],
            'Example showing that an unrecognised lti_message_type claim is passed through unchanged' => [
                'claims' => [
                    'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiSomeUnrecognisedMessageType',
                ],
                'expected' => [
                    'lti_message_type' => 'LtiSomeUnrecognisedMessageType',
                ],
            ],
        ];
    }

    /**
     * Test the use of custom JWT claim mapping during v1p3->v1p1 conversion.
     *
     * The JWT claim mapping is primarily intended to map legacy params to claims for outbound messages for core message types.
     * To support other message types or inbound messages, verify that any arbitrary claim map can be supplied, and that the
     * converter will use that for conversion.
     *
     * @return void
     */
    public function test_claims_to_params_custom_jwt_claim_map(): void {
        // This example is based on a deep linking response message JWT.
        $v1p3payload = [
            'iss' => 'abc-123',
            'aud' => 'https://platform.example.com',
            'exp' => time() + 60,
            'nonce' => 'a-nonce-value',
            'https://purl.imsglobal.org/spec/lti/claim/deployment_id' => '2',
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiDeepLinkingResponse',
            'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
            'https://purl.imsglobal.org/spec/lti-dl/claim/content_items' => [
                [
                    'type' => 'ltiResourceLink',
                    'title' => 'Link title',
                    'url' => 'https://tool.example.com/content/1',
                ],
            ],
            'https://purl.imsglobal.org/spec/lti-dl/claim/data' => 'csrftoken:c7fbba78-7b75-46e3-9201-11e6d5f36f53',
        ];

        // Provide some example JWT claim mapping in addition to the core claim mapping.
        $jwtclaimmap = array_merge(
            oauth_helper::get_jwt_claim_mapping(),
            [
                'data' => [
                    'suffix' => 'dl',
                    'group' => '',
                    'claim' => 'data',
                    'isarray' => false
                ],
                'elephant' => [
                    'suffix' => '',
                    'group' => '',
                    'claim' => 'deployment_id',
                    'isarray' => false
                ],
            ]
        );

        $payloadconverter = new lti_1px_payload_converter(
            lisvocabconverter: new lis_vocab_converter(),
            jwtclaimmapping: $jwtclaimmap,
        );

        $converted = $payloadconverter->claims_to_params($v1p3payload);

        // Example JWT claim mapping has been used successfully.
        $this->assertArrayHasKey('data', $converted);
        $this->assertEquals($converted['data'], $v1p3payload['https://purl.imsglobal.org/spec/lti-dl/claim/data']);
        $this->assertArrayHasKey('elephant', $converted);
        $this->assertEquals($converted['elephant'], $v1p3payload['https://purl.imsglobal.org/spec/lti/claim/deployment_id']);

        // And core claim mapping also.
        $this->assertArrayHasKey('lti_version', $converted);
        $this->assertArrayHasKey('content_items', $converted);
        $this->assertArrayHasKey('lti_message_type', $converted);
    }

    /**
     * Test asserting content item payload is also converted during v1p3->v1p1 conversion, if present.
     *
     * @return void
     */
    public function test_claims_to_params_content_item_handling(): void {
        // This example is based on a deep linking response message JWT.
        $v1p3payload = [
            'iss' => 'abc-123',
            'aud' => 'https://platform.example.com',
            'exp' => time() + 60,
            'nonce' => 'a-nonce-value',
            'https://purl.imsglobal.org/spec/lti/claim/deployment_id' => '2',
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiDeepLinkingResponse',
            'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
            'https://purl.imsglobal.org/spec/lti-dl/claim/content_items' => [
                [
                    'type' => 'ltiResourceLink',
                    'title' => 'Link title',
                    'url' => 'https://tool.example.com/content/1',
                ]
            ],
            'https://purl.imsglobal.org/spec/lti-dl/claim/data' => 'csrftoken:c7fbba78-7b75-46e3-9201-11e6d5f36f53',
        ];

        $payloadconverter = new lti_1px_payload_converter(new lis_vocab_converter());

        $converted = $payloadconverter->claims_to_params($v1p3payload);

        $this->assertArrayHasKey('content_items', $converted);
        $contentitemsdecoded = json_decode($converted['content_items']);
        $this->assertObjectHasProperty('@graph', $contentitemsdecoded);
        $this->assertObjectHasProperty('@context', $contentitemsdecoded);
        $this->assertEquals(
            $v1p3payload['https://purl.imsglobal.org/spec/lti-dl/claim/content_items'][0]['title'],
            $contentitemsdecoded->{'@graph'}[0]->title
        );
        $this->assertEquals(
            $v1p3payload['https://purl.imsglobal.org/spec/lti-dl/claim/content_items'][0]['url'],
            $contentitemsdecoded->{'@graph'}[0]->url
        );
        $this->assertEquals(
            'LtiLinkItem',
            $contentitemsdecoded->{'@graph'}[0]->{'@type'}
        );
    }

    /**
     * Test covering convert_content_items_v1p3_v1p1().
     *
     * @return void
     */
    #[DataProvider('convert_content_items_provider')]
    public function test_convert_content_items_v1p3_v1p1(string $v1p3contentitemsjson, string $v1p1contentitemsjson): void {

        $payloadconverter = new lti_1px_payload_converter(new lis_vocab_converter());

        $v1p1json = $payloadconverter->convert_content_items_v1p3_v1p1($v1p3contentitemsjson);

        // Note: Compare the decoded results because the ordering of keys inside the json strings may differ.
        $this->assertEquals(json_decode($v1p1contentitemsjson), json_decode($v1p1json));
    }

    /**
     * Data provider for testing content item conversion.
     *
     * @return array the test data.
     */
    public static function convert_content_items_provider(): array {
        return [
            'A list of content items' => [
                'v1p3contentitemsjson' => json_encode([
                    [
                        'type' => 'ltiResourceLink',
                        'url' => 'http://example.com/messages/launch',
                        'title' => 'Test title',
                        'text' => 'Test text',
                        'iframe' => [],
                    ],
                    [
                        'type' => 'ltiResourceLink',
                        'url' => 'http://example.com/messages/launch2',
                        'title' => 'Test title2',
                        'text' => 'Test text2',
                        'iframe' => [
                            'height' => 200,
                            'width' => 300
                        ],
                        'window' => [],
                    ],
                    [
                        'type' => 'ltiResourceLink',
                        'url' => 'http://example.com/messages/launch3',
                        'title' => 'Test title3',
                        'text' => 'Test text3',
                        'window' => [
                            'targetName' => 'test-win',
                            'height' => 400
                        ],
                    ]
                ]),
                'v1p1contentitemsjson' => json_encode([
                    '@context' => 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
                    '@graph' => [
                        [
                            'url' => 'http://example.com/messages/launch',
                            'title' => 'Test title',
                            'text' => 'Test text',
                            'placementAdvice' => [
                                'presentationDocumentTarget' => 'iframe',
                            ],
                            '@type' => 'LtiLinkItem',
                            'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                        ],
                        [
                            'url' => 'http://example.com/messages/launch2',
                            'title' => 'Test title2',
                            'text' => 'Test text2',
                            'placementAdvice' => [
                                'presentationDocumentTarget' => 'iframe',
                                'displayHeight' => 200,
                                'displayWidth' => 300,
                            ],
                            '@type' => 'LtiLinkItem',
                            'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                        ],
                        [
                            'url' => 'http://example.com/messages/launch3',
                            'title' => 'Test title3',
                            'text' => 'Test text3',
                            'placementAdvice' => [
                                'presentationDocumentTarget' => 'window',
                                'displayHeight' => 400,
                                'windowTarget' => 'test-win',
                            ],
                            '@type' => 'LtiLinkItem',
                            'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                        ],
                    ]
                ]),
            ],
            'link type item is converted to ContentItem with mediaType text/html' => [
                'v1p3contentitemsjson' => json_encode([
                    [
                        'type' => 'link',
                        'url' => 'http://example.com/content/1',
                        'title' => 'A link item',
                    ],
                ]),
                'v1p1contentitemsjson' => json_encode([
                    '@context' => 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
                    '@graph' => [
                        [
                            'url' => 'http://example.com/content/1',
                            'title' => 'A link item',
                            '@type' => 'ContentItem',
                            'mediaType' => 'text/html',
                        ],
                    ],
                ]),
            ],
            'rich type item is converted to ContentItem with mediaType text/html' => [
                'v1p3contentitemsjson' => json_encode([
                    [
                        'type' => 'rich',
                        'url' => 'http://example.com/content/2',
                        'title' => 'A rich item',
                    ],
                ]),
                'v1p1contentitemsjson' => json_encode([
                    '@context' => 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
                    '@graph' => [
                        [
                            'url' => 'http://example.com/content/2',
                            'title' => 'A rich item',
                            '@type' => 'ContentItem',
                            'mediaType' => 'text/html',
                        ],
                    ],
                ]),
            ],
            'file type item is converted to FileItem' => [
                'v1p3contentitemsjson' => json_encode([
                    [
                        'type' => 'file',
                        'url' => 'http://example.com/file.pdf',
                        'title' => 'A file item',
                    ],
                ]),
                'v1p1contentitemsjson' => json_encode([
                    '@context' => 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
                    '@graph' => [
                        [
                            'url' => 'http://example.com/file.pdf',
                            'title' => 'A file item',
                            '@type' => 'FileItem',
                        ],
                    ],
                ]),
            ],
            'html field is renamed to text' => [
                'v1p3contentitemsjson' => json_encode([
                    [
                        'type' => 'rich',
                        'html' => '<p>Some rich content</p>',
                    ],
                ]),
                'v1p1contentitemsjson' => json_encode([
                    '@context' => 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
                    '@graph' => [
                        [
                            'text' => '<p>Some rich content</p>',
                            '@type' => 'ContentItem',
                            'mediaType' => 'text/html',
                        ],
                    ],
                ]),
            ],
            'icon url is converted to @id' => [
                'v1p3contentitemsjson' => json_encode([
                    [
                        'type' => 'ltiResourceLink',
                        'url' => 'http://example.com/link',
                        'icon' => [
                            'url' => 'http://example.com/icon.png',
                            'width' => 50,
                            'height' => 50,
                        ],
                    ],
                ]),
                'v1p1contentitemsjson' => json_encode([
                    '@context' => 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
                    '@graph' => [
                        [
                            'url' => 'http://example.com/link',
                            'icon' => [
                                '@id' => 'http://example.com/icon.png',
                                'width' => 50,
                                'height' => 50,
                            ],
                            '@type' => 'LtiLinkItem',
                            'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                        ],
                    ],
                ]),
            ],
            'thumbnail url is converted to @id' => [
                'v1p3contentitemsjson' => json_encode([
                    [
                        'type' => 'ltiResourceLink',
                        'url' => 'http://example.com/link',
                        'thumbnail' => [
                            'url' => 'http://example.com/thumb.png',
                            'width' => 120,
                            'height' => 90,
                        ],
                    ],
                ]),
                'v1p1contentitemsjson' => json_encode([
                    '@context' => 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
                    '@graph' => [
                        [
                            'url' => 'http://example.com/link',
                            'thumbnail' => [
                                '@id' => 'http://example.com/thumb.png',
                                'width' => 120,
                                'height' => 90,
                            ],
                            '@type' => 'LtiLinkItem',
                            'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                        ],
                    ],
                ]),
            ],
            'lineItem is converted with all sub-fields' => [
                'v1p3contentitemsjson' => json_encode([
                    [
                        'type' => 'ltiResourceLink',
                        'url' => 'http://example.com/link',
                        'lineItem' => [
                            'scoreMaximum' => 100,
                            'label' => 'Grade',
                            'resourceId' => 'res-123',
                            'tag' => 'graded',
                            'submissionReview' => ['reviewable' => 'true'],
                        ],
                    ],
                ]),
                'v1p1contentitemsjson' => json_encode([
                    '@context' => 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
                    '@graph' => [
                        [
                            'url' => 'http://example.com/link',
                            '@type' => 'LtiLinkItem',
                            'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                            'lineItem' => [
                                '@type' => 'LineItem',
                                'reportingMethod' => 'http://purl.imsglobal.org/ctx/lis/v2p1/Result#totalScore',
                                'label' => 'Grade',
                                'assignedActivity' => ['activityId' => 'res-123'],
                                'tag' => 'graded',
                                'scoreConstraints' => [
                                    '@type' => 'NumericLimits',
                                    'totalMaximum' => 100,
                                ],
                                'submissionReview' => ['reviewable' => 'true'],
                            ],
                        ],
                    ],
                ]),
            ],
            'presentation field (early draft) is converted to placementAdvice' => [
                'v1p3contentitemsjson' => json_encode([
                    [
                        'type' => 'ltiResourceLink',
                        'url' => 'http://example.com/link',
                        'presentation' => [
                            'documentTarget' => 'frame',
                            'windowTarget' => 'my-window',
                            'width' => 800,
                            'height' => 600,
                        ],
                    ],
                ]),
                'v1p1contentitemsjson' => json_encode([
                    '@context' => 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
                    '@graph' => [
                        [
                            'url' => 'http://example.com/link',
                            '@type' => 'LtiLinkItem',
                            'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                            'placementAdvice' => [
                                'presentationDocumentTarget' => 'frame',
                                'windowTarget' => 'my-window',
                                // Note: 'dislayWidth'/'dislayHeight' are typos in the implementation, preserved here intentionally.
                                'dislayWidth' => 800,
                                'dislayHeight' => 600,
                            ],
                        ],
                    ],
                ]),
            ],
            'window presentation with dimensions but no targetName' => [
                'v1p3contentitemsjson' => json_encode([
                    [
                        'type' => 'ltiResourceLink',
                        'url' => 'http://example.com/link',
                        'window' => [
                            'width' => 800,
                            'height' => 600,
                        ],
                    ],
                ]),
                'v1p1contentitemsjson' => json_encode([
                    '@context' => 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
                    '@graph' => [
                        [
                            'url' => 'http://example.com/link',
                            '@type' => 'LtiLinkItem',
                            'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                            'placementAdvice' => [
                                'presentationDocumentTarget' => 'window',
                                'displayWidth' => 800,
                                'displayHeight' => 600,
                            ],
                        ],
                    ],
                ]),
            ],
            'items without a type field are silently skipped' => [
                'v1p3contentitemsjson' => json_encode([
                    [
                        'url' => 'http://example.com/no-type',
                        'title' => 'No type item',
                    ],
                ]),
                'v1p1contentitemsjson' => json_encode([
                    '@context' => 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
                    '@graph' => [],
                ]),
            ],
            'empty content items array produces wrapper with empty @graph' => [
                'v1p3contentitemsjson' => json_encode([]),
                'v1p1contentitemsjson' => json_encode([
                    '@context' => 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
                    '@graph' => [],
                ]),
            ],
            'invalid json input produces wrapper with empty @graph' => [
                'v1p3contentitemsjson' => 'not-valid-json',
                'v1p1contentitemsjson' => json_encode([
                    '@context' => 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem',
                    '@graph' => [],
                ]),
            ],
        ];
    }

    /**
     * Test the use of a custom JWT claim mapping during v1p1->v1p3 conversion.
     *
     * @return void
     */
    public function test_params_to_claims_custom_jwt_claim_map(): void {
        $jwtclaimmap = array_merge(
            oauth_helper::get_jwt_claim_mapping(),
            [
                'my_custom_param' => [
                    'suffix' => '',
                    'group' => '',
                    'claim' => 'my_custom_claim',
                    'isarray' => false,
                ],
            ]
        );

        $payloadconverter = new lti_1px_payload_converter(
            lisvocabconverter: new lis_vocab_converter(),
            jwtclaimmapping: $jwtclaimmap,
        );

        $converted = $payloadconverter->params_to_claims([
            'user_id' => '42',
            'my_custom_param' => 'my_custom_value',
        ]);

        // Custom mapping was applied.
        $this->assertArrayHasKey('https://purl.imsglobal.org/spec/lti/claim/my_custom_claim', $converted);
        $this->assertEquals('my_custom_value', $converted['https://purl.imsglobal.org/spec/lti/claim/my_custom_claim']);

        // Core mapping also applied.
        $this->assertArrayHasKey('sub', $converted);
        $this->assertEquals('42', $converted['sub']);
    }

    /**
     * Test the use of a custom message type mapping during v1p1->v1p3 conversion.
     *
     * @return void
     */
    public function test_params_to_claims_custom_message_type_map(): void {
        $custommapping = ['my-custom-launch' => 'LtiCustomLaunch'];

        $payloadconverter = new lti_1px_payload_converter(
            lisvocabconverter: new lis_vocab_converter(),
            messagetypemapping: $custommapping,
        );

        $converted = $payloadconverter->params_to_claims([
            'lti_message_type' => 'my-custom-launch',
        ]);

        $this->assertEquals(
            'LtiCustomLaunch',
            $converted['https://purl.imsglobal.org/spec/lti/claim/message_type']
        );
    }

    /**
     * Test the use of a custom message type mapping during v1p3->v1p1 conversion.
     *
     * @return void
     */
    public function test_claims_to_params_custom_message_type_map(): void {
        $custommapping = ['my-custom-launch' => 'LtiCustomLaunch'];

        $payloadconverter = new lti_1px_payload_converter(
            lisvocabconverter: new lis_vocab_converter(),
            messagetypemapping: $custommapping,
        );

        $converted = $payloadconverter->claims_to_params([
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiCustomLaunch',
        ]);

        $this->assertEquals('my-custom-launch', $converted['lti_message_type']);
    }
}
