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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace core_ltix\local\lticore\message\payload;

use core_ltix\constants;
use core_ltix\helper;
use core_ltix\local\lticore\facades\service\submission_review_launch_service_facade;
use core_ltix\local\lticore\message\payload\custom\custom_param_parser;
use core_ltix\local\lticore\models\resource_link;

/**
 * Generates payload data for a 1p3 submission review launch.
 *
 * @package    core_ltix
 * @copyright  2025 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class v1p3_submission_review_launch_payload_builder implements v1p3_message_payload_builder_interface {
    // TODO add interface defining 1p3 payload builders.

    // TODO: Support and test the various minor version subtleties here, such as:
    // - DONE: LTI1p3 AND LTI2 includes both the to_lowercase() version of custom param keys, as well as the non-normalized version - in helper::split_custom_parameters
    // - NIS: LTI2 adds lti_tool_settings as part of helper::build_custom_parameters()
    // - NIS: LTI2 adds capabilities, via helper::build_request_lti2()
    // - LTI2 unconditionally includes the following in helper::lti_build_request():
    //   - DONE: lis_result_sourcedid
    //   - DONE: lis_outcome_service_url
    //   - NIS: lis_person_name_given (will be done at auth time in 1p3).
    //   - NIS: lis_person_name_family (will be done at auth time in 1p3).
    //   - NIS: lis_person_name_full (will be done at auth time in 1p3).
    //   - NIS: ext_user_username (will be done at auth time in 1p3).
    //   - NIS: lis_person_contact_email_primary (will be done at auth time in 1p3).
    // - DONE (see above): LTI 1px conditionally does the above....
    // - All versions include:
    //   - DONE: lis_course_section_sourcedid if it's a course context launch
    //   - NIS: lis_person_sourcedid (will be done at auth time in 1p3).
    // - DONE: Only LTI1p1 and LTI1p3 include launch parameters includes via service::get_launch_parameters(). LTI2 does NOT.
    //    See helper::get_launch_data().
    // Also note that, for 1p3, we won't include user data at the point of building the payload; that's added at auth time.

    /**
     * Constructor.
     *
     * @param \stdClass $toolconfig the tool configuration data.
     * @param resource_link $resourcelink the link to be launched.
     * @param \stdClass $user the user record.
     * @param submission_review_launch_service_facade $servicefacade the service facade for this launch.
     * @param custom_param_parser $customparamparser the custom parameter parser.
     * @param v1px_payload_converter_interface $claimconverter the claim converter to use.
     */
    public function __construct(
        protected \stdClass $toolconfig,
        protected resource_link $resourcelink,
        protected \stdClass $user,
        protected submission_review_launch_service_facade $servicefacade,
        protected custom_param_parser $customparamparser,
        protected v1px_payload_converter_interface $claimconverter,
    ) {
    }

    /**
     * Get the array of all optional claims for this request type.
     *
     * @return array
     */
    public function get_claims(): array {
        // Create the payload data common to any RLL.
        $unformattedpayloaddata = [
            //'messagetype' => $this->get_unformatted_message_type_data(),
            // Really, context payload depends on context, but it's fetched via link.
            'context' => $this->get_unformatted_context_data($this->resourcelink),
            //'resourcelink' => $this->get_unformatted_resource_link_data($resourcelink),
            'toolplatform' => $this->get_unformatted_tool_platform_data(),
            'launchpresentation' => $this->get_unformatted_launch_presentation_data($this->resourcelink, $this->toolconfig),
            'lis' => $this->get_unformatted_lis_data($this->resourcelink, $this->user, $this->toolconfig),
        ];
        $unformattedpayloaddata = array_merge(...array_values($unformattedpayloaddata)); // Flatten the above.

        // Add custom param data configured by the tool and link - do NOT substitute yet.
        $linkunformattedpayloaddata = $this->get_unformatted_custom_data($this->resourcelink, $this->toolconfig);
        $unformattedpayloaddata = array_merge($linkunformattedpayloaddata, $unformattedpayloaddata);

        // Allow services to add claims, again using unformatted payload data because, historically, that's what services speak.
        // Note the ordering. Services add their custom claims to the unformatted data as 'custom_%SERVICECLAIM%', so care
        // must be taken to ensure that tool-level or link-level custom params, which are represented as 'custom_%CUSTOMPARAMNAME%',
        // can't override the service claims.
        $serviceunformattedpayloaddata = $this->get_unformatted_service_custom_data($this->servicefacade);
        $unformattedpayloaddata = array_merge($unformattedpayloaddata, $serviceunformattedpayloaddata);

        // TODO: we need to allow the ltixsource plugins' 'before_launch' plugin callback to be called here?
        //  That would permit augmenting any launch params prior to signing.
        //  Do this via the service facade.


        // Perform substitution for custom params.
        // Note: this won't perform substitution for variables referencing any of the user claims, since user claims aren't
        // yet present in the payload. Substitution needs to be re-run against the unformatted user data at auth time.
        $unformattedpayloaddata = $this->resolve_substitution($unformattedpayloaddata, $this->customparamparser);

        return $this->claimconverter->params_to_claims($unformattedpayloaddata);
    }

//    /**
//     * Build the unformatted link launch payload.
//     *
//     * @param \stdClass $toolconfig
//     * @param resource_link $resourcelink
//     * @param \stdClass $user
//     * @param resource_link_launch_service_facade $servicefacade
//     * @return array
//     */
//    public function build_unformatted_link_launch_payload(
//        \stdClass $toolconfig,
//        resource_link $resourcelink,
//        \stdClass $user,
//        resource_link_launch_service_facade $servicefacade,
//        custom_param_parser $customparamparser,
//    ): array {
//
//        // Create the payload data common to any RLL.
//        $unformattedpayloaddata = [
//            //'messagetype' => $this->get_unformatted_message_type_data(),
//             // Really, context payload depends on context, but it's fetched via link.
//            'context' => $this->get_unformatted_context_data($resourcelink),
//            //'resourcelink' => $this->get_unformatted_resource_link_data($resourcelink),
//            'toolplatform' => $this->get_unformatted_tool_platform_data($toolconfig),
//            'launchpresentation' => $this->get_unformatted_launch_presentation_data($resourcelink, $toolconfig),
//            'lis' => $this->get_unformatted_lis_data($resourcelink, $user, $toolconfig),
//        ];
//        $unformattedpayloaddata = array_merge(...array_values($unformattedpayloaddata)); // Flatten the above.
//
//        // Add custom param data configured by the tool and link - do NOT substitute yet.
//        $linkunformattedpayloaddata = $this->get_unformatted_custom_data($resourcelink, $toolconfig);
//        $unformattedpayloaddata = array_merge($linkunformattedpayloaddata, $unformattedpayloaddata);
//
//        // Allow services to add claims, again using unformatted payload data because, historically, that's what services speak.
//        // Note the ordering. Services add their custom claims to the unformatted data as 'custom_%SERVICECLAIM%', so care
//        // must be taken to ensure that tool-level or link-level custom params, which are represented as 'custom_%CUSTOMPARAMNAME%',
//        // can't override the service claims.
//        $serviceunformattedpayloaddata = $this->get_unformatted_service_custom_data($servicefacade);
//        $unformattedpayloaddata = array_merge($unformattedpayloaddata, $serviceunformattedpayloaddata);
//
//        // TODO: we need to allow the ltixsource plugins' 'before_launch' plugin callback to be called here?
//        //  That would permit augmenting any launch params prior to signing.
//
//
//        // Perform substitution for custom params.
//        // Note: this won't perform substitution for variables referencing any of the user claims, since user claims aren't
//        // yet present in the payload. Substitution needs to be re-run against the unformatted user data at auth time.
//        $unformattedpayloaddata = $this->resolve_substitution($unformattedpayloaddata, $customparamparser);
//
//        return $unformattedpayloaddata;
//    }

    // TODO: LTI 1p3 specific message type.
    protected function get_unformatted_message_type_data(): array {
        return ['lti_message_type' => 'LtiSubmissionReviewRequest'];  // Should we use \Packback\Lti1p3\LtiConstants::MESSAGE_TYPE_SUBMISSIONREVIEW ?
    }

    // TODO: Only LTI 1p1 and 1p3.
    protected function get_unformatted_service_custom_data(submission_review_launch_service_facade $servicefacade): array {

        $servicecustomdata = [];
        foreach ($servicefacade->get_launch_parameters() as $param => $val) {
            $servicecustomdata['custom_' . $param] = $val;
        }
        return $servicecustomdata;
    }

    // TODO: lti1p3 specific logic in the inclusion of the non-normalised custom keys. LTI1p3 and LTI 2.0 do this, but 1p1 doesn't.
    protected function get_unformatted_custom_data(
        resource_link $resourcelink,
        \stdClass $toolconfig,
    ): array {

        $toolcustomstr = !empty($toolconfig->lti_customparameters) ? $toolconfig->lti_customparameters : '';
        $linkcustomstr = !empty($resourcelink->get('customparams')) ? $resourcelink->get('customparams') : '';
        $parsedtoolcustom = [];
        $parsedlinkcustom = [];
        if ($toolcustomstr) {
            $toolcustom = helper::split_parameters($toolcustomstr);
            foreach ($toolcustom as $key => $val) {
                $key2 = helper::map_keyname($key);
                $parsedtoolcustom['custom_' . $key2] = $val;
                if ($key != $key2) {
                    $parsedtoolcustom['custom_' . $key] = $val;
                }
            }
        }

        // The link custom params override the tool-level custom params (specific trumps generic).
        if ($linkcustomstr) {
            $linkcustom = helper::split_parameters($linkcustomstr);
            foreach ($linkcustom as $key => $val) {
                $key2 = helper::map_keyname($key);
                $parsedlinkcustom['custom_' . $key2] = $val;
                if ($key != $key2) {
                    $parsedlinkcustom['custom_' . $key] = $val;
                }
            }
        }

        return array_merge($parsedtoolcustom, $parsedlinkcustom);
    }

    // TODO if we extract common functionality to a base, this would be candidate, and subclasses can implement it as needed.
    protected function get_unformatted_user_data(): array {
        return []; // lti1p3 does NOT send user data in the initial payload.
    }

    // TODO: Note 1p1 and 1p3 difference: the conditional inclusion of the legacy lis_payload. LTI 2.0 always includes this.
    protected function get_unformatted_lis_data(resource_link $resourcelink, \stdClass $user, \stdClass $toolconfig): array {

        // Some lis properties only apply when in course-related contexts.
        $contextid = $resourcelink->get('contextid');
        $context = \core\context::instance_by_id($contextid);
        if (($coursecontext = $context->get_course_context(false)) !== false) {
            $course = get_course($coursecontext->instanceid);
            $coursesectionsourcedid = $course->idnumber;
        }

        $lisdata = [
            ...(isset($coursesectionsourcedid) ? ['lis_course_section_sourcedid' => $coursesectionsourcedid] : []),
        ];

        // LTI 1p3/1p1 specific logic:
        $legacylisclaims = $this->get_legacy_lis_payload($toolconfig, $resourcelink, $user);

        return array_merge($lisdata, $legacylisclaims);
    }

    /**
     * Injects a few legacy lis_x params to provide support older services via 1p3.
     *
     * Currently, support is added for:
     * - Basic Outcomes: this service officially supports 1p3 and is always enabled in Moodle.
     * see: https://www.imsglobal.org/spec/lti-bo/v1p1#integration-with-lti-1-3.
     *
     * @param \stdClass $toolconfig the tool config
     * @param resource_link $link the resource link
     * @param \stdClass $user the user record
     * @return array the array of payload data.
     */
    protected function get_legacy_lis_payload(\stdClass $toolconfig, resource_link $link, \stdClass $user): array {
        global $CFG;
        $legacypayloaddata = [];

        // TODO: This might not be able to handle legacy, manually configured launches that use per-link privacy/acceptgrades.
        //  In such cases, I suspect we'll want to shim the toolconfig coming in to make it appear as if the config says "enabled/disabled".

        // Basic Outcomes claim support.
        if ($link->get('gradable') && !empty($link->get('servicesalt')) &&
            ($toolconfig->lti_acceptgrades == constants::LTI_SETTING_ALWAYS ||
                ($toolconfig->lti_acceptgrades == constants::LTI_SETTING_DELEGATE))) {

            // TODO: since lis_result_sourceid includes user data, should it be generated at auth time too?
            $sourcedid = json_encode(
                helper::build_sourcedid($link->get('id'), $user->id, $link->get('servicesalt'), $toolconfig->typeid)
            );
            $legacypayloaddata['lis_result_sourcedid'] = $sourcedid;

            $serviceurl = new \moodle_url('/ltix/service.php');
            $serviceurl = $serviceurl->out();

            $forcessl = false;
            if (!empty($CFG->ltix_forcessl)) {
                $forcessl = true;
            } else if (!empty($CFG->mod_lti_forcessl)) {
                // TODO: final removal of mod_lti_forcessl in Moodle 6.0.
                debugging('mod_lti_forcessl is deprecated. Please use ltix_forcessl instead.', DEBUG_DEVELOPER);
                $forcessl = true;
            }

            if ((isset($toolconfig->lti_forcessl) && ($toolconfig->lti_forcessl == '1')) || $forcessl) {
                $serviceurl = helper::ensure_url_is_https($serviceurl);
            }
            $legacypayloaddata['lis_outcome_service_url'] = $serviceurl;
        }

        return $legacypayloaddata;
    }

    // Common payload (common to RLL launches for all versions) herafter:

    protected function resolve_substitution(
        array $payloaddata,
        custom_param_parser $customparamparser
    ): array {
        foreach ($payloaddata as $key => $value) {
            // Substitution is only performed for custom params.
            if (str_starts_with($key, 'custom_')) {
                $payloaddata[$key] = $customparamparser->parse($value, $payloaddata);
            }
        }

        return $payloaddata;
    }

    protected function get_unformatted_resource_link_data(resource_link $resourcelink): array {
        // Description is optional and may be null, in which case it'll be omitted.
        $description = $resourcelink->get('text');
        if (!is_null($description)) {
            $descriptionformat = $resourcelink->get('textformat');
            $description = format_text($description, $descriptionformat);
        }

        return [
            'resource_link_id' => $resourcelink->get('id'),
            'resource_link_title' => $resourcelink->get('title'),
            ...(!is_null($description) ? ['resource_link_description' => $description] : []),
        ];
    }

    protected function get_unformatted_launch_presentation_data(resource_link $resourcelink, \stdClass $toolconfig): array {
        $contextid = $resourcelink->get('contextid');
        $context = \core\context::instance_by_id($contextid);

        $launchcontainer = helper::get_launch_container(
            (object) ['launchcontainer' => $resourcelink->get('launchcontainer')],
            ['launchcontainer' => $toolconfig->lti_launchcontainer] // Coerce into expected get_type_config() format object.
        );
        $target = '';
        switch($launchcontainer) {
            case constants::LTI_LAUNCH_CONTAINER_EMBED:
            case constants::LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS:
                $target = 'iframe';
                break;
            case constants::LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW:
                $target = 'frame';
                break;
            case constants::LTI_LAUNCH_CONTAINER_WINDOW:
                $target = 'window';
                break;
        }

        if (($coursecontext = $context->get_course_context(false)) !== false) {
            $course = get_course($coursecontext->instanceid);

            // Add the return URL. We send the launch container along to help us avoid frames-within-frames when the user returns.
            // Note: launch_presentation_return_url is only set for course-context-related launches presently,
            // given the return endpoint (ltix/return.php) is a legacy endpoint and only works in that situation.
            // The 'instanceid' param, which the endpoint supports as an optional param, is deliberately omitted here, since the
            // endpoint expects that to match a legacy 'lti' record (being a legacy endpoint).
            // It's not a required part of the return flow and can be safely omitted.
            $returnurlparams = [
                'course' => $course->id,
                'launch_container' => $launchcontainer,
                'sesskey' => sesskey(),
            ];
            $url = new \moodle_url('/ltix/return.php', $returnurlparams);
            $returnurl = $url->out(false);

            if (isset($toolconfig->forcessl) && ($toolconfig->forcessl == '1')) {
                $returnurl = helper::ensure_url_is_https($returnurl);
            }
        }

        return [
            'launch_presentation_locale' => current_language(),
            'launch_presentation_document_target' => $target,
            ...(isset($returnurl) ? ['launch_presentation_return_url' => $returnurl] : []),
        ];
    }

    protected function get_unformatted_tool_platform_data(): array {
        global $CFG;
        if (!empty($CFG->ltix_institution_name)) {
            $name = trim(html_to_text($CFG->ltix_institution_name, 0));
        } else if (!empty($CFG->mod_lti_institution_name)) {
            // TODO final removal of the mod_lti_institution_name fallback code in Moodle 6.0.
            debugging('mod_lti_institution_name is deprecated. Please use ltix_institution_name instead.', DEBUG_DEVELOPER);
            $name = trim(html_to_text($CFG->mod_lti_institution_name, 0));
        } else {
            $name = get_site()->shortname;
        }

        return [
            'tool_consumer_info_product_family_code' => 'moodle',
            'tool_consumer_info_version' => strval($CFG->version),
            'tool_consumer_instance_guid' => helper::get_organizationid((array)$this->toolconfig), // TODO this expects get_type_config format and currently won't work.
            'tool_consumer_instance_name' => $name,
            'tool_consumer_instance_description' => trim(html_to_text(get_site()->fullname, 0)),
        ];
    }

    protected function get_unformatted_context_data(resource_link $resourcelink): ?array {
        $contextid = $resourcelink->get('contextid');
        $context = \core\context::instance_by_id($contextid);

        if (($coursecontext = $context->get_course_context(false)) === false) {
            return null;
        }

        $course = get_course($coursecontext->instanceid);
        $contexttype = $course->format == 'site' ? 'Group'
            : 'CourseSection';

        return [
            'context_id' => $course->id,
            'context_label' => $context->get_context_name(),
            'context_title' => $context->get_context_name(),
            'context_type' => $contexttype,
        ];
    }
}
