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

/**
 * Tests covering message_type_factory.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(message_type_factory::class)]
class message_type_factory_test extends \basic_testcase {

    /**
     * Test that a message type can be created from a registered alias.
     *
     * @return void
     */
    public function test_create_from_registered_alias(): void {
        $definition = new message_type_definition(
            'LtiResourceLinkRequest',
            ['basic-lti-launch-request'],
        );
        $registry = new message_type_registry([$definition]);
        $factory = new message_type_factory($registry);

        $messagetype = $factory->from_string('basic-lti-launch-request');

        $this->assertSame('basic-lti-launch-request', $messagetype->value());
        $this->assertSame('LtiResourceLinkRequest', $messagetype->canonical());
    }

    /**
     * Test that a message type created from a registered alias equals one from the canonical form.
     *
     * @return void
     */
    public function test_created_from_alias_equals_canonical(): void {
        $definition = new message_type_definition(
            'LtiResourceLinkRequest',
            ['basic-lti-launch-request'],
        );
        $registry = new message_type_registry([$definition]);
        $factory = new message_type_factory($registry);

        $fromalias = $factory->from_string('basic-lti-launch-request');
        $fromcanonical = $factory->from_string('LtiResourceLinkRequest');

        $this->assertTrue($fromalias->equals($fromcanonical));
    }

    /**
     * Test that an unregistered message type is created with value as canonical form.
     *
     * @return void
     */
    public function test_create_from_unregistered_value(): void {
        $registry = new message_type_registry([]);
        $factory = new message_type_factory($registry);

        $messagetype = $factory->from_string('unknown-message-type');

        $this->assertSame('unknown-message-type', $messagetype->value());
        $this->assertSame('unknown-message-type', $messagetype->canonical());
    }

    /**
     * Test that multiple registered definitions are handled correctly.
     *
     * @return void
     */
    public function test_factory_with_multiple_definitions(): void {
        $definition1 = new message_type_definition(
            'LtiResourceLinkRequest',
            ['basic-lti-launch-request'],
        );
        $definition2 = new message_type_definition(
            'LtiDeepLinkingRequest',
            ['ContentItemSelectionRequest'],
        );
        $registry = new message_type_registry([$definition1, $definition2]);
        $factory = new message_type_factory($registry);

        $messagetype1 = $factory->from_string('LtiResourceLinkRequest');
        $messagetype2 = $factory->from_string('ContentItemSelectionRequest');

        $this->assertSame('LtiResourceLinkRequest', $messagetype1->canonical());
        $this->assertSame('LtiDeepLinkingRequest', $messagetype2->canonical());
    }

    /**
     * Test that an unregistered value is not affected by other registered definitions.
     *
     * @return void
     */
    public function test_unregistered_with_other_definitions(): void {
        $definition = new message_type_definition(
            'LtiResourceLinkRequest',
            ['basic-lti-launch-request'],
        );
        $registry = new message_type_registry([$definition]);
        $factory = new message_type_factory($registry);

        $messagetype = $factory->from_string('custom-message-type');

        $this->assertSame('custom-message-type', $messagetype->value());
        $this->assertSame('custom-message-type', $messagetype->canonical());
    }
}
