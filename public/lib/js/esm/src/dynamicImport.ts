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

/**
 * Native dynamic import of an ES module resolved through the Moodle import map.
 *
 * This lives in its own module so that callers can be unit tested: Jest cannot
 * run a native `import()`, but it can mock this module.
 *
 * @module     core/dynamicImport
 * @copyright  2026 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Import an ES module by its specifier.
 *
 * @param specifier The module specifier, e.g. `@moodle/lms/core_course/ActivityIcon`.
 * @returns The imported module namespace.
 */
export const dynamicImport = async <T>(specifier: string): Promise<T> => import(specifier) as Promise<T>;
