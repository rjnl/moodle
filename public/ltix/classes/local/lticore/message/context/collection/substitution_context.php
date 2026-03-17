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

namespace core_ltix\local\lticore\message\context\collection;

use core_ltix\local\lticore\message\context\item\oidc_user_context;
use core_ltix\local\lticore\message\context\item\pipeline_params_context;

/**
 * Superset of all data needed by the variable substitution pipelines, or more accurately, their resolvers.
 *
 * Different pipelines can of course have different compositions of resolvers, and therefore data requirements. Factory methods take
 * the roll of ensuring the right context is returned for the pipeline composition/use.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final readonly class substitution_context implements context_collection_interface {

    private context_collection $contextcollection;

    /**
     * Ctor.
     *
     * @param context_collection $contextcollection
     */
    private function __construct(context_collection $contextcollection) {
        $this->contextcollection = $contextcollection;
    }

    /**
     * Get an instance for use during parameter pipeline execution, based on the given parameters_context.
     *
     * @param array $pipelinedata the list of parameters built by the pipeline.
     * @param launch_context $launchcontext runtime data used by the parameter pipeline.
     * @return self
     */
    public static function for_parameter_pipeline(array $pipelinedata, launch_context $launchcontext): self {
        return new self(new context_collection($launchcontext, new pipeline_params_context($pipelinedata)));
    }

    /**
     * Get an instance for use at LTI1p3 OIDC auth time, where user params are added, and substitution is re-run based on that data.
     *
     * @param array $uservars list in the form of ['user_variable_name' => 'user_variable_value'].
     * @return self
     */
    public static function for_auth(array $uservars): self {
        return new self(new context_collection(new oidc_user_context($uservars)));
    }

    /**
     * Return a new immutable instance that also contains the given context object.
     *
     * @param object $context the context object to add.
     * @return static
     */
    public function with(object $context): static {
        return new self($this->contextcollection->with($context));
    }

    /**
     * Check whether a context object of the given class is present in the collection.
     *
     * @param string $contextclass fully-qualified class name to look up.
     * @return bool true if present, false otherwise.
     */
    public function has(string $contextclass): bool {
        return $this->contextcollection->has($contextclass);
    }

    /**
     * Return the context object for the given class, or null if not present.
     *
     * @param string $contextclass fully-qualified class name to look up.
     * @return object|null the context object, or null if not found.
     */
    public function get(string $contextclass): ?object {
        return $this->contextcollection->get($contextclass);
    }

    /**
     * Return the context object for the given class, throwing if it is absent.
     *
     * @param string $contextclass fully-qualified class name to look up.
     * @return object the context object.
     * @throws \core_ltix\local\lticore\exception\lti_exception if the context class is not present.
     */
    public function require(string $contextclass): object {
        return $this->contextcollection->require($contextclass);
    }
}
