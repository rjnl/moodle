@core @core_ltix
Feature: Create/edit tool configuration that has Deep Linking support
  In order to provide external tools that support Deep Linking for teachers and learners
  As an admin
  I need to be able to configure external tool registrations that support Deep Linking.

  Scenario: Verifying ContentItemSelectionRequest selection support in external tool registration
    Given I am on the "core_ltix > manage tools" page logged in as admin
    When I follow "Manage external tool registrations"
    And I follow "Configure a new external tool registration"
    Then I should see "ContentItemSelectionRequest" in the "Capabilities" "select"
