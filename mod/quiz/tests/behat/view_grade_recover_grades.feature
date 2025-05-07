@mod @mod_quiz
Feature: Testing view quiz grade with recover grades setting
  As a user
  I should be able to see my quiz grade and completion status

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher  | Teacher   | One      | teacher@example.com |
      | student  | Student   | One      | student@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher  | C1     | editingteacher |
      | student  | C1     | student        |
    And the following "activities" exist:
      | activity   | name    | intro              | course | idnumber | completion | completionusegrade |
      | quiz       | Quiz 1  | Quiz 1 description | C1     | quiz1    | 2          | 1                  |
    And the following "question categories" exist:
      | contextlevel    | reference | name           |
      | Activity module | quiz1     | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name  | questiontext    |
      | Test questions   | truefalse   | TF1   | First question  |
    And quiz "Quiz 1" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    |         |
    And user "student" has attempted "Quiz 1" with responses:
      | slot | response |
      |   1  | True     |

  Scenario Outline: Preview the quiz as teacher
    Given the following config values are set as admin:
      | recovergradesdefault  | <recovergradesetting>  |
    And user "teacher" has attempted "Quiz 1" with responses:
      | slot | response |
      |   1  | True     |
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher"
    # Recover grades setting should not affect quiz preview for teachers
    Then I should see "100.00 out of 100.00"
    And I should see "Highest grade: 100.00 / 100.00"

    Examples:
      | recovergradesetting |
      | 0                   |
      | 1                   |

  Scenario Outline: View quiz with recover grades settings
    Given the following config values are set as admin:
      | recovergradesdefault  | <recovergradesetting>  |
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "student"
    # Recover grades setting should not affect users who are not unenrolled
    Then I should see "100.00 / 100.00"
    And I should see "100.00 out of 100.00"
    And I should see "Done: Receive a grade"

    Examples:
      | recovergradesetting |
      | 0                   |
      | 1                   |

  # The @javascript tag is used because this scenario involves UI interactions,
  # such as navigating to course participants and handling dialogues, which require JavaScript execution.
  @javascript
  Scenario Outline: View quiz after unenrolling and re-enrolling
    Given the following config values are set as admin:
      | recovergradesdefault  | <recovergradesetting>  |
    And I log in as "teacher"
    And I am on "Course 1" course homepage
    And I navigate to course participants
    And I click on "Unenrol" "icon" in the "Student One" "table_row"
    And I click on "Unenrol" "button" in the "Unenrol" "dialogue"
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student  | C1     | student        |
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "student"
    Then I should see "Highest grade: <mygrade>"
    And I should see "100.00 out of 100.00"
    And I should see "<mycompletionstatus>"

    Examples:
      | recovergradesetting | mygrade         | mycompletionstatus     |
      | 0                   | 0.00 / 100.00   | To do: Receive a grade |
      | 1                   | 100.00 / 100.00 | Done: Receive a grade  |
