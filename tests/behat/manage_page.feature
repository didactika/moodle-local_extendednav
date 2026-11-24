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
    And I change window size to "large"
    And I navigate to "Appearance > Extended navigation > Manage navigation" in site administration

  @javascript
  Scenario: A node is moved with the keyboard on its handle
    When I click on "Drag First to move it" "button"
    And I press the down key
    Then "Second" "table_row" should appear before "First" "table_row"
    And I reload the page
    And "Second" "table_row" should appear before "First" "table_row"

  @javascript
  Scenario: A node is hidden and shown again without reloading the page
    When I click on "Hide First" "button" in the "First" "table_row"
    And I should see "Hide from administrators too" in the "Hide First?" "dialogue"
    And I click on "Hide" "button" in the "Hide First?" "dialogue"
    Then I should see "Not visible" in the "First" "table_row"
    And I click on "Show First" "button" in the "First" "table_row"
    And I should see "Visible to everyone" in the "First" "table_row"

  @javascript
  Scenario: A badge of the visibility explains every restriction of the node
    When I click on "Hide First" "button" in the "First" "table_row"
    And I click on "Hide" "button" in the "Hide First?" "dialogue"
    And I click on "Not visible" "button" in the "First" "table_row"
    Then I should see "Shown to nobody." in the "Who sees First" "dialogue"
    And "Edit node" "link" should exist in the "Who sees First" "dialogue"

  @javascript
  Scenario: Selecting nodes shows the bulk actions
    Then ".card[data-region='floatbar']" "css_element" should not be visible
    And I click on "tr[data-key='first'] [data-region='selectrow']" "css_element"
    And I should see "1 selected"
    And I should see "Hide" in the "[data-region='floatbar']" "css_element"
    And I should see "Delete" in the "[data-region='floatbar']" "css_element"
    And I should not see "Show" in the "[data-region='floatbar']" "css_element"
    And I click on "[data-region='selectpage']" "css_element"
    And I click on "[data-region='selectpage']" "css_element"
    And ".card[data-region='floatbar']" "css_element" should not be visible

  @javascript
  Scenario: The list is searched and filtered by visibility
    When I set the field "Search nodes" to "Second"
    Then I should see "Second" in the "[data-region='tablewrap']" "css_element"
    And I should not see "First" in the "[data-region='tablewrap']" "css_element"
    And I click on "Clear filters" "button"
    And I should see "First" in the "[data-region='tablewrap']" "css_element"

  @javascript
  Scenario: The navigations are chosen from the side column
    When I click on "Course navigation" "link" in the "[data-region='origins']" "css_element"
    Then I should see "Course navigation" in the "[data-region='origin']" "css_element"
    And I should see "Exceptions of categories and courses" in the "[data-region='scope']" "css_element"

  @javascript
  Scenario: The nodes of Moodle and the nodes added are told apart and filtered
    Then I should see "Added" in the "First" "table_row"
    And I should see "Moodle" in the "myhome" "table_row"
    And I click on "[data-menu='origin'] [data-menu-toggle]" "css_element"
    And I click on "[data-menu='origin'] [data-option='custom']" "css_element"
    And I should see "First" in the "[data-region='tablewrap']" "css_element"
    And I should not see "myhome" in the "[data-region='tablewrap']" "css_element"

  @javascript
  Scenario: Hiding several nodes says which ones it leaves as they are
    When I click on "[data-region='selectpage']" "css_element"
    And I click on "Hide" "button" in the "[data-region='floatbar']" "css_element"
    Then I should see "of the selected nodes are left as they are" in the ".modal.show" "css_element"
    And I should see "Site administration" in the ".modal.show" "css_element"
