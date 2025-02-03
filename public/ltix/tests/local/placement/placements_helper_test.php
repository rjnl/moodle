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

namespace core_ltix\local\placement;

/**
 * Placements helper tests.
 *
 * @covers \core_ltix\local\placement\placements_helper
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class placements_helper_test extends \advanced_testcase {

    /**
     * Test registration of component placement types during install/upgrade, via the update_placement_types() method.
     *
     * @return void
     * @runInSeparateProcess
     */
    public function test_update_placement_types_addition(): void {
        $this->resetAfterTest();
        global $CFG, $DB;

        // Using a fake plugin mock, verify the lti placement type are registered.
        $this->add_mocked_plugintype('fake', "{$CFG->dirroot}/lib/tests/fixtures/fakeplugins/fake", true);
        $this->add_mocked_plugin('fake', 'fullfeatured', "{$CFG->dirroot}/lib/tests/fixtures/fakeplugins/fake/fullfeatured");

        placements_helper::update_placement_types('fake_fullfeatured');
        $this->assertEquals(2, $DB->count_records('lti_placement_type', ['component' => 'fake_fullfeatured']));

        // Next, mock the situation where a placement type has already been registered, and is now removed from lti.php config.
        $placementtypeid = $DB->insert_record('lti_placement_type', [
            'component' => 'fake_fullfeatured',
            'type' => 'fake_fullfeatured:thisonetobedeleted'
        ]);
        $DB->insert_record('lti_placement', ['placementtypeid' => $placementtypeid, 'toolid' => 101]);
        $this->assertEquals(1, $DB->count_records('lti_placement_type',
            ['component' => 'fake_fullfeatured', 'type' => 'fake_fullfeatured:thisonetobedeleted']));
        $this->assertEquals(1, $DB->count_records('lti_placement',
            ['toolid' => 101]));

        // The placement type and any placements using it should have been deleted.
        placements_helper::update_placement_types('fake_fullfeatured');
        $placementtypes = $DB->get_records_menu('lti_placement_type', ['component' => 'fake_fullfeatured']);
        $this->assertCount(2, $placementtypes);
        $this->assertContains('fake_fullfeatured:myfirstplacementtype', $placementtypes);
        $this->assertContains('fake_fullfeatured:anotherplacementtype', $placementtypes);
        $this->assertEquals(0, $DB->count_records('lti_placement_type',
            ['component' => 'fake_fullfeatured', 'type' => 'fake_fullfeatured:thisonetobedeleted']));
        $this->assertEquals(0, $DB->count_records('lti_placement',
            ['toolid' => 101]));
    }
}
