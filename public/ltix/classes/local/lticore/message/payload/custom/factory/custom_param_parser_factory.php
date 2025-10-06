<?php

namespace core_ltix\local\lticore\message\payload\custom\factory;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\facades\service\resource_link_launch_service_facade;
use core_ltix\local\lticore\facades\service\submission_review_launch_service_facade;
use core_ltix\local\lticore\message\payload\custom\custom_param_parser;
use core_ltix\local\lticore\models\resource_link;
use core_ltix\local\lticore\token\lti_token;
use core_ltix\local\ltiopenid\lti_user;

/**
 * Simple factory for resolving custom_param_parser instances.
 *
 */
class custom_param_parser_factory {
    /**
     * Gets a custom param parser, for LTI 1p3, for use at auth time.
     *
     * @param \stdClass $toolconfig
     * @param lti_token $launchtoken
     * @param lti_user $ltiuser
     * @return custom_param_parser
     * @throws lti_exception if a parser instance cannot be created.
     */
    public function get_parser_from_auth_request(\stdClass $toolconfig, lti_token $launchtoken, lti_user $ltiuser): custom_param_parser {
        global $USER;

        $messagetype = $launchtoken->get_claim(\core_ltix\constants::LTI_JWT_CLAIM_PREFIX . '/claim/message_type');

        // TODO: below 'LtiResourceLinkRequest' should be replaced with a const.
        if ($messagetype === 'LtiResourceLinkRequest') { // Should we use \Packback\Lti1p3\LtiConstants::MESSAGE_TYPE_RESOURCE ???
            $user = $USER->id == $ltiuser->id ? $USER : \core_user::get_user($ltiuser->id);

            $context = \core\context\course::instance(
                $launchtoken->get_claim(\core_ltix\constants::LTI_JWT_CLAIM_PREFIX.'/claim/context')['id']
            );
            // If the auth request is for a ResourceLink Launch, create the custom parameter parser using the resource link.
            return new custom_param_parser(
                \core_ltix\helper::get_capabilities(),
                new resource_link_launch_service_facade(
                    toolconfig: $toolconfig,
                    context: $context,
                    userid: $ltiuser->id,
                    resourcelink: resource_link::get_record(
                        ['id' => $launchtoken->get_claim(\core_ltix\constants::LTI_JWT_CLAIM_PREFIX.'/claim/resource_link')['id']]
                    )
                ),
                context: $context,
                user: $user
            );
        } else if ($messagetype === 'LtiSubmissionReviewRequest') { // Should we use \Packback\Lti1p3\LtiConstants::MESSAGE_TYPE_SUBMISSIONREVIEW ???
            $user = $USER->id == $ltiuser->id ? $USER : \core_user::get_user($ltiuser->id);

            $context = \core\context\course::instance(
                $launchtoken->get_claim(\core_ltix\constants::LTI_JWT_CLAIM_PREFIX.'/claim/context')['id']
            );
            // If the auth request is for a ResourceLink Launch, create the custom parameter parser using the resource link.
            return new custom_param_parser(
                \core_ltix\helper::get_capabilities(),
                new submission_review_launch_service_facade(
                    toolconfig: $toolconfig,
                    context: $context,
                    userid: $ltiuser->id,
                    resourcelink: resource_link::get_record(
                        ['id' => $launchtoken->get_claim(\core_ltix\constants::LTI_JWT_CLAIM_PREFIX.'/claim/resource_link')['id']]
                    )
                ),
                context: $context,
                user: $user
            );
        }

        throw new lti_exception('Could not create custom_param_parser instance for message type: ' . $messagetype);
    }
}
