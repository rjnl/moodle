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

namespace core_ltix\local\lticore\message\payload;

use core_ltix\constants;
use core_ltix\oauth_helper;

/**
 * Converter class for transforming 1p3 claims payloads to legacy (1p1/2p0) params-based payloads and vice versa.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lti_1px_payload_converter {

    /** @var array|null the JWT claim mapping array. */
    private ?array $jwtclaimmapping;

    /** @var array|null the message type mapping array. */
    private ?array $messagetypemapping;

    /**
     * Constructor.
     *
     * @param lis_vocab_converter $lisvocabconverter vocab converter instance.
     * @param null|array $jwtclaimmapping optional alternative v1p1 params to v1p3 claims mapping array.
     * @param null|array $messagetypemapping optional alternative v1p1 => v1p3 message type mapping array.
     */
    public function __construct(
        private lis_vocab_converter $lisvocabconverter,
        ?array $jwtclaimmapping = null,
        ?array $messagetypemapping = null,
    ) {
        // Use sensible defaults, but permit other JWT mapping to be used.
        $this->jwtclaimmapping = $jwtclaimmapping ?? oauth_helper::get_jwt_claim_mapping();
        $this->messagetypemapping = $messagetypemapping ?? oauth_helper::get_jwt_message_type_mapping();
    }

    /**
     * Convert 1p3 claims payload to 1p1/2p0 flat params payload.
     *
     * @param array $claims the array of 1p3 claims
     * @return array the legacy launch params array
     */
    public function claims_to_params(array $claims): array {

        // A note on Role and ContextType vocabularies and how they pertain to legacy versions:
        // Role:
        // - LTI-1p0 supports the v1 urn-based vocab (e.g. 'urn:lti:role:ims/lis/Learner') as well as simple names (e.g. 'Learner').
        // - LTI-2p0 adopted the early v2 RDF style (e.g. 'http://purl.imsglobal.org/vocab/lis/v2/person#Student') roles (some of
        // which were later deprecated in LTI-1p3), but it also supports the v1 urn-based roles (e.g. 'urn:lti:role:ims/lis/Learner'
        // - which themselves were deprecated in LTI-2p0), as well as simple names (e.g. 'Learner').
        // Context type:
        // - LTI-1p0 supports v1 urn-based context types (e.g. 'urn:lti:context-type:ims/lis/CourseSection') as well as simple
        // names (e.g. 'CourseSection').
        // - LTI-2p0 adopted the v2 RDF style context types (e.g. 'http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection'),
        // but also supports the v1 urn-based types (e.g. 'urn:lti:context-type:ims/lis/CourseSection') as well as simple names
        // (e.g. 'CourseSection').
        // Whilst it would be possible to cherry-pick the relevant v1/v2 lis vocab based on LTI version, it's simpler to instead
        // unconditionally convert roles and contexttype vocab to the v1 urn-based vocab, since both 1p0 and 2p0 support this.
        $claimspayload = $this->convert_to_lis_v1_vocab($claims);
        $claimspayload = $this->convert_message_type_to_legacy($claimspayload);
        $claimprefix = constants::LTI_JWT_CLAIM_PREFIX;
        $reversemap = $this->get_reverse_claim_mapping();
        $payload = [];
        foreach ($claimspayload as $claim => $props) {
            if (array_key_exists($claim, $reversemap)) {
                if (!is_array($props)) {
                    $payload[$reversemap[$claim]] = $props;
                    continue;
                }

                if (empty($props) || array_is_list($props)) {
                    // This first conditional handles the content items case and was ported from oauth_helper::convert_from_jwt().
                    // It makes the assumption that an array of arrays IS the content items claim and sets $value = $props
                    // (the array of arrays), assuming that it's going to end up in $payload['content_items'] and be fixed in a
                    // cleanup step at the end which specifically targets $payload['content_items'].
                    // Ideally, this should be improved. What if another claim has a list of properties, each containing an array?
                    // How is that going to be represented in the parameters array?
                    if (array_key_exists(0, $props) && is_array($props[0])) {
                        $value = $props;
                    } else {
                        $value = implode(',', $props);
                    }
                    $payload[$reversemap[$claim]] = $value;
                    continue;
                }

                foreach ($props as $name => $prop) {
                    if (is_array($prop)) {
                        $prop = implode(',', $prop);
                    } else if (is_bool($prop)) {
                        $prop = $prop ? 'true' : 'false';
                    }
                    $payload[$reversemap[$claim][$name]] = $prop;
                }
            } else {
                // Special handling for custom and extension claims, which aren't mapped in JWT mapping.
                if ($claim === $claimprefix .'/claim/custom') {
                    foreach ($props as $name => $prop) {
                        $payload['custom_' . $name] = $prop;
                    }
                } else if ($claim === $claimprefix .'/claim/ext') {
                    foreach ($props as $name => $prop) {
                        $payload['ext_' . $name] = $prop;
                    }
                }
            }
        }

        // Special handling for JSON-LD formatted content item payload.
        if (isset($payload['content_items'])) {
            $payload['content_items'] = $this->convert_content_items_v1p3_v1p1(json_encode($payload['content_items']));
        }

        return $payload;
    }

    /**
     * Convert v1p1/v2p0 flat params payload to v1p3 claims payload.
     *
     * @param array $params the flat payload of v1p1.
     * @return array the converted claims array.
     */
    public function params_to_claims(array $params): array {
        $claimmapping = $this->jwtclaimmapping;
        $payload = [];
        // N.b. this currently doesn't support conversion of the v1 content items JSON-LD => v1p3 contentitems claim,
        // since there is no use case where the platform would create an outgoing v1p3 message containing the contentitems claim.
        // $this->claims_to_params(), however, does support this as that needs to process the contentitems arriving in an inbound
        // JWT claim into its legacy contentitems JSON-LD format.
        foreach ($params as $key => $value) {
            $claim = constants::LTI_JWT_CLAIM_PREFIX;
            if (array_key_exists($key, $claimmapping)) {
                $mapping = $claimmapping[$key];
                $type = $mapping["type"] ?? "string";
                if ($mapping['isarray']) {
                    $value = explode(',', $value);
                    sort($value);
                } else if ($type == 'boolean') {
                    $value = isset($value) && ($value == 'true');
                }
                if (!empty($mapping['suffix'])) {
                    $claim .= "-{$mapping['suffix']}";
                }
                $claim .= '/claim/';
                if (is_null($mapping['group'])) {
                    $payload[$mapping['claim']] = $value;
                } else if (empty($mapping['group'])) {
                    $payload["{$claim}{$mapping['claim']}"] = $value;
                } else {
                    $claim .= $mapping['group'];
                    $payload[$claim][$mapping['claim']] = $value;
                }
            } else if (strpos($key, 'custom_') === 0) {
                $payload["{$claim}/claim/custom"][substr($key, 7)] = $value;
            } else if (strpos($key, 'ext_') === 0) {
                $payload["{$claim}/claim/ext"][substr($key, 4)] = $value;
            }
        }

        return $this->convert_message_type_to_1p3($this->convert_to_lis_v2_vocab($payload));
    }

    /**
     * Converts the v1p3 Deep-Linking format for Content-Items to the old v1p1 JSON-LD format.
     *
     * @param string $v1p3contentitems JSON string representing v1p3 content-items.
     * @return string v1p1 (JSON-LD) representation of content-items
     */
    public function convert_content_items_v1p3_v1p1($v1p3contentitems): string {
        $items = [];
        $json = json_decode($v1p3contentitems);
        if (!empty($json) && is_array($json)) {
            foreach ($json as $item) {
                if (isset($item->type)) {
                    $newitem = clone $item;
                    switch ($item->type) {
                        case 'ltiResourceLink':
                            $newitem->{'@type'} = 'LtiLinkItem';
                            $newitem->mediaType = 'application\/vnd.ims.lti.v1.ltilink';
                            break;
                        case 'link':
                        case 'rich':
                            $newitem->{'@type'} = 'ContentItem';
                            $newitem->mediaType = 'text/html';
                            break;
                        case 'file':
                            $newitem->{'@type'} = 'FileItem';
                            break;
                    }
                    unset($newitem->type);
                    if (isset($item->html)) {
                        $newitem->text = $item->html;
                        unset($newitem->html);
                    }
                    if (isset($item->iframe)) {
                        // DeepLinking allows multiple options to be declared as supported.
                        // We favor iframe over new window if both are specified.
                        $newitem->placementAdvice = new \stdClass();
                        $newitem->placementAdvice->presentationDocumentTarget = 'iframe';
                        if (isset($item->iframe->width)) {
                            $newitem->placementAdvice->displayWidth = $item->iframe->width;
                        }
                        if (isset($item->iframe->height)) {
                            $newitem->placementAdvice->displayHeight = $item->iframe->height;
                        }
                        unset($newitem->iframe);
                        unset($newitem->window);
                    } else if (isset($item->window)) {
                        $newitem->placementAdvice = new \stdClass();
                        $newitem->placementAdvice->presentationDocumentTarget = 'window';
                        if (isset($item->window->targetName)) {
                            $newitem->placementAdvice->windowTarget = $item->window->targetName;
                        }
                        if (isset($item->window->width)) {
                            $newitem->placementAdvice->displayWidth = $item->window->width;
                        }
                        if (isset($item->window->height)) {
                            $newitem->placementAdvice->displayHeight = $item->window->height;
                        }
                        unset($newitem->window);
                    } else if (isset($item->presentation)) {
                        // This may have been part of an early draft but is not in the final spec
                        // so keeping it around for now in case it's actually been used.
                        $newitem->placementAdvice = new \stdClass();
                        if (isset($item->presentation->documentTarget)) {
                            $newitem->placementAdvice->presentationDocumentTarget = $item->presentation->documentTarget;
                        }
                        if (isset($item->presentation->windowTarget)) {
                            $newitem->placementAdvice->windowTarget = $item->presentation->windowTarget;
                        }
                        if (isset($item->presentation->width)) {
                            $newitem->placementAdvice->dislayWidth = $item->presentation->width;
                        }
                        if (isset($item->presentation->height)) {
                            $newitem->placementAdvice->dislayHeight = $item->presentation->height;
                        }
                        unset($newitem->presentation);
                    }
                    if (isset($item->icon) && isset($item->icon->url)) {
                        $newitem->icon->{'@id'} = $item->icon->url;
                        unset($newitem->icon->url);
                    }
                    if (isset($item->thumbnail) && isset($item->thumbnail->url)) {
                        $newitem->thumbnail->{'@id'} = $item->thumbnail->url;
                        unset($newitem->thumbnail->url);
                    }
                    if (isset($item->lineItem)) {
                        unset($newitem->lineItem);
                        $newitem->lineItem = new \stdClass();
                        $newitem->lineItem->{'@type'} = 'LineItem';
                        $newitem->lineItem->reportingMethod = 'http://purl.imsglobal.org/ctx/lis/v2p1/Result#totalScore';
                        if (isset($item->lineItem->label)) {
                            $newitem->lineItem->label = $item->lineItem->label;
                        }
                        if (isset($item->lineItem->resourceId)) {
                            $newitem->lineItem->assignedActivity = new \stdClass();
                            $newitem->lineItem->assignedActivity->activityId = $item->lineItem->resourceId;
                        }
                        if (isset($item->lineItem->tag)) {
                            $newitem->lineItem->tag = $item->lineItem->tag;
                        }
                        if (isset($item->lineItem->scoreMaximum)) {
                            $newitem->lineItem->scoreConstraints = new \stdClass();
                            $newitem->lineItem->scoreConstraints->{'@type'} = 'NumericLimits';
                            $newitem->lineItem->scoreConstraints->totalMaximum = $item->lineItem->scoreMaximum;
                        }
                        if (isset($item->lineItem->submissionReview)) {
                            $newitem->lineItem->submissionReview = $item->lineItem->submissionReview;
                        }
                    }
                    $items[] = $newitem;
                }
            }
        }

        $newitems = new \stdClass();
        $newitems->{'@context'} = 'http://purl.imsglobal.org/ctx/lti/v1/ContentItem';
        $newitems->{'@graph'} = $items;

        return json_encode($newitems);
    }

    /**
     * Convert the LIS data within a v1p3 claims payload to suitable LIS v2 vocabulary.
     *
     * @param array $claimspayload the v1p3 claims array.
     * @return array the claims payload array, where the relevant claims have been converted to use LIS v2 vocab.
     */
    private function convert_to_lis_v2_vocab(array $claimspayload): array {
        $claimprefix = constants::LTI_JWT_CLAIM_PREFIX;

        if (isset($claimspayload[$claimprefix.'/claim/context']['type'])) {
            $claimspayload[$claimprefix.'/claim/context']['type'] =
                $this->lisvocabconverter->to_v2_context_types($claimspayload[$claimprefix.'/claim/context']['type']);
        }

        if (isset($claimspayload[$claimprefix.'/claim/roles'])) {
            $claimspayload[$claimprefix.'/claim/roles'] =
                $this->lisvocabconverter->to_v2_roles($claimspayload[$claimprefix.'/claim/roles']);
        }

        return $claimspayload;
    }

    /**
     * Convert the LIS data within a v1p3 claims payload to suitable LIS v1 vocabulary.
     *
     * @param array $claimspayload the v1p3 claims array.
     * @return array the claims payload array, where the relevant claims have been converted to use LIS v1 vocab.
     */
    private function convert_to_lis_v1_vocab(array $claimspayload): array {
        $claimprefix = constants::LTI_JWT_CLAIM_PREFIX;

        if (isset($claimspayload[$claimprefix.'/claim/context']['type'])) {
            $claimspayload[$claimprefix.'/claim/context']['type']
                = $this->lisvocabconverter->to_v1_context_types($claimspayload[$claimprefix.'/claim/context']['type']);
        }

        if (isset($claimspayload[$claimprefix.'/claim/roles'])) {
            $claimspayload[$claimprefix.'/claim/roles'] =
                $this->lisvocabconverter->to_v1_roles($claimspayload[$claimprefix.'/claim/roles']);
        }

        return $claimspayload;
    }

    /**
     * Convert the LTI message type within a v1p3 claims payload to the relevant legacy (v1p1/2p0) message type.
     *
     * @param array $claimspayload the v1p3 claims array.
     * @return array the claims array, where the message type has been converted to legacy.
     */
    private function convert_message_type_to_legacy(array $claimspayload): array {
        $claimprefix = constants::LTI_JWT_CLAIM_PREFIX;

        if (isset($claimspayload[$claimprefix.'/claim/message_type'])) {
            if (in_array($claimspayload[$claimprefix.'/claim/message_type'], $this->messagetypemapping)) {
                $claimspayload[$claimprefix.'/claim/message_type']
                    = array_search($claimspayload[$claimprefix.'/claim/message_type'], $this->messagetypemapping);
            }
        }

        return $claimspayload;
    }

    /**
     * Convert the LTI message type within a v1p3 claims payload to the relevant v1p3 message type.
     *
     * @param array $claimspayload the v1p3 claims array.
     * @return array the claims array, where the message type has been converted to v1p3.
     */
    private function convert_message_type_to_1p3(array $claimspayload): array {
        $claimprefix = constants::LTI_JWT_CLAIM_PREFIX;

        if (isset($claimspayload[$claimprefix.'/claim/message_type'])) {
            if (array_key_exists($claimspayload[$claimprefix.'/claim/message_type'], $this->messagetypemapping)) {
                $claimspayload[$claimprefix.'/claim/message_type']
                    = $this->messagetypemapping[$claimspayload[$claimprefix.'/claim/message_type']];
            }
        }

        return $claimspayload;
    }

    /**
     * Gets a reverse the JWT claim mapping array, useful for mapping claims back to params.
     *
     * @return array the reversed map.
     */
    private function get_reverse_claim_mapping(): array {
        $claimprefix = constants::LTI_JWT_CLAIM_PREFIX;
        $reversemap = [];
        foreach ($this->jwtclaimmapping as $legacy => $claiminfo) {
            // The nulls map to simple claims without the RDF prefix.
            if (is_null($claiminfo['group'])) {
                $reversemap[$claiminfo['claim']] = $legacy;
                continue;
            }

            $claimstring = $claimprefix;
            if (!empty($claiminfo['suffix'])) {
                $claimstring .= '-'.$claiminfo['suffix'];
            }
            $claimstring .= '/claim/';

            if (empty($claiminfo['group'])) {
                $claimstring .= $claiminfo['claim'];
                $reversemap[$claimstring] = $legacy;
            } else {
                $claimstring .= $claiminfo['group'];
                $reversemap[$claimstring][$claiminfo['claim']] = $legacy;
            }
        }
        return $reversemap;
    }
}
