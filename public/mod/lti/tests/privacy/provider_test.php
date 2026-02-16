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

/**
 * Privacy provider tests.
 *
 * @package    mod_lti
 * @copyright  2018 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_lti\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/lti/locallib.php');

/**
 * Privacy provider tests class.
 *
 * @package    mod_lti
 * @copyright  2018 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_test extends \core_privacy\tests\provider_testcase {

    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata(): void {
        $collection = new collection('mod_lti');
        $collection = provider::get_metadata($collection);
        $this->assertNotEmpty($collection);
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        // Create a course tool.
        $coursetoolid = $this->create_course_tool($course->id);

        // The LTI activity the user will have submitted something for.
        $lti1 = $this->getDataGenerator()->create_module('lti', ['course' => $course->id]);
        $lti1context = \context_module::instance($lti1->cmid);
        // Create a resource link associated to the lti1 activity.
        $resourcelink1 = \core_ltix\local\placement\service\resource_link_manager::create_resource_link(
            'mod_lti:activityplacement',
            'mod_lti',
            $lti1context,
            $coursetoolid,
            1,
            'http://example.com/tool/1/resource/1',
            'Resource title',
            gradable: true
        );

        // Another LTI activity that has no user activity.
        $this->getDataGenerator()->create_module('lti', ['course' => $course->id]);

        // Create a user which will make a submission.
        $user = $this->getDataGenerator()->create_user();

        // Create LTI submission for the user for the lti1 activity.
        $this->create_lti_submission($resourcelink1->get('id'), $user->id);

        // Check the contexts supplied are correct.
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(1, $contextlist);
        $contextformodule = $contextlist->current();
        $this->assertEquals($lti1context->id, $contextformodule->id);
    }

    /**
     * Test for provider::test_get_users_in_context()
     */
    public function test_get_users_in_context(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        // Create a course tool.
        $coursetoolid = $this->create_course_tool($course->id);

        // The LTI activity the users will have submitted something for.
        $lti1 = $this->getDataGenerator()->create_module('lti', ['course' => $course->id]);
        $lti1context = \context_module::instance($lti1->cmid);
        // Create a resource link associated to the lti1 activity.
        $resourcelink1 = \core_ltix\local\placement\service\resource_link_manager::create_resource_link(
            'mod_lti:activityplacement',
            'mod_lti',
            $lti1context,
            $coursetoolid,
            1,
            'http://example.com/tool/1/resource/1',
            'Resource title',
            gradable: true
        );

        // Another LTI activity that has no user activity.
        $lti2 = $this->getDataGenerator()->create_module('lti', ['course' => $course->id]);
        $lti2context = \context_module::instance($lti2->cmid);
        // Create a resource link associated to the lti2 activity.
        \core_ltix\local\placement\service\resource_link_manager::create_resource_link(
            'mod_lti:activityplacement',
            'mod_lti',
            $lti2context,
            $coursetoolid,
            2,
            'http://example.com/tool/1/resource/1',
            'Resource title',
            gradable: true
        );

        // Create user which will make a submission each.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create LTI submissions for user1 and user2 for the lti1 activity.
        $this->create_lti_submission($resourcelink1->get('id'), $user1->id);
        $this->create_lti_submission($resourcelink1->get('id'), $user2->id);

        // Confirm that the correct users with data are returned for the lti1 context.
        $userlist = new \core_privacy\local\request\userlist($lti1context, 'mod_lti');
        provider::get_users_in_context($userlist);
        $this->assertCount(2, $userlist);
        $expected = [$user1->id, $user2->id];
        $actual = $userlist->get_userids();
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);

        // Confirm that no users are returned for the lti2 context.
        $userlist = new \core_privacy\local\request\userlist($lti2context, 'mod_lti');
        provider::get_users_in_context($userlist);
        $this->assertEmpty($userlist->get_userids());
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_for_context_submissions(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        // Create a course tool.
        $coursetoolid = $this->create_course_tool($course->id);

        // The LTI activity the users will have submitted something for.
        $lti1 = $this->getDataGenerator()->create_module('lti', ['course' => $course->id]);
        $lti1context = \context_module::instance($lti1->cmid);
        // Create a resource link associated to the lti activity.
        $resourcelink1 = \core_ltix\local\placement\service\resource_link_manager::create_resource_link(
            'mod_lti:activityplacement',
            'mod_lti',
            $lti1context,
            $coursetoolid,
            1,
            'http://example.com/tool/1/resource/1',
            'Resource title',
            gradable: true
        );

        // Another LTI activity that has no user activity.
        $lti2 = $this->getDataGenerator()->create_module('lti', ['course' => $course->id]);
        $lti2context = \context_module::instance($lti2->cmid);
        // Create a resource link associated to the lti2 activity.
        \core_ltix\local\placement\service\resource_link_manager::create_resource_link(
            'mod_lti:activityplacement',
            'mod_lti',
            $lti2context,
            $coursetoolid,
            2,
            'http://example.com/tool/1/resource/1',
            'Resource title',
            gradable: true
        );

        // Create users which will make submissions.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create LTI submissions for user1 and user2 for the lti1 activity.
        $this->create_lti_submission($resourcelink1->get('id'), $user1->id);
        $this->create_lti_submission($resourcelink1->get('id'), $user1->id);
        $this->create_lti_submission($resourcelink1->get('id'), $user2->id);

        // Export all of the data for user 1 in the lti1 module context.
        $this->export_context_data_for_user($user1->id, $lti1context, 'mod_lti');
        $writer = \core_privacy\local\request\writer::with_context($lti1context);
        $this->assertTrue($writer->has_any_data());
        $data = $writer->get_data();
        $this->assertCount(2, $data->submissions);

        // Export all of the data for user 2 in the lti1 module context.
        $this->export_context_data_for_user($user2->id, $lti1context, 'mod_lti');
        $writer = \core_privacy\local\request\writer::with_context($lti1context);
        $this->assertTrue($writer->has_any_data());
        $data = $writer->get_data();
        $this->assertCount(1, $data->submissions);

        // Export all of the data for user 1 in the lti2 module context.
        $this->export_context_data_for_user($user1->id, $lti2context, 'mod_lti');
        $writer = \core_privacy\local\request\writer::with_context($lti2context);
        // Confirm that no data is returned.
        $this->assertFalse($writer->has_any_data());
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        // Create a course tool.
        $coursetoolid = $this->create_course_tool($course->id);

        // The LTI activity the users will have submitted something for.
        $lti1 = $this->getDataGenerator()->create_module('lti', ['course' => $course->id]);
        $lti1context = \context_module::instance($lti1->cmid);
        // Create a resource link associated to the lti1 activity.
        $resourcelink1 = \core_ltix\local\placement\service\resource_link_manager::create_resource_link(
            'mod_lti:activityplacement',
            'mod_lti',
            $lti1context,
            $coursetoolid,
            1,
            'http://example.com/tool/1/resource/1',
            'Resource title',
            gradable: true
        );

        // Another LTI activity the users will have submitted something for.
        $lti2 = $this->getDataGenerator()->create_module('lti', ['course' => $course->id]);
        $lti2context = \context_module::instance($lti2->cmid);
        // Create a resource link associated to the lti2 activity.
        $resourcelink2 = \core_ltix\local\placement\service\resource_link_manager::create_resource_link(
            'mod_lti:activityplacement',
            'mod_lti',
            $lti2context,
            $coursetoolid,
            2,
            'http://example.com/tool/1/resource/1',
            'Resource title',
            gradable: true
        );

        // Create users that will make submissions.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create lti submissions for user1 and user2 for the lti1 activity.
        $this->create_lti_submission($resourcelink1->get('id'), $user1->id);
        $this->create_lti_submission($resourcelink1->get('id'), $user2->id);
        // Create lti submission for user1 for the lti2 activity.
        $this->create_lti_submission($resourcelink2->get('id'), $user1->id);

        // Before deletion, ensure that we have 2 submissions for lti1 activity and 1 submission for lti2 activity.
        $count = $DB->count_records('lti_submission', ['ltiresourcelinkid' => $resourcelink1->get('id')]);
        $this->assertEquals(2, $count);
        $count = $DB->count_records('lti_submission', ['ltiresourcelinkid' => $resourcelink2->get('id')]);
        $this->assertEquals(1, $count);

        // Delete data for all users in lti1 module context.
        provider::delete_data_for_all_users_in_context($lti1context);

        // After deletion, the lti submissions for lti1 activity should have been deleted.
        $count = $DB->count_records('lti_submission', ['ltiresourcelinkid' => $resourcelink1->get('id')]);
        $this->assertEquals(0, $count);
        // Confirm the lti submissions for lti2 activity still exist.
        $count = $DB->count_records('lti_submission', ['ltiresourcelinkid' => $resourcelink2->get('id')]);
        $this->assertEquals(1, $count);
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        // Create a course tool.
        $coursetoolid = $this->create_course_tool($course->id);

        // The LTI activity the users will have submitted something for.
        $lti1 = $this->getDataGenerator()->create_module('lti', ['course' => $course->id]);
        $lti1context = \context_module::instance($lti1->cmid);
        // Create a resource link associated to the lti1 activity.
        $resourcelink1 = \core_ltix\local\placement\service\resource_link_manager::create_resource_link(
            'mod_lti:activityplacement',
            'mod_lti',
            $lti1context,
            $coursetoolid,
            1,
            'http://example.com/tool/1/resource/1',
            'Resource title',
            gradable: true
        );

        // Another LTI activity the users will have submitted something for.
        $lti2 = $this->getDataGenerator()->create_module('lti', ['course' => $course->id]);
        $lti2context = \context_module::instance($lti2->cmid);
        // Create a resource link associated to the lti2 activity.
        $resourcelink2 = \core_ltix\local\placement\service\resource_link_manager::create_resource_link(
            'mod_lti:activityplacement',
            'mod_lti',
            $lti2context,
            $coursetoolid,
            2,
            'http://example.com/tool/1/resource/1',
            'Resource title',
            gradable: true
        );

        // Create users that will make submissions.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Create lti submissions for user1 and user2 for the lti1 activity.
        $this->create_lti_submission($resourcelink1->get('id'), $user1->id);
        $this->create_lti_submission($resourcelink1->get('id'), $user2->id);
        // Create lti submission for user1 for the lti2 activity.
        $this->create_lti_submission($resourcelink2->get('id'), $user1->id);

        // Before deletion, confirm that there are 2 lti submissions for lti1 activity.
        $count = $DB->count_records('lti_submission', ['ltiresourcelinkid' => $resourcelink1->get('id')]);
        $this->assertEquals(2, $count);

        // Delete the data for user1 in the lti1 activity context and system context.
        $contextlist = new approved_contextlist($user1, 'lti', [\context_system::instance()->id, $lti1context->id]);
        provider::delete_data_for_user($contextlist);

        // After deletion the lti submission for user1 should have been deleted for lti1 activity.
        $count = $DB->count_records(
            'lti_submission',
            ['ltiresourcelinkid' => $resourcelink1->get('id'), 'userid' => $user1->id]
        );
        $this->assertEquals(0, $count);

        // Confirm the lti submission data for user2 for lti1 activity is still there.
        $count = $DB->count_records(
            'lti_submission',
            ['ltiresourcelinkid' => $resourcelink1->get('id'),
            'userid' => $user2->id]
        );
        $this->assertEquals(1, $count);

        // Confirm the lti submission data for user1 for lti2 activity is still there.
        $count = $DB->count_records(
            'lti_submission',
            ['ltiresourcelinkid' => $resourcelink2->get('id'),
            'userid' => $user1->id]
        );
        $this->assertEquals(1, $count);
    }

    /**
     * Test for provider::delete_data_for_users().
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        // Create a course tool.
        $coursetoolid = $this->create_course_tool($course->id);

        // The LTI activity the users will have submitted something for.
        $lti = $this->getDataGenerator()->create_module('lti', ['course' => $course->id]);
        $lticontext = \context_module::instance($lti->cmid);
        // Create a resource link associated to the lti activity.
        $resourcelink = \core_ltix\local\placement\service\resource_link_manager::create_resource_link(
            'mod_lti:activityplacement',
            'mod_lti',
            $lticontext,
            $coursetoolid,
            1,
            'http://example.com/tool/1/resource/1',
            'Resource title',
            gradable: true
        );

        // Create users that will make submissions.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // Create lti submissions for user1, user2 and user3 for the lti activity.
        $this->create_lti_submission($resourcelink->get('id'), $user1->id);
        $this->create_lti_submission($resourcelink->get('id'), $user2->id);
        $this->create_lti_submission($resourcelink->get('id'), $user3->id);

        // Before deletion we should have 3 lti submissions for the lti1 activity.
        $count = $DB->count_records('lti_submission', ['ltiresourcelinkid' => $resourcelink->get('id')]);
        $this->assertEquals(3, $count);

        // Delete the data for user1 and user2 for lti activity context.
        $approveduserids = [$user1->id, $user2->id];
        $approvedlist = new approved_userlist($lticontext, 'mod_lti', $approveduserids);
        provider::delete_data_for_users($approvedlist);

        // After deletion the lti submission data for user1 and user2 should have been deleted.
        list($insql, $inparams) = $DB->get_in_or_equal($approveduserids, SQL_PARAMS_NAMED);
        $sql = "ltiresourcelinkid = :ltiresourcelinkid AND userid {$insql}";
        $params = array_merge($inparams, ['ltiresourcelinkid' => $lti->id]);
        $count = $DB->count_records_select('lti_submission', $sql, $params);
        $this->assertEquals(0, $count);

        // Check the lit submission for the user3 is still there.
        $ltisubmission = $DB->get_records('lti_submission');
        $this->assertCount(1, $ltisubmission);
        $lastsubmission = reset($ltisubmission);
        $this->assertEquals($user3->id, $lastsubmission->userid);
    }

    /**
     * Mimicks the creation of an LTI submission.
     *
     * There is no API we can use to insert an LTI submission, so we
     * will simply insert directly into the database.
     *
     * @param int $ltiresourcelinkid The resource link ID
     * @param int $userid
     */
    protected function create_lti_submission(int $ltiresourcelinkid, int $userid) {
        global $DB;

        $ltisubmissiondata = [
            'ltiresourcelinkid' => $ltiresourcelinkid,
            'userid' => $userid,
            'datesubmitted' => time(),
            'dateupdated' => time(),
            'gradepercent' => 65,
            'originalgrade' => 70,
            'launchid' => 3,
            'state' => 1
        ];

        $DB->insert_record('lti_submission', $ltisubmissiondata);
    }

    /**
     * Helper method for creating a course tool with a configured and enabled 'mod_lti:activityplacement' placement.
     *
     * @param int $courseid The course ID
     * @return int The ID of the created course tool
     */
    private function create_course_tool(int $courseid): int {
        global $DB;

        $ltigenerator = $this->getDataGenerator()->get_plugin_generator('core_ltix');
        $coursetoolid = $ltigenerator->create_course_tool_types([
            'baseurl' => 'https://www.moodle.org',
            'course' => $courseid,
            'coursevisible' => \core_ltix\constants::LTI_COURSEVISIBLE_PRECONFIGURED,
            'state' => \core_ltix\constants::LTI_TOOL_STATE_CONFIGURED
        ]);
        // Create and enable 'mod_lti:activityplacement' placement in the course tool.
        $placementtypeid = $DB->get_field('lti_placement_type', 'id', ['type' => 'mod_lti:activityplacement']);
        $ltigenerator->create_tool_placements([
            'toolid' => $coursetoolid,
            'placementtypeid' => $placementtypeid,
            'config_default_usage' => 'enabled',
            'config_supports_deep_linking' => 0,
        ]);

        return $coursetoolid;
    }
}
