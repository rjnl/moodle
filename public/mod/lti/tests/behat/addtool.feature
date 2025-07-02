@mod @mod_lti
Feature: Add tools
  In order to provide activities for learners
  As a teacher
  I need to be able to add instances of external tools to a course

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  @javascript
  Scenario: Add a site tool via the activity picker
    # A site tool configured to show in courses.
    Given the following "core_ltix > tool types" exist:
      | name            | baseurl                                | coursevisible | state |
      | Teaching Tool 1 | /ltix/tests/fixtures/tool_provider.php | 1             | 1     |
    # Configure the site tool's activity chooser placement, so it will be shown in the activity chooser.
    And the following "core_ltix > tool placements" exist:
      | tool            | placementtype             | config_default_usage |
      | Teaching Tool 1 | mod_lti:activityplacement | enabled              |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "Teaching Tool 1" to section "1" using the activity chooser
    And I set the field "Activity name" to "Test tool activity 1"
    And "Launch container" "field" should not be visible
    # For tool that does not support Content-Item message type, the Select content button must be disabled.
    And "Select content" "button" should not be visible
    And "Tool URL" "field" should not be visible
    And I press "Save and return to course"
    And I am on the "Test tool activity 1" "lti activity editing" page
    Then the field "Activity name" matches value "Test tool activity 1"
    And "Launch container" "field" should not be visible
    And "Select content" "button" should not be visible
    And "Tool URL" "field" should not be visible

  @javascript
  Scenario: Add a course tool via the activity picker
    # A course tool in course 1.
    Given the following "core_ltix > course tools" exist:
      | name          | baseurl                                | course |
      | Course tool 1 | /ltix/tests/fixtures/tool_provider.php | C1     |
    # Configure the course tool's activity chooser placement, so it will be shown in the activity chooser.
    And the following "core_ltix > tool placements" exist:
      | tool          | placementtype             | config_default_usage |
      | Course tool 1 | mod_lti:activityplacement | enabled              |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I add a "Course tool 1" to section "1" using the activity chooser
    And I set the field "Activity name" to "Test tool activity 2"
    And "Launch container" "field" should not be visible
    # For tool that does not support Content-Item message type, the Select content button must be disabled.
    And "Select content" "button" should not be visible
    And I press "Save and return to course"
    And I am on the "Test tool activity 2" "lti activity editing" page
    Then the field "Activity name" matches value "Test tool activity 2"
    And "Launch container" "field" should not be visible
    And "Select content" "button" should not be visible
    And "Tool URL" "field" should not be visible

  @javascript
  Scenario: Hidden site tools won't show in the activity chooser even with a valid placement configuration.
    # A site tool.
    Given the following "core_ltix > tool types" exist:
      | name        | baseurl                                | coursevisible | state |
      | Site tool 1 | /ltix/tests/fixtures/tool_provider.php | 0             | 1     |
    # Configure placements.
    And the following "core_ltix > tool placements" exist:
      | tool          | placementtype             | config_default_usage |
      | Site tool 1   | mod_lti:activityplacement | enabled              |
    # Confirm not visible.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I open the activity chooser
    Then I should not see "Site tool 1" in the ".modal-body" "css_element"

  @javascript
  Scenario Outline: Tools appearing in activity chooser according to placement settings
    # A site tool.
    Given the following "core_ltix > tool types" exist:
      | name        | baseurl                                | coursevisible | state |
      | Site tool 1 | /ltix/tests/fixtures/tool_provider.php | 1             | 1     |
    And the following "core_ltix > course tools" exist:
      | name          | course | baseurl                                |
      | Course tool 1 | C1     | /ltix/tests/fixtures/tool_provider.php |
    # Configure placements with the desired default usage.
    And the following "core_ltix > tool placements" exist:
      | tool          | placementtype             | config_default_usage    |
      | Site tool 1   | mod_lti:activityplacement | <placementdefaultusage> |
      | Course tool 1 | mod_lti:activityplacement | <placementdefaultusage> |
    # Confirm default behaviour (based on the default usage).
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    When I open the activity chooser
    Then I <visibilitydefault> see "<toolname>" in the ".modal-body" "css_element"
    And I click on "Close" "button" in the ".modal-dialog" "css_element"
    # Now, override the placement status and confirm the expected results in terms of activity chooser visibility.
    And I navigate to "LTI External tools" in current page administration
    And I choose the "Manage placements" item in the "Actions" action menu of the "<toolname>" "table_row"
    And I set the field "Activity chooser" to "<placementstatusoverride>"
    And I click on "Apply" "button" in the ".modal-content" "css_element"
    And I should see "Placement status saved"
    And I am on "Course 1" course homepage with editing mode on
    And I open the activity chooser
    And I <visibilityoverride> see "<toolname>" in the ".modal-body" "css_element"

    # Note: course tools placements don't support default=disabled, so that case is deliberately omitted below.
    Examples:
      | toolname      | placementdefaultusage | visibilitydefault | placementstatusoverride | visibilityoverride |
      | Site tool 1   | enabled               | should            | 0                       | should not         |
      | Site tool 1   | disabled              | should not        | 1                       | should             |
      | Course tool 1 | enabled               | should            | 0                       | should not         |

  @javascript
  Scenario: Editing a (deprecated) manually configured activity instance, confirming that config changes aren't possible
    Given the following "activities" exist:
      | activity | name          | course | toolurl                                               |
      | lti      | A manual tool | C1     | /ltix/tests/fixtures/ims_cartridge_basic_lti_link.xml |
    # Add a course tool with the same URL as that of the manually configured instance (the tool URL found in the above cartridge).
    # This would normally be domain-matched during edit, resulting in the assignment of a preconfigured tool to the instance.
    # In this case, because config changes and domain matching are disabled, the test confirms this doesn't take place.
    And the following "core_ltix > course tools" exist:
      | name          | baseurl                                 | course | lti_sendname | lti_sendemailaddr | lti_acceptgrades |
      | Course tool 2 | http://www.example.com/lti/provider.php | C1     | 0            | 1                 | 2                |
    When I am on the "A manual tool" "lti activity editing" page logged in as teacher1
    Then I should see "Manually configured External tool activities are no longer supported"
    And I follow "Show more..."
    And I expand all fieldsets
    # The privacy values below represent the existing values of the privacy settings, before saving and inheriting from the
    # domain-matched tool values.
    And the following fields match these values:
    | Activity name                    | A manual tool                                 |
    | id_showdescription               | 0                                             |
    | Consumer key                     | 12345                                         |
    | Icon URL                         | http://download.moodle.org/unittest/test.jpg  |
    | Secure icon URL                  | https://download.moodle.org/unittest/test.jpg |
    | Tool URL                         | http://www.example.com/lti/provider.php       |
    | id_instructorchoicesendname      | 1                                             |
    | id_instructorchoicesendemailaddr | 1                                             |
    | id_instructorchoiceacceptgrades  | 1                                             |
    And the "Activity name" "field" should be enabled
    And the "Activity description" "field" should be enabled
    And the "id_showdescription" "checkbox" should be enabled
    And the "id_showtitlelaunch" "checkbox" should be enabled
    And the "id_showdescriptionlaunch" "checkbox" should be enabled
    And the "Secure tool URL" "field" should be disabled
    And the "Consumer key" "field" should be enabled
    And the "Shared secret" "field" should be enabled
    And I click on "Reveal" "icon"
    And I should see "secret"
    And the "Custom parameters" "field" should be disabled
    And the "Icon URL" "field" should be disabled
    And the "Secure icon URL" "field" should be disabled
    And I should see "Automatic, based on tool URL"
    And the "Select content" "button" should be disabled
    And the "Tool URL" "field" should be disabled
    And the "id_instructorchoicesendname" "checkbox" should be disabled
    And the "id_instructorchoicesendemailaddr" "checkbox" should be disabled
    And the "id_instructorchoiceacceptgrades" "checkbox" should be disabled
    And I set the following fields to these values:
    | Activity name      | A manual tool name edited |
    | id_showdescription | 1                         |
    | Consumer key       | key                       |
    | Shared secret      | secret                    |
    And I press "Save and return to course"
    And I am on the "A manual tool" "lti activity editing" page logged in as teacher1
    And I follow "Show more..."
    # This confirms that the instance config, while locked to user edits, still inherits privacy settings from the tool which
    # it was domain-matched to.
    And the following fields match these values:
    | Activity name                    | A manual tool name edited                     |
    | id_showdescription               | 1                                             |
    | Consumer key                     | key                                           |
    | Shared secret                    | secret                                        |
    | Icon URL                         | http://download.moodle.org/unittest/test.jpg  |
    | Secure icon URL                  | https://download.moodle.org/unittest/test.jpg |
    | Tool URL                         | http://www.example.com/lti/provider.php       |
    | id_instructorchoicesendname      | 0                                             |
    | id_instructorchoicesendemailaddr | 1                                             |
    | id_instructorchoiceacceptgrades  | 2                                             |
    And the "Activity name" "field" should be enabled
    And the "Activity description" "field" should be enabled
    And the "id_showdescription" "checkbox" should be enabled
    And the "id_showtitlelaunch" "checkbox" should be enabled
    And the "id_showdescriptionlaunch" "checkbox" should be enabled
    And the "Secure tool URL" "field" should be disabled
    And the "Consumer key" "field" should be enabled
    And the "Shared secret" "field" should be enabled
    And I click on "Reveal" "icon"
    And I should see "secret"
    And the "Custom parameters" "field" should be disabled
    And the "Icon URL" "field" should be disabled
    And the "Secure icon URL" "field" should be disabled
    And I should see "Automatic, based on tool URL"
    And the "Select content" "button" should be disabled
    And the "Tool URL" "field" should be disabled
    And the "id_instructorchoicesendname" "checkbox" should be disabled
    And the "id_instructorchoicesendemailaddr" "checkbox" should be disabled
    And the "id_instructorchoiceacceptgrades" "checkbox" should be disabled
