@mod @mod_lti
Feature: Manage placement status for LTI tools
  In order to use LTI tools in a course
  As a teacher
  I need to be able to control the placement for the LTI tools in a course

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    # A site tool configured to show in courses.
    And the following "core_ltix > tool types" exist:
      | name            | baseurl                                | coursevisible | state |
      | Teaching Tool 1 | /ltix/tests/fixtures/tool_provider.php | 1             | 1     |
    # Configure the site tool's activity chooser placement, so it will be shown in the activity chooser.
    And the following "core_ltix > tool placements" exist:
      | tool            | placementtype             | config_default_usage |
      | Teaching Tool 1 | mod_lti:activityplacement | enabled              |
    # A course tool in course 1.
    And the following "core_ltix > course tools" exist:
      | name          | baseurl                                | course |
      | Course tool 1 | /ltix/tests/fixtures/tool_provider.php | C1     |
    # Configure the course tool's activity chooser placement, so it will be shown in the activity chooser.
    And the following "core_ltix > tool placements" exist:
      | tool          | placementtype             | config_default_usage |
      | Course tool 1 | mod_lti:activityplacement | enabled              |
    And I am on the "Course 1" course page logged in as teacher1

  Scenario Outline: Users without permission sees the lock icon
    Given the following "role capability" exists:
      | role                        | editingteacher    |
      | moodle/ltix:addcoursetool   | <capabilitystate> |
    When I navigate to "LTI External tools" in current page administration
    Then I should see "Teaching Tool 1" in the "reportbuilder-table" "table"
    And I should see "Course tool 1" in the "reportbuilder-table" "table"
    And "You don't have permission to edit this tool" "icon" <viewlockicon> exist in the "Teaching Tool 1" "table_row"
    And "You don't have permission to edit this tool" "icon" <viewlockicon> exist in the "Course tool 1" "table_row"

    Examples:
      | capabilitystate | viewlockicon |
      | prohibit        | should       |
      | allow           | should not   |

  Scenario Outline: View manage placements context menu
    Given the following "role capability" exists:
      | role                        | editingteacher |
      | moodle/ltix:addcoursetool   | allow          |
    When I navigate to "LTI External tools" in current page administration
    Then the "Manage placements" item <manageplacementmenu> exist in the "Actions" action menu of the "<toolname>" "table_row"
    And the "Edit" item <editmenu> exist in the "Actions" action menu of the "<toolname>" "table_row"
    And the "Delete" item <editmenu> exist in the "Actions" action menu of the "<toolname>" "table_row"

    Examples:
      | toolname        | manageplacementmenu | editmenu   |
      | Teaching Tool 1 | should              | should not |
      | Course tool 1   | should              | should     |

  @javascript
  Scenario Outline: Configure placement status for a LTI tool
    Given I navigate to "LTI External tools" in current page administration
    When I choose the "Manage placements" item in the "Actions" action menu of the "Course tool 1" "table_row"
    Then I should see "Manage placements" in the ".modal-header" "css_element"
    And I set the field "Activity chooser" to "<fieldvalue>"
    # Test Cancel first
    And I click on "Cancel" "button" in the ".modal-footer" "css_element"
    And I choose the "Manage placements" item in the "Actions" action menu of the "Course tool 1" "table_row"
    And the field "Activity chooser" matches value "<expectedcancelvalue>"
    # Test Save/Apply
    And I set the field "Activity chooser" to "<fieldvalue>"
    And I click on "Apply" "button" in the ".modal-content" "css_element"
    And I should see "Placement status saved"
    And I choose the "Manage placements" item in the "Actions" action menu of the "Course tool 1" "table_row"
    And the field "Activity chooser" matches value "<expectedapplyvalue>"

    Examples:
      | fieldvalue | expectedcancelvalue | expectedapplyvalue |
      | 0          | 1                   | 0                  |
      | 1          | 1                   | 1                  |

  @javascript
  Scenario Outline: Verify default placement status in course
    Given the following "core_ltix > tool types" exist:
      | name            | baseurl                                | coursevisible | state |
      | Teaching Tool 2 | /ltix/tests/fixtures/tool_provider.php | 1             | 1     |
    And the following "core_ltix > tool placements" exist:
      | tool            | placementtype             | config_default_usage |
      | Teaching Tool 2 | mod_lti:activityplacement | <default_usage>      |
    And I navigate to "LTI External tools" in current page administration
    When I choose the "Manage placements" item in the "Actions" action menu of the "Teaching Tool 2" "table_row"
    Then the field "Activity chooser" matches value "<expectedvalue>"

    Examples:
      | default_usage | expectedvalue |
      | enabled       | 1             |
      | disabled      | 0             |
