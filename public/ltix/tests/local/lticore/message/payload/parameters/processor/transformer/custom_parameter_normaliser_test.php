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

namespace core_ltix\local\lticore\message\payload\parameters\processor\transformer;

use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering custom_parameter_normaliser.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(custom_parameter_normaliser::class)]
class custom_parameter_normaliser_test extends \basic_testcase {

    /**
     * Data provider for normalisation mode tests.
     *
     * @return array test cases.
     */
    public static function normalisation_mode_provider(): array {
        return [
            'MODE_NORMALISED_ONLY with special characters' => [
                'mode' => custom_parameter_normalisation_mode::MODE_NORMALISED_ONLY,
                'params' => [
                    'user_id' => '123',
                    'custom_Course-ID' => '456',
                    'custom_User.Name' => 'John Doe',
                    'context_id' => '789',
                ],
                'expected' => [
                    'user_id' => '123',
                    'custom_course_id' => '456',
                    'custom_user_name' => 'John Doe',
                    'context_id' => '789',
                ],
            ],
            'MODE_NORMALISED_ONLY with already normalised keys' => [
                'mode' => custom_parameter_normalisation_mode::MODE_NORMALISED_ONLY,
                'params' => [
                    'custom_course_id' => '100',
                    'custom_user_name' => 'Test User',
                    'resource_link_id' => '200',
                ],
                'expected' => [
                    'custom_course_id' => '100',
                    'custom_user_name' => 'Test User',
                    'resource_link_id' => '200',
                ],
            ],
            'MODE_BOTH with special characters' => [
                'mode' => custom_parameter_normalisation_mode::MODE_BOTH,
                'params' => [
                    'user_id' => '123',
                    'custom_Course-ID' => '456',
                    'custom_User.Name' => 'John Doe',
                    'context_id' => '789',
                ],
                'expected' => [
                    'user_id' => '123',
                    'custom_Course-ID' => '456',
                    'custom_course_id' => '456',
                    'custom_User.Name' => 'John Doe',
                    'custom_user_name' => 'John Doe',
                    'context_id' => '789',
                ],
            ],
            'MODE_BOTH with already normalised keys' => [
                'mode' => custom_parameter_normalisation_mode::MODE_BOTH,
                'params' => [
                    'custom_course_id' => '100',
                    'custom_user_name' => 'Test User',
                    'resource_link_id' => '200',
                ],
                'expected' => [
                    'custom_course_id' => '100',
                    'custom_user_name' => 'Test User',
                    'resource_link_id' => '200',
                ],
            ],
            'MODE_NORMALISED_ONLY with uppercase and mixed case' => [
                'mode' => custom_parameter_normalisation_mode::MODE_NORMALISED_ONLY,
                'params' => [
                    'custom_TestValue' => 'abc',
                    'custom_ANOTHER_TEST' => 'def',
                ],
                'expected' => [
                    'custom_testvalue' => 'abc',
                    'custom_another_test' => 'def',
                ],
            ],
            'MODE_BOTH with uppercase and mixed case' => [
                'mode' => custom_parameter_normalisation_mode::MODE_BOTH,
                'params' => [
                    'custom_TestValue' => 'abc',
                    'custom_ANOTHER_TEST' => 'def',
                ],
                'expected' => [
                    'custom_TestValue' => 'abc',
                    'custom_testvalue' => 'abc',
                    'custom_ANOTHER_TEST' => 'def',
                    'custom_another_test' => 'def',
                ],
            ],
            'MODE_NORMALISED_ONLY without any custom parameters' => [
                'mode' => custom_parameter_normalisation_mode::MODE_NORMALISED_ONLY,
                'params' => [
                    'user_id' => '123',
                    'context_id' => '456',
                    'resource_link_id' => '789',
                    'SOME_OTHER_KEY' => 'some value',
                ],
                'expected' => [
                    'user_id' => '123',
                    'context_id' => '456',
                    'resource_link_id' => '789',
                    'SOME_OTHER_KEY' => 'some value',
                ],
            ],
            'MODE_BOTH without any custom parameters' => [
                'mode' => custom_parameter_normalisation_mode::MODE_BOTH,
                'params' => [
                    'user_id' => '123',
                    'context_id' => '456',
                    'resource_link_id' => '789',
                    'SOME_OTHER_KEY' => 'some value',
                ],
                'expected' => [
                    'user_id' => '123',
                    'context_id' => '456',
                    'resource_link_id' => '789',
                    'SOME_OTHER_KEY' => 'some value',
                ],
            ],
            'MODE_NORMALISED_ONLY with colliding normalised keys' => [
                'mode' => custom_parameter_normalisation_mode::MODE_NORMALISED_ONLY,
                'params' => [
                    'custom_test-param' => 'value1',
                    'custom_test.param' => 'value2',
                    'custom_test param' => 'value3',
                    'custom_test@param' => 'value4',
                    'custom_test#param' => 'value5',
                    'custom_test$param' => 'value6',
                ],
                'expected' => [
                    'custom_test_param' => 'value6',
                ]
            ],
            'MODE_NORMALISED_BOTH with colliding normalised keys' => [
                'mode' => custom_parameter_normalisation_mode::MODE_BOTH,
                'params' => [
                    'custom_test-param' => 'value1',
                    'custom_test.param' => 'value2',
                    'custom_test param' => 'value3',
                    'custom_test@param' => 'value4',
                    'custom_test#param' => 'value5',
                    'custom_test$param' => 'value6',
                ],
                'expected' => [
                    'custom_test_param' => 'value6',
                    'custom_test-param' => 'value1',
                    'custom_test.param' => 'value2',
                    'custom_test param' => 'value3',
                    'custom_test@param' => 'value4',
                    'custom_test#param' => 'value5',
                    'custom_test$param' => 'value6',
                ]
            ],
            'MODE_BOTH with numbers in keys' => [
                'mode' => custom_parameter_normalisation_mode::MODE_BOTH,
                'params' => [
                    'custom_param123' => 'value1',
                    'custom_Test-123-Value' => 'value2',
                    'custom_456param' => 'value3',
                ],
                'expected' => [
                    'custom_param123' => 'value1',
                    'custom_Test-123-Value' => 'value2',
                    'custom_test_123_value' => 'value2',
                    'custom_456param' => 'value3',
                ]
            ],
            'MODE_BOTH with empty parameters list' => [
                'mode' => custom_parameter_normalisation_mode::MODE_BOTH,
                'params' => [],
                'expected' => [],
            ],
        ];
    }

    /**
     * Test processing with different normalisation modes.
     *
     * @param custom_parameter_normalisation_mode $mode the normalisation mode to test.
     * @param array $params the input parameters.
     * @param array $expected the expected output.
     * @return void
     */
    #[DataProvider('normalisation_mode_provider')]
    public function test_process_with_normalisation_modes(
        custom_parameter_normalisation_mode $mode,
        array $params,
        array $expected
    ): void {
        $normaliser = new custom_parameter_normaliser($mode);
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest'))
        );

        $result = $normaliser->process($params, $launchcontext);

        $this->assertEquals($expected, $result);
    }
}
