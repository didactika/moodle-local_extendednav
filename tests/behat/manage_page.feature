@local @local_extendednav
Feature: Order, hide and select nodes on the management page
  In order to arrange the navigation quickly
  As an administrator
  I need to order nodes, hide them and act on several nodes at once without the page reloading

  Background:
    Given the following "local_extendednav > nodes" exist:
      | nodekey | title  | url      |
      | first   | First  | /course/ |
      | second  | Second | /badges/ |
    And I log in as "admin"
    And I navigate to "Appearance > Extended navigation > Manage navigation" in site administration

  @javascript
  Scenario: A node is moved with the keyboard on its handle
    When I press key "40" in "Drag First to move it" "button"
    Then "Second" "table_row" should appear before "First" "table_row"
    And I reload the page
    And "Second" "table_row" should appear before "First" "table_row"

  @javascript
  Scenario: A node is hidden and shown again without reloading the page
    When I click on "Hide First" "button" in the "First" "table_row"
    Then I should see "Not visible" in the "First" "table_row"
    And I click on "Show First" "button" in the "First" "table_row"
    And I should see "Visible to everyone" in the "First" "table_row"

  @javascript
  Scenario: Selecting nodes shows the bulk actions
    Then ".card[data-region='floatbar']" "css_element" should not be visible
    And I click on "Select First" "checkbox"
    And I should see "1 selected"
    And I should see "Hide" in the "[data-region='floatbar']" "css_element"
    And I click on "Select all" "checkbox"
    And I click on "Select all" "checkbox"
    And ".card[data-region='floatbar']" "css_element" should not be visible

  @javascript
  Scenario: The list is searched and filtered by visibility
    When I set the field "Search nodes" to "Second"
    And I press key "13" in the field "Search nodes"
    Then I should see "Second" in the "[data-region='tablewrap']" "css_element"
    And I should not see "First" in the "[data-region='tablewrap']" "css_element"
    And I click on "Clear filters" "button"
    And I should see "First" in the "[data-region='tablewrap']" "css_element"

  @javascript
  Scenario: The navigations are chosen from the side column
    When I click on "Course navigation" "link" in the "[data-region='origins']" "css_element"
    Then I should see "Course navigation" in the "[data-region='origin']" "css_element"
    And I should see "Applies to"
