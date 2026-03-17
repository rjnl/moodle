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

use core_ltix\constants;
use core_ltix\local\lticore\models\resource_link;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering resource_link_context.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(resource_link_context::class)]
class resource_link_context_test extends \basic_testcase {

    /**
     * Return a minimal resource_link instance for use in tests.
     *
     * @return resource_link
     */
    private static function make_resource_link(): resource_link {
        return new resource_link(0, (object) [
            'typeid'          => 1,
            'contextid'       => 2,
            'url'             => 'https://tool.example.com/launch',
            'title'           => 'My Resource',
            'launchcontainer' => constants::LTI_LAUNCH_CONTAINER_DEFAULT,
        ]);
    }

    /**
     * Test that the resourcelink property holds the resource_link passed to the constructor.
     *
     * @return void
     */
    public function test_resourcelink_property_holds_the_supplied_resource_link(): void {
        $link = self::make_resource_link();
        $ctx = new resource_link_context($link);

        $this->assertSame($link, $ctx->resourcelink);
    }

    /**
     * Test that the resourcelink property is the exact same object instance (no cloning).
     *
     * @return void
     */
    public function test_resourcelink_property_is_same_instance(): void {
        $link = self::make_resource_link();
        $ctx = new resource_link_context($link);

        $this->assertSame($link, $ctx->resourcelink);
    }

    /**
     * Test that the resource link's data is accessible through the property.
     *
     * @return void
     */
    public function test_resource_link_data_is_accessible_through_property(): void {
        $ctx = new resource_link_context(self::make_resource_link());

        $this->assertSame('https://tool.example.com/launch', $ctx->resourcelink->get('url'));
        $this->assertSame('My Resource', $ctx->resourcelink->get('title'));
    }
}
