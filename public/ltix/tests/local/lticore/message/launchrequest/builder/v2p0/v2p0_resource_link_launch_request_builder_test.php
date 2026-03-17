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

namespace core_ltix\local\lticore\message\launchrequest\builder\v2p0;

use core_ltix\constants;
use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\models\resource_link;
use core_ltix\OAuthRequest;
use core_ltix\OAuthServer;
use core_ltix\OAuthSignatureMethod_HMAC_SHA1;
use core_ltix\TrivialOAuthDataStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering v2p0_resource_link_launch_request_builder.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(v2p0_resource_link_launch_request_builder::class)]
class v2p0_resource_link_launch_request_builder_test extends \basic_testcase {

    /** Capabilities required for the three core resource-link params to appear in any launch message. */
    private const RESOURCE_LINK_CAPS = "ResourceLink.id\nResourceLink.title\nResourceLink.description";

    /**
     * Return a default tool config, optionally with extra capability lines appended.
     *
     * The base config always includes RESOURCE_LINK_CAPS so tests only need to specify
     * the capabilities that are relevant to what they are asserting.
     *
     * @param string $extracapabilities newline-separated capability names to append.
     * @return \stdClass
     */
    private static function make_toolconfig(string $extracapabilities = ''): \stdClass {
        $capabilities = self::RESOURCE_LINK_CAPS . ($extracapabilities ? "\n{$extracapabilities}" : '');
        return (object) [
            'tool' => (object) [
                'id' => '123',
                'baseurl' => 'https://tool.example.com',
                'enabledcapability' => $capabilities,
            ],
            'toolproxy' => (object) [
                'guid' => 'RDL4JE5M1wr2Ke9',
                'secret' => '6E2KfWpbMsrw',
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
            'gradable' => true,
            'servicesalt' => 'abc123',
        ], $overrides));
    }

    /**
     * Strip OAuth-added params from a message's parameter array so that assertions can
     * focus on LTI-level params without being coupled to OAuth internals.
     *
     * @param array $messageparams the full parameter array from an lti_message.
     * @return array the parameters with all oauth_* keys removed.
     */
    private static function non_oauth_params(array $messageparams): array {
        return array_diff_key($messageparams, array_flip([
            'oauth_version',
            'oauth_nonce',
            'oauth_timestamp',
            'oauth_consumer_key',
            'oauth_signature_method',
            'oauth_signature',
            'oauth_callback',
        ]));
    }

    /**
     * Test that the produced message carries a valid OAuth signature and includes the
     * oauth_callback field required for 1.0a compliance.
     *
     * @return void
     */
    public function test_message_is_properly_signed(): void {
        $toolconfig = self::make_toolconfig();
        $message = (new v2p0_resource_link_launch_request_builder())
            ->build_message($toolconfig, self::make_resource_link());

        $this->assertEquals('about:blank', $message->get_parameters()['oauth_callback']);

        $store = new TrivialOAuthDataStore();
        $store->add_consumer($toolconfig->toolproxy->guid, $toolconfig->toolproxy->secret);
        $server = new OAuthServer($store);
        $server->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
        $request = new OAuthRequest('POST', $message->get_url(), $message->get_parameters());
        // Note: verify_request() throws on an invalid signature.
        $this->assertIsArray($server->verify_request($request));
    }

    /**
     * Test that a missing toolproxy on the tool config throws an lti_exception.
     *
     * @return void
     */
    public function test_missing_tool_proxy_throws(): void {
        $toolconfig = (object) [
            'tool' => (object) [
                'id' => '123',
                'baseurl' => 'https://tool.example.com',
                'enabledcapability' => self::RESOURCE_LINK_CAPS,
            ],
            // No 'toolproxy' key.
        ];

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessage('Error: Tool is missing Tool Proxy. Tool Proxy required for LTI-2p0 launches');
        (new v2p0_resource_link_launch_request_builder())
            ->build_message($toolconfig, self::make_resource_link());
    }

    /**
     * Test that a missing ResourceLink.id capability throws an lti_exception.
     * This capability is required because the resource_link_id param is fundamental to the message type.
     *
     * @return void
     */
    public function test_missing_resource_link_id_capability_throws(): void {
        $toolconfig = (object) [
            'tool' => (object) [
                'id' => '123',
                'baseurl' => 'https://tool.example.com',
                // No enabledcapability at all — ResourceLink.id will not be present.
            ],
            'toolproxy' => (object) [
                'guid' => 'RDL4JE5M1wr2Ke9',
                'secret' => '6E2KfWpbMsrw',
            ],
        ];

        $this->expectException(lti_exception::class);
        $this->expectExceptionMessage('ResourceLink.id capability is required for v2p0 resource link launch requests');
        (new v2p0_resource_link_launch_request_builder())
            ->build_message($toolconfig, self::make_resource_link());
    }

    /**
     * Test that the resource link's own URL is used as the launch URL when it is set.
     *
     * @return void
     */
    public function test_resource_link_url_takes_precedence_over_tool_baseurl(): void {
        $resourcelink = self::make_resource_link(['url' => 'https://tool.example.com/lti/resource/1']);
        $message = (new v2p0_resource_link_launch_request_builder())
            ->build_message(self::make_toolconfig(), $resourcelink);

        $this->assertEquals('https://tool.example.com/lti/resource/1', $message->get_url());
    }

    /**
     * Test that the tool's base URL is used as the launch URL when the resource link URL is empty.
     *
     * @return void
     */
    public function test_tool_baseurl_used_when_resource_link_url_is_empty(): void {
        $resourcelink = self::make_resource_link(['url' => '']);
        $message = (new v2p0_resource_link_launch_request_builder())
            ->build_message(self::make_toolconfig(), $resourcelink);

        $this->assertEquals('https://tool.example.com', $message->get_url());
    }

    /**
     * Test that resource_link_description is omitted from the message when the resource link has no text.
     *
     * @return void
     */
    public function test_null_description_omits_description_param(): void {
        $resourcelink = self::make_resource_link(['text' => null]);
        $message = (new v2p0_resource_link_launch_request_builder())
            ->build_message(self::make_toolconfig(), $resourcelink);

        $this->assertArrayNotHasKey('resource_link_description', self::non_oauth_params($message->get_parameters()));
    }

    /**
     * Test that required params (lti_version, lti_message_type, resource_link_id) cannot be
     * overridden by values supplied via $extraparams.
     *
     * @return void
     */
    public function test_required_params_cannot_be_overridden_by_extra_params(): void {
        $extraparams = [
            'lti_version' => '42',
            'lti_message_type' => 'not-a-real-message-type',
            'resource_link_id' => '77777777777',
        ];
        $message = (new v2p0_resource_link_launch_request_builder())
            ->build_message(self::make_toolconfig(), self::make_resource_link(), extraparams: $extraparams);

        $params = self::non_oauth_params($message->get_parameters());
        $this->assertEquals(\core_ltix\constants::LTI_VERSION_2, $params['lti_version']);
        $this->assertEquals('basic-lti-launch-request', $params['lti_message_type']);
        $this->assertEquals('24', $params['resource_link_id']);
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
        $message = (new v2p0_resource_link_launch_request_builder())
            ->build_message($this->make_toolconfig(), $resourcelink);

        $this->assertEquals('This is a PARAGRAPH with _some_ links.', $message->get_parameters()['resource_link_description']);
    }

    /**
     * Test that the builder correctly includes params based on the capabilities of the tool.
     *
     * @param array $input          Keys: toolconfig, resourcelink, and optionally userid, roles, extraparams.
     * @param array $expectedparams Expected non-OAuth params in the returned message.
     * @return void
     */
    #[DataProvider('capability_controlled_params_provider')]
    public function test_capability_controlled_params(array $input, array $expectedparams): void {
        $message = (new v2p0_resource_link_launch_request_builder())->build_message(
            $input['toolconfig'],
            $input['resourcelink'],
            $input['userid'] ?? '',
            $input['roles'] ?? [],
            $input['extraparams'] ?? [],
        );

        $this->assertEquals($expectedparams, self::non_oauth_params($message->get_parameters()));
    }

    /**
     * Data provider for test_capability_controlled_params().
     *
     * @return array
     */
    public static function capability_controlled_params_provider(): array {
        // Base params present in every case: the three core resource-link fields plus the
        // required LTI protocol fields. Individual cases extend or narrow this as needed.
        $base = [
            'lti_version' => \core_ltix\constants::LTI_VERSION_2,
            'lti_message_type' => 'basic-lti-launch-request',
            'resource_link_id' => '24',
            'resource_link_title' => 'Resource 1',
            'resource_link_description' => 'A plain text description of resource 1',
        ];

        return [
            'user_id and role are included when User.id and Membership.role capabilities are enabled' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig("User.id\nMembership.role"),
                    'resourcelink' => self::make_resource_link(),
                    'userid' => '35',
                    'roles' => ['Instructor'],
                ],
                'expectedparams' => array_merge($base, ['user_id' => '35', 'roles' => 'Instructor']),
            ],
            'user_id and roles are excluded when User.id and Membership.role capabilities are not enabled' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(), // No User.id or Membership.role.
                    'resourcelink' => self::make_resource_link(),
                    'userid' => '35',
                    'roles' => ['Instructor'],
                ],
                'expectedparams' => $base,
            ],
            'multiple roles are comma-joined in the roles param' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig("User.id\nMembership.role"),
                    'resourcelink' => self::make_resource_link(),
                    'userid' => '35',
                    'roles' => ['Instructor', 'Learner', 'AnotherRole'],
                ],
                'expectedparams' => array_merge($base, [
                    'user_id' => '35',
                    'roles' => 'Instructor,Learner,AnotherRole',
                ]),
            ],
            'empty userid and empty roles are both omitted even when their capabilities are enabled' => [
                // Note: build() only merges user_id when $userid is non-empty, and only merges roles when $roles is non-empty.
                'input' => [
                    'toolconfig' => self::make_toolconfig("User.id\nMembership.role"),
                    'resourcelink' => self::make_resource_link(),
                    'userid' => '',
                    'roles' => [],
                ],
                'expectedparams' => $base,
            ],
            'extra param included when its controlling capability is enabled' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig('Person.name.full'),
                    'resourcelink' => self::make_resource_link(),
                    'extraparams' => ['lis_person_name_full' => 'John Doe'],
                ],
                'expectedparams' => array_merge($base, ['lis_person_name_full' => 'John Doe']),
            ],
            'extra param excluded when its controlling capability is not enabled' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(), // Person.name.full not enabled.
                    'resourcelink' => self::make_resource_link(),
                    'extraparams' => ['lis_person_name_full' => 'John Doe'],
                ],
                'expectedparams' => $base,
            ],
            'extra params with no capability mapping always pass through' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(), // No extra capabilities needed.
                    'resourcelink' => self::make_resource_link(),
                    'extraparams' => ['some_uncontrolled_param' => 'param_value'],
                ],
                'expectedparams' => array_merge($base, ['some_uncontrolled_param' => 'param_value']),
            ],
        ];
    }
}
