@local @local_extendednav
Feature: Read the activity log of a navigation
  In order to know who changed the navigation and when
  As an administrator
  I need a log of the nodes created, changed and deleted in each navigation

  Background:
    Given I log in as "admin"
    And I navigate to "Appearance > Extended navigation > Manage navigation" in site administration
    And I follow "Add node"
    And I set the following fields to these values:
      | Node key | staffhelp   |
      | Title    | Staff help  |
      | URL      | /my/        |
    And I press "Save changes"

  @javascript
  Scenario: A node that is created is listed, and the menu of the actions narrows the list
    When I follow "Activity log"
    Then I should see "staffhelp" in the "[data-region='tablewrap']" "css_element"
    And I should see "Created" in the "[data-region='tablewrap']" "css_element"
    And I click on "[data-menu='status'] [data-menu-toggle]" "css_element"
    And I click on "[data-status='deleted']" "css_element"
    And I should see "No results"
    And I click on "Clear filters" "button" in the "[data-region='empty']" "css_element"
    And I should see "staffhelp" in the "[data-region='tablewrap']" "css_element"

  @javascript
  Scenario: The search finds a node by its key
    When I follow "Activity log"
    And I set the field "Search by node key or person" to "nothing like this"
    Then I should see "No results"
    And I set the field "Search by node key or person" to "staffhelp"
    And I should see "staffhelp" in the "[data-region='tablewrap']" "css_element"

  @javascript
  Scenario: The log of another navigation does not list the node
    When I visit "/local/extendednav/activity.php?menu=frontpage"
    Then I should see "No activity yet"
    And I should not see "staffhelp"
