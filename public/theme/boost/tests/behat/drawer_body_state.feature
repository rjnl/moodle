@theme @theme_boost
Feature: The open state of the drawers is available as classes on the body
  In order to style the page for the open drawers without slow :has() selectors
  As a user
  I need the body to be marked while the course index or the block drawer is open

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "course" exists:
      | fullname  | Course 1 |
      | shortname | C1       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "blocks" exist:
      | blockname    | contextlevel | reference | pagetypepattern | defaultregion |
      | online_users | Course       | C1        | course-view-*   | side-pre      |

  @javascript
  Scenario: The body follows the course index drawer
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And "body.page-drawer-left-open" "css_element" should exist
    When I click on "Close course index" "button"
    Then I wait until "body.page-drawer-left-open" "css_element" does not exist
    When I click on "Open course index" "button"
    Then "body.page-drawer-left-open" "css_element" should exist

  @javascript
  Scenario: The body follows the block drawer
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And "body.page-drawer-right-open" "css_element" should exist
    When I click on "Close block drawer" "button"
    Then I wait until "body.page-drawer-right-open" "css_element" does not exist
    When I click on "Open block drawer" "button"
    Then "body.page-drawer-right-open" "css_element" should exist
