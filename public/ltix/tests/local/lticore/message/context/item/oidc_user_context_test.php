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

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering oidc_user_context.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(oidc_user_context::class)]
class oidc_user_context_test extends \basic_testcase {

    /**
     * Test that the userfields property holds the array passed to the constructor.
     *
     * @return void
     */
    public function test_userfields_property_holds_the_supplied_array(): void {
        $userfields = ['sub' => 'user-42', 'email' => 'user@example.com', 'name' => 'Jane Doe'];
        $ctx = new oidc_user_context($userfields);

        $this->assertSame($userfields, $ctx->userfields);
    }

    /**
     * Test that the userfields property is an empty array when an empty array is supplied.
     *
     * @return void
     */
    public function test_userfields_property_holds_empty_array(): void {
        $ctx = new oidc_user_context([]);

        $this->assertSame([], $ctx->userfields);
    }
}
