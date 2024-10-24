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

namespace core_ltix\local\lticore\models;

/**
 * Tests covering resource_link.
 *
 * @covers \core_ltix\local\lticore\models\resource_link
 * @package    core_ltix
 * @copyright  2024 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class resource_link_test extends \advanced_testcase {

    /**
     * Test creation using the bare minimum number of fields (those required).
     *
     * @dataProvider create_resource_link_provider
     * @param array $setdata the data to pass to set().
     * @param array $expecteddata the expected dataset, to compare with calls to get().
     * @param null|string $expectedexception the exception type to expect, if any, else null.
     * @return void
     */
    public function test_resource_link_creation(array $setdata, array $expecteddata, ?string$expectedexception = null): void {
        $this->resetAfterTest();

        $rl = new resource_link();

        if (!is_null($expectedexception)) {
            $this->expectException($expectedexception);
        }
        foreach ($setdata as $name => $value) {
            $rl->set($name, $value);
        }
        $rl->save();

        foreach ($expecteddata as $name => $value) {
            $this->assertEquals($value, $rl->get($name));
        }
        // In cases where this is generated, just confirm it's a string.
        $this->assertIsString($rl->get('uuid'));
        $this->assertNotEmpty($rl->get('uuid'));
    }

    public static function create_resource_link_provider(): array {
        global $CFG;
        require_once($CFG->dirroot . '/ltix/constants.php');

        return [
            'minimal set, required fields only' => [
                'setdata' => [
                    'typeid' => 4,
                    'contextid' => 33,
                    'url' => (new \moodle_url('http://tool.example.com/my/resource'))->out(false),
                    'title' => 'My resource',
                ],
                'expecteddata' => [
                    'typeid' => 4,
                    'contextid' => 33,
                    'legacyid' => null,
                    // Note: can't check UUID in this case since it's a randomly generated default, so it's omitted.
                    'url' => 'http://tool.example.com/my/resource',
                    'title' => 'My resource',
                    'text' => null,
                    'textformat' => FORMAT_MOODLE,
                    'launchcontainer' => LTI_LAUNCH_CONTAINER_DEFAULT,
                    'customparams' => null,
                    'icon' => null,
                    'servicesalt' => null,
                ],
            ],
            'full set, all fields specified' => [
                'setdata' => [
                    'typeid' => 4,
                    'contextid' => 33,
                    'legacyid' => 56001,
                    'uuid' => '123',
                    'url' => (new \moodle_url('http://tool.example.com/my/resource'))->out(false),
                    'title' => 'My resource',
                    'text' => '<div>This is a larger description of the resource link</div>',
                    'textformat' => FORMAT_HTML,
                    'launchcontainer' => LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
                    'customparams' => 'id=abc-123-fff',
                    'icon' => (new \moodle_url('http://tool.example.com/my/resource/icon.png'))->out(false),
                    'servicesalt' => '664c4261e47d85.53212526',
                ],
                'expecteddata' => [
                    'typeid' => 4,
                    'contextid' => 33,
                    'legacyid' => 56001,
                    'uuid' => '123',
                    'url' => 'http://tool.example.com/my/resource',
                    'title' => 'My resource',
                    'text' => '<div>This is a larger description of the resource link</div>',
                    'textformat' => FORMAT_HTML,
                    'launchcontainer' => LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
                    'customparams' => 'id=abc-123-fff',
                    'icon' => 'http://tool.example.com/my/resource/icon.png',
                    'servicesalt' => '664c4261e47d85.53212526',
                ]
            ],
            'Exceptional case, invalid launchcontainer value' => [
                'setdata' => [
                    'typeid' => 4,
                    'contextid' => 33,
                    'url' => (new \moodle_url('http://tool.example.com/my/resource'))->out(false),
                    'title' => 'My resource',
                    'launchcontainer' => 999999, // Invalid launch container.
                ],
                'expecteddata' => [],
                'expectedexception' => \core\invalid_persistent_exception::class,
            ],
            'Exceptional case, invalid textformat value' => [
                'setdata' => [
                    'typeid' => 4,
                    'contextid' => 33,
                    'url' => (new \moodle_url('http://tool.example.com/my/resource'))->out(false),
                    'title' => 'My resource',
                    'textformat' => 999999, // Invalid text format.
                ],
                'expecteddata' => [],
                'expectedexception' => \core\invalid_persistent_exception::class,
            ],
        ];
    }
}
