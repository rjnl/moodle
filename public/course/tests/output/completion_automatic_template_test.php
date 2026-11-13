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

namespace core_course\output;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the core_course/completion_automatic template.
 *
 * @package    core_course
 * @copyright  2026 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversNothing]
final class completion_automatic_template_test extends \advanced_testcase {
    /**
     * Data provider for test_status_class_is_on_list_item.
     *
     * @return array
     */
    public static function status_provider(): array {
        return [
            'Complete' => ['statuscomplete', 'automatic-completion-item-complete'],
            'Failed' => ['statuscompletefail', 'automatic-completion-item-failed'],
        ];
    }

    /**
     * The list item carries the status class, so that themes can style it without :has().
     *
     * @param string $status The template context key that sets the status.
     * @param string $class The expected class of the list item.
     */
    #[DataProvider('status_provider')]
    public function test_status_class_is_on_list_item(string $status, string $class): void {
        global $PAGE;
        $this->resetAfterTest();

        $html = $PAGE->get_renderer('core')->render_from_template('core_course/completion_automatic', [
            'istrackeduser' => true,
            $status => true,
            'description' => 'View',
        ]);

        $this->assertStringContainsString("<li class=\"{$class}\">", $html);
    }
}
