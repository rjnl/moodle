@core @core_user @javascript
Feature: Bulk message
  In order to communicate with my students
  As a teacher
  I need to be able to send a message to all my students

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 1         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher@example.com  |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |

  Scenario: Send a message to students from participants list
    When I am on the "Course 1" "enrolled users" page logged in as "teacher1"
    And I click on "Select all" "checkbox"
    And I set the field "With selected users..." to "Send a message"
    And "Send message to 3 people" "dialogue" should exist
    # Try to send an empty message.
    When I press "Send message to 3 people"
    Then I should see "Please enter message text"
    And I set the following fields to these values:
      | bulk-message | "Hello world!" |
    And I press "Send message to 3 people"
    And I should see "Message sent to 3 people"

  Scenario: Send a message to students when one of them cannot be messaged
    Given the following "role capabilities" exist:
      | role           | moodle/site:messageanyuser |
      | editingteacher | prevent                    |
    And the following "user preferences" exist:
      | user     | preference               | value |
      | student1 | message_blocknoncontacts | 1     |
    When I am on the "Course 1" "enrolled users" page logged in as "teacher1"
    And I click on "Select 'Student 1'" "checkbox"
    And I click on "Select 'Student 2'" "checkbox"
    And I set the field "With selected users..." to "Send a message"
    And I set the field "bulk-message" to "Hello world!"
    And I press "Send message to 2 people"
    Then I should see "You can't message Student 1 due to their message preferences"
    And I should see "Message sent to 1 of 2 people"
    And I log out
    And I log in as "student2"
    And I should see "1" in the "Toggle messaging drawer" "button"
    And I log out
    And I log in as "student1"
    And I should not see "1" in the "Toggle messaging drawer" "button"

  Scenario: Send a message to students who cannot be messaged
    Given the following "role capabilities" exist:
      | role           | moodle/site:messageanyuser |
      | editingteacher | prevent                    |
    And the following "user preferences" exist:
      | user     | preference               | value |
      | student1 | message_blocknoncontacts | 1     |
      | student2 | message_blocknoncontacts | 1     |
    When I am on the "Course 1" "enrolled users" page logged in as "teacher1"
    And I click on "Select 'Student 1'" "checkbox"
    And I click on "Select 'Student 2'" "checkbox"
    And I set the field "With selected users..." to "Send a message"
    And I set the field "bulk-message" to "Hello world!"
    And I press "Send message to 2 people"
    Then I should see "You can't message the following users due to their message preferences"
    And I should see "Student 1, Student 2"
    And I should not see "Message sent to"

  Scenario: Send a message that is too long to students
    When I am on the "Course 1" "enrolled users" page logged in as "teacher1"
    And I click on "Select 'Student 1'" "checkbox"
    And I click on "Select 'Student 2'" "checkbox"
    And I set the field "With selected users..." to "Send a message"
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
    And I press "Send message to 2 people"
    # The error applies to every recipient, but it must only be shown once.
    Then I should see "1" occurrences of "The message is longer than the maximum allowed." in the "#user-notifications" "css_element"
    And I should not see "Message sent to"
    And I log out
    And I log in as "student1"
    And I should not see "1" in the "Toggle messaging drawer" "button"
    And I log out
    And I log in as "student2"
    And I should not see "1" in the "Toggle messaging drawer" "button"
