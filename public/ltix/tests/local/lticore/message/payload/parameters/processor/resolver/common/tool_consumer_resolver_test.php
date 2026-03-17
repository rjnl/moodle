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

namespace core_ltix\local\lticore\message\payload\parameters\processor\resolver\common;

use core_ltix\constants;
use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\context\item\tool_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering tool_consumer_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(tool_consumer_resolver::class)]
class tool_consumer_resolver_test extends \advanced_testcase {

    /**
     * Helper returning a tool config stub.
     *
     * @param string|null $organizationid the organization ID, or null to omit.
     * @return \stdClass the object stub.
     */
    protected function get_tool_config_stub(?string $organizationid = 'https://platform.example.com'): \stdClass {
        return (object) [
            'tool' => (object) [
                'id' => '123',
                'clientid' => '123456-abcd',
                'ltiversion' => '1.3.0',
            ],
            'config' => (object) [
                'initiatelogin' => 'https://tool.example.com/lti/initiatelogin',
                'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
                ...($organizationid !== null ? ['organizationid' => $organizationid] : []),
            ],
        ];
    }

    /**
     * Test processing with default configuration.
     *
     * @return void
     */
    public function test_process_with_default_config(): void {
        global $CFG;

        $resolver = new tool_consumer_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub())
        );

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The existing params are unchanged.
        $this->assertEquals($params['existing_param'], $finalparams['existing_param']);

        // The expected tool consumer params were added.
        $this->assertArrayHasKey('tool_consumer_info_product_family_code', $finalparams);
        $this->assertEquals('moodle', $finalparams['tool_consumer_info_product_family_code']);

        $this->assertArrayHasKey('tool_consumer_info_version', $finalparams);
        $this->assertEquals(strval($CFG->version), $finalparams['tool_consumer_info_version']);

        $this->assertArrayHasKey('tool_consumer_instance_guid', $finalparams);
        $this->assertEquals('https://platform.example.com', $finalparams['tool_consumer_instance_guid']);

        $this->assertArrayHasKey('tool_consumer_instance_name', $finalparams);
        $this->assertNotEmpty($finalparams['tool_consumer_instance_name']);

        // The instance description should match site fullname (with HTML stripped).
        $this->assertArrayHasKey('tool_consumer_instance_description', $finalparams);
        $site = get_site();
        $expectedDescription = trim(html_to_text($site->fullname, 0));
        $this->assertEquals($expectedDescription, $finalparams['tool_consumer_instance_description']);
    }

    /**
     * Test processing with custom ltix_institution_name.
     *
     * @return void
     */
    public function test_process_with_ltix_institution_name(): void {
        global $CFG;

        $resolver = new tool_consumer_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub())
        );

        // Temporarily set the global institution name config.
        $originalinstitutionname = $CFG->ltix_institution_name ?? null;
        $CFG->ltix_institution_name = 'Test Institution Name';

        try {
            $params = ['existing_param' => 'value'];
            $finalparams = $resolver->process($params, $launchcontext);

            // The instance name should use the ltix_institution_name.
            $this->assertArrayHasKey('tool_consumer_instance_name', $finalparams);
            $this->assertEquals('Test Institution Name', $finalparams['tool_consumer_instance_name']);
        } finally {
            // Restore original value.
            if ($originalinstitutionname === null) {
                unset($CFG->ltix_institution_name);
            } else {
                $CFG->ltix_institution_name = $originalinstitutionname;
            }
        }
    }

    /**
     * Test processing with HTML and whitespace in institution name (should be stripped and trimmed).
     *
     * @return void
     */
    public function test_process_strips_html_and_whitespace_from_institution_name(): void {
        global $CFG;

        $resolver = new tool_consumer_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub())
        );

        // Temporarily set the global institution name config with HTML.
        $originalinstitutionname = $CFG->ltix_institution_name ?? null;
        $CFG->ltix_institution_name = '  <strong>Test</strong> Institution <em>Name</em>  ';

        try {
            $params = ['existing_param' => 'value'];
            $finalparams = $resolver->process($params, $launchcontext);

            // The instance name should have HTML stripped.
            $this->assertArrayHasKey('tool_consumer_instance_name', $finalparams);
            $this->assertEquals('TEST Institution _Name_', $finalparams['tool_consumer_instance_name']);
            $this->assertStringNotContainsString('<', $finalparams['tool_consumer_instance_name']);
            $this->assertStringNotContainsString('>', $finalparams['tool_consumer_instance_name']);
        } finally {
            // Restore original value.
            if ($originalinstitutionname === null) {
                unset($CFG->ltix_institution_name);
            } else {
                $CFG->ltix_institution_name = $originalinstitutionname;
            }
        }
    }

    /**
     * Test processing with legacy mod_lti_institution_name (deprecated fallback).
     *
     * @return void
     */
    public function test_process_with_deprecated_mod_lti_institution_name(): void {
        global $CFG;

        $resolver = new tool_consumer_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub())
        );

        // Temporarily set the legacy institution name config.
        $originalinstitutionname = $CFG->ltix_institution_name ?? null;
        $originallegacyname = $CFG->mod_lti_institution_name ?? null;

        // Ensure ltix_institution_name is not set so fallback is used.
        unset($CFG->ltix_institution_name);
        $CFG->mod_lti_institution_name = 'Legacy Institution Name';

        try {
            $params = ['existing_param' => 'value'];
            $finalparams = $resolver->process($params, $launchcontext);
            $this->assertDebuggingCalled();

            // The instance name should use the legacy mod_lti_institution_name.
            $this->assertArrayHasKey('tool_consumer_instance_name', $finalparams);
            $this->assertEquals('Legacy Institution Name', $finalparams['tool_consumer_instance_name']);
        } finally {
            // Restore original values.
            if ($originalinstitutionname === null) {
                unset($CFG->ltix_institution_name);
            } else {
                $CFG->ltix_institution_name = $originalinstitutionname;
            }
            if ($originallegacyname === null) {
                unset($CFG->mod_lti_institution_name);
            } else {
                $CFG->mod_lti_institution_name = $originallegacyname;
            }
        }
    }

    /**
     * Test processing falls back to site shortname when no institution name is configured.
     *
     * @return void
     */
    public function test_process_falls_back_to_site_shortname(): void {
        global $CFG;

        $resolver = new tool_consumer_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub())
        );

        // Temporarily unset institution name configs.
        $originalinstitutionname = $CFG->ltix_institution_name ?? null;
        $originallegacyname = $CFG->mod_lti_institution_name ?? null;

        unset($CFG->ltix_institution_name);
        unset($CFG->mod_lti_institution_name);

        try {
            $params = ['existing_param' => 'value'];
            $finalparams = $resolver->process($params, $launchcontext);

            // The instance name should fall back to site shortname.
            $this->assertArrayHasKey('tool_consumer_instance_name', $finalparams);
            $site = get_site();
            $this->assertEquals($site->shortname, $finalparams['tool_consumer_instance_name']);
        } finally {
            // Restore original values.
            if ($originalinstitutionname === null) {
                unset($CFG->ltix_institution_name);
            } else {
                $CFG->ltix_institution_name = $originalinstitutionname;
            }
            if ($originallegacyname === null) {
                unset($CFG->mod_lti_institution_name);
            } else {
                $CFG->mod_lti_institution_name = $originallegacyname;
            }
        }
    }

    /**
     * Test processing with custom organization ID in tool config.
     *
     * @return void
     */
    public function test_process_with_custom_organization_id(): void {
        $resolver = new tool_consumer_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $customorgid = 'https://custom.institution.edu';
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub($customorgid))
        );

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The instance GUID should use the custom organization ID.
        $this->assertArrayHasKey('tool_consumer_instance_guid', $finalparams);
        $this->assertEquals($customorgid, $finalparams['tool_consumer_instance_guid']);
    }

    /**
     * Test processing without organization ID uses default (site host).
     *
     * @return void
     */
    public function test_process_without_organization_id_uses_site_host(): void {
        global $CFG;

        $resolver = new tool_consumer_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        // Create tool config without organizationid.
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new tool_context($this->get_tool_config_stub(null))
        );

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The instance GUID should default to the site host.
        $this->assertArrayHasKey('tool_consumer_instance_guid', $finalparams);
        $urlparts = parse_url($CFG->wwwroot);
        $this->assertEquals($urlparts['host'], $finalparams['tool_consumer_instance_guid']);
    }

    /**
     * Test processing without a required tool context.
     *
     * @return void
     */
    public function test_process_no_tool_context(): void {
        $resolver = new tool_consumer_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);

        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        $params = ['existing_param' => 'value'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*tool_context, but it was not provided.*/");
        $resolver->process($params, $launchcontext);
    }
}
