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

namespace core_ltix\local\lticore\message\launchrequest\builder\v1p1;

use core_ltix\constants;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\models\resource_link;
use core_ltix\OAuthRequest;
use core_ltix\OAuthServer;
use core_ltix\OAuthSignatureMethod_HMAC_SHA1;
use core_ltix\TrivialOAuthDataStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering v1p1_resource_link_launch_request_builder.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(v1p1_resource_link_launch_request_builder::class)]
class v1p1_resource_link_launch_request_builder_test extends \basic_testcase {

    /**
     * Return a default tool config suitable for v1p1 launches.
     *
     * @return \stdClass
     */
    private static function make_toolconfig(): \stdClass {
        return (object) [
            'tool' => (object) [
                'baseurl' => 'https://tool.example.com',
            ],
            'config' => (object) [
                'resourcekey' => 'CONSUMER_KEY',
                'password' => 'CONSUMER_SECRET',
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
     * Test that the produced message carries a valid HMAC-SHA1 OAuth signature.
     *
     * @return void
     */
    public function test_message_is_properly_signed(): void {
        $toolconfig = self::make_toolconfig();
        $message = (new v1p1_resource_link_launch_request_builder())
            ->build_message($toolconfig, self::make_resource_link());

        $this->assertEquals('about:blank', $message->get_parameters()['oauth_callback']);

        $store = new TrivialOAuthDataStore();
        $store->add_consumer($toolconfig->config->resourcekey, $toolconfig->config->password);
        $server = new OAuthServer($store);
        $server->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
        $request = new OAuthRequest('POST', $message->get_url(), $message->get_parameters());
        // Note: verify_request() throws on an invalid signature; a successful return is an array.
        $this->assertIsArray($server->verify_request($request));
    }

    /**
     * Test that the resource link's own URL is used as the launch URL when it is set.
     *
     * @return void
     */
    public function test_resource_link_url_used_as_launch_url(): void {
        $resourcelink = self::make_resource_link(['url' => 'https://tool.example.com/lti/resource/1']);
        $message = (new v1p1_resource_link_launch_request_builder())
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
        $message = (new v1p1_resource_link_launch_request_builder())
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
        $message = (new v1p1_resource_link_launch_request_builder())
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
        $message = (new v1p1_resource_link_launch_request_builder())
            ->build_message(self::make_toolconfig(), self::make_resource_link(), extraparams: $extraparams);

        $params = self::non_oauth_params($message->get_parameters());
        $this->assertEquals(lti_version::LTI_VERSION_1->value, $params['lti_version']);
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
        $message = (new v1p1_resource_link_launch_request_builder())
            ->build_message($this->make_toolconfig(), $resourcelink);

        $this->assertEquals('This is a PARAGRAPH with _some_ links.', $message->get_parameters()['resource_link_description']);
    }

    /**
     * Test that the builder can build unsigned requests when resourcekey and password config are not set.
     * @return void
     */
    public function test_unsigned_request_is_valid(): void {
        $toolconfig = self::make_toolconfig();
        unset($toolconfig->config->resourcekey);
        unset($toolconfig->config->password);
        $message = (new v1p1_resource_link_launch_request_builder())
            ->build_message($toolconfig, self::make_resource_link());
        $this->assertIsArray($message->get_parameters());
        $this->assertSame($this->non_oauth_params($message->get_parameters()), $message->get_parameters());
    }

    /**
     * Test that build_message() produces the correct message parameters for a range of inputs.
     *
     * @param array $input keys: toolconfig, resourcelink, and optionally roles and extraparams.
     * @param array $expectedparams expected key/value pairs to find in the non-OAuth params.
     * @return void
     */
    #[DataProvider('build_message_provider')]
    public function test_build_message(array $input, array $expectedparams): void {
        $message = (new v1p1_resource_link_launch_request_builder())->build_message(
            $input['toolconfig'],
            $input['resourcelink'],
            $input['roles'] ?? [],
            $input['extraparams'] ?? [],
        );

        $params = self::non_oauth_params($message->get_parameters());
        foreach ($expectedparams as $key => $value) {
            $this->assertArrayHasKey($key, $params, "Expected param '$key' to be present");
            $this->assertEquals($value, $params[$key], "Param '$key' has unexpected value");
        }
    }

    /**
     * Data provider for test_build_message().
     *
     * @return array
     */
    public static function build_message_provider(): array {
        $base = [
            'lti_version' => \core_ltix\constants::LTI_VERSION_1,
            'lti_message_type' => 'basic-lti-launch-request',
            'resource_link_id' => '24',
            'resource_link_title' => 'Resource 1',
            'resource_link_description' => 'A plain text description of resource 1',
        ];
        return [
            'all required params are present in every launch' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(),
                    'resourcelink' => self::make_resource_link(),
                ],
                'expectedparams' => $base,
            ],
            'resource_link_id is always cast to a string even when model id is an integer' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(),
                    'resourcelink' => self::make_resource_link(['id' => 999]),
                ],
                'expectedparams' => array_merge($base, ['resource_link_id' => '999']),
            ],
            'empty roles array results in an omitted roles param' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(),
                    'resourcelink' => self::make_resource_link(),
                    'roles' => [],
                ],
                'expectedparams' => $base,
            ],
            'a single role is set directly as the roles param value' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(),
                    'resourcelink' => self::make_resource_link(),
                    'roles' => ['Instructor'],
                ],
                'expectedparams' => array_merge($base, ['roles' => 'Instructor']),
            ],
            'multiple roles are comma-joined with no spaces' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(),
                    'resourcelink' => self::make_resource_link(),
                    'roles' => ['Instructor', 'Administrator', 'urn:lti:sysrole:ims/lis/SysAdmin'],
                ],
                'expectedparams' => array_merge($base, ['roles' => 'Instructor,Administrator,urn:lti:sysrole:ims/lis/SysAdmin']),
            ],
            'extra params supplied by the caller are included in the message' => [
                'input' => [
                    'toolconfig' => self::make_toolconfig(),
                    'resourcelink' => self::make_resource_link(),
                    'extraparams' => [
                        'user_id' => '103001',
                        'lis_person_name_full' => 'Jane Doe',
                    ],
                ],
                'expectedparams' => array_merge($base, ['user_id' => '103001', 'lis_person_name_full' => 'Jane Doe']),
            ],
        ];
    }
}
