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

namespace core_ltix\local\lticore\message\context\item;

use core_ltix\local\lticore\message\type\message_type;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering message_context.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(message_context::class)]
class message_context_test extends \basic_testcase {

    /**
     * Test that the messagetype property holds the message_type passed to the constructor.
     *
     * @return void
     */
    public function test_messagetype_property_holds_the_supplied_message_type(): void {
        $type = message_type::create('basic-lti-launch-request', 'LtiResourceLinkRequest');
        $ctx = new message_context($type);

        $this->assertSame($type, $ctx->messagetype);
    }

    /**
     * Test that the messagetype property is the exact same object instance (no cloning).
     *
     * @return void
     */
    public function test_messagetype_property_is_same_instance(): void {
        $type = message_type::create('basic-lti-launch-request', 'LtiResourceLinkRequest');
        $ctx = new message_context($type);

        $this->assertSame($type, $ctx->messagetype);
    }

    /**
     * Test that the message type's value is accessible through the property.
     *
     * @return void
     */
    public function test_messagetype_value_is_accessible_through_property(): void {
        $ctx = new message_context(message_type::create('basic-lti-launch-request', 'LtiResourceLinkRequest'));

        $this->assertSame('basic-lti-launch-request', $ctx->messagetype->value());
    }

    /**
     * Test that the message type's canonical form is accessible through the property.
     *
     * @return void
     */
    public function test_messagetype_canonical_is_accessible_through_property(): void {
        $ctx = new message_context(message_type::create('basic-lti-launch-request', 'LtiResourceLinkRequest'));

        $this->assertSame('LtiResourceLinkRequest', $ctx->messagetype->canonical());
    }
}
