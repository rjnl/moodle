@core @core_ltix
Feature: Configure placements for a tool
  In order to enable LTI placements
  As an admin
  I need to be able to configure the placements for a tool

  Background:
    Given I log in as "admin"

@javascript
  Scenario: View default form fields for Activity chooser placement
    Given I navigate to "LTI > Manage tools" in site administration
    When I click on "configure a tool manually" "link"
    Then I should see "Placement: Activity chooser"
    And the following fields in the "Placement: Activity chooser" "fieldset" match these values:
      | LTI Deep Linking Request       | 0 |
      | LTI Resource Linking Request   | 0 |
    And the "LTI Deep Linking URL" "field" should be disabled
    And the "LTI Resource Linking URL" "field" should be disabled

  @javascript
  Scenario: Adding a tool configuration with placement configuration
    Given I navigate to "LTI > Manage tools" in site administration
    When I click on "configure a tool manually" "link"
    And I set the following fields in the "Tool settings" "fieldset" to these values:
      | Tool name        | Test Tool 2                     |
      | Tool URL         | http://example.com              |
    And I set the following fields in the "Placement: Activity chooser" "fieldset" to these values:
      | LTI Deep Linking Request     | 1                      |
      | LTI Deep Linking URL         | http://deeplink        |
      | Icon URL                     | https://icon           |
      | Text                         | Some text for the tool |
    And I press "Save changes"
    Then I should see "Test Tool 2"
    And I click on "Edit" "link"
    And the following fields in the "Placement: Activity chooser" "fieldset" match these values:
      | LTI Deep Linking Request     | 1                      |
      | LTI Deep Linking URL         | http://deeplink        |
      | LTI Resource Linking Request | 0                      |
      | LTI Resource Linking URL     |                        |
      | Icon URL                     | https://icon           |
      | Text                         | Some text for the tool |

  @javascript
  Scenario: Editing a tool configuration to add placement configuration
    Given the following "core_ltix > tool types" exist:
      | name            | baseurl                                   |
      | Test Tool 1     | /ltix/tests/fixtures/tool_provider.php    |
    And I navigate to "LTI > Manage tools" in site administration
    And I should see "Test Tool 1"
    When I click on "Edit" "link"
    Then I should see "Placement: Activity chooser"
    And I set the following fields in the "Placement: Activity chooser" "fieldset" to these values:
      | LTI Deep Linking Request     | 1                      |
      | LTI Deep Linking URL         | http://deeplink        |
      | Icon URL                     | https://icon           |
      | Text                         | Some text for the tool |
    And I press "Save changes"
    And I click on "Edit" "link"
    And the following fields in the "Placement: Activity chooser" "fieldset" match these values:
      | LTI Deep Linking Request     | 1                      |
      | LTI Deep Linking URL         | http://deeplink        |
      | LTI Resource Linking Request | 0                      |
      | LTI Resource Linking URL     |                        |
      | Icon URL                     | https://icon           |
      | Text                         | Some text for the tool |
