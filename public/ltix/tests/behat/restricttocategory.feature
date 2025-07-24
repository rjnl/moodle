@core @core_ltix
Feature: Make an LTI only available to specific course categories
  In order to restrict which courses a tool can be used in
  As an administrator
  I need to be able to select which course category the tool is available in

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
    And the following "categories" exist:
      | name  | category | idnumber |
      | cata  | 0        | cata     |
      | catca | cata     | catca    |
      | catb  | 0        | catb     |
      | catcb | catb     | catcb    |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | cata  |
      | Course 2 | C2 | catb  |
      | Course 3 | C3 | catca |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | teacher1 | C2 | editingteacher |
      | teacher1 | C3 | editingteacher |
    And the following "core_ltix > tool types" exist:
      | name            | description        | baseurl                                | coursevisible | state | lti_coursecategories |
      | Teaching Tool 1 | Tool 1 description | /ltix/tests/fixtures/tool_provider.php | 1             | 1     | catb                 |
      | Teaching Tool 2 | Tool 2 description | /ltix/tests/fixtures/tool_provider.php | 1             | 1     | catca                |
    # Configure Teaching Tool 2's activity chooser placement, so it will be shown in the activity chooser.
    And the following "core_ltix > tool placements" exist:
      | tool            | placementtype             | config_default_usage |
      | Teaching Tool 2 | mod_lti:activityplacement | enabled              |

  Scenario: Tool is set to "Show as preconfigured tool" on parent category
    Given I am on the "Course 2" "core_ltix > Course tools" page logged in as teacher1
    Then I should see "Teaching Tool 1" in the "reportbuilder-table" "table"
    And I should not see "Teaching Tool 2" in the "reportbuilder-table" "table"

  Scenario: Tool is set to "Show as preconfigured tool" on child category
    Given I am on the "Course 3" "core_ltix > Course tools" page logged in as teacher1
    Then I should see "Teaching Tool 2" in the "reportbuilder-table" "table"
    And I should not see "Teaching Tool 1" in the "reportbuilder-table" "table"

  Scenario: View a course in a category in which no tools are available
    Given I am on the "Course 1" "core_ltix > Course tools" page logged in as teacher1
    # The following assertion should be possible, but due to a bug in calculating the count of visible course tools, is not.
    # Then I should see "There are no LTI External tools yet"
    And I should not see "Teaching Tool 1"
    And I should not see "Teaching Tool 2"

  @javascript
  Scenario: Editing and saving selected parent / child categories
    Given I am on the "core_ltix > manage tools" page logged in as admin
    And I follow "Manage preconfigured tools"
    And I follow "Add preconfigured tool"
    And I expand all fieldsets
    And I click on "catb" "link"
    And I set the following fields to these values:
      | Tool name | Teaching Tool 3 |
      | Tool configuration usage | Do not show; use only when a matching tool URL is found |
      | catb | 1 |
    # If parent is selected, child should be selected.
    And the field "catcb" matches value "1"
    # If parent is unselected, child should be unselected.
    And I set the following fields to these values:
    | catb | 0 |
    And the field "catcb" matches value "0"
    # If parent is selected, child is unselected, parent should still be selected.
    # Step 1 - Select parent first so child is selected.
    And I set the following fields to these values:
    | catb  | 1 |
    And the field "catcb" matches value "1"
    # Step 2 - Unselect child but parent should stay as selected.
    And I set the following fields to these values:
    | catcb | 0 |
    And the field "catb" matches value "1"
    And I set the field "Tool URL" to local url "/ltix/tests/fixtures/tool_provider.php"
    And I press "Save changes"
    And I wait until the page is ready
    And I should see "Teaching Tool 3"
    When I click on "Update" "link" in the "Teaching Tool 3" "table_row"
    And I expand all fieldsets
    Then the following fields match these values:
      | catb  | 1 |
      | catcb | 0 |

  @javascript
  Scenario: Category restriction only shown for a site tool
    Given the following "core_ltix > tool types" exist:
      | name            | baseurl                                | coursevisible | state |
      | Teaching Tool 1 | /ltix/tests/fixtures/tool_provider.php | 1             | 1     |
    And the following "core_ltix > course tools" exist:
      | name          | description         | baseurl                  | course |
      | Course Tool 1 | Example description | https://example.com/tool | C1     |
    And I am on the "Course 1" "core_ltix > Course tools" page logged in as admin
    When I click on "Add tool" "link"
    And I should not see "Restrict to category"
    And I press "Cancel"
    And I open the action menu in "Course Tool 1" "table_row"
    And I choose "Edit" in the open action menu
    And I should not see "Restrict to category"
    And I am on the "core_ltix > manage tools" page
    And I follow "Manage preconfigured tools"
    And I follow "Add preconfigured tool"
    And I should see "Restrict to category"
    And I press "Cancel"
    And I click on "Update" "link" in the "Teaching Tool 1" "table_row"
    Then I should see "Restrict to category"
