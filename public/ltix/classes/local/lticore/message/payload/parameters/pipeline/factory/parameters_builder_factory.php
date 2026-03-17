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

namespace core_ltix\local\lticore\message\payload\parameters\pipeline\factory;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\payload\parameters\pipeline\core\parameters_builder;
use core_ltix\local\lticore\message\payload\parameters\pipeline\registry\parameter_processor_registry;
use core_ltix\local\lticore\message\payload\parameters\processor\converter\jwt_claim_converter;
use core_ltix\local\lticore\message\payload\parameters\processor\policy\exclude_user_params_policy;
use core_ltix\local\lticore\message\payload\parameters\processor\policy\lis_bo_policy;
use core_ltix\local\lticore\message\payload\parameters\processor\policy\pii_policy;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\context_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\ext_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\launch_presentation_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\lis_bo_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\lis_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\ltixservice_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\tool_consumer_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\common\user_resolver;
use core_ltix\local\lticore\message\payload\parameters\processor\resolver\custom\resource_link_launch_custom_resolver;
use core_ltix\local\lticore\message\type\message_type;
use core_ltix\local\lticore\message\type\message_type_factory;

/**
 * Factory building parameters_builder composites.
 *
 * The correct parameters_builder composition (a pipeline of data processors) is returned for a given version + message type,
 * for all core LTI messages. {@see parameters_builder} for details of the pipeline is run to create the output params.
 *
 * Runtime registration of additional parameter builder pipelines is supported, in order to support non-core extension messages.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class parameters_builder_factory {

    /** @var array internal registry of [ltiversion:messagetype => processor composition] defining how parameters are built. */
    protected array $registry;

    /**
     * Constructor.
     *
     * @param parameter_processor_registry $processorregistry
     * @param custom_param_substitutor_factory $substitutorfactory
     * @param custom_parameter_normaliser_factory $normaliserfactory
     * @param message_type_factory $messagetypefactory
     */
    public function __construct(
        private parameter_processor_registry $processorregistry,
        private custom_param_substitutor_factory $substitutorfactory,
        private custom_parameter_normaliser_factory $normaliserfactory,
        private message_type_factory $messagetypefactory,
    ) {
        $this->register_default();
    }

    /**
     * Register core parameter builder compositions.
     *
     * NOTE: composition ordering is important for pipelines, so keep this in mind.
     *
     * @return void
     */
    private function register_default(): void {
        // There are numerous minor differences in the parameters included in launches across versions, such as:
        // - LTI1p3 AND LTI2 includes both the to_lowercase() version of custom param keys, as well as the non-normalized version - in helper::split_custom_parameters
        // - LTI2 adds lti_tool_settings as part of helper::build_custom_parameters()
        // - LTI2 adds capabilities, via helper::build_request_lti2()
        // - LTI2 unconditionally includes the following in helper::lti_build_request():
        //   - lis_result_sourcedid
        //   - lis_outcome_service_url
        //   - lis_person_name_given (will be done at auth time in 1p3).
        //   - lis_person_name_family (will be done at auth time in 1p3).
        //   - lis_person_name_full (will be done at auth time in 1p3).
        //   - ext_user_username (will be done at auth time in 1p3).
        //   - lis_person_contact_email_primary (will be done at auth time in 1p3).
        // - LTI 1px conditionally does the above....
        // - All versions include:
        //   - lis_course_section_sourcedid if it's a course context launch
        //   - lis_person_sourcedid (will be done at auth time in 1p3).
        // - Only LTI1p1 and LTI1p3 include launch parameters includes via service::get_launch_parameters(). LTI2 does NOT.
        //    See helper::get_launch_data().
        // Also note that, for 1p3, we won't include user data at the point of building the payload; that's added at auth time.

        // V1p3 Resource Link.
        $this->register_pipeline(
            lti_version::LTI_VERSION_1P3,
            $this->messagetypefactory->from_string('LtiResourceLinkRequest'),
            [
                context_resolver::class,
                resource_link_launch_custom_resolver::class,
                // V1p3: both lowercase-normalised and non-normalised custom param keys are included.
                fn (lti_version $ltiversion) => $this->normaliserfactory->get_for_version($ltiversion),
                lis_resolver::class,
                ext_resolver::class,
                lis_bo_resolver::class,
                tool_consumer_resolver::class,
                launch_presentation_resolver::class,
                ltixservice_resolver::class,
                // V1p3 only includes basic outcomes params if gradable.
                lis_bo_policy::class,
                pii_policy::class,
                exclude_user_params_policy::class, // Exclude all user params for v1p3 launches; they are added at auth time.
                fn (lti_version $ltiversion) => $this->substitutorfactory->get_for_version($ltiversion),
                jwt_claim_converter::class,
            ]
        );

        // V1p1 Resource Link (aka basic-lti-launch-request).
        $this->register_pipeline(
            lti_version::LTI_VERSION_1,
            $this->messagetypefactory->from_string('basic-lti-launch-request'),
            [
                context_resolver::class,
                resource_link_launch_custom_resolver::class,
                // In v1p1, only the lowercase-normalised custom params are included.
                fn(lti_version $ltiversion) => $this->normaliserfactory->get_for_version($ltiversion),
                user_resolver::class,
                lis_resolver::class,
                ext_resolver::class,
                lis_bo_resolver::class,
                tool_consumer_resolver::class,
                launch_presentation_resolver::class,
                ltixservice_resolver::class,
                pii_policy::class,
                // V1p1 only includes basic outcomes params if gradable.
                lis_bo_policy::class,
                fn(lti_version $ltiversion) => $this->substitutorfactory->get_for_version($ltiversion),
            ]
        );

        // V2p0 Resource Link (aka basic-lti-launch-request).
        $this->register_pipeline(
            lti_version::LTI_VERSION_2,
            $this->messagetypefactory->from_string('basic-lti-launch-request'),
            [
                context_resolver::class,
                resource_link_launch_custom_resolver::class,
                // V2p0: both lowercase-normalised and non-normalised custom param keys are included.
                fn(lti_version $ltiversion) => $this->normaliserfactory->get_for_version($ltiversion),
                user_resolver::class,
                lis_resolver::class,
                ext_resolver::class,
                lis_bo_resolver::class,
                tool_consumer_resolver::class,
                launch_presentation_resolver::class,
                // Note: Unconditionally includes bo params in v2p0, so no lis_bo_policy here.
                // Note: Unconditionally includes user params in v2p0, so no pii_policy here.
                fn(lti_version $ltiversion) => $this->substitutorfactory->get_for_version($ltiversion),
            ]
        );
    }

    /**
     * Register version + message type construction pipelines with the factory.
     *
     * @param lti_version $version the LTI version.
     * @param message_type $messagetype the message type.
     * @param string[] $compositionkeys the keys of the processors to use in the pipeline.
     */
    public function register_pipeline(lti_version $version, message_type $messagetype, array $compositionkeys): void {
        $this->registry["$version->value:{$messagetype->value()}"] = $compositionkeys;
    }

    /**
     * Get a configured pipeline for building the parameters for a specific version + message type.
     *
     * @param lti_version $ltiversion
     * @param message_type $messagetype
     * @return parameters_builder a configured parameter builder instance.
     */
    public function create_for(lti_version $ltiversion, message_type $messagetype): parameters_builder {

        $composition = $this->registry["$ltiversion->value:{$messagetype->value()}"] ?? null;
        if (!$composition) {
            throw new lti_exception("No pipeline registered for specification: '$ltiversion->value:{$messagetype->value()}'.");
        }

        $processors = [];
        foreach ($composition as $key) {
            if (is_string($key)) {
                $processors[] = $this->processorregistry->get($key);
            } else if (is_callable($key)) {
                $processors[] = $key($ltiversion);
            } else {
                throw new lti_exception("Invalid pipeline composition key: " . print_r($key, true));
            }
        }

        return new parameters_builder($processors);
    }
}
