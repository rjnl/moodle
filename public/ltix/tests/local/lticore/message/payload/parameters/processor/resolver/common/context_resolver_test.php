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

namespace core_ltix\local\lticore\message\payload\parameters\processor\resolver\common;

use core_ltix\local\lticore\exception\lti_exception;
use core_ltix\local\lticore\message\context\collection\launch_context;
use core_ltix\local\lticore\message\context\item\course_context;
use core_ltix\local\lticore\message\context\item\message_context;
use core_ltix\local\lticore\message\type\message_type_factory;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests covering context_resolver.
 *
 * @package    core_ltix
 * @copyright  2026 Jake Dallimore <jrhdallimore@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(context_resolver::class)]
class context_resolver_test extends \basic_testcase {

    /**
     * Helper returning a course stub.
     *
     * @return \stdClass the course object stub.
     */
    protected function get_course_stub(): \stdClass {
        return (object) [
            'id' => '100000',
            'category' => '1',
            'sortorder' => '0',
            'fullname' => 'Test course 1',
            'shortname' => 'tc_1',
            'idnumber' => '',
            'summary' => 'Test course 1
                Lorem ipsum dolor sit amet',
            'summaryformat' => '0',
            'format' => 'topics',
            'showgrades' => '1',
            'newsitems' => '0',
            'startdate' => '1765382400',
            'enddate' => '0',
            'relativedatesmode' => '0',
            'marker' => '0',
            'maxbytes' => '0',
            'legacyfiles' => '0',
            'showreports' => '0',
            'visible' => '1',
            'visibleold' => '1',
            'downloadcontent' => NULL,
            'groupmode' => '0',
            'groupmodeforce' => '0',
            'defaultgroupingid' => '0',
            'lang' => '',
            'calendartype' => '',
            'theme' => '',
            'timecreated' => '1765422564',
            'timemodified' => '1765422564',
            'requested' => '0',
            'enablecompletion' => '0',
            'completionnotify' => '0',
            'cacherev' => '0',
            'originalcourseid' => NULL,
            'showactivitydates' => '0',
            'showcompletionconditions' => '1',
            'pdfexportfont' => NULL,
            'hiddensections' => 1,
            'coursedisplay' => 0,
        ];
    }

    /**
     * Test processing using a regular (non-site) course.
     *
     * @return void
     */
    public function test_process_regular_course(): void {

        $resolver = new context_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new course_context($this->get_course_stub()))
        ;

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The existing params are unchanged.
        $this->assertEquals($params['existing_param'], $finalparams['existing_param']);

        // The expected context params were added.
        $this->assertArrayHasKey('context_id', $finalparams);
        $this->assertEquals(100000, $finalparams['context_id']);
        $this->assertArrayHasKey('context_label', $finalparams);
        $this->assertStringContainsString('tc_1', $finalparams['context_label']);
        $this->assertArrayHasKey('context_title', $finalparams);
        $this->assertStringContainsString('Test course 1', $finalparams['context_title']);
        $this->assertArrayHasKey('context_type', $finalparams);
        $this->assertStringContainsString('CourseSection', $finalparams['context_type']);
    }

    /**
     * Test processing using a site course.
     *
     * @return void
     */
    public function test_process_site_course(): void {
        $sitecourse = $this->get_course_stub();
        $sitecourse->format = 'site';

        $resolver = new context_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
            new course_context($sitecourse))
        ;

        $params = ['existing_param' => 'value'];
        $finalparams = $resolver->process($params, $launchcontext);

        // The existing params are unchanged.
        $this->assertEquals($params['existing_param'], $finalparams['existing_param']);

        // The expected context params were added.
        $this->assertArrayHasKey('context_id', $finalparams);
        $this->assertEquals(100000, $finalparams['context_id']);
        $this->assertArrayHasKey('context_label', $finalparams);
        $this->assertStringContainsString('tc_1', $finalparams['context_label']);
        $this->assertArrayHasKey('context_title', $finalparams);
        $this->assertStringContainsString('Test course 1', $finalparams['context_title']);
        $this->assertArrayHasKey('context_type', $finalparams);
        $this->assertEquals('Group', $finalparams['context_type']);
    }

    /**
     * Test processing without a required course context in the launch context.
     *
     * @return void
     */
    public function test_process_no_course(): void {
        $resolver = new context_resolver();
        $messagetypefactory = \core\di::get(message_type_factory::class);
        $launchcontext = launch_context::instance(
            new message_context($messagetypefactory->from_string('LtiResourceLinkRequest')),
        );
        $params = ['existing_param' => 'value'];
        $this->expectException(lti_exception::class);
        $this->expectExceptionMessageMatches("/^.*context_collection requires context.*course_context, but it was not provided.*/");
        $resolver->process($params, $launchcontext);
    }
}
