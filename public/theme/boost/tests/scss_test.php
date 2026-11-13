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

namespace theme_boost;

/**
 * Unit tests for scss compilation.
 *
 * @package   theme_boost
 * @copyright 2016 onwards Ankit Agarwal
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class scss_test extends \advanced_testcase {
    /**
     * Test that boost can be compiled using SassC (the defacto implemention).
     */
    public function test_scss_compilation_with_sassc(): void {
        if (!defined('PHPUNIT_PATH_TO_SASSC')) {
            $this->markTestSkipped('Path to SassC not provided');
        }

        $this->resetAfterTest();
        set_config('pathtosassc', PHPUNIT_PATH_TO_SASSC);

        $this->assertNotEmpty(
            \theme_config::load('boost')->get_css_content_debug('scss', null, null)
        );
    }

    /**
     * Test that no :has() selector depends on the drawer state, the drawer toggles or the completion state.
     *
     * Such selectors make the browser restyle the whole page on every DOM change.
     */
    #[\PHPUnit\Framework\Attributes\CoversNothing]
    public function test_no_page_wide_has_selectors(): void {
        $this->resetAfterTest();

        $css = \theme_config::load('boost')->get_css_content_debug('scss', null, null);
        $pattern = '/[^{}]*:has\([^)]*(?:show-drawer-|drawer-(?:left|right)-toggle|automatic-completion-is-)[^{]*/';
        preg_match_all($pattern, $css, $matches);

        $this->assertSame([], array_values(array_unique(array_map('trim', $matches[0]))));
    }
}
