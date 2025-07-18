@core @core_ltix
Feature: Configure placements for a tool
  In order to enable LTI placements
  As an admin or a teacher
  I need to be able to configure the placements for a tool

  @javascript
  Scenario: Verifying default placement configuration state
    Given the following "core_ltix > placement types" exist:
      | placementtype           | component |
      | core_ltix:mockplacement | core_ltix |
    And I log in as "admin"
    And I navigate to "LTI > Manage tools" in site administration
    When I click on "configure a tool manually" "link"
    Then "Placements" "autocomplete" should exist in the "Placement" "fieldset"
    And the field "Placements" matches value ""
    And I expand the "Placements" autocomplete
    And I should see "Mock placement" in the "Placements" "autocomplete"
    And the following fields in the "Placement: Mock placement" "fieldset" match these values:
      | Default state                |  |
      | Deep Linking Request URL     |  |
      | Resource Linking Request URL |  |
      | Icon URL                     |  |
      | Text                         |  |

  @javascript
  Scenario: Creating a site tool placement configuration
    Given the following "core_ltix > tool types" exist:
      | name        | baseurl                                |
      | Site Tool 1 | /ltix/tests/fixtures/tool_provider.php |
    And the following "core_ltix > placement types" exist:
      | placementtype           | component |
      | core_ltix:mockplacement | core_ltix |
    And I log in as "admin"
    And I navigate to "LTI > Manage tools" in site administration
    And I click on "Edit" "link"
    When I set the field "Placements" in the "Placement" "fieldset" to "Mock placement"
    And I set the following fields in the "Placement: Mock placement" "fieldset" to these values:
      | Default state                | 1                      |
      | Deep Linking Request URL     | http://deep.link       |
      | Resource Linking Request URL | http://resource.link   |
      | Icon URL                     | https://icon           |
      | Text                         | Some text for the tool |
    And I press "Save changes"
    And I click on "Edit" "link"
    Then "Mock placement" "autocomplete_selection" should exist in the "Placement" "fieldset"
    And the following fields in the "Placement: Mock placement" "fieldset" match these values:
      | Default state                | 1                      |
      | Deep Linking Request URL     | http://deep.link       |
      | Resource Linking Request URL | http://resource.link   |
      | Icon URL                     | https://icon           |
      | Text                         | Some text for the tool |

  @javascript
  Scenario: Creating a course tool placement configuration
    Given the following "users" exist:
      | username | firstname | lastname |Given email     |
      | teacher1 | Teacher   | 1        | t1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "core_ltix > course tools" exist:
      | name          | description         | baseurl                  | course |
      | Course Tool 1 | Example description | https://example.com/tool | C1     |
    And the following "core_ltix > placement types" exist:
      | placementtype           | component |
      | core_ltix:mockplacement | core_ltix |
    And I am on the "Course 1" course page logged in as teacher1
    And I navigate to "LTI External tools" in current page administration
    And I open the action menu in "Course Tool 1" "table_row"
    And I choose "Edit" in the open action menu
    When I set the field "Placements" in the "Placement" "fieldset" to "Mock placement"
    And I set the following fields in the "Placement: Mock placement" "fieldset" to these values:
      | Deep Linking Request URL     | http://deep.link       |
      | Resource Linking Request URL | http://resource.link   |
      | Icon URL                     | https://icon           |
      | Text                         | Some text for the tool |
    And I press "Save changes"
    # Course tool placement should be enabled by default, so it should show in the Active placements column
    Then I should see "Mock placement" in the "Course Tool 1" "table_row"
    And I open the action menu in "Course Tool 1" "table_row"
    And I choose "Edit" in the open action menu
    And "Mock placement" "autocomplete_selection" should exist in the "Placement" "fieldset"
    And the following fields in the "Placement: Mock placement" "fieldset" match these values:
      | Deep Linking Request URL     | http://deep.link       |
      | Resource Linking Request URL | http://resource.link   |
      | Icon URL                     | https://icon           |
      | Text                         | Some text for the tool |

  @javascript
  Scenario: Editing a site tool placement configuration
    Given the following "core_ltix > tool types" exist:
      | name        | baseurl                                |
      | Site Tool 1 | /ltix/tests/fixtures/tool_provider.php |
    And the following "core_ltix > placement types" exist:
      | placementtype           | component |
      | core_ltix:mockplacement | core_ltix |
    And the following "core_ltix > tool placements" exist:
      | tool        | placementtype             | config_default_usage | config_deep_linking_url | config_icon_url | config_text            |
      | Site Tool 1 | core_ltix:mockplacement   | 1                    | http://deeplink         | https://icon    | Some text for the tool |
    And I log in as "admin"
    And I navigate to "LTI > Manage tools" in site administration
    And I click on "Edit" "link"
    # Edit several configuration options for the placement and verify the changes are applied.
    When I set the following fields in the "Placement: Mock placement" "fieldset" to these values:
      | Deep Linking Request URL     | http://deep.link.updated        |
      | Text                         | Some text for the tool (edited) |
    And I press "Save changes"
    And I click on "Edit" "link"
    Then "Mock placement" "autocomplete_selection" should exist in the "Placement" "fieldset"
    And the following fields in the "Placement: Mock placement" "fieldset" match these values:
      | Default state                |                                 |
      | Deep Linking Request URL     | http://deep.link.updated        |
      | Resource Linking Request URL |                                 |
      | Icon URL                     | https://icon                    |
      | Text                         | Some text for the tool (edited) |
    # Deselect the placement and verify the related configurations are removed upon saving.
    And I click on "Mock placement" "autocomplete_selection"
    And I press "Save changes"
    And I click on "Edit" "link"
    And "Mock placement" "autocomplete_selection" should not exist in the "Placement" "fieldset"
    And the following fields in the "Placement: Mock placement" "fieldset" match these values:
      | Default state                |   |
      | Deep Linking Request URL     |   |
      | Resource Linking Request URL |   |
      | Icon URL                     |   |
      | Text                         |   |

  @javascript
  Scenario: Editing a course tool placement configuration
    Given the following "users" exist:
      | username | firstname | lastname |Given email     |
      | teacher1 | Teacher   | 1        | t1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "core_ltix > course tools" exist:
      | name          | description         | baseurl                  | course |
      | Course Tool 1 | Example description | https://example.com/tool | C1     |
    And the following "core_ltix > placement types" exist:
      | placementtype           | component |
      | core_ltix:mockplacement | core_ltix |
    And the following "core_ltix > tool placements" exist:
      | tool          | placementtype             | config_deep_linking_url | config_icon_url | config_text            |
      | Course Tool 1 | core_ltix:mockplacement   | http://deeplink         | https://icon    | Some text for the tool |
    And I am on the "Course 1" course page logged in as teacher1
    And I navigate to "LTI External tools" in current page administration
    And I open the action menu in "Course Tool 1" "table_row"
    And I choose "Edit" in the open action menu
    # Edit several configuration options for the placement and verify the changes are applied.
    When I set the following fields in the "Placement: Mock placement" "fieldset" to these values:
      | Deep Linking Request URL     | http://deep.link.updated        |
      | Text                         | Some text for the tool (edited) |
    And I press "Save changes"
    # Course tool placement should be enabled by default, so it should show in the Active placements column
    Then I should see "Mock placement" in the "Course Tool 1" "table_row"
    And I open the action menu in "Course Tool 1" "table_row"
    And I choose "Edit" in the open action menu
    And "Mock placement" "autocomplete_selection" should exist in the "Placement" "fieldset"
    And the following fields in the "Placement: Mock placement" "fieldset" match these values:
      | Deep Linking Request URL     | http://deep.link.updated        |
      | Resource Linking Request URL |                                 |
      | Icon URL                     | https://icon                    |
      | Text                         | Some text for the tool (edited) |
    # Deselect the placement and verify the related configurations are removed upon saving.
    And I click on "Mock placement" "autocomplete_selection"
    And I press "Save changes"
    And I should not see "Mock placement" in the "Course Tool 1" "table_row"
    And I open the action menu in "Course Tool 1" "table_row"
    And I choose "Edit" in the open action menu
    And "Mock placement" "autocomplete_selection" should not exist in the "Placement" "fieldset"
    And the following fields in the "Placement: Mock placement" "fieldset" match these values:
      | Deep Linking Request URL     |   |
      | Resource Linking Request URL |   |
      | Icon URL                     |   |
      | Text                         |   |
