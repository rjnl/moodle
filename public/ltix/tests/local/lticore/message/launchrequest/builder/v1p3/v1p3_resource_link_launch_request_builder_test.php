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

namespace core_ltix\local\lticore\message\launchrequest\builder\v1p3;

use core_ltix\constants;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\models\resource_link;
use core_ltix\local\ltiopenid\jwks_helper;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering v1p3_resource_link_launch_request_builder.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(v1p3_resource_link_launch_request_builder::class)]
class v1p3_resource_link_launch_request_builder_test extends \basic_testcase {

    /**
     * Return a default tool config suitable for v1p3 launches.
     *
     * @return \stdClass
     */
    private static function make_toolconfig(): \stdClass {
        return (object) [
            'tool' => (object) [
                'id' => '44444',
                'clientid' => '123456-abcd',
                'issuer' => 'https://moodle-lms.institution.example.org',
                'baseurl' => 'https://tool.example.com',
            ],
            'config' => (object) [
                'initiatelogin' => 'https://tool.example.com/lti/initiatelogin',
            ],
        ];
    }

    /**
     * Return a default resource_link, with individual fields overridden by $overrides.
     *
     * @param array $overrides field values to override on the default resource link record.
     * @return resource_link
     */
    private static function make_resource_link(array $overrides = []): resource_link {
        return new resource_link(0, (object) array_merge([
            'id' => 24,
            'typeid' => 123,
            'contextid' => 456,
            'url' => 'https://tool.example.com/lti/resource/1',
            'title' => 'Resource 1',
            'text' => 'A plain text description of resource 1',
            'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_DEFAULT,
            'customparams' => '',
            'gradable' => false,
        ], $overrides));
    }

    /**
     * Decode the lti_message_hint JWT from a message's parameters into an associative claims array.
     *
     * @param array $messageparams the full parameter array from an lti_message.
     * @return array decoded JWT claims.
     */
    private static function decode_message_hint(array $messageparams): array {
        $decoded = JWT::decode($messageparams['lti_message_hint'], JWK::parseKeySet(jwks_helper::get_jwks()));
        return json_decode(json_encode($decoded), true);
    }

    /**
     * Test that the message is addressed to the tool's initiate login endpoint.
     *
     * @return void
     */
    public function test_message_url_is_initiate_login_endpoint(): void {
        $message = (new v1p3_resource_link_launch_request_builder())
            ->build_message(self::make_toolconfig(), self::make_resource_link(), userid: 103001);

        $this->assertEquals('https://tool.example.com/lti/initiatelogin', $message->get_url());
    }

    /**
     * Test that the lti_message_hint is a valid JWT, verifiable with the platform's public JWKS.
     *
     * @return void
     */
    public function test_jwt_hint_is_verifiable(): void {
        $message = (new v1p3_resource_link_launch_request_builder())
            ->build_message(self::make_toolconfig(), self::make_resource_link(), userid: 103001);

        // decode_message_hint() will throw on an invalid or unverifiable JWT.
        $claims = self::decode_message_hint($message->get_parameters());
        $this->assertIsArray($claims);
    }

    /**
     * Test that the resource link's own URL is used as the target_link_uri when it is set.
     *
     * The URL must appear in both the top-level message params and the JWT hint claim.
     *
     * @return void
     */
    public function test_resource_link_url_used_as_target_link_uri(): void {
        $resourcelink = self::make_resource_link(['url' => 'https://tool.example.com/lti/resource/1']);
        $message = (new v1p3_resource_link_launch_request_builder())
            ->build_message(self::make_toolconfig(), $resourcelink, userid: 103001);

        $this->assertEquals('https://tool.example.com/lti/resource/1', $message->get_parameters()['target_link_uri']);

        $claims = self::decode_message_hint($message->get_parameters());
        $this->assertEquals(
            'https://tool.example.com/lti/resource/1',
            $claims[constants::LTI_JWT_CLAIM_PREFIX . '/claim/target_link_uri']
        );
    }

    /**
     * Test that the tool's base URL is used as the target_link_uri when the resource link URL is empty.
     *
     * The fallback must apply in both the top-level message params and the JWT hint claim.
     *
     * @return void
     */
    public function test_tool_baseurl_used_when_resource_link_url_is_empty(): void {
        $resourcelink = self::make_resource_link(['url' => '']);
        $message = (new v1p3_resource_link_launch_request_builder())
            ->build_message(self::make_toolconfig(), $resourcelink, userid: 103001);

        $this->assertEquals('https://tool.example.com', $message->get_parameters()['target_link_uri']);

        $claims = self::decode_message_hint($message->get_parameters());
        $this->assertEquals(
            'https://tool.example.com',
            $claims[constants::LTI_JWT_CLAIM_PREFIX . '/claim/target_link_uri']
        );
    }

    /**
     * Test that description is omitted from the resource_link claim when the resource link has no text.
     *
     * @return void
     */
    public function test_null_description_omits_description_from_resource_link_claim(): void {
        $resourcelink = self::make_resource_link(['text' => null]);
        $message = (new v1p3_resource_link_launch_request_builder())
            ->build_message(self::make_toolconfig(), $resourcelink, userid: 103001);

        $claims = self::decode_message_hint($message->get_parameters());
        $resourcelinkclaim = $claims[constants::LTI_JWT_CLAIM_PREFIX . '/claim/resource_link'];
        $this->assertArrayNotHasKey('description', $resourcelinkclaim);
    }

    /**
     * Test that required claims (message_type, version, resource_link) cannot be overridden by extraclaims.
     *
     * @return void
     */
    public function test_required_claims_cannot_be_overridden_by_extraclaims(): void {
        $extraclaims = [
            constants::LTI_JWT_CLAIM_PREFIX . '/claim/message_type' => 'NotARealMessageType',
            constants::LTI_JWT_CLAIM_PREFIX . '/claim/version' => '0.0.1',
            constants::LTI_JWT_CLAIM_PREFIX . '/claim/resource_link' => ['id' => 99999],
        ];
        $message = (new v1p3_resource_link_launch_request_builder())
            ->build_message(self::make_toolconfig(), self::make_resource_link(), userid: 103001, extraclaims: $extraclaims);

        $claims = self::decode_message_hint($message->get_parameters());
        $this->assertEquals('LtiResourceLinkRequest', $claims[constants::LTI_JWT_CLAIM_PREFIX . '/claim/message_type']);
        $this->assertEquals(lti_version::LTI_VERSION_1P3->value, $claims[constants::LTI_JWT_CLAIM_PREFIX . '/claim/version']);
        $this->assertEquals(24, $claims[constants::LTI_JWT_CLAIM_PREFIX . '/claim/resource_link']['id']);
    }

    /**
     * Test that the link description is formatted to plain text during request build.
     *
     * @return void
     */
    public function test_description_formatted_to_plain_text(): void {
        $resourcelink = $this->make_resource_link([
            'text' => '<p>This is a <b>paragraph</b> with <i>some</i> <a href="https://example.com">links</a>.</p>',
            'textformat' => FORMAT_HTML,
        ]);
        $message = (new v1p3_resource_link_launch_request_builder())
            ->build_message($this->make_toolconfig(), $resourcelink, userid: 103001);

        $claims = self::decode_message_hint($message->get_parameters());
        $this->assertEquals(
            'This is a PARAGRAPH with _some_ links.',
            $claims[constants::LTI_JWT_CLAIM_PREFIX . '/claim/resource_link']['description']
        );
    }

    /**
     * Test that build_message() produces the correct top-level params and JWT hint claims for a range of inputs.
     *
     * @param array $input keys: toolconfig, resourcelink, userid, and optionally roles and extraclaims.
     * @param array $expectedparams expected key/value pairs in the top-level message params.
     * @param array $expectedhintclaims expected key/value pairs in the decoded JWT hint claims.
     * @return void
     */
    #[DataProvider('build_message_provider')]
    public function test_build_message(array $input, array $expectedparams, array $expectedhintclaims): void {
        $message = (new v1p3_resource_link_launch_request_builder())->build_message(
            $input['toolconfig'],
            $input['resourcelink'],
            $input['userid'],
            $input['roles'] ?? [],
            $input['extraclaims'] ?? [],
        );

        $messageparams = $message->get_parameters();
        foreach ($expectedparams as $key => $value) {
            $this->assertArrayHasKey($key, $messageparams, "Expected top-level param '$key' to be present");
            $this->assertEquals($value, $messageparams[$key], "Top-level param '$key' has unexpected value");
        }

        $hintclaims = self::decode_message_hint($messageparams);
        foreach ($expectedhintclaims as $key => $value) {
            $this->assertArrayHasKey($key, $hintclaims, "Expected JWT hint claim '$key' to be present");
            $this->assertEquals($value, $hintclaims[$key], "JWT hint claim '$key' has unexpected value");
        }

        // Dynamic claims: assert presence and type only.
        $this->assertIsString($hintclaims['nonce']);
        $this->assertIsInt($hintclaims['exp']);
        $this->assertIsInt($hintclaims['iat']);
    }

    /**
     * Data provider for test_build_message().
     *
     * @return array
     */
    public static function build_message_provider(): array {
        $prefix = constants::LTI_JWT_CLAIM_PREFIX;

        return [
            'all required top-level params and JWT hint claims are present' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(),
                    'resourcelink' => self::make_resource_link(),
                    'userid' => 103001,
                ],
                'expectedparams' => [
                    'iss' => 'https://moodle-lms.institution.example.org',
                    'target_link_uri' => 'https://tool.example.com/lti/resource/1',
                    'login_hint' => '103001',
                    'client_id' => '123456-abcd',
                    'lti_deployment_id' => '44444',
                ],
                'expectedhintclaims' => [
                    'tool_registration_id' => '44444',
                    'iss' => 'https://moodle-lms.institution.example.org',
                    'aud' => '123456-abcd',
                    $prefix . '/claim/message_type' => 'LtiResourceLinkRequest',
                    $prefix . '/claim/deployment_id' => '44444',
                    $prefix . '/claim/version' => lti_version::LTI_VERSION_1P3->value,
                    $prefix . '/claim/resource_link' => [
                        'id' => 24,
                        'title' => 'Resource 1',
                        'description' => 'A plain text description of resource 1',
                    ],
                    $prefix . '/claim/target_link_uri' => 'https://tool.example.com/lti/resource/1',
                    $prefix . '/claim/roles' => [],
                ],
            ],
            'login_hint is the user id cast to a string' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(),
                    'resourcelink' => self::make_resource_link(),
                    'userid' => 999,
                ],
                'expectedparams' => ['login_hint' => '999'],
                'expectedhintclaims' => [],
            ],
            'resource_link_id in the claim is not cast to a string' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(),
                    'resourcelink' => self::make_resource_link(['id' => 42]),
                    'userid' => 103001,
                ],
                'expectedparams' => [],
                'expectedhintclaims' => [
                    $prefix . '/claim/resource_link' => [
                        'id' => 42, // Raw int, not strval()-ed.
                        'title' => 'Resource 1',
                        'description' => 'A plain text description of resource 1',
                    ],
                ],
            ],
            'empty roles array produces an empty roles claim' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(),
                    'resourcelink' => self::make_resource_link(),
                    'userid' => 103001,
                    'roles' => [],
                ],
                'expectedparams' => [],
                'expectedhintclaims' => [$prefix . '/claim/roles' => []],
            ],
            'a single role is placed in the roles claim array' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(),
                    'resourcelink' => self::make_resource_link(),
                    'userid' => 103001,
                    'roles' => ['Instructor'],
                ],
                'expectedparams' => [],
                'expectedhintclaims' => [$prefix . '/claim/roles' => ['Instructor']],
            ],
            'multiple roles are preserved as an array in the roles claim' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(),
                    'resourcelink' => self::make_resource_link(),
                    'userid' => 103001,
                    'roles' => [
                        'Instructor',
                        'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator',
                        'http://example.com/custom/role',
                    ],
                ],
                'expectedparams' => [],
                'expectedhintclaims' => [
                    $prefix . '/claim/roles' => [
                        'Instructor',
                        'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator',
                        'http://example.com/custom/role',
                    ],
                ],
            ],
            'extra claims supplied by the caller appear in the JWT hint' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(),
                    'resourcelink' => self::make_resource_link(),
                    'userid' => 103001,
                    'extraclaims' => [
                        'some_claim' => ['item1', 'item2'],
                        $prefix . '/claim/context' => ['id' => 'ctx-456', 'label' => 'My Course'],
                    ],
                ],
                'expectedparams' => [],
                'expectedhintclaims' => [
                    'some_claim' => ['item1', 'item2'],
                    $prefix . '/claim/context' => ['id' => 'ctx-456', 'label' => 'My Course'],
                ],
            ],
        ];
    }
}
