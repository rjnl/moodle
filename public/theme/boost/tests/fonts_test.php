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

use core\output\theme_config;

/**
 * Unit tests for the webfonts bundled with the theme.
 *
 * @package   theme_boost
 * @copyright 2026 Moodle
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \core\output\theme_config::editor_scss_to_css
 */
final class fonts_test extends \advanced_testcase {
    /**
     * Extracts a --bs-font-sans-serif font stack from compiled CSS as a list of families.
     *
     * Families are compared whole rather than as substrings, since "Noto Sans" is a
     * substring of "Noto Sans JP" and would otherwise match a stack containing only
     * the latter.
     *
     * @param string $css The compiled CSS to search.
     * @param string|null $selector Selector whose block to read, or null for the first
     *                              declaration in the file (the default :root stack).
     * @return string[] The font families, in stack order, unquoted.
     */
    private function get_font_stack(string $css, ?string $selector = null): array {
        $pattern = $selector === null
            ? '/--bs-font-sans-serif:\s*([^;]+);/'
            : '/' . preg_quote($selector, '/') . '\s*\{[^}]*?--bs-font-sans-serif:\s*([^;]+);/s';

        $this->assertSame(
            1,
            preg_match($pattern, $css, $matches),
            "No --bs-font-sans-serif stack found for '" . ($selector ?? ':root') . "'."
        );

        return array_map(
            static fn(string $family): string => trim(trim($family), '"\''),
            explode(',', $matches[1])
        );
    }

    /**
     * Noto Sans JP must not appear in the font stack used by every other language.
     *
     * Noto Sans JP covers the Han characters that Japanese shares with Chinese, and draws
     * them in Japanese regional forms with no Chinese alternates available. Naming it in
     * the default stack therefore renders Chinese text in Japanese letterforms, and splits
     * a sentence across two typefaces wherever a simplified-only character falls through
     * to the system font. It belongs only in the :lang(ja) stack.
     */
    public function test_default_font_stack_excludes_japanese_family(): void {
        $this->resetAfterTest();

        $stack = $this->get_font_stack(theme_config::load('boost')->editor_scss_to_css());

        $this->assertContains('Noto Sans', $stack, 'The default font stack is missing Noto Sans.');
        $this->assertNotContains(
            'Noto Sans JP',
            $stack,
            'Noto Sans JP must not be in the default font stack; it is scoped to :lang(ja).'
        );
    }

    /**
     * Every webfont family bundled with the theme must be reachable from some font stack.
     *
     * Shipping the @font-face declarations is not enough on its own: unless the family is
     * also named in a stack, nothing can ever select it and the bundled files are dead
     * weight. Noto Sans JP is reachable only from the Japanese stack, so that is where it
     * is asserted. The stacks are read from the --bs-font-sans-serif custom property
     * rather than a body rule, as that is where Bootstrap exposes them regardless of how
     * the theme chooses to apply them.
     */
    public function test_japanese_font_stack_includes_bundled_families(): void {
        $this->resetAfterTest();

        $css = theme_config::load('boost')->editor_scss_to_css();
        $stack = $this->get_font_stack($css, ':lang(ja)');

        $this->assertContains('Noto Sans JP', $stack, 'The Japanese font stack is missing Noto Sans JP.');
        $this->assertContains('Noto Sans', $stack, 'The Japanese font stack is missing Noto Sans.');

        // Noto Sans must still be reached first, so shared Latin and Cyrillic text renders
        // identically on Japanese and non-Japanese pages.
        $this->assertLessThan(
            array_search('Noto Sans JP', $stack, true),
            array_search('Noto Sans', $stack, true),
            'Noto Sans must precede Noto Sans JP in the Japanese font stack.'
        );
    }

    /**
     * The two stacks must differ by nothing except the Japanese family.
     *
     * They are written out as separate literal lists in moodle/font-stack.scss rather than
     * composed from a shared variable, because a nested SCSS list serialises with
     * parentheses under Dart Sass and flat under scssphp. That duplication is only safe if
     * the lists cannot drift, so the invariant is asserted here: dropping Noto Sans JP from
     * the Japanese stack must leave exactly the default stack.
     */
    public function test_language_font_stacks_differ_only_by_the_japanese_family(): void {
        $this->resetAfterTest();

        $css = theme_config::load('boost')->editor_scss_to_css();

        $default = $this->get_font_stack($css);
        $japanese = $this->get_font_stack($css, ':lang(ja)');

        $this->assertSame(
            $default,
            array_values(array_diff($japanese, ['Noto Sans JP'])),
            'The Japanese and default font stacks have drifted apart. They must list the '
                . 'same fallback families in the same order, differing only by Noto Sans JP.'
        );
    }
}
