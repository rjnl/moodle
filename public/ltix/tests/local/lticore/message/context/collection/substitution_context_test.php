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
use core_ltix\local\lticore\message\context\item\oidc_user_context;
use core_ltix\local\lticore\message\context\item\pipeline_params_context;
use core_ltix\local\lticore\message\type\message_type;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering substitution_context.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(substitution_context::class)]
class substitution_context_test extends \basic_testcase {

    /**
     * Return a minimal launch_context for use in tests.
     *
     * @return launch_context
     */
    private static function make_launch_context(): launch_context {
        $msgctx = new message_context(message_type::create('basic-lti-launch-request', 'LtiResourceLinkRequest'));
        return launch_context::instance($msgctx);
    }

    /**
     * Test that for_parameter_pipeline() stores the supplied launch_context and makes it retrievable.
     *
     * @return void
     */
    public function test_for_parameter_pipeline_stores_launch_context(): void {
        $launchctx = self::make_launch_context();
        $ctx = substitution_context::for_parameter_pipeline([], $launchctx);

        $this->assertTrue($ctx->has(launch_context::class));
        $this->assertSame($launchctx, $ctx->get(launch_context::class));
    }

    /**
     * Test that for_parameter_pipeline() creates a pipeline_params_context from the supplied array.
     *
     * @return void
     */
    public function test_for_parameter_pipeline_stores_pipeline_params_context(): void {
        $params = ['lti_version' => 'LTI-1p0'];
        $ctx = substitution_context::for_parameter_pipeline($params, self::make_launch_context());

        $this->assertTrue($ctx->has(pipeline_params_context::class));
        $this->assertSame($params, $ctx->get(pipeline_params_context::class)->params);
    }

    /**
     * Test that for_parameter_pipeline() does not include an oidc_user_context.
     *
     * @return void
     */
    public function test_for_parameter_pipeline_does_not_include_oidc_user_context(): void {
        $ctx = substitution_context::for_parameter_pipeline([], self::make_launch_context());

        $this->assertFalse($ctx->has(oidc_user_context::class));
    }

    /**
     * Test that for_auth() creates an oidc_user_context from the supplied user vars.
     *
     * @return void
     */
    public function test_for_auth_stores_oidc_user_context(): void {
        $uservars = ['sub' => 'user-1', 'email' => 'user@example.com'];
        $ctx = substitution_context::for_auth($uservars);

        $this->assertTrue($ctx->has(oidc_user_context::class));
        $this->assertSame($uservars, $ctx->get(oidc_user_context::class)->userfields);
    }

    /**
     * Test that for_auth() does not include a launch_context or pipeline_params_context.
     *
     * @return void
     */
    public function test_for_auth_does_not_include_launch_or_pipeline_context(): void {
        $ctx = substitution_context::for_auth(['sub' => 'user-1']);

        $this->assertFalse($ctx->has(launch_context::class));
        $this->assertFalse($ctx->has(pipeline_params_context::class));
    }

    /**
     * Test that has() returns true for a context that is present.
     *
     * @return void
     */
    public function test_has_returns_true_for_present_class(): void {
        $ctx = substitution_context::for_auth(['sub' => 'u1']);

        $this->assertTrue($ctx->has(oidc_user_context::class));
    }

    /**
     * Test that has() returns false for a class that is not present.
     *
     * @return void
     */
    public function test_has_returns_false_for_absent_class(): void {
        $ctx = substitution_context::for_auth(['sub' => 'u1']);

        $this->assertFalse($ctx->has(pipeline_params_context::class));
    }

    /**
     * Test that get() returns the context object for a present class.
     *
     * @return void
     */
    public function test_get_returns_context_for_present_class(): void {
        $uservars = ['sub' => 'u1'];
        $ctx = substitution_context::for_auth($uservars);

        $result = $ctx->get(oidc_user_context::class);
        $this->assertInstanceOf(oidc_user_context::class, $result);
        $this->assertSame($uservars, $result->userfields);
    }

    /**
     * Test that get() returns null for an absent class.
     *
     * @return void
     */
    public function test_get_returns_null_for_absent_class(): void {
        $ctx = substitution_context::for_auth(['sub' => 'u1']);

        $this->assertNull($ctx->get(pipeline_params_context::class));
    }

    /**
     * Test that require() returns the context object for a present class.
     *
     * @return void
     */
    public function test_require_returns_context_for_present_class(): void {
        $uservars = ['sub' => 'u1'];
        $ctx = substitution_context::for_auth($uservars);

        $result = $ctx->require(oidc_user_context::class);
        $this->assertInstanceOf(oidc_user_context::class, $result);
    }

    /**
     * Test that require() throws lti_exception for an absent class.
     *
     * @return void
     */
    public function test_require_throws_for_absent_context(): void {
        $ctx = substitution_context::for_auth(['sub' => 'u1']);

        $this->expectException(lti_exception::class);
        $ctx->require(pipeline_params_context::class);
    }

    /**
     * Test that with() returns a new instance (immutability).
     *
     * @return void
     */
    public function test_with_returns_new_instance(): void {
        $original = substitution_context::for_auth(['sub' => 'u1']);
        $updated = $original->with(new pipeline_params_context(['k' => 'v']));

        $this->assertNotSame($original, $updated);
    }

    /**
     * Test that with() makes the new context available in the returned instance.
     *
     * @return void
     */
    public function test_with_makes_new_context_available(): void {
        $original = substitution_context::for_auth(['sub' => 'u1']);
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
        $original = substitution_context::for_auth(['sub' => 'u1']);
        $original->with(new pipeline_params_context(['k' => 'v']));

        $this->assertFalse($original->has(pipeline_params_context::class));
    }

    /**
     * Test that with() preserves previously stored contexts in the returned instance.
     *
     * @return void
     */
    public function test_with_preserves_existing_contexts(): void {
        $uservars = ['sub' => 'u1'];
        $original = substitution_context::for_auth($uservars);
        $updated = $original->with(new pipeline_params_context(['k' => 'v']));

        $this->assertTrue($updated->has(oidc_user_context::class));
        $this->assertSame($uservars, $updated->get(oidc_user_context::class)->userfields);
    }
}
