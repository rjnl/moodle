@core @core_ltix
Feature: Manage course tools
  In order to provide richer experiences for learners
  As a teacher
  I need to be able to add external tools to a course

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
    And the following "course" exists:
      | fullname    | Course 1 |
      | shortname   | C1       |
      | category    | 0        |
      | format      | topics   |
      | numsections | 1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following config values are set as admin:
      | enableasyncbackup | 0 |

  Scenario: Create a course tool from the zero state
    Given I am on the "Course 1" course page logged in as teacher1
    And I navigate to "LTI External tools" in current page administration
    And I should see "LTI External tools are add-on apps"
    And I should see "There are no LTI External tools yet."
    When I click on "Add tool" "link"
    And I press "Cancel"
    Then I should see "LTI External tools are add-on apps"
    And I should see "There are no LTI External tools yet."
    And I click on "Add tool" "link"
    And I set the following fields to these values:
      | Tool name        | Teaching Tool 1                 |
      | Tool URL         | http://example.com              |
      | Tool description | A short description of the tool |
    And I press "Save changes"
    And I should see "Teaching Tool 1 added"
    And I should see "A short description of the tool" in the "Teaching Tool 1" "table_row"

  Scenario: Viewing site level tools in the course tools table
    # Add 3 site tools: one visible, one hidden, and one visible but with a pending state.
    Given the following "core_ltix > tool types" exist:
      | name        | description             | baseurl                   | coursevisible | state |
      | Site tool 1 | Site tool 1 description | https://example.com/tool1 | 1             | 1     |
      | Site tool 2 | Site tool 2 description | https://example.com/tool2 | 0             | 1     |
      | Site tool 3 | Site tool 3 description | https://example.com/tool3 | 1             | 2     |
    And I am on the "Course 1" course page logged in as teacher1
    When I navigate to "LTI External tools" in current page administration
    Then I should see "Site tool 1" in the "reportbuilder-table" "table"
    And the "Manage placements" item should exist in the "Actions" action menu of the "Site tool 1" "table_row"
    And the "Edit" item should not exist in the "Actions" action menu of the "Site tool 1" "table_row"
    And the "Delete" item should not exist in the "Actions" action menu of the "Site tool 1" "table_row"
    And I should not see "Site tool 2" in the "reportbuilder-table" "table"
    And I should not see "Site tool 3" in the "reportbuilder-table" "table"

  Scenario Outline: Users without permission see the lock icon
    # A site tool configured to show in courses.
    Given the following "core_ltix > tool types" exist:
      | name        | baseurl                                | coursevisible | state |
      | Site tool 1 | /ltix/tests/fixtures/tool_provider.php | 1             | 1     |
    # A course tool in course 1.
    And the following "core_ltix > course tools" exist:
      | name          | baseurl                                | course |
      | Course tool 1 | /ltix/tests/fixtures/tool_provider.php | C1     |
    And the following "role capability" exists:
      | role                        | editingteacher    |
      | moodle/ltix:addcoursetool   | <capabilitystate> |
    When I am on the "Course 1" course page logged in as teacher1
    And I navigate to "LTI External tools" in current page administration
    Then I should see "Site tool 1" in the "reportbuilder-table" "table"
    And I should see "Course tool 1" in the "reportbuilder-table" "table"
    And "You don't have permission to edit this tool" "icon" <viewlockicon> exist in the "Site tool 1" "table_row"
    And "You don't have permission to edit this tool" "icon" <viewlockicon> exist in the "Course tool 1" "table_row"

    Examples:
      | capabilitystate | viewlockicon |
      | prohibit        | should       |
      | allow           | should not   |

  Scenario Outline: View manage placements context menu
    # A site tool configured to show in courses.
    Given the following "core_ltix > tool types" exist:
      | name        | baseurl                                | coursevisible | state |
      | Site tool 1 | /ltix/tests/fixtures/tool_provider.php | 1             | 1     |
    # A course tool in course 1.
    And the following "core_ltix > course tools" exist:
      | name          | baseurl                                | course |
      | Course tool 1 | /ltix/tests/fixtures/tool_provider.php | C1     |
    And the following "role capability" exists:
      | role                        | editingteacher |
      | moodle/ltix:addcoursetool   | allow          |
    When I am on the "Course 1" course page logged in as teacher1
    And I navigate to "LTI External tools" in current page administration
    Then the "Manage placements" item <manageplacementmenu> exist in the "Actions" action menu of the "<toolname>" "table_row"
    And the "Edit" item <editmenu> exist in the "Actions" action menu of the "<toolname>" "table_row"
    And the "Delete" item <editmenu> exist in the "Actions" action menu of the "<toolname>" "table_row"

    Examples:
      | toolname        | manageplacementmenu | editmenu   |
      | Site tool 1     | should              | should not |
      | Course tool 1   | should              | should     |

  Scenario: Viewing course tools with the capability to add/edit and without the capability to use
    Given the following "role capability" exists:
      | role                        | editingteacher |
      | moodle/ltix:addcoursetool   | allow          |
      | moodle/ltix:viewcoursetools | prohibit       |
    When I am on the "Course 1" course page logged in as teacher1
    Then "LTI External tools" "link" should not exist in current page administration

  @javascript
  Scenario Outline: Configure placement status for an LTI tool
    # A site tool configured to show in courses.
    Given the following "core_ltix > tool types" exist:
      | name        | baseurl                                | coursevisible | state |
      | Site tool 1 | /ltix/tests/fixtures/tool_provider.php | 1             | 1     |
    # A course tool in course 1.
    And the following "core_ltix > course tools" exist:
      | name          | baseurl                                | course |
      | Course tool 1 | /ltix/tests/fixtures/tool_provider.php | C1     |
    # Mock a placement type for testing.
    And the following "core_ltix > placement types" exist:
      | placementtype           | component |
      | core_ltix:mockplacement | core_ltix |
    # Configure the site and course tools with a mock placement.
    And the following "core_ltix > tool placements" exist:
      | tool          | placementtype           | config_default_usage |
      | Site tool 1   | core_ltix:mockplacement | enabled              |
      | Course tool 1 | core_ltix:mockplacement | enabled              |
    And I am on the "Course 1" course page logged in as teacher1
    And I navigate to "LTI External tools" in current page administration
    When I choose the "Manage placements" item in the "Actions" action menu of the "Site tool 1" "table_row"
    Then I should see "Manage placements" in the ".modal-header" "css_element"
    And I should not see "There are no available placements for this tool" in the ".modal-body" "css_element"
    And I set the field "Mock placement" to "<fieldvalue>"
    # Test Cancel first
    And I click on "Cancel" "button" in the ".modal-footer" "css_element"
    And I choose the "Manage placements" item in the "Actions" action menu of the "Site tool 1" "table_row"
    And the field "Mock placement" matches value "<expectedcancelvalue>"
    # Test Save/Apply
    And I set the field "Mock placement" to "<fieldvalue>"
    And I click on "Apply" "button" in the ".modal-content" "css_element"
    And I should see "Placement status saved"
    And I <activeplacementvisible> see "Mock placement" in the "Site tool 1" "table_row"
    And I choose the "Manage placements" item in the "Actions" action menu of the "Site tool 1" "table_row"
    And the field "Mock placement" matches value "<expectedapplyvalue>"

    Examples:
      | fieldvalue | expectedcancelvalue | expectedapplyvalue | activeplacementvisible |
      | 0          | 1                   | 0                  | should not             |
      | 1          | 1                   | 1                  | should                 |

  @javascript
  Scenario Outline: There are no placements to manage
    # A site tool configured to show in courses.
    Given the following "core_ltix > tool types" exist:
      | name        | baseurl                                | coursevisible | state |
      | Site tool 1 | /ltix/tests/fixtures/tool_provider.php | 1             | 1     |
    # A course tool in course 1.
    And the following "core_ltix > course tools" exist:
      | name          | baseurl                                | course |
      | Course tool 1 | /ltix/tests/fixtures/tool_provider.php | C1     |
    And I am on the "Course 1" course page logged in as teacher1
    And I navigate to "LTI External tools" in current page administration
    When I choose the "Manage placements" item in the "Actions" action menu of the "<toolname>" "table_row"
    Then I should see "There are no available placements for this tool" in the ".modal-body" "css_element"
    And the "Apply" "button" should be disabled

    Examples:
      | toolname      |
      | Site tool 1   |
      | Course tool 1 |

  @javascript
  Scenario Outline: Verify default placement status in course
    Given the following "core_ltix > tool types" exist:
      | name        | baseurl                                | coursevisible | state |
      | Site tool 2 | /ltix/tests/fixtures/tool_provider.php | 1             | 1     |
    And the following "core_ltix > placement types" exist:
      | placementtype           | component |
      | core_ltix:mockplacement | core_ltix |
    And the following "core_ltix > tool placements" exist:
      | tool        | placementtype           | config_default_usage |
      | Site tool 2 | core_ltix:mockplacement | <default_usage>      |
    And I am on the "Course 1" course page logged in as teacher1
    And I navigate to "LTI External tools" in current page administration
    When I choose the "Manage placements" item in the "Actions" action menu of the "Site tool 2" "table_row"
    Then the field "Mock placement" matches value "<expectedvalue>"

    Examples:
      | default_usage | expectedvalue |
      | enabled       | 1             |
      | disabled      | 0             |

  @javascript
  Scenario: Edit a course tool
    Given the following "core_ltix > course tools" exist:
      | name      | description         | baseurl                  | course |
      | Test tool | Example description | https://example.com/tool | C1     |
    And I am on the "Course 1" course page logged in as teacher1
    And I navigate to "LTI External tools" in current page administration
    And the "Edit" item should exist in the "Actions" action menu of the "Test tool" "table_row"
    And the "Delete" item should exist in the "Actions" action menu of the "Test tool" "table_row"
    When I open the action menu in "Test tool" "table_row"
    And I choose "Edit" in the open action menu
    And I press "Cancel"
    Then I should see "Test tool" in the "reportbuilder-table" "table"
    And I open the action menu in "Test tool" "table_row"
    And I choose "Edit" in the open action menu
    And I set the following fields to these values:
      | Tool name        | Test tool (edited)                       |
      | Tool URL         | http://example.com                       |
      | Tool description | A short description of the tool (edited) |
    And I press "Save changes"
    And I should see "Changes saved"
    And I should see "A short description of the tool (edited)" in the "Test tool (edited)" "table_row"

  @javascript
  Scenario: Navigate through the listing of course tools
    Given 20 "core_ltix > course tools" exist with the following data:
    | name        | Test tool [count]                   |
    | description | Example description [count]         |
    | baseurl     | https://www.example.com/tool[count] |
    | course      | C1                                  |
    And I am on the "Course 1" course page logged in as teacher1
    When I navigate to "LTI External tools" in current page administration
    Then I should see "Test tool 1" in the "reportbuilder-table" "table"
    And I click on "Name" "link"
    And I should see "Test tool 20" in the "reportbuilder-table" "table"
    And I click on "2" "link" in the "page" "region"
    And I should see "Test tool 1" in the "reportbuilder-table" "table"

  @javascript
  Scenario: Delete a course tool
    Given the following "core_ltix > course tools" exist:
      | name         | description         | baseurl                          | course |
      | Test tool    | Example description | https://example.com/tool         | C1     |
      | Another tool | Example 123         | https://another.example.com/tool | C1     |
    And I am on the "Course 1" course page logged in as teacher1
    And I navigate to "LTI External tools" in current page administration
    When I open the action menu in "Test tool" "table_row"
    And I choose "Delete" in the open action menu
    Then I should see "This will delete Test tool from the available LTI tools in your course."
    And I click on "Cancel" "button" in the "Delete Test tool" "dialogue"
    And I should see "Test tool" in the "reportbuilder-table" "table"
    And I open the action menu in "Test tool" "table_row"
    And I choose "Delete" in the open action menu
    And I should see "This will delete Test tool from the available LTI tools in your course."
    And I click on "Delete" "button" in the "Delete Test tool" "dialogue"
    And I should see "Test tool deleted"
    And I should not see "Test tool" in the "reportbuilder-table" "table"

  @javascript
  Scenario: Add a course tool using a cartridge URL
    Given I am on the "Course 1" course page logged in as teacher1
    And I navigate to "LTI External tools" in current page administration
    When I click on "Add tool" "link"
    And I set the following fields to these values:
      | Tool name        | Test tool 1             |
      | Tool description | Test tool 1 description |
    And I set the field "Tool URL" to local url "/ltix/tests/fixtures/ims_cartridge_basic_lti_link.xml"
    And I press "Save changes"
    Then I should see "Test tool 1" in the "reportbuilder-table" "table"
    # The cartridge description, if set, overrides the description set in the type edit form (bug?).
    And I should see "Example tool description" in the "Test tool 1" "table_row"
    And I open the action menu in "Test tool 1" "table_row"
    And I choose "Edit" in the open action menu
    And the field "Tool name" matches value "Test tool 1"
    And the field "Tool URL" matches value "http://www.example.com/lti/provider.php"
    And the field "Icon URL" matches value "http://download.moodle.org/unittest/test.jpg"
    And the field "Secure icon URL" matches value "https://download.moodle.org/unittest/test.jpg"

# TODO: reinstate this test once core_ltix backup and restore has landed.
#  @javascript
#  Scenario: Site and course tools settings are preserved when backup and restore
#    Given the following "core_ltix > tool types" exist:
#      | name            | baseurl                                | coursevisible | state |
#      | Teaching Tool 1 | /ltix/tests/fixtures/tool_provider.php | 2             | 1     |
#      | Teaching Tool 2 | /ltix/tests/fixtures/tool_provider.php | 1             | 1     |
#    And the following "core_ltix > course tools" exist:
#      | name          | description         | baseurl                  | course |
#      | Course Tool 1 | Example description | https://example.com/tool | C1     |
#    And I log in as "admin"
#    And I am on "Course 1" course homepage with editing mode on
#    And I add a "Teaching Tool 1" to section "1" using the activity chooser
#    And I set the field "Activity name" to "Test tool activity 1"
#    And I press "Save and return to course"
#    And I add a "Course Tool 1" to section "1" using the activity chooser
#    And I set the field "Activity name" to "Course tool activity 1"
#    And I press "Save and return to course"
#    And I navigate to "LTI External tools" in current page administration
#    And I click on "Don't show in activity chooser" "field" in the "Teaching Tool 1" "table_row"
#    And I click on "Show in activity chooser" "field" in the "Teaching Tool 2" "table_row"
#    And I click on "Don't show in activity chooser" "field" in the "Course Tool 1" "table_row"
#    And I am on "Course 1" course homepage
#    And I add a "Teaching Tool 2" to section "1" using the activity chooser
#    And I set the field "Activity name" to "Test tool activity 2"
#    And I press "Save and return to course"
#    When I backup "Course 1" course using this options:
#      | Confirmation | Filename | test_backup.mbz |
#    And I restore "test_backup.mbz" backup into a new course using this options:
#      | Schema | Course name | Restored course |
#    And I should see "Restored course"
#    And I open the activity chooser
#    Then I should not see "Teaching Tool 1" in the ".modal-body" "css_element"
#    And I should see "Teaching Tool 2" in the ".modal-body" "css_element"
#    And I should not see "Course Tool 2" in the ".modal-body" "css_element"
#    And I click on "Close" "button" in the ".modal-dialog" "css_element"
#    And I navigate to "LTI External tools" in current page administration
#    And I should see "Show in activity chooser" in the "Teaching Tool 1" "table_row"
#    And I should see "Don't show in activity chooser" in the "Teaching Tool 2" "table_row"
#    And I should see "Show in activity chooser" in the "Course Tool 1" "table_row"
