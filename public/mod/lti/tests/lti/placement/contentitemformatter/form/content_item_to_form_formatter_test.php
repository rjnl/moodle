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

/**
 * Test content item to form data formatter.
 *
 * @covers     \mod_lti\lti\placement\contentitemformatter\form\content_item_to_form_formatter
 * @package    mod_lti
 * @copyright  Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class content_item_to_form_formatter_test extends \advanced_testcase {

    /**
     * Test format method.
     *
     * @dataProvider format_provider
     * @param array $contentitems The array containing the content item data.
     * @param array $toolconfig The array containing configuration parameters used for creating the tool.
     * @param object|null $expected The expected result.
     * @return void
     */
    public function test_format(array $contentitems, array $toolconfig, ?object $expected): void {
        global $DB;

        $this->resetAfterTest();
        // Create a tool.
        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');
        $toolid = $ltigenerator->create_tool_types($toolconfig);
        $tool = $DB->get_record('lti_types', ['id' => $toolid]);
        // Instantiate the content item formatter, and then format the content item data.
        $formatter = new \mod_lti\lti\placement\contentitemformatter\form\content_item_to_form_formatter();
        $actual = $formatter->format($contentitems, $tool);
        // Ensure the returned data matches the expected result.
        $this->assertEquals($expected, $actual);
    }

    /**
     * Data provider for test_format().
     *
     * @return array
     */
    public static function format_provider(): array {
        global $OUTPUT;

        return [
            'Content items: No content items.' => [
                // The content item data.
                [],
                // The tool configuration parameters.
                [
                    'name' => 'Tool name',
                    'baseurl' => 'http://example.com/tool/1',
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                    'lti_contentitem' => 1,
                ],
                // The expected return.
                null
            ],
            'Content items: single, no icon; ' .
            'Tool: LTI v1.3, never accept grades.' => [
                // The content item data.
                [
                    (object) [
                        'title' => 'Assignment 1',
                        'text' => 'Description text',
                        'url' => 'http://example.com/tool/1/contentitem/1',
                        'custom' => (object) [
                            'id' => 'id12345'
                        ],
                        '@type' => 'LtiLinkItem',
                        'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                        'placementAdvice' => 'iframe',
                    ],
                ],
                // The tool configuration parameters.
                [
                    'name' => 'Tool name',
                    'baseurl' => 'http://example.com/tool/1',
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                    'lti_contentitem' => 1,
                    'lti_acceptgrades' => \core_ltix\constants::LTI_SETTING_NEVER,
                    'ltiversion' => \core_ltix\constants::LTI_VERSION_1P3,
                ],
                // The expected return.
                (object) [
                    'name' => 'Assignment 1',
                    'introeditor' => ['text' => 'Description text', 'format' => FORMAT_PLAIN],
                    'toolurl' => 'http://example.com/tool/1/contentitem/1',
                    'typeid' => 0,
                    'instructorchoiceacceptgrades' => \core_ltix\constants::LTI_SETTING_NEVER,
                    'instructorchoicesendname' => \core_ltix\constants::LTI_SETTING_NEVER,
                    'instructorchoicesendemailaddr' => \core_ltix\constants::LTI_SETTING_NEVER,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                    'instructorcustomparameters' => 'id=id12345',
                    'selectcontentindicator' => $OUTPUT->pix_icon('i/valid', get_string('yes')) .
                        get_string('contentselected', 'core_ltix'),
                ]
            ],
            'Content items: single, icon (standard), no line item; ' .
            'Tool: LTI v1.0, always accept grades.' => [
                // The content item data.
                [
                    (object) [
                        'title' => 'Assignment 1',
                        'text' => 'Description text',
                        'url' => 'http://example.com/tool/1/contentitem/1',
                        'custom' => (object) [
                            'id' => 'id12345'
                        ],
                        'icon' => (object) [
                            '@id' => 'http://example.com/tool/1/contentitem/1/icon'
                        ],
                        '@type' => 'LtiLinkItem',
                        'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                        'placementAdvice' => 'iframe',
                    ],
                ],
                // The tool configuration parameters.
                [
                    'name' => 'Tool name',
                    'baseurl' => 'http://example.com/tool/1',
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                    'lti_contentitem' => 1,
                    'lti_acceptgrades' => \core_ltix\constants::LTI_SETTING_ALWAYS,
                    'ltiversion' => \core_ltix\constants::LTI_VERSION_1P3,
                ],
                // The expected return.
                (object) [
                    'name' => 'Assignment 1',
                    'introeditor' => ['text' => 'Description text', 'format' => FORMAT_PLAIN],
                    'icon' => 'http://example.com/tool/1/contentitem/1/icon',
                    'toolurl' => 'http://example.com/tool/1/contentitem/1',
                    'typeid' => 0,
                    'instructorchoicesendname' => \core_ltix\constants::LTI_SETTING_NEVER,
                    'instructorchoicesendemailaddr' => \core_ltix\constants::LTI_SETTING_NEVER,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                    'instructorchoiceacceptgrades' => \core_ltix\constants::LTI_SETTING_ALWAYS,
                    'grade_modgrade_point' => 100,
                    'instructorcustomparameters' => 'id=id12345',
                    'selectcontentindicator' => $OUTPUT->pix_icon('i/valid', get_string('yes')) .
                        get_string('contentselected', 'core_ltix'),
                ]
            ],
            'Content items: single, icon (secure), line item; ' .
            'Tool: LTI v2.0, always accept grades.' => [
                // The content item data.
                [
                    (object) [
                        'title' => 'Assignment 1',
                        'text' => 'Description text',
                        'url' => 'https://example.com/tool/1/contentitem/1',
                        'custom' => (object) [
                            'id' => 'id12345'
                        ],
                        'icon' => (object) [
                            '@id' => 'https://example.com/tool/1/contentitem/1/icon'
                        ],
                        '@type' => 'LtiLinkItem',
                        'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                        'placementAdvice' => 'iframe',
                        'lineItem' => (object) [
                            '@type' => 'LineItem',
                            'scoreConstraints' => (object) [
                                '@type' => 'NumericLimits',
                                'totalMaximum' => 80,
                                'normalMaximum' => 90,
                            ],
                        ],
                    ],
                ],
                // The tool configuration parameters.
                [
                    'name' => 'Tool name',
                    'baseurl' => 'http://example.com/tool/1',
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                    'lti_contentitem' => 1,
                    'lti_acceptgrades' => \core_ltix\constants::LTI_SETTING_ALWAYS,
                    'ltiversion' => \core_ltix\constants::LTI_VERSION_2,
                ],
                // The expected return.
                (object) [
                    'name' => 'Assignment 1',
                    'introeditor' => ['text' => 'Description text', 'format' => FORMAT_PLAIN],
                    'secureicon' => 'https://example.com/tool/1/contentitem/1/icon',
                    'toolurl' => 'https://example.com/tool/1/contentitem/1',
                    'typeid' => 0,
                    'instructorchoicesendname' => \core_ltix\constants::LTI_SETTING_NEVER,
                    'instructorchoicesendemailaddr' => \core_ltix\constants::LTI_SETTING_NEVER,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                    'instructorchoiceacceptgrades' => \core_ltix\constants::LTI_SETTING_NEVER,
                    'instructorcustomparameters' => 'id=id12345',
                    'selectcontentindicator' => $OUTPUT->pix_icon('i/valid', get_string('yes')) .
                        get_string('contentselected', 'core_ltix'),
                ]
            ],
            'Content items: single, no title, no text, no custom params, has line item; ' .
            'Tool: LTI v1.3, always accept grades.' => [
                // The content item data.
                [
                    (object) [
                        'url' => 'https://example.com/tool/1/contentitem/1',
                        'icon' => (object) [
                            '@id' => 'https://example.com/tool/1/contentitem/1/icon'
                        ],
                        '@type' => 'LtiLinkItem',
                        'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                        'placementAdvice' => 'iframe',
                        'lineItem' => (object) [
                            '@type' => 'LineItem',
                            'scoreConstraints' => (object) [
                                '@type' => 'NumericLimits',
                                'totalMaximum' => 80,
                                'normalMaximum' => 90,
                            ],
                            'assignedActivity' => (object) [
                                'activityId' => '12345',
                            ],
                            'tag' => 'final',
                            'submissionReview' => (object) [],
                        ],
                    ],
                ],
                // The tool configuration parameters.
                [
                    'name' => 'Tool name',
                    'baseurl' => 'http://example.com/tool/1',
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                    'lti_contentitem' => 1,
                    'lti_acceptgrades' => \core_ltix\constants::LTI_SETTING_ALWAYS,
                    'ltiversion' => \core_ltix\constants::LTI_VERSION_1P3,
                ],
                // The expected return.
                (object) [
                    'name' => 'Tool name',
                    'introeditor' => ['text' => '', 'format' => FORMAT_PLAIN],
                    'secureicon' => 'https://example.com/tool/1/contentitem/1/icon',
                    'toolurl' => 'https://example.com/tool/1/contentitem/1',
                    'typeid' => 0,
                    'instructorchoicesendname' => \core_ltix\constants::LTI_SETTING_NEVER,
                    'instructorchoicesendemailaddr' => \core_ltix\constants::LTI_SETTING_NEVER,
                    'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                    'instructorchoiceacceptgrades' => \core_ltix\constants::LTI_SETTING_ALWAYS,
                    'grade_modgrade_point' => 80,
                    'lineitemresourceid' => '12345',
                    'lineitemtag' => 'final',
                    'lineitemsubreviewurl' => 'DEFAULT',
                    'lineitemsubreviewparams' => '',
                    'selectcontentindicator' => $OUTPUT->pix_icon('i/valid', get_string('yes')) .
                        get_string('contentselected', 'core_ltix'),
                ]
            ],
            'Content items: multiple; ' .
            'Tool: LTI v1.3, always accept grades.' => [
                // The content item data.
                [
                    (object) [
                        'url' => 'https://example.com/tool/1/contentitem/1',
                        'icon' => (object) [
                            '@id' => 'https://example.com/tool/1/contentitem/1/icon'
                        ],
                        '@type' => 'LtiLinkItem',
                        'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                        'placementAdvice' => 'iframe',
                        'lineItem' => (object) [
                            '@type' => 'LineItem',
                            'scoreConstraints' => (object) [
                                '@type' => 'NumericLimits',
                                'totalMaximum' => 80,
                            ],
                            'tag' => 'final',
                        ],
                    ],
                    (object) [
                        'title' => 'Assignment 1',
                        'text' => 'Description text 1',
                        'url' => 'https://example.com/tool/1/contentitem/2',
                        'icon' => (object) [
                            '@id' => 'https://example.com/tool/1/contentitem/2/icon'
                        ],
                        '@type' => 'LtiLinkItem',
                        'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                        'placementAdvice' => 'iframe',
                        'lineItem' => (object) [
                            '@type' => 'LineItem',
                            'scoreConstraints' => (object) [
                                '@type' => 'NumericLimits',
                                'normalMaximum' => 90,

                            ],
                            'assignedActivity' => (object) [
                                'activityId' => '12345',
                            ],
                            'tag' => 'final',
                            'submissionReview' => (object) [
                                'url' => 'https://testsub.url',
                                'custom' => (object) ['a' => 'b'],
                            ],
                        ],
                    ],
                    (object) [
                        'title' => 'Assignment 2',
                        'text' => 'Description text 2',
                        'url' => 'https://example.com/tool/1/contentitem/3',
                        'custom' => (object) [
                            'id' => 'id12345'
                        ],
                        '@type' => 'LtiLinkItem',
                        'mediaType' => 'application\/vnd.ims.lti.v1.ltilink',
                        'placementAdvice' => 'iframe',
                    ],
                ],
                // The tool configuration parameters.
                [
                    'name' => 'Tool name',
                    'baseurl' => 'http://example.com/tool/1',
                    'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
                    'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED,
                    'lti_contentitem' => 1,
                    'lti_acceptgrades' => \core_ltix\constants::LTI_SETTING_ALWAYS,
                    'ltiversion' => \core_ltix\constants::LTI_VERSION_1P3,
                ],
                // The expected return.
                (object) [
                    'multiple' => [
                        (object) [
                            'name' => 'Tool name',
                            'introeditor' => ['text' => '', 'format' => FORMAT_PLAIN],
                            'secureicon' => 'https://example.com/tool/1/contentitem/1/icon',
                            'toolurl' => 'https://example.com/tool/1/contentitem/1',
                            'typeid' => 0,
                            'instructorchoicesendname' => \core_ltix\constants::LTI_SETTING_NEVER,
                            'instructorchoicesendemailaddr' => \core_ltix\constants::LTI_SETTING_NEVER,
                            'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                            'instructorchoiceacceptgrades' => \core_ltix\constants::LTI_SETTING_ALWAYS,
                            'grade_modgrade_point' => 80,
                            'lineitemresourceid' => '',
                            'lineitemtag' => 'final',
                            'lineitemsubreviewurl' => '',
                            'lineitemsubreviewparams' => '',
                            'selectcontentindicator' => $OUTPUT->pix_icon('i/valid', get_string('yes')) .
                                get_string('contentselected', 'core_ltix'),
                        ],
                        (object) [
                            'name' => 'Assignment 1',
                            'introeditor' => ['text' => 'Description text 1', 'format' => FORMAT_PLAIN],
                            'secureicon' => 'https://example.com/tool/1/contentitem/2/icon',
                            'toolurl' => 'https://example.com/tool/1/contentitem/2',
                            'typeid' => 0,
                            'instructorchoicesendname' => \core_ltix\constants::LTI_SETTING_NEVER,
                            'instructorchoicesendemailaddr' => \core_ltix\constants::LTI_SETTING_NEVER,
                            'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                            'instructorchoiceacceptgrades' => \core_ltix\constants::LTI_SETTING_ALWAYS,
                            'grade_modgrade_point' => 90,
                            'lineitemresourceid' => '12345',
                            'lineitemtag' => 'final',
                            'lineitemsubreviewurl' => 'https://testsub.url',
                            'lineitemsubreviewparams' => 'a=b',
                            'selectcontentindicator' => $OUTPUT->pix_icon('i/valid', get_string('yes')) .
                                get_string('contentselected', 'core_ltix'),
                        ],
                        (object) [
                            'name' => 'Assignment 2',
                            'introeditor' => ['text' => 'Description text 2', 'format' => FORMAT_PLAIN],
                            'toolurl' => 'https://example.com/tool/1/contentitem/3',
                            'typeid' => 0,
                            'instructorchoicesendname' => \core_ltix\constants::LTI_SETTING_NEVER,
                            'instructorchoicesendemailaddr' => \core_ltix\constants::LTI_SETTING_NEVER,
                            'launchcontainer' => \core_ltix\constants::LTI_LAUNCH_CONTAINER_DEFAULT,
                            'instructorchoiceacceptgrades' => \core_ltix\constants::LTI_SETTING_ALWAYS,
                            'grade_modgrade_point' => 100,
                            'instructorcustomparameters' => 'id=id12345',
                            'selectcontentindicator' => $OUTPUT->pix_icon('i/valid', get_string('yes')) .
                                get_string('contentselected', 'core_ltix'),
                        ],
                    ]
                ]
            ],
        ];
    }
}
