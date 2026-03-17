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

namespace core_ltix\local\lticore\message\type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering message_type.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(message_type::class)]
class message_type_test extends \basic_testcase {

    /**
     * Test that a message type can be created with value and canonical form.
     *
     * @return void
     */
    public function test_create_message_type(): void {
        $messagetype = message_type::create('basic-lti-launch-request', 'basic-lti-launch-request');

        $this->assertSame('basic-lti-launch-request', $messagetype->value());
        $this->assertSame('basic-lti-launch-request', $messagetype->canonical());
    }

    /**
     * Test that value and canonical form can differ.
     *
     * @return void
     */
    public function test_create_with_different_value_and_canonical(): void {
        $messagetype = message_type::create('basic-lti-launch-request', 'LtiResourceLinkRequest');

        $this->assertSame('basic-lti-launch-request', $messagetype->value());
        $this->assertSame('LtiResourceLinkRequest', $messagetype->canonical());
    }

    /**
     * Test that two message types with the same canonical form are equal.
     *
     * @return void
     */
    public function test_equals_same_canonical(): void {
        $messagetype1 = message_type::create('basic-lti-launch-request', 'LtiResourceLinkRequest');
        $messagetype2 = message_type::create('LtiResourceLinkRequest', 'LtiResourceLinkRequest');

        $this->assertTrue($messagetype1->equals($messagetype2));
    }

    /**
     * Test that two message types with different canonical forms are not equal.
     *
     * @return void
     */
    public function test_not_equals_different_canonical(): void {
        $messagetype1 = message_type::create('basic-lti-launch-request', 'LtiResourceLinkRequest');
        $messagetype2 = message_type::create('ContentItemSelectionRequest', 'LtiDeepLinkingRequest');

        $this->assertFalse($messagetype1->equals($messagetype2));
    }

    /**
     * Data provider for string representation tests.
     *
     * @return array test cases with [value, canonical, expectedString]
     */
    public static function string_representations(): array {
        return [
            'lowercase' => [
                'basic-lti-launch-request',
                'basic-lti-launch-request',
                'basic-lti-launch-request',
            ],
            'camelcase to lowercase' => [
                'BasicLTILaunchRequest',
                'basic-lti-launch-request',
                'BasicLTILaunchRequest',
            ],
            'uppercase to lowercase' => [
                'BASIC-LTI-LAUNCH-REQUEST',
                'basic-lti-launch-request',
                'BASIC-LTI-LAUNCH-REQUEST',
            ],
        ];
    }

    /**
     * Test that string representation returns the raw value.
     *
     * @param string $value the message type value
     * @param string $canonical the canonical form
     * @param string $expectedString the expected string representation
     * @return void
     */
    #[DataProvider('string_representations')]
    public function test_to_string(string $value, string $canonical, string $expectedString): void {
        $messagetype = message_type::create($value, $canonical);

        $this->assertSame($expectedString, (string)$messagetype);
    }
}
