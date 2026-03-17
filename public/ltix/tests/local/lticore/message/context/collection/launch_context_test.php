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

namespace core_ltix\local\lticore\message\context\collection;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\context\item\pipeline_params_context;
use core_ltix\local\lticore\message\context\item\oidc_user_context;
use core_ltix\local\lticore\message\type\message_type;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering launch_context.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(launch_context::class)]
class launch_context_test extends \basic_testcase {

    /**
     * Return a minimal message_context for use in tests.
     *
     * @return message_context
     */
    private static function make_message_context(): message_context {
        return new message_context(message_type::create('basic-lti-launch-request', 'LtiResourceLinkRequest'));
    }

    /**
     * Test that the message_context passed to instance() is stored and retrievable via has().
     *
     * @return void
     */
    public function test_instance_stores_message_context(): void {
        $ctx = self::make_message_context();
        $launch = launch_context::instance($ctx);

        $this->assertTrue($launch->has(message_context::class));
    }

    /**
     * Test that additional contexts passed to instance() are stored.
     *
     * @return void
     */
    public function test_instance_stores_additional_variadic_contexts(): void {
        $extra = new pipeline_params_context(['k' => 'v']);
        $launch = launch_context::instance(self::make_message_context(), $extra);

        $this->assertTrue($launch->has(pipeline_params_context::class));
    }

    /**
     * Test that has() returns false for a class not added to the collection.
     *
     * @return void
     */
    public function test_has_returns_false_for_absent_context(): void {
        $launch = launch_context::instance(self::make_message_context());

        $this->assertFalse($launch->has(oidc_user_context::class));
    }

    /**
     * Test that get() returns the message_context object.
     *
     * @return void
     */
    public function test_get_returns_message_context(): void {
        $ctx = self::make_message_context();
        $launch = launch_context::instance($ctx);

        $this->assertSame($ctx, $launch->get(message_context::class));
    }

    /**
     * Test that get() returns null for an absent class.
     *
     * @return void
     */
    public function test_get_returns_null_for_absent_context(): void {
        $launch = launch_context::instance(self::make_message_context());

        $this->assertNull($launch->get(oidc_user_context::class));
    }

    /**
     * Test that get() returns an additional context added via the variadic constructor arg.
     *
     * @return void
     */
    public function test_get_returns_additional_context(): void {
        $extra = new pipeline_params_context(['x' => '1']);
        $launch = launch_context::instance(self::make_message_context(), $extra);

        $this->assertSame($extra, $launch->get(pipeline_params_context::class));
    }

    /**
     * Test that require() returns the message_context object.
     *
     * @return void
     */
    public function test_require_returns_message_context(): void {
        $ctx = self::make_message_context();
        $launch = launch_context::instance($ctx);

        $this->assertSame($ctx, $launch->require(message_context::class));
    }

    /**
     * Test that require() throws lti_exception for an absent class.
     *
     * @return void
     */
    public function test_require_throws_for_absent_context(): void {
        $launch = launch_context::instance(self::make_message_context());

        $this->expectException(lti_exception::class);
        $launch->require(oidc_user_context::class);
    }

    /**
     * Test that with() returns a new instance (immutability).
     *
     * @return void
     */
    public function test_with_returns_new_instance(): void {
        $original = launch_context::instance(self::make_message_context());
        $updated = $original->with(new pipeline_params_context(['k' => 'v']));

        $this->assertNotSame($original, $updated);
    }

    /**
     * Test that with() makes the new context available in the returned instance.
     *
     * @return void
     */
    public function test_with_makes_new_context_available(): void {
        $original = launch_context::instance(self::make_message_context());
        $extra = new pipeline_params_context(['k' => 'v']);
        $updated = $original->with($extra);

        $this->assertTrue($updated->has(pipeline_params_context::class));
        $this->assertSame($extra, $updated->get(pipeline_params_context::class));
    }

    /**
     * Test that with() does not modify the original instance.
     *
     * @return void
     */
    public function test_with_does_not_modify_original_instance(): void {
        $original = launch_context::instance(self::make_message_context());
        $original->with(new pipeline_params_context(['k' => 'v']));

        $this->assertFalse($original->has(pipeline_params_context::class));
    }

    /**
     * Test that with() preserves previously stored contexts in the returned instance.
     *
     * @return void
     */
    public function test_with_preserves_existing_contexts(): void {
        $ctx = self::make_message_context();
        $original = launch_context::instance($ctx);
        $updated = $original->with(new pipeline_params_context(['k' => 'v']));

        $this->assertSame($ctx, $updated->get(message_context::class));
    }
}
