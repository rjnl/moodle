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

namespace mod_assign;

use mod_assign_test_generator;
use mod_assign_testable_assign;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

/**
 * Tests that students cannot change their own submission once it has been graded or marked.
 *
 * @package    mod_assign
 * @category   test
 * @copyright  2026 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\assign::class)]
final class submissions_open_graded_test extends \advanced_testcase {
    // Use the generator helper.
    use mod_assign_test_generator;

    /**
     * Create an assignment with a teacher, and a student who has made a submission.
     *
     * Without draft submissions by default, so the student's work is submitted as soon as it is saved.
     *
     * @param array $params Extra assignment settings.
     * @return array [assign, teacher, student]
     */
    private function create_assign_with_submission(array $params = []): array {
        $course = self::getDataGenerator()->create_course();
        $teacher = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = self::getDataGenerator()->create_and_enrol($course, 'student');

        $assign = $this->create_instance($course, $params + [
            'assignsubmission_onlinetext_enabled' => 1,
            'submissiondrafts' => 0,
        ]);
        $this->add_submission($student, $assign);

        return [$assign, $teacher, $student];
    }

    /**
     * Apply a grade and/or marking workflow state to a student's attempt, as the teacher.
     *
     * @param mod_assign_testable_assign $assign
     * @param \stdClass $teacher
     * @param \stdClass $student
     * @param array $data Grade form data, for example grade, workflowstate or addattempt.
     * @param int $attemptnumber
     */
    private function apply_grade(
        mod_assign_testable_assign $assign,
        \stdClass $teacher,
        \stdClass $student,
        array $data,
        int $attemptnumber = 0,
    ): void {
        $this->setUser($teacher);
        $assign->testable_apply_grade_to_user((object) $data, $student->id, $attemptnumber);
    }

    /**
     * Test when a student's own submission is open for changes, depending on grading status.
     *
     * @param bool $markingworkflow Whether the assignment uses marking workflow.
     * @param string|null $workflowstate The marking workflow state to apply, if any.
     * @param float|null $grade The grade to apply, if any.
     * @param bool $expected Whether the student should still be able to change their submission.
     */
    #[DataProvider('submissions_open_provider')]
    public function test_student_submissions_open(
        bool $markingworkflow,
        ?string $workflowstate,
        ?float $grade,
        bool $expected,
    ): void {
        $this->resetAfterTest();
        [$assign, $teacher, $student] = $this->create_assign_with_submission(['markingworkflow' => $markingworkflow]);

        $data = [];
        if ($grade !== null) {
            $data['grade'] = $grade;
        }
        if ($workflowstate !== null) {
            $data['workflowstate'] = $workflowstate;
        }
        if ($data) {
            $this->apply_grade($assign, $teacher, $student, $data);
        }

        $this->setUser($student);
        $this->assertSame($expected, $assign->submissions_open($student->id));
        $this->assertSame($expected, $assign->can_edit_submission($student->id, $student->id));
    }

    /**
     * Data provider for {@see self::test_student_submissions_open()}.
     *
     * @return array
     */
    public static function submissions_open_provider(): array {
        return [
            'No workflow, not graded' => [false, null, null, true],
            'No workflow, graded' => [false, null, 50.0, false],
            'No workflow, graded with zero' => [false, null, 0.0, false],
            'Workflow, not marked' => [true, ASSIGN_MARKING_WORKFLOW_STATE_NOTMARKED, null, true],
            'Workflow, graded but not marked' => [true, ASSIGN_MARKING_WORKFLOW_STATE_NOTMARKED, 50.0, false],
            'Workflow, in marking' => [true, ASSIGN_MARKING_WORKFLOW_STATE_INMARKING, null, false],
            'Workflow, marking completed' => [true, ASSIGN_MARKING_WORKFLOW_STATE_READYFORREVIEW, 50.0, false],
            'Workflow, in review' => [true, ASSIGN_MARKING_WORKFLOW_STATE_INREVIEW, 50.0, false],
            'Workflow, ready for release' => [true, ASSIGN_MARKING_WORKFLOW_STATE_READYFORRELEASE, 50.0, false],
            'Workflow, released' => [true, ASSIGN_MARKING_WORKFLOW_STATE_RELEASED, 50.0, false],
        ];
    }

    /**
     * Test a student cannot remove their own submission once it has been graded.
     */
    public function test_student_cannot_remove_graded_submission(): void {
        global $DB;

        $this->resetAfterTest();
        [$assign, $teacher, $student] = $this->create_assign_with_submission();
        $this->apply_grade($assign, $teacher, $student, ['grade' => 50.0]);
        $status = $assign->get_user_submission($student->id, false)->status;

        $this->setUser($student);
        $this->assertFalse($assign->remove_submission($student->id));
        $this->assertNotEmpty($assign->get_error_messages());

        // The graded submission is untouched.
        $submission = $assign->get_user_submission($student->id, false);
        $this->assertEquals($status, $submission->status);
        $this->assertTrue($DB->record_exists('assignsubmission_onlinetext', ['submission' => $submission->id]));
    }

    /**
     * Test a student can still remove their own submission while it has not been graded.
     */
    public function test_student_can_remove_ungraded_submission(): void {
        $this->resetAfterTest();
        [$assign, , $student] = $this->create_assign_with_submission();

        $this->setUser($student);
        $this->assertTrue($assign->remove_submission($student->id));
        $this->assertEquals(
            ASSIGN_SUBMISSION_STATUS_NEW,
            $assign->get_user_submission($student->id, false)->status,
        );
    }

    /**
     * Test a grader can still change a graded submission on behalf of the student.
     */
    public function test_grader_can_change_graded_submission(): void {
        $this->resetAfterTest();
        [$assign, $teacher, $student] = $this->create_assign_with_submission();
        $this->apply_grade($assign, $teacher, $student, ['grade' => 50.0]);

        // Graders need this capability to change submissions on behalf of students.
        $roleid = self::getDataGenerator()->create_role();
        assign_capability('mod/assign:editothersubmission', CAP_ALLOW, $roleid, $assign->get_context()->id);
        role_assign($roleid, $teacher->id, $assign->get_context()->id);

        $this->setUser($teacher);
        $this->assertTrue($assign->submissions_open($student->id));
        $this->assertTrue($assign->can_edit_submission($student->id, $teacher->id));
        $this->assertTrue($assign->remove_submission($student->id));
    }

    /**
     * Test a new attempt is open for the student, and closes again once the new attempt is graded.
     *
     * @param bool $markingworkflow Whether the assignment uses marking workflow.
     * @param array $gradedata The grade form data used to grade each attempt.
     */
    #[DataProvider('new_attempt_provider')]
    public function test_student_new_attempt_submissions_open(bool $markingworkflow, array $gradedata): void {
        $this->resetAfterTest();
        [$assign, $teacher, $student] = $this->create_assign_with_submission([
            'markingworkflow' => $markingworkflow,
            'attemptreopenmethod' => ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL,
            'maxattempts' => 3,
        ]);

        // Grade the first attempt and allow another attempt.
        $this->apply_grade($assign, $teacher, $student, $gradedata + ['addattempt' => 1]);

        $this->setUser($student);
        $this->assertTrue($assign->submissions_open($student->id));
        $this->assertTrue($assign->can_edit_submission($student->id, $student->id));

        // Grading the new attempt closes it for the student.
        $this->add_submission($student, $assign, 'Second attempt');
        $this->apply_grade($assign, $teacher, $student, $gradedata, 1);

        $this->setUser($student);
        $this->assertFalse($assign->submissions_open($student->id));
        $this->assertFalse($assign->can_edit_submission($student->id, $student->id));
    }

    /**
     * Data provider for {@see self::test_student_new_attempt_submissions_open()}.
     *
     * @return array
     */
    public static function new_attempt_provider(): array {
        return [
            'No workflow' => [false, ['grade' => 50.0]],
            'Workflow, released' => [true, ['grade' => 50.0, 'workflowstate' => ASSIGN_MARKING_WORKFLOW_STATE_RELEASED]],
        ];
    }

    /**
     * Test a student who was graded without submitting can still submit, and is closed once they have.
     */
    public function test_student_graded_without_submission_can_submit(): void {
        $this->resetAfterTest();
        $course = self::getDataGenerator()->create_course();
        $teacher = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student = self::getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course, ['assignsubmission_onlinetext_enabled' => 1, 'submissiondrafts' => 0]);

        // Grading a student who has not submitted creates an empty submission for them.
        $this->apply_grade($assign, $teacher, $student, ['grade' => 0.0]);
        $this->assertEquals(ASSIGN_SUBMISSION_STATUS_NEW, $assign->get_user_submission($student->id, false)->status);

        $this->setUser($student);
        $this->assertTrue($assign->submissions_open($student->id));

        // Once the student has submitted, the grade applies to their work.
        $this->add_submission($student, $assign, 'Late work');
        $this->assertFalse($assign->submissions_open($student->id));
    }

    /**
     * Test a grader reverting a graded submission to draft hands it back to the student.
     */
    public function test_student_can_change_graded_submission_reverted_to_draft(): void {
        $this->resetAfterTest();
        [$assign, $teacher, $student] = $this->create_assign_with_submission(['submissiondrafts' => 1]);
        $this->submit_for_grading($student, $assign);
        $this->apply_grade($assign, $teacher, $student, ['grade' => 50.0]);
        $assign->revert_to_draft($student->id);

        $this->setUser($student);
        $this->assertTrue($assign->submissions_open($student->id));
        $this->assertTrue($assign->can_edit_submission($student->id, $student->id));
    }

    /**
     * Test a team submission is closed for every member once any member has been graded or marked.
     *
     * @param bool $markingworkflow Whether the assignment uses marking workflow.
     * @param array $gradedata The grade form data used to grade one member.
     */
    #[DataProvider('team_submission_provider')]
    public function test_team_submission_closed_for_all_members(bool $markingworkflow, array $gradedata): void {
        $this->resetAfterTest();
        $course = self::getDataGenerator()->create_course();
        $teacher = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $student1 = self::getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = self::getDataGenerator()->create_and_enrol($course, 'student');
        $group = self::getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group, $student1);
        groups_add_member($group, $student2);
        $assign = $this->create_instance($course, [
            'assignsubmission_onlinetext_enabled' => 1,
            'submissiondrafts' => 0,
            'teamsubmission' => 1,
            'markingworkflow' => $markingworkflow,
        ]);
        $this->add_submission($student1, $assign);

        $this->setUser($student2);
        $this->assertTrue($assign->submissions_open($student2->id));

        // Only grade one member, as when the grade is not applied to the entire group.
        $this->apply_grade($assign, $teacher, $student1, $gradedata);

        foreach ([$student1, $student2] as $student) {
            $this->setUser($student);
            $this->assertFalse($assign->submissions_open($student->id));
            $this->assertFalse($assign->can_edit_submission($student->id, $student->id));
        }
    }

    /**
     * Data provider for {@see self::test_team_submission_closed_for_all_members()}.
     *
     * @return array
     */
    public static function team_submission_provider(): array {
        return [
            'No workflow' => [false, ['grade' => 50.0]],
            'Workflow, in marking' => [true, ['workflowstate' => ASSIGN_MARKING_WORKFLOW_STATE_INMARKING]],
        ];
    }

    /**
     * Test feedback without a grade does not close the submission.
     */
    public function test_student_submissions_open_with_feedback_only(): void {
        $this->resetAfterTest();
        [$assign, $teacher, $student] = $this->create_assign_with_submission([
            'grade' => 0,
            'assignfeedback_comments_enabled' => 1,
        ]);
        $this->apply_grade($assign, $teacher, $student, [
            'assignfeedbackcomments_editor' => ['text' => 'Well done', 'format' => FORMAT_HTML],
        ]);

        $this->setUser($student);
        $this->assertTrue($assign->submissions_open($student->id));
    }

    /**
     * Test the submission status tells the student, and only the student, why they can no longer change it.
     */
    public function test_submission_status_graded_locked(): void {
        global $PAGE;

        $this->resetAfterTest();
        [$assign, $teacher, $student] = $this->create_assign_with_submission();
        $PAGE->set_url(new \moodle_url('/mod/assign/view.php', ['id' => $assign->get_course_module()->id]));

        $this->setUser($student);
        $this->assertFalse($assign->get_assign_submission_status_renderable($student, true)->gradedlocked);

        $this->apply_grade($assign, $teacher, $student, ['grade' => 50.0]);

        $this->setUser($student);
        $this->assertTrue($assign->get_assign_submission_status_renderable($student, true)->gradedlocked);
        $this->setUser($teacher);
        $this->assertFalse($assign->get_assign_submission_status_renderable($student, true)->gradedlocked);
    }
    /**
     * Test a mark from one of multiple markers closes the submission before an agreed grade exists.
     */
    public function test_student_submissions_open_multiple_markers(): void {
        global $DB;

        $this->resetAfterTest();
        [$assign, $teacher, $student] = $this->create_assign_with_submission([
            'markingworkflow' => 1,
            'markingallocation' => 1,
            'markercount' => 2,
            'multimarkmethod' => ASSIGN_MULTIMARKING_METHOD_AVERAGE,
        ]);
        $teacher2 = self::getDataGenerator()->create_and_enrol($assign->get_course(), 'editingteacher');

        $this->setUser($teacher);
        $assign->update_marker_allocations($student->id, [1 => [$teacher->id], 2 => [$teacher2->id]]);
        $grade = $assign->get_user_grade($student->id, true);

        $this->setUser($student);
        $this->assertTrue($assign->submissions_open($student->id));

        // The first marker marks the submission, without changing the marking workflow state.
        $this->setUser($teacher);
        $grade->grader = $teacher->id;
        $assign->update_mark($grade, 70);
        $this->assertEquals(-1, $DB->get_field('assign_grades', 'grade', ['id' => $grade->id]));

        $this->setUser($student);
        $this->assertFalse($assign->submissions_open($student->id));
        $this->assertFalse($assign->can_edit_submission($student->id, $student->id));
    }

    /**
     * Test graders are told whether the student can still change their submission.
     */
    public function test_grader_view_student_editing_status(): void {
        $this->resetAfterTest();
        [$assign, $teacher, $student] = $this->create_assign_with_submission();

        $this->setUser($teacher);
        $this->assertFalse($assign->is_user_submission_graded_or_marked($student->id));

        $this->apply_grade($assign, $teacher, $student, ['grade' => 50.0]);

        // Graders can still change the submission, but the student cannot.
        $this->setUser($teacher);
        $this->assertTrue($assign->submissions_open($student->id));
        $this->assertTrue($assign->is_user_submission_graded_or_marked($student->id));
    }
}
