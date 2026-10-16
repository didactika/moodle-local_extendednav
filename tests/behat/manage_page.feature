@local @local_extendednav
Feature: Order and select nodes on the management page
  In order to arrange the navigation quickly
  As an administrator
  I need to reorder nodes and act on several nodes at once

  Background:
    Given the following "local_extendednav > nodes" exist:
      | nodekey | title  | url      |
      | first   | First  | /course/ |
      | second  | Second | /badges/ |
    And I log in as "admin"
    And I navigate to "Appearance > Extended navigation > Manage navigation" in site administration

  @javascript
  Scenario: A node is moved with the move dialogue
    When I click on "Move First" "button" in the "First" "table_row"
    And I click on "After \"Second\"" "link" in the "Move First" "dialogue"
    Then "Second" "table_row" should appear before "First" "table_row"
    And I reload the page
    And "Second" "table_row" should appear before "First" "table_row"

  @javascript
  Scenario: Selecting nodes shows the bulk actions
    Then I should not see "Delete selected"
    And I click on "Select" "checkbox" in the "First" "table_row"
    And I should see "1 node(s) selected"
    And I should see "Delete selected"
