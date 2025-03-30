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

namespace mod_lti\lti\placement\contentitemformatter\form;

use core_ltix\local\placement\contentitemformatter\content_item_data_formatter;

/**
 * Class for formatting content item data into a structured form data object.
 *
 * @package    mod_lti
 * @copyright  2025 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_item_to_form_formatter extends content_item_data_formatter {

    /**
     * Formats the provided content item data into a structured form data object.
     *
     * @param array $contentitems An array of content item data to be formatted.
     * @param object $tool The tool object that contain additional context to assist in formatting.
     *
     * @return object|null Returns the formatted data.
     */
    public function format(array $contentitems, object $tool): ?object {

        if (empty($contentitems)) { // Early return if there are no content items.
            return null;
        }

        $typeconfig = \core_ltix\helper::get_type_type_config($tool->id);
        $acceptsgrades = $typeconfig->lti_acceptgrades ?? null;

        if (count($contentitems) === 1) { // There is only one content item.
            return $this->content_item_to_form_data($contentitems[0], $tool, $acceptsgrades);
        }
        // Otherwise, there are multiple content items.
        $multiple = array_map(function($contentitem) use ($tool, $acceptsgrades) {
            return $this->content_item_to_form_data($contentitem, $tool, $acceptsgrades);
        }, $contentitems);

        return (object) ['multiple' => $multiple];
    }

    /**
     * Converts a content item to a standardized form data object.
     *
     * This method takes a content item, the associated tool, and an optional `acceptgrades` parameter to build
     * a standardized form data object that can be used in the context of processing the content item selection return.
     *
     * @param object $item The content item to be converted into form data.
     * @param object $tool The tool object.
     * @param int|null $acceptgrades (optional) Parameter that determines whether the tool is configured to accept grades.
     *
     * @return object Returns an object that contains the content item formatted as form data.
     */
    private function content_item_to_form_data(object $item, object $tool, ?int $acceptgrades): object {
        global $OUTPUT;

        $data = [];
        // Use the content item's title as the name if available; otherwise, fall back to the tool name, or default to
        // an empty string if neither is set.
        $data['name'] = $item->title ?? $tool->name ?? '';
        $data['introeditor'] = [
            'text' => $item->text ?? '',
            'format' => FORMAT_PLAIN
        ];

        if (isset($item->icon->{'@id'})) {
            $iconurl = new \moodle_url($item->icon->{'@id'});
            // Assign item's icon URL to secureicon or icon depending on its scheme.
            $name = strtolower($iconurl->get_scheme()) === 'https' ? 'secureicon' : 'icon';
            $data[$name] = $iconurl->out(false);
        }

        if (isset($item->url)) {
            $url = new \moodle_url($item->url);
            $data['toolurl'] = $url->out(false);
            $data['typeid'] = 0;
        } else {
            $data['typeid'] = $tool->id;
        }

        $data['instructorchoiceacceptgrades'] = \core_ltix\constants::LTI_SETTING_NEVER;
        $data['instructorchoicesendname'] = \core_ltix\constants::LTI_SETTING_NEVER;
        $data['instructorchoicesendemailaddr'] = \core_ltix\constants::LTI_SETTING_NEVER;
        // Since 4.3, the launch container is dictated by the value set in tool configuration and isn't controllable
        // by content items.
        $data['launchcontainer'] = \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT;

        if ($tool->ltiversion !== \core_ltix\constants::LTI_VERSION_2 && !is_null($acceptgrades)) {
            $maxscore = 100;
            if ($acceptgrades === \core_ltix\constants::LTI_SETTING_ALWAYS) {
                // We create a line item regardless if the definition contains one or not.
                $data['instructorchoiceacceptgrades'] = \core_ltix\constants::LTI_SETTING_ALWAYS;
                $data['grade_modgrade_point'] = $maxscore;
            }

            if (in_array($acceptgrades, [\core_ltix\constants::LTI_SETTING_DELEGATE, \core_ltix\constants::LTI_SETTING_ALWAYS])
                    && isset($item->lineItem)) {
                $lineitem = $item->lineItem;
                $data['instructorchoiceacceptgrades'] = \core_ltix\constants::LTI_SETTING_ALWAYS;

                if (isset($lineitem->scoreConstraints)) {
                    $sc = $lineitem->scoreConstraints;
                    $maxscore = $sc->totalMaximum ?? $sc->normalMaximum ?? $maxscore;
                }

                $data['grade_modgrade_point'] = $maxscore;
                $data['lineitemresourceid'] = $lineitem->assignedActivity->activityId ?? '';
                $data['lineitemtag'] = $lineitem->tag ?? '';
                $data['lineitemsubreviewurl'] = '';
                $data['lineitemsubreviewparams'] = '';

                if (isset($lineitem->submissionReview)) {
                    $subreview = $lineitem->submissionReview;
                    $data['lineitemsubreviewurl'] = !empty($subreview->url) ? $subreview->url : 'DEFAULT';

                    if (isset($subreview->custom)) {
                        $data['lineitemsubreviewparams'] = \core_ltix\helper::params_to_string($subreview->custom);
                    }
                }
            }
        }

        if (isset($item->custom)) {
            $data['instructorcustomparameters'] = \core_ltix\helper::params_to_string($item->custom);
        }
        // Pass an indicator to the relevant form field.
        $data['selectcontentindicator'] = $OUTPUT->pix_icon('i/valid', get_string('yes')) .
            get_string('contentselected', 'core_ltix');

        return (object) $data;
    }
}
