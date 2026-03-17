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

use core_ltix\local\lticore\lti_version;
use core_ltix\local\lticore\message\payload\parameters\processor\transformer\custom_parameter_normalisation_mode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering custom_parameter_normaliser_factory.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(custom_parameter_normaliser_factory::class)]
class custom_parameter_normaliser_factory_test extends \basic_testcase {

    /**
     * Data provider for version to normalisation mode mapping.
     *
     * @return array[]
     */
    public static function version_mode_provider(): array {
        return [
            'LTI 1.1 uses normalised-only mode' => [
                'version' => lti_version::LTI_VERSION_1,
                'expectedmode' => custom_parameter_normalisation_mode::MODE_NORMALISED_ONLY,
            ],
            'LTI 1.3 uses both mode' => [
                'version' => lti_version::LTI_VERSION_1P3,
                'expectedmode' => custom_parameter_normalisation_mode::MODE_BOTH,
            ],
            'LTI 2.0 uses both mode' => [
                'version' => lti_version::LTI_VERSION_2,
                'expectedmode' => custom_parameter_normalisation_mode::MODE_BOTH,
            ],
        ];
    }

    /**
     * Test that the factory returns a normaliser with the expected mode for each version.
     *
     * @param lti_version $version the LTI version.
     * @param custom_parameter_normalisation_mode $expectedmode the expected normalisation mode.
     * @return void
     */
    #[DataProvider('version_mode_provider')]
    public function test_get_for_version_returns_expected_mode(
        lti_version $version,
        custom_parameter_normalisation_mode $expectedmode,
    ): void {
        $factory = new custom_parameter_normaliser_factory();

        $normaliser = $factory->get_for_version($version);

        $reflection = new \ReflectionClass($normaliser);
        $property = $reflection->getProperty('normalisationmode');
        $property->setAccessible(true);
        $this->assertEquals($expectedmode, $property->getValue($normaliser));
    }
}
