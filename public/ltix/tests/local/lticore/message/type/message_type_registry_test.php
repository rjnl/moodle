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
 * Tests covering message_type_registry.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(message_type_registry::class)]
class message_type_registry_test extends \basic_testcase {

    /**
     * Test that a definition can be resolved by its canonical form.
     *
     * @return void
     */
    public function test_resolve_by_canonical(): void {
        $definition = new message_type_definition(
            'LtiResourceLinkRequest',
            ['basic-lti-launch-request'],
        );
        $registry = new message_type_registry([$definition]);

        $resolved = $registry->resolve('LtiResourceLinkRequest');

        $this->assertNotNull($resolved);
        $this->assertSame('LtiResourceLinkRequest', $resolved->canonical());
        $this->assertSame(['basic-lti-launch-request'], $resolved->aliases());
    }

    /**
     * Test that a definition can be resolved by one of its aliases.
     *
     * @return void
     */
    public function test_resolve_by_alias(): void {
        $definition = new message_type_definition(
            'LtiResourceLinkRequest',
            ['basic-lti-launch-request'],
        );
        $registry = new message_type_registry([$definition]);

        $resolved = $registry->resolve('basic-lti-launch-request');

        $this->assertNotNull($resolved);
        $this->assertSame('LtiResourceLinkRequest', $resolved->canonical());
        $this->assertSame(['basic-lti-launch-request'], $resolved->aliases());
    }

    /**
     * Test that resolving an unknown value returns null.
     *
     * @return void
     */
    public function test_resolve_unknown_returns_null(): void {
        $definition = new message_type_definition(
            'LtiResourceLinkRequest',
            ['basic-lti-launch-request'],
        );
        $registry = new message_type_registry([$definition]);

        $resolved = $registry->resolve('unknown-type');

        $this->assertNull($resolved);
    }

    /**
     * Test that resolving with case-sensitive lookup.
     *
     * @return void
     */
    public function test_resolve_case_sensitive(): void {
        $definition = new message_type_definition(
            'LtiResourceLinkRequest',
            ['basic-lti-launch-request'],
        );
        $registry = new message_type_registry([$definition]);

        $resolvedcorrect = $registry->resolve('basic-lti-launch-request');
        $resolvedwrongCase = $registry->resolve('BASIC-LTI-launch-request');

        $this->assertNotNull($resolvedcorrect);
        $this->assertNull($resolvedwrongCase);
    }

    /**
     * Test that multiple definitions can be registered and resolved independently.
     *
     * @return void
     */
    public function test_resolve_multiple_definitions(): void {
        $definition1 = new message_type_definition(
            'LtiResourceLinkRequest',
            ['basic-lti-launch-request'],
        );
        $definition2 = new message_type_definition(
            'LtiDeepLinkingRequest',
            ['ContentItemSelectionRequest'],
        );
        $registry = new message_type_registry([$definition1, $definition2]);

        $resolved1 = $registry->resolve('LtiResourceLinkRequest');
        $resolved2 = $registry->resolve('ContentItemSelectionRequest');

        $this->assertNotNull($resolved1);
        $this->assertNotNull($resolved2);
        $this->assertSame('LtiResourceLinkRequest', $resolved1->canonical());
        $this->assertSame('LtiDeepLinkingRequest', $resolved2->canonical());
    }

    /**
     * Test that a definition with multiple aliases creates multiple lookup entries.
     *
     * @return void
     */
    public function test_definition_with_multiple_aliases(): void {
        $definition = new message_type_definition(
            'canonical-form',
            ['alias1', 'alias2', 'alias3']
        );
        $registry = new message_type_registry([$definition]);

        $resolved1 = $registry->resolve('alias1');
        $resolved2 = $registry->resolve('alias2');
        $resolved3 = $registry->resolve('alias3');

        $this->assertNotNull($resolved1);
        $this->assertNotNull($resolved2);
        $this->assertNotNull($resolved3);
        $this->assertSame('canonical-form', $resolved1->canonical());
        $this->assertSame('canonical-form', $resolved2->canonical());
        $this->assertSame('canonical-form', $resolved3->canonical());
    }

    /**
     * Test that an empty registry resolves nothing.
     *
     * @return void
     */
    public function test_empty_registry_resolves_nothing(): void {
        $registry = new message_type_registry([]);

        $this->assertNull($registry->resolve('any-value'));
        $this->assertNull($registry->resolve(''));
    }

    /**
     * Test that a definition with empty aliases still works in the registry.
     *
     * @return void
     */
    public function test_definition_with_empty_aliases(): void {
        $definition = new message_type_definition('canonical', []);
        $registry = new message_type_registry([$definition]);

        $resolved = $registry->resolve('canonical');

        $this->assertNotNull($resolved);
        $this->assertSame('canonical', $resolved->canonical());
        $this->assertSame([], $resolved->aliases());
    }
}
