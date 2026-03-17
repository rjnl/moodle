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

namespace core_ltix\local\lticore\message\payload\parameters\processor\converter;

use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\payload\lti_1px_payload_converter;
use core_ltix\local\lticore\message\payload\lis_vocab_converter;
use core_ltix\local\lticore\message\type\message_type_factory;
use local\lticore\message\payload\lti_1px_payload_converter_test;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering jwt_claim_converter.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(jwt_claim_converter::class)]
class jwt_claim_converter_test extends \basic_testcase {

    /**
     * Test processing with mixed parameters.
     *
     * This is deliberately not a comprehensive test of all possible parameter inputs. Instead, it focuses on the most common
     * parameters in order to ensure the lti_1px_payload_converter and vocab_converter dependencies are being called correctly.
     * {@see lti_1px_payload_converter_test for a comprehensive test of the lti_1px_payload_converter}.
     *
     * @return void
     */
    public function test_process_with_mixed_parameters(): void {
        $converter = new lti_1px_payload_converter(new lis_vocab_converter());
        $jwtclaimconverter = new jwt_claim_converter($converter);

        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        $params = [
            'user_id' => '5',
            'lis_person_sourcedid' => 'source123',
            'lis_person_name_given' => 'John',
            'lis_person_name_family' => 'Doe',
            'lis_person_name_full' => 'John Doe',
            'lis_person_contact_email_primary' => 'john@example.com',
            'lti_message_type' => 'LtiResourceLinkRequest',
            'context_id' => '5',
            'context_label' => 'Test course 1',
            'context_title' => 'Test course 1',
            'context_type' => 'CourseSection', // Hits vocab converter dependency.
            'resource_link_id' => '10',
            'resource_link_title' => 'Assignment 1',
            'launch_presentation_locale' => 'en',
            'launch_presentation_document_target' => 'iframe',
            'launch_presentation_return_url' => 'https://lms.example.com/lti/return',
            'tool_consumer_info_product_family_code' => 'moodle',
            'tool_consumer_info_version' => '2025041401.07',
            'tool_consumer_instance_guid' => '3edd2c9e4820ec0ac5d875bc636a70cd',
            'tool_consumer_instance_name' => 'Test Server',
            'tool_consumer_instance_description' => 'Test Instance',
            'ext_user_username' => 'john_doe',
            'custom_environment' => 'staging',
        ];

        $claims = $jwtclaimconverter->process($params, $launchcontext);

        // Verify the subject claim.
        $this->assertEquals('5', $claims['sub']);

        // Verify the message_type claim.
        $this->assertEquals('LtiResourceLinkRequest', $claims['https://purl.imsglobal.org/spec/lti/claim/message_type']);

        // Verify LIS claims.
        $this->assertArrayHasKey('https://purl.imsglobal.org/spec/lti/claim/lis', $claims);
        $this->assertEquals('source123', $claims['https://purl.imsglobal.org/spec/lti/claim/lis']['person_sourcedid']);

        // Verify user name claims.
        $this->assertEquals('John', $claims['given_name']);
        $this->assertEquals('Doe', $claims['family_name']);
        $this->assertEquals('John Doe', $claims['name']);

        // Verify email claim.
        $this->assertEquals('john@example.com', $claims['email']);

        // Verify context claim.
        $this->assertArrayHasKey('https://purl.imsglobal.org/spec/lti/claim/context', $claims);
        $contextclaim = $claims['https://purl.imsglobal.org/spec/lti/claim/context'];
        $this->assertEquals('5', $contextclaim['id']);
        $this->assertEquals('Test course 1', $contextclaim['label']);
        $this->assertEquals('Test course 1', $contextclaim['title']);
        $this->assertIsArray($contextclaim['type']);
        $this->assertEquals((new lis_vocab_converter())->to_v2_context_types(['CourseSection'])[0], $contextclaim['type'][0]);

        // Verify resource link claim.
        $this->assertArrayHasKey('https://purl.imsglobal.org/spec/lti/claim/resource_link', $claims);
        $this->assertEquals('10', $claims['https://purl.imsglobal.org/spec/lti/claim/resource_link']['id']);
        $this->assertEquals('Assignment 1', $claims['https://purl.imsglobal.org/spec/lti/claim/resource_link']['title']);

        // Verify launch presentation claims were created.
        $this->assertArrayHasKey('https://purl.imsglobal.org/spec/lti/claim/launch_presentation', $claims);
        $launchpresentationclaim = $claims['https://purl.imsglobal.org/spec/lti/claim/launch_presentation'];
        $this->assertEquals('en', $launchpresentationclaim['locale']);
        $this->assertEquals('iframe', $launchpresentationclaim['document_target']);
        $this->assertEquals('https://lms.example.com/lti/return', $launchpresentationclaim['return_url']);

        // Verify tool platform claims were created.
        $this->assertArrayHasKey('https://purl.imsglobal.org/spec/lti/claim/tool_platform', $claims);
        $toolplatformclaim = $claims['https://purl.imsglobal.org/spec/lti/claim/tool_platform'];
        $this->assertEquals('moodle', $toolplatformclaim['product_family_code']);
        $this->assertEquals('2025041401.07', $toolplatformclaim['version']);
        $this->assertEquals('3edd2c9e4820ec0ac5d875bc636a70cd', $toolplatformclaim['guid']);
        $this->assertEquals('Test Server', $toolplatformclaim['name']);
        $this->assertEquals('Test Instance', $toolplatformclaim['description']);

        // Verify extension claims.
        $this->assertArrayHasKey('https://purl.imsglobal.org/spec/lti/claim/ext', $claims);
        $this->assertEquals('john_doe', $claims['https://purl.imsglobal.org/spec/lti/claim/ext']['user_username']);

        // Verify custom claims.
        $this->assertArrayHasKey('https://purl.imsglobal.org/spec/lti/claim/custom', $claims);
        $this->assertEquals('staging', $claims['https://purl.imsglobal.org/spec/lti/claim/custom']['environment']);
    }
}
