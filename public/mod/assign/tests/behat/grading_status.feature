@mod @mod_assign
Feature: View the grading status of an assignment
  In order to test the grading status for assignments is displaying correctly
  As a student
  I need to view my grading status

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |

  @javascript
  Scenario: View the grading status for an assignment with marking workflow enabled
    Given the following "activity" exists:
      | activity                            | assign                  |
      | course                              | C1                      |
      | name                                | Test assignment name    |
      | intro                               | Submit your online text |
      | submissiondrafts                    | 0                       |
      | markingworkflow                     | 1                       |
      | assignfeedback_comments_enabled     | 1                       |
      | assignsubmission_onlinetext_enabled | 1                       |
    # Add a submission.
    And the following "mod_assign > submissions" exist:
      | assign                | user      | onlinetext                        |
      | Test assignment name  | student1  | I'm the student first submission  |
    # Mark the submission.
    And I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I should see "Not marked" in the "Student 1" "table_row"
    And I click on "Grade actions" "actionmenu" in the "Student 1" "table_row"
    And I choose "Grade" in the open action menu
    And I should see "1 of 2"
    And I click on "Change filters" "link"
    And I set the field "Filter" to "submitted"
    And I should see "1 of 1"
    And I set the field "Grade out of 100" to "50"
    And I set the field "Marking workflow state" to "In review"
    And I set the field "Feedback comments" to "Great job! Lol, not really."
    And I set the field "Notify student" to "0"
    And I press "Save changes"
    And I am on the "Test assignment name" "assign activity" page
    And I navigate to "Submissions" in current page administration
    And I should see "In review" in the "Student 1" "table_row"
    And I log out
    # View the grading status as a student.
    And I am on the "Test assignment name" "assign activity" page logged in as student1
    And I should see "In review" in the "Grading status" "table_row"
    And I should not see "Great job! Lol, not really."
    # Marking has started, so the student can no longer change the submission.
    And "Edit submission" "button" should not exist
    And "Remove submission" "button" should not exist
    And I log out
    # Mark the submission again but set the marking workflow to 'Released'.
    And I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I should see "In review" in the "Student 1" "table_row"
    And I change window size to "large"
    And I click on "Grade actions" "actionmenu" in the "Student 1" "table_row"
    And I choose "Grade" in the open action menu
    And I change window size to "medium"
    And I should see "1 of 1"
    And I set the field "Marking workflow state" to "Released"
    And I press "Save changes"
    And I follow "View all submissions"
    And I should see "Released" in the "Student 1" "table_row"
    And I log out
    # View the grading status as a student.
    And I am on the "Test assignment name" "assign activity" page logged in as student1
    And I should see "Released" in the "Grading status" "table_row"
    And I should see "Great job! Lol, not really."
    And I log out
    # Now, change the status from 'Released' to 'In marking' (this will remove the grade from the gradebook).
    And I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I should see "Released" in the "Student 1" "table_row"
    And I change window size to "large"
    And I click on "Grade actions" "actionmenu" in the "Student 1" "table_row"
    And I choose "Grade" in the open action menu
    And I change window size to "medium"
    And I should see "1 of 1"
    And I set the field "Marking workflow state" to "In marking"
    And I set the field "Notify student" to "0"
    And I press "Save changes"
    And I am on the "Test assignment name" "assign activity" page
    And I navigate to "Submissions" in current page administration
    And I should see "In marking" in the "Student 1" "table_row"
    # The grade should also remain displayed as it's stored in the assign DB tables, but the final grade should be empty.
    And "Student 1" row "Grade" column of "generaltable" table should contain "50.00"
    And "Student 1" row "Final grade" column of "generaltable" table should contain "-"
    And I change window size to "large"
    And I click on "Grade actions" "actionmenu" in the "Student 1" "table_row"
    And I choose "Grade" in the open action menu
    And I change window size to "medium"
    And I click on "Change filters" "link"
    And I set the field "Workflow filter" to "In review"
    And I should see "0 of 0"

  @javascript
  Scenario: View the grading status for an assignment with marking workflow disabled
    Given the following "activity" exists:
      | activity                            | assign                  |
      | course                              | C1                      |
      | name                                | Test assignment name    |
      | intro                               | Submit your online text |
      | submissiondrafts                    | 0                       |
      | assignfeedback_comments_enabled     | 1                       |
      | markingworkflow                     | 0                       |
      | assignsubmission_onlinetext_enabled | 1                       |
    # Add a submission.
    And the following "mod_assign > submissions" exist:
      | assign                | user      | onlinetext                        |
      | Test assignment name  | student1  | I'm the student first submission  |
    # Mark the submission.
    And I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I should not see "Graded" in the "Student 1" "table_row"
    And I click on "Grade actions" "actionmenu" in the "Student 1" "table_row"
    And I choose "Grade" in the open action menu
    And I should see "1 of 2"
    And I click on "Change filters" "link"
    And I set the field "Filter" to "submitted"
    And I should see "1 of 1"
    And I set the field "Grade out of 100" to "50"
    And I set the field "Feedback comments" to "Great job! Lol, not really."
    And I press "Save changes"
    And I click on "Edit settings" "link"
    And I am on the "Test assignment name" "assign activity" page
    And I navigate to "Submissions" in current page administration
    And I should see "Graded" in the "Student 1" "table_row"
    And I log out
    # View the grading status as a student.
    And I am on the "Test assignment name" "assign activity" page logged in as student1
    And I should see "Graded" in the "Grading status" "table_row"
    And I should see "Great job! Lol, not really."
    # The graded submission can no longer be changed or removed by the student.
    And "Edit submission" "button" should not exist
    And "Remove submission" "button" should not exist

  @javascript
  Scenario: A graded submission can be changed again once the teacher reverts it to draft
    Given the following "activity" exists:
      | activity                            | assign                  |
      | course                              | C1                      |
      | name                                | Test assignment name    |
      | submissiondrafts                    | 1                       |
      | assignsubmission_onlinetext_enabled | 1                       |
    And the following "mod_assign > submissions" exist:
      | assign                | user      | onlinetext                        |
      | Test assignment name  | student1  | I'm the student first submission  |
    # The student submits the draft for grading.
    And I am on the "Test assignment name" "assign activity" page logged in as student1
    And I press "Submit assignment"
    And I press "Continue"
    And I should see "Submitted for grading" in the "Submission status" "table_row"
    And I log out
    # The teacher grades the submission.
    And I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I go to "Student 1" "Test assignment name" activity advanced grading page
    And I set the field "Grade out of 100" to "50"
    And I press "Save changes"
    And I log out
    # The graded submission cannot be changed by the student.
    And I am on the "Test assignment name" "assign activity" page logged in as student1
    And "Edit submission" "button" should not exist
    And I log out
    # The teacher reverts the submission to draft.
    And I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I open the action menu in "Student 1" "table_row"
    And I follow "Revert the submission to draft"
    And I log out
    # The student can change it again.
    When I am on the "Test assignment name" "assign activity" page logged in as student1
    Then I should see "Draft (not submitted)" in the "Submission status" "table_row"
    And "Edit submission" "button" should exist
    And I press "Edit submission"
    And I set the following fields to these values:
      | Online text | I'm the student's corrected submission |
    And I press "Save changes"
    And I should see "I'm the student's corrected submission"

  @javascript
  Scenario: The grade is read-only once released, and is preserved when the workflow state is changed
    Given the following "activity" exists:
      | activity                            | assign                  |
      | course                              | C1                      |
      | name                                | Test assignment name    |
      | intro                               | Submit your online text |
      | submissiondrafts                    | 0                       |
      | markingworkflow                     | 1                       |
      | assignfeedback_comments_enabled     | 1                       |
      | assignsubmission_onlinetext_enabled | 1                       |
    And the following "mod_assign > submissions" exist:
      | assign               | user     | onlinetext                       |
      | Test assignment name | student1 | I'm the student first submission |
    And I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I go to "Student 1" "Test assignment name" activity advanced grading page
    # The grade is not editable in the grading panel once released.
    When I set the following fields to these values:
      | Grade out of 100       | 70       |
      | Marking workflow state | Released |
      | Notify student         | 0        |
    And I press "Save changes"
    Then the following fields match these values:
      | Grade out of 100       | 70.00    |
      | Marking workflow state | Released |
    And the "Grade out of 100" "field" should be readonly
    # The grade is also not editable in the quick grading table once released.
    And I follow "View all submissions"
    And I click on "Quick grading" "checkbox"
    And "User grade" "field" should not exist in the "Student 1" "table_row"
    And I should see "70.00" in the "Student 1" "table_row"
    And I should see "Released" in the "Student 1" "table_row"
    # The grade is editable again when the status it changed into an editable state.
    And I go to "Student 1" "Test assignment name" activity advanced grading page
    And I set the following fields to these values:
      | Marking workflow state | In review |
      | Notify student         | 0         |
    And I press "Save changes"
    And the following fields match these values:
      | Grade out of 100       | 70.00     |
      | Marking workflow state | In review |
    And the "Grade out of 100" "field" should not be readonly
    # The grade is editable again on the submissions page.
    And I follow "View all submissions"
    And the following fields match these values:
      | User grade | 70.00 |
    And I should see "In review" in the "Student 1" "table_row"
    And "User grade" "field" should exist in the "Student 1" "table_row"
