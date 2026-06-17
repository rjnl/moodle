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

namespace core_ltix\local\lticore\message\substitution\resolver\mapping;

use core_ltix\local\lticore\message\context\collection\substitution_context;
use core_ltix\local\lticore\message\context\item\oidc_user_context;
use core_ltix\local\lticore\message\substitution\pipeline\variable_resolver;
use core_ltix\local\lticore\message\substitution\variable_map;

/**
 * Resolves variable values based on a fixed mapping to OIDC user data.
 *
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class oidc_user_params_map_resolver implements variable_resolver {

    /**
     * Ctor.
     *
     * @param variable_map $mapresolver service able to resolve params to sourcedata based on a mapping.
     */
    public function __construct(private variable_map $mapresolver) {
    }

    #[\Override]
    public function resolve(string $str, substitution_context $resolvecontext): ?string {
        $sourceuserdata = $resolvecontext->require(oidc_user_context::class)->userfields;
        if (!str_starts_with($str, '$')) {
            return null;
        }
        $str = substr($str, 1);

        return $this->mapresolver->resolve($sourceuserdata, $str);
    }
}
