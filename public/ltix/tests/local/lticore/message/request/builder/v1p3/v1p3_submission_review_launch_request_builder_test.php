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

namespace core_ltix\lticore\message\request\builder\v1p3;

use core_ltix\constants;
use core_ltix\local\lticore\message\request\builder\v1p3\lti_resource_link_launch_request_builder;
use core_ltix\local\lticore\message\request\builder\v1p3\v1p3_submission_review_launch_request_builder;
use core_ltix\local\lticore\models\resource_link;
use core_ltix\local\ltiopenid\jwks_helper;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for v1p3_submission_review_launch_request_builder.
 *
 * @package    core_ltix
 * @copyright  2025 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(v1p3_submission_review_launch_request_builder::class)]
final class v1p3_submission_review_launch_request_builder_test extends \basic_testcase {
    /**
     * Test building the initiate login launch message for a resource link launch.
     *
     * This test verifies that the builder creates the correct message (addressed to the login endpoint, having the relevant params)
     * and checks that the lti_message_hint contains a partially completed launch JWT.
     *
     * @param array $params the params to pass to the builder.
     * @param array $expected the expected parts of the generated lti_message.
     * @return void
     */
    #[DataProvider('build_message_provider')]
    public function test_build_message(array $params, array $expected): void {
        $message = (new v1p3_submission_review_launch_request_builder(...$params))->build_message();

        // The message should be configured to be sent to the init login endpoint.
        $this->assertEquals($expected['ltimessageurl'], $message->get_url());

        // Verify message params.
        $messageparams = $message->get_parameters();
        foreach ($expected['ltimessageparams'] as $expectedparamname => $expectedparamvalue) {
            $this->assertEquals($expectedparamvalue, $messageparams[$expectedparamname]);
        }

        // The message should contain a partially complete JWT inside the lti_message_hint parameter.
        $this->assertNotEmpty($message->get_parameters()['lti_message_hint']);
        $decodedltimessagehint = JWT::decode(
            $message->get_parameters()['lti_message_hint'],
            JWK::parseKeySet(jwks_helper::get_jwks())
        );
        $decodedltimessagehint = json_decode(json_encode($decodedltimessagehint), true); // Coerce into array format.

        // Verify JWT claims.
        foreach ($expected['ltimessagehintjwtclaims'] as $expectedclaimname => $expectedclaimvalue) {
            $this->assertEquals($expectedclaimvalue, $decodedltimessagehint[$expectedclaimname]);
        }

        // Some claims are dynamic and cannot be anticipated, but their presence should be verified.
        $this->assertIsString($decodedltimessagehint['nonce']);
        $this->assertIsInt($decodedltimessagehint['exp']);
        $this->assertIsInt($decodedltimessagehint['iat']);

        // TODO: confirm we have test coverage of the case where services override the target_link_uri.
        // update: I think this only needs testing where we expect a service to interact with a launch builder in this way, and
        // where it's implemented in the builder. For RLL, it doesn't occur.
        // For subreview, this will need to be tested, however, so keeping this note to make sure we add it to the subreview test.
    }

    /**
     * Provider for testing build_message().
     *
     * @return array the test data.
     */
    public static function build_message_provider(): array {
        return [
            'basic link launch, required params only' => [
                'params' => [
                    'toolconfig' => (object) [
                        'typeid' => 44444,
                        'lti_toolurl' => 'https://tool.example.com',
                        'lti_clientid' => '123456-abcd',
                        'lti_ltiversion' => '1.3.0',
                        'lti_initiatelogin' => 'https://tool.example.com/lti/initiatelogin',
                        'lti_organizationid' => 'https://platform.example.com',
                        'lti_launchcontainer' => constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
                        'lti_acceptgrades' => constants::LTI_SETTING_ALWAYS,
                        'ltixservice_gradesynchronization' => 2,
                        'ltixservice_memberships' => 1,
                        'lti_customparameters' => "idnumber=\$Person.sourcedId\nuser#TIME#zone=\$Person.address.timezone\n" .
                            "toollevelparam=test\nsubContainingPII=\$Person.name.full",
                    ],
                    'resourcelink' => new resource_link(0, (object) [
                        'id' => 24,
                        'typeid' => 123,
                        'contextid' => 456,
                        'url' => 'https://tool.example.com/lti/resource/1',
                        'title' => 'Resource 1',
                        'text' => 'A plain text description of resource 1',
                        'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_DEFAULT, // Defer to tool configuration value.
                        'customparams' => http_build_query(['mycustomparam' => '123'], '', "\n"),
                        'gradable' => true,
                        'servicesalt' => 'abc123',
                    ]),
                    'issuer' => 'https://moodle-lms.institution.example.org',
                    'userid' => 103001,
                    'foruser' => (object) [ // The user on whose behalf the submission review is being launched.
                        'id' => 103002,
                        'idnumber' => 'sourcedid-103002',
                        'firstname' => 'Student',
                        'lastname' => 'User',
                        'email' => 'student@email.com',
                    ],
                    'returnurl' => 'https://moodle-lms.institution.example.org/mod/assign/view.php?id=12345',
                ],
                'expected' => [
                    'ltimessageurl' => 'https://tool.example.com/lti/initiatelogin',
                    'ltimessageparams' => [
                        'iss' => 'https://moodle-lms.institution.example.org',
                        'target_link_uri' => 'https://tool.example.com/lti/resource/1',
                        'login_hint' => '103001',
                        'client_id' => '123456-abcd',
                        'lti_deployment_id' => '44444',
                    ],
                    'ltimessagehintjwtclaims' => [
                        // Note: the existence of 'nonce', 'iat' and 'exp' can be checked, but the values are dynamic and cannot.
                        'tool_registration_id' => '44444',
                        'iss' => 'https://moodle-lms.institution.example.org',
                        'aud' => '123456-abcd',
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/message_type' => 'LtiSubmissionReviewRequest',
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/deployment_id' => '44444',
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/version' => constants::LTI_VERSION_1P3,
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/resource_link' => [
                            'id' => '24',
                            'title' => 'Resource 1',
                            'description' => 'A plain text description of resource 1',
                        ],
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/target_link_uri' => 'https://tool.example.com/lti/resource/1',
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/roles' => [],
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/for_user' => [
                            'user_id' => 103002,
                            'person_sourcedid' => 'sourcedid-103002',
                            'given_name' => 'Student',
                            'family_name' => 'User',
                            'email' => 'student@email.com',
                            'roles' => ["http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"],
                        ],
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/launch_presentation' => [
                            'return_url' => 'https://moodle-lms.institution.example.org/mod/assign/view.php?id=12345',
                            'locale' => 'en',
                            'document_target' => 'iframe',
                        ],
                    ],
                ],
            ],
            'basic link launch, providing optional params (roles and extra claims)' => [
                'params' => [
                    'toolconfig' => (object) [
                        'typeid' => 44444,
                        'lti_toolurl' => 'https://tool.example.com',
                        'lti_clientid' => '123456-abcd',
                        'lti_ltiversion' => '1.3.0',
                        'lti_initiatelogin' => 'https://tool.example.com/lti/initiatelogin',
                        'lti_organizationid' => 'https://platform.example.com',
                        'lti_launchcontainer' => constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
                        'lti_acceptgrades' => constants::LTI_SETTING_ALWAYS,
                        'ltixservice_gradesynchronization' => 2,
                        'ltixservice_memberships' => 1,
                        'lti_customparameters' => "idnumber=\$Person.sourcedId\nuser#TIME#zone=\$Person.address.timezone\n" .
                            "toollevelparam=test\nsubContainingPII=\$Person.name.full",
                    ],
                    'resourcelink' => new resource_link(0, (object) [
                        'id' => 24,
                        'typeid' => 123,
                        'contextid' => 456,
                        'url' => 'https://tool.example.com/lti/resource/1',
                        'title' => 'Resource 1',
                        'text' => 'A plain text description of resource 1',
                        'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_DEFAULT, // Defer to tool configuration value.
                        'customparams' => http_build_query(['mycustomparam' => '123'], '', "\n"),
                        'gradable' => true,
                        'servicesalt' => 'abc123',
                    ]),
                    'issuer' => 'https://moodle-lms.institution.example.org',
                    'roles' => [
                        'Instructor',
                        'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator',
                        'http://example.com/another/role/namespace/myrole',
                    ],
                    'userid' => 103001,
                    'foruser' => (object) [ // The user on whose behalf the submission review is being launched.
                        'id' => 103002,
                        'idnumber' => 'sourcedid-103002',
                        'firstname' => 'Student',
                        'lastname' => 'User',
                        'email' => 'student@email.com',
                    ],
                    'returnurl' => 'https://moodle-lms.institution.example.org/mod/assign/view.php?id=12345',
                    'extraclaims' => [
                        'some_claim' => ['item1', 'item2'],
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/someclaim' => 'an example claim',
                        // Attempt to override one of the required claims. This shouldn't be possible.
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/resource_link' => [
                            'id' => 10,
                        ],
                    ],
                ],
                'expected' => [
                    'ltimessageurl' => 'https://tool.example.com/lti/initiatelogin',
                    'ltimessageparams' => [
                        'iss' => 'https://moodle-lms.institution.example.org',
                        'target_link_uri' => 'https://tool.example.com/lti/resource/1',
                        'login_hint' => '103001',
                        'client_id' => '123456-abcd',
                        'lti_deployment_id' => '44444',
                    ],
                    'ltimessagehintjwtclaims' => [
                        // Note: the existence of 'nonce', 'iat' and 'exp' can be checked, but the values are dynamic and cannot.
                        'tool_registration_id' => '44444',
                        'iss' => 'https://moodle-lms.institution.example.org',
                        'aud' => '123456-abcd',
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/message_type' => 'LtiSubmissionReviewRequest',
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/deployment_id' => '44444',
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/version' => constants::LTI_VERSION_1P3,
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/resource_link' => [
                            'id' => '24',
                            'title' => 'Resource 1',
                            'description' => 'A plain text description of resource 1',
                        ],
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/target_link_uri' => 'https://tool.example.com/lti/resource/1',
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/roles' => [
                            'Instructor',
                            'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator',
                            'http://example.com/another/role/namespace/myrole',
                        ],
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/for_user' => [
                            'user_id' => 103002,
                            'person_sourcedid' => 'sourcedid-103002',
                            'given_name' => 'Student',
                            'family_name' => 'User',
                            'email' => 'student@email.com',
                            'roles' => ["http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"],
                        ],
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/launch_presentation' => [
                            'return_url' => 'https://moodle-lms.institution.example.org/mod/assign/view.php?id=12345',
                            'locale' => 'en',
                            'document_target' => 'iframe',
                        ],
                        'some_claim' => ['item1', 'item2'],
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/someclaim' => 'an example claim',
                    ],
                ],
            ],
            'basic link launch, link has empty URL and target should be derived from the tool URL' => [
                'params' => [
                    'toolconfig' => (object) [
                        'typeid' => 44444,
                        'lti_toolurl' => 'https://tool.example.com',
                        'lti_clientid' => '123456-abcd',
                        'lti_ltiversion' => '1.3.0',
                        'lti_initiatelogin' => 'https://tool.example.com/lti/initiatelogin',
                        'lti_organizationid' => 'https://platform.example.com',
                        'lti_launchcontainer' => constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
                        'lti_acceptgrades' => constants::LTI_SETTING_ALWAYS,
                        'ltixservice_gradesynchronization' => 2,
                        'ltixservice_memberships' => 1,
                        'lti_customparameters' => "idnumber=\$Person.sourcedId\nuser#TIME#zone=\$Person.address.timezone\n" .
                            "toollevelparam=test\nsubContainingPII=\$Person.name.full",
                    ],
                    'resourcelink' => new resource_link(0, (object) [
                        'id' => 24,
                        'typeid' => 123,
                        'contextid' => 456,
                        'url' => '', // Note: empty.
                        'title' => 'Resource 1',
                        'text' => 'A plain text description of resource 1',
                        'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_DEFAULT, // Defer to tool configuration value.
                        'customparams' => http_build_query(['mycustomparam' => '123'], '', "\n"),
                        'gradable' => true,
                        'servicesalt' => 'abc123',
                    ]),
                    'issuer' => 'https://moodle-lms.institution.example.org',
                    // Note: during init request build, $user->id is needed but this would normally be a full user object.
                    'userid' => 103001,
                    'foruser' => (object) [ // The user on whose behalf the submission review is being launched.
                        'id' => 103002,
                        'idnumber' => 'sourcedid-103002',
                        'firstname' => 'Student',
                        'lastname' => 'User',
                        'email' => 'student@email.com',
                    ],
                    'returnurl' => 'https://moodle-lms.institution.example.org/mod/assign/view.php?id=12345',
                ],
                'expected' => [
                    'ltimessageurl' => 'https://tool.example.com/lti/initiatelogin',
                    'ltimessageparams' => [
                        'iss' => 'https://moodle-lms.institution.example.org',
                        'target_link_uri' => 'https://tool.example.com',
                        'login_hint' => '103001',
                        'client_id' => '123456-abcd',
                        'lti_deployment_id' => '44444',
                    ],
                    'ltimessagehintjwtclaims' => [
                        // Note: the existence of 'nonce', 'iat' and 'exp' can be checked, but the values are dynamic and cannot.
                        'tool_registration_id' => '44444',
                        'iss' => 'https://moodle-lms.institution.example.org',
                        'aud' => '123456-abcd',
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/message_type' => 'LtiSubmissionReviewRequest',
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/deployment_id' => '44444',
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/version' => constants::LTI_VERSION_1P3,
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/resource_link' => [
                            'id' => '24',
                            'title' => 'Resource 1',
                            'description' => 'A plain text description of resource 1',
                        ],
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/target_link_uri' => 'https://tool.example.com',
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/roles' => [],
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/for_user' => [
                            'user_id' => 103002,
                            'person_sourcedid' => 'sourcedid-103002',
                            'given_name' => 'Student',
                            'family_name' => 'User',
                            'email' => 'student@email.com',
                            'roles' => ["http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"],
                        ],
                        constants::LTI_JWT_CLAIM_PREFIX . '/claim/launch_presentation' => [
                            'return_url' => 'https://moodle-lms.institution.example.org/mod/assign/view.php?id=12345',
                            'locale' => 'en',
                            'document_target' => 'iframe',
                        ],
                    ],
                ],
            ],
        ];
    }
}
