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
 * Tests covering pipeline_params_context.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(pipeline_params_context::class)]
class pipeline_params_context_test extends \basic_testcase {

    /**
     * Test that the params property holds the array passed to the constructor.
     *
     * @return void
     */
    public function test_params_property_holds_the_supplied_array(): void {
        $params = ['lti_version' => 'LTI-1p0', 'lti_message_type' => 'basic-lti-launch-request'];
        $ctx = new pipeline_params_context($params);

        $this->assertSame($params, $ctx->params);
    }

    /**
     * Test that the params property is an empty array when an empty array is supplied.
     *
     * @return void
     */
    public function test_params_property_holds_empty_array(): void {
        $ctx = new pipeline_params_context([]);

        $this->assertSame([], $ctx->params);
    }
}
