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
use core_ltix\local\lticore\message\context\item\oidc_user_context;
use core_ltix\local\lticore\message\context\item\pipeline_params_context;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering context_collection.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(context_collection::class)]
class context_collection_test extends \basic_testcase {

    /**
     * Test that has() returns false when the collection is empty.
     *
     * @return void
     */
    public function test_has_returns_false_on_empty_collection(): void {
        $collection = new context_collection();

        $this->assertFalse($collection->has(pipeline_params_context::class));
    }

    /**
     * Test that has() returns false for a class that is not in the collection.
     *
     * @return void
     */
    public function test_has_returns_false_for_absent_class(): void {
        $collection = new context_collection(new pipeline_params_context(['a' => '1']));

        $this->assertFalse($collection->has(oidc_user_context::class));
    }

    /**
     * Test that has() returns true for a class that is present in the collection.
     *
     * @return void
     */
    public function test_has_returns_true_for_present_class(): void {
        $collection = new context_collection(new pipeline_params_context(['a' => '1']));

        $this->assertTrue($collection->has(pipeline_params_context::class));
    }

    /**
     * Test that has() returns true for each of several classes added to the collection.
     *
     * @return void
     */
    public function test_has_returns_true_for_each_of_multiple_added_classes(): void {
        $collection = new context_collection(
            new pipeline_params_context(['a' => '1']),
            new oidc_user_context(['sub' => 'user-1']),
        );

        $this->assertTrue($collection->has(pipeline_params_context::class));
        $this->assertTrue($collection->has(oidc_user_context::class));
    }

    /**
     * Test that get() returns null for a class that is not in the collection.
     *
     * @return void
     */
    public function test_get_returns_null_for_absent_class(): void {
        $collection = new context_collection();

        $this->assertNull($collection->get(pipeline_params_context::class));
    }

    /**
     * Test that get() returns the correct object for a present class.
     *
     * @return void
     */
    public function test_get_returns_correct_object_for_present_class(): void {
        $context = new pipeline_params_context(['foo' => 'bar']);
        $collection = new context_collection($context);

        $this->assertSame($context, $collection->get(pipeline_params_context::class));
    }

    /**
     * Test that get() returns the correct object when multiple contexts are stored.
     *
     * @return void
     */
    public function test_get_returns_correct_object_among_multiple_contexts(): void {
        $params = new pipeline_params_context(['x' => '1']);
        $user = new oidc_user_context(['sub' => 'u1']);
        $collection = new context_collection($params, $user);

        $this->assertSame($params, $collection->get(pipeline_params_context::class));
        $this->assertSame($user, $collection->get(oidc_user_context::class));
    }

    /**
     * Test that require() returns the context object for a class that is present.
     *
     * @return void
     */
    public function test_require_returns_object_for_present_class(): void {
        $context = new pipeline_params_context(['k' => 'v']);
        $collection = new context_collection($context);

        $this->assertSame($context, $collection->require(pipeline_params_context::class));
    }

    /**
     * Test that require() throws lti_exception for a class that is not present.
     *
     * @return void
     */
    public function test_require_throws_lti_exception_for_absent_class(): void {
        $collection = new context_collection();

        $this->expectException(lti_exception::class);
        $collection->require(pipeline_params_context::class);
    }

    /**
     * Test that the lti_exception message from require() references the missing class.
     *
     * @return void
     */
    public function test_require_exception_message_references_missing_class(): void {
        $collection = new context_collection();

        try {
            $collection->require(pipeline_params_context::class);
            $this->fail('Expected lti_exception was not thrown');
        } catch (lti_exception $e) {
            $this->assertStringContainsString(pipeline_params_context::class, $e->getMessage());
        }
    }

    /**
     * Test that the constructor throws lti_exception when two contexts of the same class are passed.
     *
     * @return void
     */
    public function test_constructor_throws_on_duplicate_context_class(): void {
        $this->expectException(lti_exception::class);
        new context_collection(
            new pipeline_params_context(['a' => '1']),
            new pipeline_params_context(['b' => '2']),
        );
    }

    /**
     * Test that the constructor accepts multiple contexts of distinct classes without throwing.
     *
     * @return void
     */
    public function test_constructor_accepts_multiple_distinct_context_classes(): void {
        $collection = new context_collection(
            new pipeline_params_context(['a' => '1']),
            new oidc_user_context(['sub' => 'u1']),
        );

        $this->assertTrue($collection->has(pipeline_params_context::class));
        $this->assertTrue($collection->has(oidc_user_context::class));
    }

    /**
     * Test that with() returns a new instance (immutability).
     *
     * @return void
     */
    public function test_with_returns_new_instance(): void {
        $original = new context_collection(new pipeline_params_context(['a' => '1']));
        $updated = $original->with(new oidc_user_context(['sub' => 'u1']));

        $this->assertNotSame($original, $updated);
    }

    /**
     * Test that with() makes the new context available in the returned collection.
     *
     * @return void
     */
    public function test_with_makes_new_context_available(): void {
        $original = new context_collection(new pipeline_params_context(['a' => '1']));
        $user = new oidc_user_context(['sub' => 'u1']);
        $updated = $original->with($user);

        $this->assertTrue($updated->has(oidc_user_context::class));
        $this->assertSame($user, $updated->get(oidc_user_context::class));
    }

    /**
     * Test that with() does not modify the original collection (immutability).
     *
     * @return void
     */
    public function test_with_does_not_modify_original_collection(): void {
        $original = new context_collection(new pipeline_params_context(['a' => '1']));
        $original->with(new oidc_user_context(['sub' => 'u1']));

        $this->assertFalse($original->has(oidc_user_context::class));
    }

    /**
     * Test that with() preserves existing contexts in the returned collection.
     *
     * @return void
     */
    public function test_with_preserves_existing_contexts(): void {
        $params = new pipeline_params_context(['a' => '1']);
        $original = new context_collection($params);
        $updated = $original->with(new oidc_user_context(['sub' => 'u1']));

        $this->assertTrue($updated->has(pipeline_params_context::class));
        $this->assertSame($params, $updated->get(pipeline_params_context::class));
    }

    /**
     * Test that with() overwrites an existing context of the same class.
     *
     * @return void
     */
    public function test_with_overwrites_existing_context_of_same_class(): void {
        $original = new context_collection(new pipeline_params_context(['old' => 'value']));
        $replacement = new pipeline_params_context(['new' => 'value']);
        $updated = $original->with($replacement);

        $this->assertSame($replacement, $updated->get(pipeline_params_context::class));
    }
}
