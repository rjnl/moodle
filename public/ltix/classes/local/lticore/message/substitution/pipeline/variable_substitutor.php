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

namespace core_ltix\local\lticore\message\substitution\pipeline;

use core_ltix\local\lticore\message\context\collection\substitution_context;

/**
 * Performs variable substitution according to the specified policy and resolver chain.
 *
 * @internal
 * @package    core_ltix
 * @copyright  2025 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class variable_substitutor {

    /**
     * Ctor.
     *
     * @param substitution_policy $policy a policy instance, controlling whether a variable is resolved.
     * @param variable_resolver[] $resolvers a resolver list, handling the resolving itself, should the policy permit it.
     */
    public function __construct(
       protected substitution_policy $policy,
       protected array $resolvers,
    ) {
    }

    /**
     * Perform substitution for an array of substitution parameters.
     *
     * @param array $params the array of substitution params (keys don't matter, they are not changed)
     * @param substitution_context $resolvecontext runtime data used during substitution.
     * @return array the array once value substitution has taken place.
     */
    public function substitute(array $params, substitution_context $resolvecontext): array {
        return array_map(function ($param) use ($resolvecontext) {
            if (!$this->policy->should_substitute($param, $resolvecontext)) {
                return $param;
            }
            foreach ($this->resolvers as $resolver) {
                $resolved = $resolver->resolve($param, $resolvecontext);
                if (!is_null($resolved)) {
                    return $resolved;
                }
            }
            return $param;
        }, $params);
    }
}
