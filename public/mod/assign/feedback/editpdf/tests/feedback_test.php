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

namespace assignfeedback_editpdf;

use assignfeedback_editpdf\event\observer;
use assignfeedback_editpdf\task\convert_submission;
use mod_assign_test_generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/tests/generator.php');

/**
 * Unit tests for assignfeedback_editpdf\comments_quick_list
 *
 * @package    assignfeedback_editpdf
 * @category   test
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(page_editor::class)]
#[CoversClass(document_services::class)]
#[CoversClass(observer::class)]
#[CoversClass(convert_submission::class)]
#[CoversClass(\core_files\conversion::class)]
final class feedback_test extends \advanced_testcase {

    // Use the generator helper.
    use mod_assign_test_generator;

    /**
     * Ensure that GS is available.
     */
    protected function require_ghostscript() {
        // Skip this test if ghostscript is not supported.
        $result = pdf::test_gs_path(false);
        if ($result->status !== pdf::GSPATH_OK) {
            $this->markTestSkipped('Ghostscript not setup');
        }
    }

    /**
     * Helper method to add a file to a submission.
     *
     * @param \stdClass $student Student submitting.
     * @param \assign   $assign Assignment being submitted.
     * @param bool     $textfile Use textfile fixture instead of pdf.
     */
    protected function add_file_submission($student, $assign, $textfile = false) {
        global $CFG;

        $this->setUser($student);

        // Create a file submission with the test pdf.
        $submission = $assign->get_user_submission($student->id, true);

        $fs = get_file_storage();
        $filerecord = (object) array(
            'contextid' => $assign->get_context()->id,
            'component' => 'assignsubmission_file',
            'filearea' => ASSIGNSUBMISSION_FILE_FILEAREA,
            'itemid' => $submission->id,
            'filepath' => '/',
            'filename' => $textfile ? 'submission.txt' : 'submission.pdf'
        );
        $sourcefile = $CFG->dirroot . '/mod/assign/feedback/editpdf/tests/fixtures/submission.' . ($textfile ? 'txt' : 'pdf');
        $fs->create_file_from_pathname($filerecord, $sourcefile);

        $data = new \stdClass();
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);
    }

    /**
     * Create an assignment with file submissions and editpdf enabled.
     *
     * @param array $extra Extra instance settings.
     * @return array [assign, student, teacher]
     */
    protected function create_editpdf_assign(array $extra = []): array {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course, $extra + [
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 1,
            'assignsubmission_file_maxsizebytes' => 1000000,
            'assignfeedback_editpdf_enabled' => 1,
        ]);
        return [$assign, $student, $teacher];
    }

    /**
     * Put a dummy file in each editpdf file area.
     *
     * @param \assign $assign
     * @param array $areas List of [filearea, itemid] pairs.
     */
    protected function seed_editpdf_files(\assign $assign, array $areas): void {
        $fs = get_file_storage();
        foreach ($areas as [$filearea, $itemid]) {
            $fs->create_file_from_string([
                'contextid' => $assign->get_context()->id,
                'component' => 'assignfeedback_editpdf',
                'filearea' => $filearea,
                'itemid' => $itemid,
                'filepath' => '/',
                'filename' => 'dummy.pdf',
            ], 'dummy content');
        }
    }

    /**
     * Assert that each editpdf file area is empty (or not).
     *
     * @param \assign $assign
     * @param array $areas List of [filearea, itemid] pairs.
     * @param bool $empty Expected result.
     */
    protected function assert_editpdf_areas_empty(\assign $assign, array $areas, bool $empty): void {
        $fs = get_file_storage();
        foreach ($areas as [$filearea, $itemid]) {
            $this->assertSame(
                $empty,
                $fs->is_area_empty($assign->get_context()->id, 'assignfeedback_editpdf', $filearea, $itemid),
                "{$filearea}/{$itemid}",
            );
        }
    }

    /**
     * Add a draft comment to page 0.
     *
     * @param int $gradeid
     */
    protected function add_draft_comment(int $gradeid): void {
        $comment = new comment();
        $comment->rawtext = 'Draft comment';
        $comment->width = 100;
        $comment->x = 0;
        $comment->y = 0;
        $comment->colour = 'red';
        page_editor::set_comments($gradeid, 0, [$comment]);
    }

    public function test_comments_quick_list(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        $this->setUser($teacher);

        $this->assertEmpty(comments_quick_list::get_comments());

        $comment = comments_quick_list::add_comment('test', 45, 'red');
        $comments = comments_quick_list::get_comments();
        $this->assertEquals(count($comments), 1);
        $first = reset($comments);
        $this->assertEquals($comment, $first);

        $commentbyid = comments_quick_list::get_comment($comment->id);
        $this->assertEquals($comment, $commentbyid);

        $this->assertTrue(comments_quick_list::remove_comment($comment->id));

        $comments = comments_quick_list::get_comments();
        $this->assertEmpty($comments);
    }

    public function test_page_editor(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course, [
                'assignsubmission_onlinetext_enabled' => 1,
                'assignsubmission_file_enabled' => 1,
                'assignsubmission_file_maxfiles' => 1,
                'assignfeedback_editpdf_enabled' => 1,
                'assignsubmission_file_maxsizebytes' => 1000000,
            ]);

        // Add the standard submission.
        $this->add_file_submission($student, $assign);

        $this->setUser($teacher);

        $grade = $assign->get_user_grade($student->id, true);

        $notempty = page_editor::has_annotations_or_comments($grade->id, false);
        $this->assertFalse($notempty);

        $comment = new comment();
        $comment->rawtext = 'Comment text';
        $comment->width = 100;
        $comment->x = 100;
        $comment->y = 100;
        $comment->colour = 'red';

        $comment2 = new comment();
        $comment2->rawtext = 'Comment text 2';
        $comment2->width = 100;
        $comment2->x = 200;
        $comment2->y = 100;
        $comment2->colour = 'clear';

        page_editor::set_comments($grade->id, 0, array($comment, $comment2));

        $annotation = new annotation();
        $annotation->path = '';
        $annotation->x = 100;
        $annotation->y = 100;
        $annotation->endx = 200;
        $annotation->endy = 200;
        $annotation->type = 'line';
        $annotation->colour = 'red';

        $annotation2 = new annotation();
        $annotation2->path = '';
        $annotation2->x = 100;
        $annotation2->y = 100;
        $annotation2->endx = 200;
        $annotation2->endy = 200;
        $annotation2->type = 'rectangle';
        $annotation2->colour = 'yellow';

        page_editor::set_annotations($grade->id, 0, array($annotation, $annotation2));

        // Still empty because all edits are still drafts.
        $this->assertFalse(page_editor::has_annotations_or_comments($grade->id, false));

        $comments = page_editor::get_comments($grade->id, 0, false);
        $this->assertEmpty($comments);

        $comments = page_editor::get_comments($grade->id, 0, true);
        $this->assertEquals(count($comments), 2);

        $annotations = page_editor::get_annotations($grade->id, 0, false);
        $this->assertEmpty($annotations);

        $annotations = page_editor::get_annotations($grade->id, 0, true);
        $this->assertEquals(count($annotations), 2);

        $comment = reset($comments);
        $annotation = reset($annotations);

        page_editor::remove_comment($comment->id);
        page_editor::remove_annotation($annotation->id);

        $comments = page_editor::get_comments($grade->id, 0, true);
        $this->assertEquals(count($comments), 1);

        $annotations = page_editor::get_annotations($grade->id, 0, true);
        $this->assertEquals(count($annotations), 1);

        // Release the drafts.
        page_editor::release_drafts($grade->id);

        $notempty = page_editor::has_annotations_or_comments($grade->id, false);
        $this->assertTrue($notempty);

        // Unrelease the drafts.
        page_editor::unrelease_drafts($grade->id);

        $notempty = page_editor::has_annotations_or_comments($grade->id, false);
        $this->assertFalse($notempty);
    }

    /**
     * Test the annotations and comments for marker files and that they are successfully retrieved.
     */
    public function test_page_editor_markers(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher1 = $this->getDataGenerator()->create_and_enrol($course, 'teacher1');
        $teacher2 = $this->getDataGenerator()->create_and_enrol($course, 'teacher2');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course, [
            'assignsubmission_onlinetext_enabled' => 1,
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 1,
            'assignfeedback_editpdf_enabled' => 1,
            'assignsubmission_file_maxsizebytes' => 1000000,
        ]);

        // Add the standard submission.
        $this->add_file_submission($student, $assign);

        $this->setUser($teacher1);

        $grade = $assign->get_user_grade($student->id, true);
        $mark = $assign->get_mark($grade->id, $teacher1->id, true);

        $notempty = page_editor::has_annotations_or_comments($grade->id, false);
        $this->assertFalse($notempty);
        $notempty = page_editor::has_annotations_or_comments($grade->id, false, $mark->id);
        $this->assertFalse($notempty);

        // First add two marker comments.
        $comment = new comment();
        $comment->rawtext = 'A marker comment';
        $comment->width = 100;
        $comment->x = 100;
        $comment->y = 100;
        $comment->colour = 'red';

        $comment2 = new comment();
        $comment2->rawtext = 'Another marker comment';
        $comment2->width = 100;
        $comment2->x = 10;
        $comment2->y = 10;
        $comment2->colour = 'blue';
        page_editor::set_comments($grade->id, 0, [$comment, $comment2], $mark->id);

        // Then an overall comment.
        $comment3 = new comment();
        $comment3->rawtext = 'Overall comment';
        $comment3->width = 100;
        $comment3->x = 200;
        $comment3->y = 100;
        $comment3->colour = 'clear';
        page_editor::set_comments($grade->id, 0, [$comment3]);

        // Add one marker annotation.
        $annotation = new annotation();
        $annotation->path = '';
        $annotation->x = 100;
        $annotation->y = 100;
        $annotation->endx = 200;
        $annotation->endy = 200;
        $annotation->type = 'line';
        $annotation->colour = 'red';
        page_editor::set_annotations($grade->id, 0, [$annotation], $mark->id);

        // And two overall annotations.
        $annotation2 = new annotation();
        $annotation2->path = '';
        $annotation2->x = 100;
        $annotation2->y = 100;
        $annotation2->endx = 200;
        $annotation2->endy = 200;
        $annotation2->type = 'rectangle';
        $annotation2->colour = 'yellow';

        $annotation3 = new annotation();
        $annotation3->path = '';
        $annotation3->x = 10;
        $annotation3->y = 10;
        $annotation3->endx = 20;
        $annotation3->endy = 20;
        $annotation3->type = 'rectangle';
        $annotation3->colour = 'red';
        page_editor::set_annotations($grade->id, 0, [$annotation2, $annotation3]);

        // Overall annotation should still be empty as they are drafts.
        $this->assertFalse(page_editor::has_annotations_or_comments($grade->id, false));
        // As should the marker annotation.
        $this->assertFalse(page_editor::has_annotations_or_comments($grade->id, false, $mark->id));

        // Double check comments specifically are empty due to being drafts.
        $comments = page_editor::get_comments($grade->id, 0, false);
        $this->assertEmpty($comments);
        $comments = page_editor::get_comments($grade->id, 0, false, $mark->id);
        $this->assertEmpty($comments);

        // Then check comments specifically for any status.
        $comments = page_editor::get_comments($grade->id, 0, true);
        $this->assertCount(1, $comments);
        $comments = page_editor::get_comments($grade->id, 0, true, $mark->id);
        $this->assertCount(2, $comments);

        // Double check annotations specifically are empty due to being drafts.
        $annotations = page_editor::get_annotations($grade->id, 0, false);
        $this->assertEmpty($annotations);
        $annotations = page_editor::get_annotations($grade->id, 0, false, $mark->id);
        $this->assertEmpty($annotations);

        // Then check annotations specifically for any status.
        $annotations = page_editor::get_annotations($grade->id, 0, true);
        $this->assertCount(2, $annotations);
        $annotations = page_editor::get_annotations($grade->id, 0, true, $mark->id);
        $this->assertCount(1, $annotations);

        // Release the drafts.
        page_editor::release_drafts($grade->id);
        page_editor::release_drafts($grade->id, $mark->id);

        $notempty = page_editor::has_annotations_or_comments($grade->id, false);
        $this->assertTrue($notempty);
        $notempty = page_editor::has_annotations_or_comments($grade->id, false, $mark->id);
        $this->assertTrue($notempty);

        // Unrelease the drafts.
        page_editor::unrelease_drafts($grade->id);
        page_editor::unrelease_drafts($grade->id, $mark->id);

        $notempty = page_editor::has_annotations_or_comments($grade->id, false);
        $this->assertFalse($notempty);
        $notempty = page_editor::has_annotations_or_comments($grade->id, false, $mark->id);
        $this->assertFalse($notempty);
    }

    public function test_document_services(): void {
        $this->require_ghostscript();
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course, [
                'assignsubmission_onlinetext_enabled' => 1,
                'assignsubmission_file_enabled' => 1,
                'assignsubmission_file_maxfiles' => 1,
                'assignfeedback_editpdf_enabled' => 1,
                'assignsubmission_file_maxsizebytes' => 1000000,
            ]);

        // Add the standard submission.
        $this->add_file_submission($student, $assign);

        $this->setUser($teacher);

        $grade = $assign->get_user_grade($student->id, true);

        $contextid = $assign->get_context()->id;
        $component = 'assignfeedback_editpdf';
        $filearea = document_services::COMBINED_PDF_FILEAREA;
        $itemid = $grade->id;
        $filepath = '/';
        $filename = document_services::COMBINED_PDF_FILENAME;
        $fs = \get_file_storage();

        // Generate a blank combined pdf.
        $record = new \stdClass();
        $record->contextid = $contextid;
        $record->component = $component;
        $record->filearea = $filearea;
        $record->itemid = $itemid;
        $record->filepath = $filepath;
        $record->filename = $filename;
        $fs->create_file_from_string($record, base64_decode(document_services::BLANK_PDF_BASE64));

        // Verify that the blank combined pdf has the expected hash.
        $combinedpdf = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
        $this->assertEquals($combinedpdf->get_contenthash(), document_services::BLANK_PDF_HASH);

        // Generate page images and verify that the combined pdf has been replaced.
        $pageimages = document_services::get_page_images_for_attempt($assign, $student->id, -1);
        $combinedpdf = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);
        $this->assertNotEquals($combinedpdf->get_contenthash(), document_services::BLANK_PDF_HASH);

        // Verify that the file name contains the page number from Ghostscript.
        foreach ($pageimages as $index => $image) {
            $this->assertTrue(str_contains($image->get_filename(), "page{$index}"));
        }

        $notempty = page_editor::has_annotations_or_comments($grade->id, false);
        $this->assertFalse($notempty);

        $comment = new comment();

        // Use some different charset in the comment text.
        $comment->rawtext = 'Testing example: בקלות ואמנות';
        $comment->width = 100;
        $comment->x = 100;
        $comment->y = 100;
        $comment->colour = 'red';

        page_editor::set_comments($grade->id, 0, array($comment));

        $annotations = array();

        $annotation = new annotation();
        $annotation->path = '';
        $annotation->x = 100;
        $annotation->y = 100;
        $annotation->endx = 200;
        $annotation->endy = 200;
        $annotation->type = 'line';
        $annotation->colour = 'red';
        array_push($annotations, $annotation);

        $annotation = new annotation();
        $annotation->path = '';
        $annotation->x = 100;
        $annotation->y = 100;
        $annotation->endx = 200;
        $annotation->endy = 200;
        $annotation->type = 'rectangle';
        $annotation->colour = 'yellow';
        array_push($annotations, $annotation);

        $annotation = new annotation();
        $annotation->path = '';
        $annotation->x = 100;
        $annotation->y = 100;
        $annotation->endx = 200;
        $annotation->endy = 200;
        $annotation->type = 'oval';
        $annotation->colour = 'green';
        array_push($annotations, $annotation);

        $annotation = new annotation();
        $annotation->path = '';
        $annotation->x = 100;
        $annotation->y = 100;
        $annotation->endx = 200;
        $annotation->endy = 116;
        $annotation->type = 'highlight';
        $annotation->colour = 'blue';
        array_push($annotations, $annotation);

        $annotation = new annotation();
        $annotation->path = '100,100:105,105:110,100';
        $annotation->x = 100;
        $annotation->y = 100;
        $annotation->endx = 110;
        $annotation->endy = 105;
        $annotation->type = 'pen';
        $annotation->colour = 'black';
        array_push($annotations, $annotation);
        page_editor::set_annotations($grade->id, 0, $annotations);

        page_editor::release_drafts($grade->id);

        $notempty = page_editor::has_annotations_or_comments($grade->id, false);

        $this->assertTrue($notempty);

        $file = document_services::generate_feedback_document($assign->get_instance()->id, $grade->userid, $grade->attemptnumber);
        $this->assertNotEmpty($file);

        $file2 = document_services::get_feedback_document($assign->get_instance()->id, $grade->userid, $grade->attemptnumber);

        $this->assertEquals($file, $file2);

        document_services::delete_feedback_document($assign->get_instance()->id, $grade->userid, $grade->attemptnumber);
        $file3 = document_services::get_feedback_document($assign->get_instance()->id, $grade->userid, $grade->attemptnumber);

        $this->assertEmpty($file3);
    }

    /**
     * Test Convert submission ad-hoc task.
     */
    public function test_conversion_task(): void {
        $this->require_ghostscript();
        $this->resetAfterTest();
        \core\cron::setup_user();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assignopts = [
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 1,
            'assignfeedback_editpdf_enabled' => 1,
            'assignsubmission_file_maxsizebytes' => 1000000,
        ];
        $assign = $this->create_instance($course, $assignopts);

        // Add the standard submission.
        $this->add_file_submission($student, $assign);

        // Run the conversion task.
        $task = \core\task\manager::get_next_adhoc_task(time(), true, convert_submission::class);
        ob_start();
        $task->execute();
        \core\task\manager::adhoc_task_complete($task);
        $output = ob_get_clean();

        // Confirm, that submission has been converted and the task queue is now empty.
        $this->assertStringContainsString('Converting submission for user id ' . $student->id, $output);
        $this->assertStringContainsString('The document has been successfully converted', $output);
        $this->assertNull(\core\task\manager::get_next_adhoc_task(time(), true, convert_submission::class));

        // Trigger a re-queue by 'updating' a submission.
        $submission = $assign->get_user_submission($student->id, true);
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, (new \stdClass));

        $task = \core\task\manager::get_next_adhoc_task(time(), true, convert_submission::class);
        // Verify that queued a conversion task.
        $this->assertNotNull($task);

        ob_start();
        $task->execute();
        \core\task\manager::adhoc_task_complete($task);
        $output = ob_get_clean();

        // Confirm, that submission has been converted and the task queue is now empty.
        $this->assertStringContainsString('Converting submission for user id ' . $student->id, $output);
        $this->assertStringContainsString('The document has been successfully converted', $output);
        $this->assertNull(\core\task\manager::get_next_adhoc_task(time(), true, convert_submission::class));
    }

    /**
     * Test that modifying the annotated pdf form return true when modified
     * and false when not modified.
     */
    public function test_is_feedback_modified(): void {
        $this->require_ghostscript();
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course, [
                'assignsubmission_onlinetext_enabled' => 1,
                'assignsubmission_file_enabled' => 1,
                'assignsubmission_file_maxfiles' => 1,
                'assignfeedback_editpdf_enabled' => 1,
                'assignsubmission_file_maxsizebytes' => 1000000,
            ]);

        // Add the standard submission.
        $this->add_file_submission($student, $assign);

        $this->setUser($teacher);
        $grade = $assign->get_user_grade($student->id, true);

        $notempty = page_editor::has_annotations_or_comments($grade->id, false);
        $this->assertFalse($notempty);

        $comment = new comment();

        $comment->rawtext = 'Comment text';
        $comment->width = 100;
        $comment->x = 100;
        $comment->y = 100;
        $comment->colour = 'red';

        page_editor::set_comments($grade->id, 0, array($comment));

        $annotations = array();

        $annotation = new annotation();
        $annotation->path = '';
        $annotation->x = 100;
        $annotation->y = 100;
        $annotation->endx = 200;
        $annotation->endy = 200;
        $annotation->type = 'line';
        $annotation->colour = 'red';
        array_push($annotations, $annotation);

        page_editor::set_annotations($grade->id, 0, $annotations);

        $plugin = $assign->get_feedback_plugin_by_type('editpdf');
        $data = new \stdClass();
        $data->editpdf_source_userid = $student->id;
        $this->assertTrue($plugin->is_feedback_modified($grade, $data));
        $plugin->save($grade, $data);

        $annotation = new annotation();
        $annotation->gradeid = $grade->id;
        $annotation->pageno = 0;
        $annotation->path = '';
        $annotation->x = 100;
        $annotation->y = 100;
        $annotation->endx = 200;
        $annotation->endy = 200;
        $annotation->type = 'rectangle';
        $annotation->colour = 'yellow';

        $yellowannotationid = page_editor::add_annotation($annotation);

        // Add a comment as well.
        $comment = new comment();
        $comment->gradeid = $grade->id;
        $comment->pageno = 0;
        $comment->rawtext = 'Second Comment text';
        $comment->width = 100;
        $comment->x = 100;
        $comment->y = 100;
        $comment->colour = 'red';
        page_editor::add_comment($comment);

        $this->assertTrue($plugin->is_feedback_modified($grade, $data));
        $plugin->save($grade, $data);

        // We should have two annotations.
        $this->assertCount(2, page_editor::get_annotations($grade->id, 0, false));
        // And two comments.
        $this->assertCount(2, page_editor::get_comments($grade->id, 0, false));

        // Add one annotation and delete another.
        $annotation = new annotation();
        $annotation->gradeid = $grade->id;
        $annotation->pageno = 0;
        $annotation->path = '100,100:105,105:110,100';
        $annotation->x = 100;
        $annotation->y = 100;
        $annotation->endx = 110;
        $annotation->endy = 105;
        $annotation->type = 'pen';
        $annotation->colour = 'black';
        page_editor::add_annotation($annotation);

        $annotations = page_editor::get_annotations($grade->id, 0, true);
        page_editor::remove_annotation($yellowannotationid);
        $this->assertTrue($plugin->is_feedback_modified($grade, $data));
        $plugin->save($grade, $data);

        // We should have two annotations.
        $this->assertCount(2, page_editor::get_annotations($grade->id, 0, false));
        // And two comments.
        $this->assertCount(2, page_editor::get_comments($grade->id, 0, false));

        // Add a comment and then remove it. Should not be considered as modified.
        $comment = new comment();
        $comment->gradeid = $grade->id;
        $comment->pageno = 0;
        $comment->rawtext = 'Third Comment text';
        $comment->width = 400;
        $comment->x = 57;
        $comment->y = 205;
        $comment->colour = 'black';
        $comment->id = page_editor::add_comment($comment);

        // We should now have three comments.
        $this->assertCount(3, page_editor::get_comments($grade->id, 0, true));
        // Now delete the newest record.
        page_editor::remove_comment($comment->id);
        // Back to two comments.
        $this->assertCount(2, page_editor::get_comments($grade->id, 0, true));
        // No modification.
        $this->assertFalse($plugin->is_feedback_modified($grade, $data));
    }

    /**
     * Test that overwriting a submission file deletes any associated conversions.
     */
    public function test_submission_file_overridden(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assign = $this->create_instance($course, [
            'assignsubmission_onlinetext_enabled' => 1,
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 1,
            'assignfeedback_editpdf_enabled' => 1,
            'assignsubmission_file_maxsizebytes' => 1000000,
        ]);

        $this->add_file_submission($student, $assign, true);
        $submission = $assign->get_user_submission($student->id, true);

        $fs = get_file_storage();
        $sourcefile = $fs->get_file(
            $assign->get_context()->id,
            'assignsubmission_file',
            ASSIGNSUBMISSION_FILE_FILEAREA,
            $submission->id,
            '/',
            'submission.txt'
        );

        $conversion = new \core_files\conversion(0, (object)[
            'sourcefileid' => $sourcefile->get_id(),
            'targetformat' => 'pdf'
        ]);
        $conversion->create();

        $conversions = \core_files\conversion::get_conversions_for_file($sourcefile, 'pdf');
        $this->assertCount(1, $conversions);

        $filerecord = (object)[
            'contextid' => $assign->get_context()->id,
            'component' => 'core',
            'filearea'  => 'unittest',
            'itemid'    => $submission->id,
            'filepath'  => '/',
            'filename'  => 'submission.txt'
        ];

        $fs = get_file_storage();
        $newfile = $fs->create_file_from_string($filerecord, 'something totally different');
        $sourcefile->replace_file_with($newfile);

        $conversions = \core_files\conversion::get_conversions_for_file($sourcefile, 'pdf');
        $this->assertCount(0, $conversions);
    }

    /**
     * Removing a submission deletes the data generated from it (MDL-68693).
     */
    public function test_submission_removed_deletes_generated_data(): void {
        $this->resetAfterTest();
        [$assign, $student, $teacher] = $this->create_editpdf_assign();
        $this->add_file_submission($student, $assign);
        $submission = $assign->get_user_submission($student->id, false);
        $this->setUser($teacher);
        $grade = $assign->get_user_grade($student->id, true);

        $areas = [
            [document_services::PAGE_IMAGE_FILEAREA, $grade->id],
            [document_services::PAGE_IMAGE_READONLY_FILEAREA, $grade->id],
            [document_services::COMBINED_PDF_FILEAREA, $grade->id],
            [document_services::PARTIAL_PDF_FILEAREA, $grade->id],
            [document_services::TMP_JPG_TO_PDF_FILEAREA, $grade->id],
            [document_services::TMP_ROTATED_JPG_FILEAREA, $grade->id],
            [document_services::IMPORT_HTML_FILEAREA, $submission->id],
        ];
        $this->seed_editpdf_files($assign, $areas);
        $this->add_draft_comment($grade->id);
        page_editor::set_page_rotation($grade->id, 0, true, 'abc', 90);

        $this->setUser($student);
        $this->assertTrue($assign->remove_submission($student->id));

        $this->assert_editpdf_areas_empty($assign, $areas, true);
        $this->assertEmpty(page_editor::get_comments($grade->id, 0, true));
        $this->assertFalse(page_editor::get_page_rotation($grade->id, 0));
    }

    /**
     * Released feedback survives removal of the submission.
     */
    public function test_submission_removed_keeps_released_feedback(): void {
        $this->resetAfterTest();
        [$assign, $student, $teacher] = $this->create_editpdf_assign();
        $this->add_file_submission($student, $assign);
        $this->setUser($teacher);
        $grade = $assign->get_user_grade($student->id, true);

        $kept = [
            [document_services::PAGE_IMAGE_READONLY_FILEAREA, $grade->id],
            [document_services::FINAL_PDF_FILEAREA, $grade->id],
        ];
        $deleted = [[document_services::PAGE_IMAGE_FILEAREA, $grade->id]];
        $this->seed_editpdf_files($assign, array_merge($kept, $deleted));
        $this->add_draft_comment($grade->id);
        page_editor::release_drafts($grade->id);

        $this->setUser($student);
        $this->assertTrue($assign->remove_submission($student->id));

        $this->assert_editpdf_areas_empty($assign, $kept, false);
        $this->assert_editpdf_areas_empty($assign, $deleted, true);
        $this->assertTrue(page_editor::has_annotations_or_comments($grade->id, false));
    }

    /**
     * Only the removed attempt is cleaned.
     */
    public function test_submission_removed_only_removed_attempt(): void {
        $this->resetAfterTest();
        [$assign, $student, $teacher] = $this->create_editpdf_assign([
            'attemptreopenmethod' => ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL,
            'maxattempts' => -1,
        ]);
        $this->add_file_submission($student, $assign);
        $teacher->ignoresesskey = true;
        $this->setUser($teacher);
        $first = $assign->get_user_grade($student->id, true);
        $this->assertTrue($assign->testable_process_add_attempt($student->id));
        $this->add_file_submission($student, $assign);
        $this->setUser($teacher);
        $second = $assign->get_user_grade($student->id, true);
        $this->assertNotEquals($first->id, $second->id);

        $firstareas = [[document_services::PAGE_IMAGE_FILEAREA, $first->id]];
        $secondareas = [[document_services::PAGE_IMAGE_FILEAREA, $second->id]];
        $this->seed_editpdf_files($assign, array_merge($firstareas, $secondareas));

        $this->setUser($student);
        $this->assertTrue($assign->remove_submission($student->id));

        $this->assert_editpdf_areas_empty($assign, $firstareas, false);
        $this->assert_editpdf_areas_empty($assign, $secondareas, true);
    }

    /**
     * Removing a submission with nothing generated from it does nothing.
     */
    public function test_submission_removed_nothing_generated(): void {
        global $DB;
        $this->resetAfterTest();
        [$assign, $student] = $this->create_editpdf_assign();
        $this->add_file_submission($student, $assign);

        $this->setUser($student);
        $this->assertTrue($assign->remove_submission($student->id));

        $this->assertEquals(0, $DB->count_records('assign_grades'));
    }

    /**
     * Team submission: every member is cleaned, including suspended ones, and no one else.
     */
    public function test_submission_removed_team_submission(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        // The student removing the submission cannot see suspended members.
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student', null, 'manual', 0, 0, ENROL_USER_SUSPENDED);
        $outsider = $this->getDataGenerator()->create_and_enrol($course, 'student');
        // A member of the group who is also in another group submits in the default group instead.
        $multigroup = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $student1->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $student2->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $multigroup->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group2->id, 'userid' => $multigroup->id]);
        $assign = $this->create_instance($course, [
            'teamsubmission' => 1,
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 1,
            'assignsubmission_file_maxsizebytes' => 1000000,
            'assignfeedback_editpdf_enabled' => 1,
        ]);
        $this->setUser($student1);
        $submission = $assign->get_group_submission($student1->id, 0, true);
        $this->assertEquals($group->id, $submission->groupid);

        $areas = [];
        foreach ([$student1, $student2] as $student) {
            $grade = $assign->get_user_grade($student->id, true);
            $areas[] = [document_services::PAGE_IMAGE_FILEAREA, $grade->id];
        }
        $outsiderareas = [];
        foreach ([$outsider, $multigroup] as $student) {
            $outsiderareas[] = [document_services::PAGE_IMAGE_FILEAREA, $assign->get_user_grade($student->id, true)->id];
        }
        $this->seed_editpdf_files($assign, array_merge($areas, $outsiderareas));

        $this->assertTrue($assign->remove_submission($student1->id));

        $this->assert_editpdf_areas_empty($assign, $areas, true);
        $this->assert_editpdf_areas_empty($assign, $outsiderareas, false);
    }

    /**
     * Team submission without groups (the default group): every member is cleaned, including suspended
     * ones, and no one else.
     */
    public function test_submission_removed_team_submission_default_group(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        // The student removing the submission cannot see suspended members.
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student', null, 'manual', 0, 0, ENROL_USER_SUSPENDED);
        $outsider = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $outsider->id]);
        $assign = $this->create_instance($course, [
            'teamsubmission' => 1,
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 1,
            'assignsubmission_file_maxsizebytes' => 1000000,
            'assignfeedback_editpdf_enabled' => 1,
        ]);
        $this->setUser($student1);
        $assign->get_group_submission($student1->id, 0, true);

        $areas = [];
        foreach ([$student1, $student2] as $student) {
            $grade = $assign->get_user_grade($student->id, true);
            $areas[] = [document_services::PAGE_IMAGE_FILEAREA, $grade->id];
        }
        $outsiderareas = [[document_services::PAGE_IMAGE_FILEAREA, $assign->get_user_grade($outsider->id, true)->id]];
        $this->seed_editpdf_files($assign, array_merge($areas, $outsiderareas));

        $this->assertTrue($assign->remove_submission($student1->id));

        $this->assert_editpdf_areas_empty($assign, $areas, true);
        $this->assert_editpdf_areas_empty($assign, $outsiderareas, false);
    }

    /**
     * Team submission by a member of several groups (the default group): the default group members
     * are cleaned, and not the other members of their groups.
     */
    public function test_submission_removed_team_submission_multiple_groups(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $outsider = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group1->id, 'userid' => $student1->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group2->id, 'userid' => $student1->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group1->id, 'userid' => $outsider->id]);
        $assign = $this->create_instance($course, [
            'teamsubmission' => 1,
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 1,
            'assignsubmission_file_maxsizebytes' => 1000000,
            'assignfeedback_editpdf_enabled' => 1,
        ]);
        $this->setUser($student1);
        $submission = $assign->get_group_submission($student1->id, 0, true);
        $this->assertEquals(0, $submission->groupid);

        $areas = [];
        foreach ([$student1, $student2] as $student) {
            $grade = $assign->get_user_grade($student->id, true);
            $areas[] = [document_services::PAGE_IMAGE_FILEAREA, $grade->id];
        }
        $outsiderareas = [[document_services::PAGE_IMAGE_FILEAREA, $assign->get_user_grade($outsider->id, true)->id]];
        $this->seed_editpdf_files($assign, array_merge($areas, $outsiderareas));

        $this->assertTrue($assign->remove_submission($student1->id));

        $this->assert_editpdf_areas_empty($assign, $areas, true);
        $this->assert_editpdf_areas_empty($assign, $outsiderareas, false);
    }

    /**
     * Data provider for test_submission_removed_plugin_disabled.
     *
     * @return array
     */
    public static function plugin_disabled_provider(): array {
        return [
            'Disabled in the assignment' => [false],
            'Disabled on the site' => [true],
        ];
    }

    /**
     * A disabled plugin still cleans up, or re-enabling it would show the removed pages again.
     *
     * @param bool $site Whether the plugin is disabled on the site, rather than in the assignment.
     */
    #[DataProvider('plugin_disabled_provider')]
    public function test_submission_removed_plugin_disabled(bool $site): void {
        $this->resetAfterTest();
        [$assign, $student, $teacher] = $this->create_editpdf_assign();
        $this->add_file_submission($student, $assign);
        $this->setUser($teacher);
        $grade = $assign->get_user_grade($student->id, true);
        $areas = [[document_services::PAGE_IMAGE_FILEAREA, $grade->id]];
        $this->seed_editpdf_files($assign, $areas);

        if ($site) {
            set_config('disabled', 1, 'assignfeedback_editpdf');
        } else {
            $assign->get_feedback_plugin_by_type('editpdf')->disable();
        }

        $this->setUser($student);
        $this->assertTrue($assign->remove_submission($student->id));

        $this->assert_editpdf_areas_empty($assign, $areas, true);
    }

    /**
     * No grade record: nothing to clean, and nothing created.
     */
    public function test_delete_submission_files_for_attempt_no_grade(): void {
        global $DB;
        $this->resetAfterTest();
        [$assign, $student] = $this->create_editpdf_assign();

        document_services::delete_submission_files_for_attempt($assign, $student->id, 0, 0);

        $this->assertEquals(0, $DB->count_records('assign_grades'));
        $this->assertEquals(0, $DB->count_records('assign_submission'));
    }

    /**
     * End to end: after removal the grader gets the blank PDF, not the old pages.
     */
    public function test_removed_submission_pages_are_blank(): void {
        $this->require_ghostscript();
        $this->resetAfterTest();
        [$assign, $student, $teacher] = $this->create_editpdf_assign();
        $this->add_file_submission($student, $assign);

        $this->setUser($teacher);
        $before = document_services::get_combined_pdf_for_attempt($assign, $student->id, -1);
        $this->assertNotSame(document_services::BLANK_PDF_HASH, $before->get_combined_file()->get_contenthash());
        $pagesbefore = document_services::get_page_images_for_attempt($assign, $student->id, -1);
        $this->assertGreaterThan(1, count($pagesbefore));

        $this->setUser($student);
        $this->assertTrue($assign->remove_submission($student->id));

        $this->setUser($teacher);
        $after = document_services::get_combined_pdf_for_attempt($assign, $student->id, -1);
        $this->assertSame(document_services::BLANK_PDF_HASH, $after->get_combined_file()->get_contenthash());
        $this->assertCount(1, document_services::get_page_images_for_attempt($assign, $student->id, -1));
    }

    /**
     * Tests that when the plugin is not enabled for an assignment it does not create conversion tasks.
     */
    public function test_submission_not_enabled(): void {
        $this->require_ghostscript();
        $this->resetAfterTest();
        \core\cron::setup_user();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $assignopts = [
            'assignsubmission_file_enabled' => 1,
            'assignsubmission_file_maxfiles' => 1,
            'assignfeedback_editpdf_enabled' => 0,
            'assignsubmission_file_maxsizebytes' => 1000000,
        ];
        $assign = $this->create_instance($course, $assignopts);

        // Add the standard submission.
        $this->add_file_submission($student, $assign);

        $task = \core\task\manager::get_next_adhoc_task(time());

        // No task was created.
        $this->assertNull($task);
    }
}
