@mod @mod_assign
Feature: Students cannot change a submission once it has been graded
  In order to keep the work that a grade was awarded for
  As a teacher
  I need students to be unable to edit or remove their submission after grading has started

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: A student cannot edit or remove their submission after it has been graded
    Given the following "activity" exists:
      | activity                            | assign               |
      | course                              | C1                   |
      | name                                | Test assignment name |
      | submissiondrafts                    | 0                    |
      | markingworkflow                     | 0                    |
      | assignsubmission_onlinetext_enabled | 1                    |
    And the following "mod_assign > submissions" exist:
      | assign               | user     | onlinetext                 |
      | Test assignment name | student1 | I'm the student submission |
    And I am on the "Test assignment name" "assign activity" page logged in as student1
    And "Edit submission" "button" should exist
    And "Remove submission" "button" should exist
    And I log out
    When I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I click on "Grade actions" "actionmenu" in the "Student 1" "table_row"
    And I choose "Grade" in the open action menu
    And I set the field "Grade out of 100" to "50"
    And I press "Save changes"
    And I log out
    Then I am on the "Test assignment name" "assign activity" page logged in as student1
    And I should see "I'm the student submission"
    And I should see "You can no longer change this submission because marking has started."
    And "Edit submission" "button" should not exist
    And "Remove submission" "button" should not exist

  @javascript
  Scenario: A student cannot edit or remove their submission once marking is in progress
    Given the following "activity" exists:
      | activity                            | assign               |
      | course                              | C1                   |
      | name                                | Test assignment name |
      | submissiondrafts                    | 0                    |
      | markingworkflow                     | 1                    |
      | assignsubmission_onlinetext_enabled | 1                    |
    And the following "mod_assign > submissions" exist:
      | assign               | user     | onlinetext                 |
      | Test assignment name | student1 | I'm the student submission |
    And I am on the "Test assignment name" "assign activity" page logged in as student1
    And "Edit submission" "button" should exist
    And "Remove submission" "button" should exist
    And I log out
    When I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I click on "Grade actions" "actionmenu" in the "Student 1" "table_row"
    And I choose "Grade" in the open action menu
    And I set the field "Marking workflow state" to "In marking"
    And I press "Save changes"
    And I log out
    Then I am on the "Test assignment name" "assign activity" page logged in as student1
    And I should see "I'm the student submission"
    And I should see "You can no longer change this submission because marking has started."
    And "Edit submission" "button" should not exist
    And "Remove submission" "button" should not exist

  @javascript
  Scenario: A student can change a graded submission once it is reverted to draft
    Given the following "activity" exists:
      | activity                            | assign               |
      | course                              | C1                   |
      | name                                | Test assignment name |
      | submissiondrafts                    | 1                    |
      | markingworkflow                     | 0                    |
      | assignsubmission_onlinetext_enabled | 1                    |
    And I am on the "Test assignment name" "assign activity" page logged in as student1
    And I press "Add submission"
    And I set the field "Online text" to "I'm the student submission"
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out
    And I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I click on "Grade actions" "actionmenu" in the "Student 1" "table_row"
    And I choose "Grade" in the open action menu
    And I set the field "Grade out of 100" to "50"
    And I press "Save changes"
    And I am on the "Test assignment name" "assign activity" page
    And I navigate to "Submissions" in current page administration
    And I click on "Submission actions" "actionmenu" in the "Student 1" "table_row"
    When I choose "Revert the submission to draft" in the open action menu
    And I log out
    And I am on the "Test assignment name" "assign activity" page logged in as student1
    Then I should not see "You can no longer change this submission because marking has started."
    And I press "Edit submission"
    And I set the field "Online text" to "I'm the student's improved submission"
    And I press "Save changes"
    And I press "Submit assignment"
    And I press "Continue"
    And I log out
    # The teacher can see the graded work has been changed and needs grading again.
    And I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I should see "Graded - resubmitted" in the "Student 1" "table_row"

  @javascript
  Scenario: Grading one member of a team prevents all team members from changing the submission
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student2 | C1     | student |
      | student3 | C1     | student |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student2 | G1    |
      | student3 | G2    |
    And the following "activity" exists:
      | activity                            | assign               |
      | course                              | C1                   |
      | name                                | Test assignment name |
      | submissiondrafts                    | 0                    |
      | assignsubmission_onlinetext_enabled | 1                    |
      | assignsubmission_file_enabled       | 0                    |
      | teamsubmission                      | 1                    |
      | requireallteammemberssubmit         | 0                    |
    And the following "mod_assign > submissions" exist:
      | assign               | user     | onlinetext                       |
      | Test assignment name | student1 | I'm the first team's submission  |
      | Test assignment name | student3 | I'm the second team's submission |
    # Grade only one member of the first team.
    And I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I go to "Student 1" "Test assignment name" activity advanced grading page
    And I set the following fields to these values:
      | Grade out of 100                          | 50.0 |
      | Apply grades and feedback to entire group | 0    |
    And I press "Save changes"
    And I log out
    # Both members of the first team are prevented from changing the submission.
    When I am on the "Test assignment name" "assign activity" page logged in as student1
    Then "Edit submission" "button" should not exist
    And I log out
    And I am on the "Test assignment name" "assign activity" page logged in as student2
    And I should see "You can no longer change this submission because marking has started."
    And "Edit submission" "button" should not exist
    And I log out
    # The second team has not been graded and can still change its submission.
    And I am on the "Test assignment name" "assign activity" page logged in as student3
    And "Edit submission" "button" should exist

  @javascript
  Scenario: Graders are told a graded submission is locked and can still edit it on behalf of the student
    Given the following "activity" exists:
      | activity                            | assign               |
      | course                              | C1                   |
      | name                                | Test assignment name |
      | submissiondrafts                    | 0                    |
      | markingworkflow                     | 0                    |
      | assignsubmission_onlinetext_enabled | 1                    |
    And the following "mod_assign > submissions" exist:
      | assign               | user     | onlinetext                 |
      | Test assignment name | student1 | I'm the student submission |
    And I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I go to "Student 1" "Test assignment name" activity advanced grading page
    And I should see "Student can edit this submission"
    And I set the field "Grade out of 100" to "50"
    And I press "Save changes"
    When I go to "Student 1" "Test assignment name" activity advanced grading page
    Then I should see "Student cannot edit this submission"
    And I log out
    # Users who can edit on behalf of the student are not restricted.
    And I am on the "Test assignment name" "assign activity" page logged in as admin
    And I navigate to "Submissions" in current page administration
    And I change window size to "large"
    And I open the action menu in "Student 1" "table_row"
    And I change window size to "medium"
    And I choose "Edit submission" in the open action menu
    And I set the field "Online text" to "Edited after grading"
    And I press "Save changes"
    And I should see "Edited after grading"

  @javascript
  Scenario: A student cannot change a submission that has a grade while the marking workflow state is not marked
    Given the following "activity" exists:
      | activity                            | assign               |
      | course                              | C1                   |
      | name                                | Test assignment name |
      | submissiondrafts                    | 0                    |
      | markingworkflow                     | 1                    |
      | assignsubmission_onlinetext_enabled | 1                    |
    And the following "mod_assign > submissions" exist:
      | assign               | user     | onlinetext                 |
      | Test assignment name | student1 | I'm the student submission |
    And I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I go to "Student 1" "Test assignment name" activity advanced grading page
    And I set the following fields to these values:
      | Grade out of 100        | 50         |
      | Marking workflow state  | Not marked |
    And I press "Save changes"
    And I log out
    When I am on the "Test assignment name" "assign activity" page logged in as student1
    Then I should see "Not marked" in the "Grading status" "table_row"
    And I should see "You can no longer change this submission because marking has started."
    And "Edit submission" "button" should not exist
    And "Remove submission" "button" should not exist

  @javascript
  Scenario: A new attempt is open for the student until it is graded
    Given the following "activity" exists:
      | activity                            | assign               |
      | course                              | C1                   |
      | name                                | Test assignment name |
      | submissiondrafts                    | 0                    |
      | markingworkflow                     | 0                    |
      | assignsubmission_onlinetext_enabled | 1                    |
      | assignsubmission_file_enabled       | 0                    |
      | attemptreopenmethod                 | manual               |
      | maxattempts                         | 3                    |
    And the following "mod_assign > submissions" exist:
      | assign               | user     | onlinetext                  |
      | Test assignment name | student1 | I'm the student's attempt 1 |
    And I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I go to "Student 1" "Test assignment name" activity advanced grading page
    And I set the following fields to these values:
      | Grade out of 100      | 50 |
      | Allow another attempt | 1  |
    And I press "Save changes"
    And I log out
    # The new attempt is open.
    And I am on the "Test assignment name" "assign activity" page logged in as student1
    And I should not see "You can no longer change this submission because marking has started."
    And I press "Add a new attempt based on previous submission"
    And I set the field "Online text" to "I'm the student's attempt 2"
    And I press "Save changes"
    And "Edit submission" "button" should exist
    And I log out
    # Grading the new attempt locks it.
    And I am on the "Test assignment name" "assign activity" page logged in as teacher1
    And I go to "Student 1" "Test assignment name" activity advanced grading page
    And I set the field "Grade out of 100" to "60"
    And I press "Save changes"
    And I log out
    When I am on the "Test assignment name" "assign activity" page logged in as student1
    Then I should see "I'm the student's attempt 2"
    And I should see "You can no longer change this submission because marking has started."
    And "Edit submission" "button" should not exist
    And "Remove submission" "button" should not exist
