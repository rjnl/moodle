@mod @mod_assign
Feature: Bulk message students from the grading table
  In order to communicate with my students about an assignment
  As a teacher
  I need to be able to send a message to the selected students and be told if it could not be sent

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activity" exists:
      | activity                            | assign               |
      | course                              | C1                   |
      | name                                | Test assignment name |
      | assignsubmission_onlinetext_enabled | 1                    |
      | assignsubmission_file_enabled       | 0                    |

  @javascript
  Scenario: Send a message to the selected students
    Given I am on the "Test assignment name" Activity page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I set the field "selectall" to "1"
    And I click on "Message" "button" in the "sticky-footer" "region"
    And I set the field "bulk-message" to "Hello students"
    When I click on "Send message to 2 people" "button"
    Then I should see "Message sent to 2 people"
    And I log out
    And I log in as "student1"
    And I should see "1" in the "Toggle messaging drawer" "button"
    And I log out
    And I log in as "student2"
    And I should see "1" in the "Toggle messaging drawer" "button"

  @javascript
  Scenario: Send a message that is too long to the selected students
    Given I am on the "Test assignment name" Activity page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I set the field "selectall" to "1"
    And I click on "Message" "button" in the "sticky-footer" "region"
    # Messages are limited to 4096 characters, so the text below has to be longer than that.
    And I set the field "bulk-message" to multiline:
      """
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      This message is intentionally far too long. It only repeats filler text to exceed the 4096 character limit.
      """
    When I click on "Send message to 2 people" "button"
    # The error applies to every recipient, but it must only be shown once.
    Then I should see "1" occurrences of "The message is longer than the maximum allowed." in the "#user-notifications" "css_element"
    And I should not see "Message sent to"
    And I log out
    And I log in as "student1"
    And I should not see "1" in the "Toggle messaging drawer" "button"
    And I log out
    And I log in as "student2"
    And I should not see "1" in the "Toggle messaging drawer" "button"
