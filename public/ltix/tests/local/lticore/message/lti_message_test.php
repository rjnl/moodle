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

namespace core_ltix\local\lticore\message;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering lti_message.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(lti_message::class)]
class lti_message_test extends \basic_testcase {

    /**
     * Test that get_url() returns the URL passed to the constructor.
     *
     * @return void
     */
    public function test_get_url_returns_the_url(): void {
        $message = new lti_message('https://tool.example.com/launch', []);

        $this->assertSame('https://tool.example.com/launch', $message->get_url());
    }

    /**
     * Test that get_parameters() returns the parameters array passed to the constructor.
     *
     * @return void
     */
    public function test_get_parameters_returns_the_parameters(): void {
        $params = ['lti_version' => 'LTI-1p0', 'lti_message_type' => 'basic-lti-launch-request'];
        $message = new lti_message('https://tool.example.com/launch', $params);

        $this->assertSame($params, $message->get_parameters());
    }

    /**
     * Test that get_parameters() returns an empty array when no parameters were given.
     *
     * @return void
     */
    public function test_get_parameters_returns_empty_array_when_no_params(): void {
        $message = new lti_message('https://tool.example.com/launch', []);

        $this->assertSame([], $message->get_parameters());
    }

    /**
     * Test that the form's action attribute is set to the message URL.
     *
     * @return void
     */
    public function test_to_html_form_action_is_the_message_url(): void {
        $message = new lti_message('https://tool.example.com/launch', []);

        $this->assertStringContainsString('action="https://tool.example.com/launch"', $message->to_html_form());
    }

    /**
     * Test that the form uses the POST method.
     *
     * @return void
     */
    public function test_to_html_form_uses_post_method(): void {
        $message = new lti_message('https://tool.example.com/launch', []);

        $this->assertStringContainsString('method="post"', $message->to_html_form());
    }

    /**
     * Test that each parameter appears as a hidden input field in the form.
     *
     * @return void
     */
    public function test_to_html_form_produces_hidden_input_for_each_parameter(): void {
        $message = new lti_message('https://tool.example.com/launch', [
            'lti_version' => 'LTI-1p0',
            'lti_message_type' => 'basic-lti-launch-request',
        ]);
        $html = $message->to_html_form();

        $this->assertStringContainsString('name="lti_version" value="LTI-1p0"', $html);
        $this->assertStringContainsString('name="lti_message_type" value="basic-lti-launch-request"', $html);
    }

    /**
     * Test that a message with no parameters produces a form with no hidden inputs.
     *
     * @return void
     */
    public function test_to_html_form_with_no_parameters_has_no_hidden_inputs(): void {
        $message = new lti_message('https://tool.example.com/launch', []);

        $this->assertStringNotContainsString('<input', $message->to_html_form());
    }

    /**
     * Test that parameter keys and values containing HTML special characters are properly encoded.
     *
     * @return void
     */
    public function test_to_html_form_html_encodes_parameter_keys_and_values(): void {
        $message = new lti_message('https://tool.example.com/launch', [
            'key_with_<special>' => 'value & "quoted"',
        ]);
        $html = $message->to_html_form();

        $this->assertStringContainsString('name="key_with_&lt;special&gt;"', $html);
        $this->assertStringContainsString('value="value &amp; &quot;quoted&quot;"', $html);
        // Ensure the raw unencoded characters are not present in the input attributes.
        $this->assertStringNotContainsString('name="key_with_<special>"', $html);
        $this->assertStringNotContainsString('value="value & "quoted""', $html);
    }

    /**
     * Test that the auto-submit JavaScript is present in the output.
     *
     * @return void
     */
    public function test_to_html_form_contains_auto_submit_javascript(): void {
        $message = new lti_message('https://tool.example.com/launch', []);
        $html = $message->to_html_form();

        $this->assertStringContainsString('<script type="text/javascript">', $html);
        $this->assertStringContainsString('document.ltiMessagePostForm.submit();', $html);
    }

    /**
     * Test that to_html_form() returns a string.
     *
     * @return void
     */
    public function test_to_html_form_returns_a_string(): void {
        $message = new lti_message('https://tool.example.com/launch', ['foo' => 'bar']);

        $this->assertIsString($message->to_html_form());
    }
}
