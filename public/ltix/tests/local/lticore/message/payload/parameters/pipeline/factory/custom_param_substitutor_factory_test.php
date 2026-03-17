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
use core_ltix\local\lticore\message\substitution\factory\variable_substitutor_factory;
use core_ltix\local\lticore\message\substitution\pipeline\variable_substitutor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests covering custom_param_substitutor_factory.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(custom_param_substitutor_factory::class)]
class custom_param_substitutor_factory_test extends \basic_testcase {

    /**
     * Data provider for supported versions.
     *
     * @return array[]
     */
    public static function supported_versions_provider(): array {
        return [
            'LTI 1.1' => ['version' => lti_version::LTI_VERSION_1],
            'LTI 1.3' => ['version' => lti_version::LTI_VERSION_1P3],
            'LTI 2.0' => ['version' => lti_version::LTI_VERSION_2],
        ];
    }

    /**
     * Test that the factory delegates to variable_substitutor_factory and wraps the returned substitutor.
     *
     * @param lti_version $version
     * @return void
     */
    #[DataProvider('supported_versions_provider')]
    public function test_get_for_version_delegates_and_wraps(lti_version $version): void {
        $substitutor = $this->createMock(variable_substitutor::class);

        $subfactory = $this->createMock(variable_substitutor_factory::class);
        $subfactory->expects($this->once())
            ->method('get_for_version')
            ->with($this->equalTo($version))
            ->willReturn($substitutor);

        $factory = new custom_param_substitutor_factory($subfactory);

        $processor = $factory->get_for_version($version);

        $reflection = new \ReflectionClass($processor);
        $property = $reflection->getProperty('substitutor');
        $property->setAccessible(true);

        $this->assertSame($substitutor, $property->getValue($processor));
    }
}
