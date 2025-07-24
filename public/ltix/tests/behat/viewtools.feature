@core @core_ltix
Feature: Navigate existing LTI tool types using pagination
  In order to manage reusable activities for teachers
  As an admin
  I need to view existing tools

  Background:
    Given 70 "core_ltix > tool types" exist with the following data:
      |name        |Test tool [count]                  |
      |description |Example description [count]        |
      |baseurl     |https://www.example.com/tool[count]|

  # Note: 60 entries per page, and ordering is lexical.
  @javascript
  Scenario: View tool types using pagination controls
    # First page (default landing page).
    Given I am on the "core_ltix > manage tools" page logged in as admin
    And I should see "Test tool 30"
    And I should not see "Test tool 70"
    # Using the 'page 2' link.
    When I click on "2" "link"
    Then I should see "Test tool 70"
    And I should not see "Test tool 30"
    # Using the 'First page' link.
    And I click on "First" "link"
    And I should see "Test tool 30"
    And I should not see "Test tool 70"
    # Using the 'Last page link.
    And I click on "Last" "link"
    And I should see "Test tool 70"
    And I should not see "Test tool 30"
