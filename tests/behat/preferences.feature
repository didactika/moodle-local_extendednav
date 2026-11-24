@local @local_extendednav
Feature: Arrange one's own preferences
  In order to find quickly the preferences I use
  As a user
  I need to hide and order the options of my Preferences page, without changing anyone else's

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Sam       | Student  |

  @javascript
  Scenario: A user hides an option and restores the page
    Given I log in as "student1"
    And I follow "Preferences" in the user menu
    And I should see "Forum preferences"
    When I follow "Arrange my preferences"
    And I click on "Show Forum preferences" "checkbox"
    And I wait until the page is ready
    And I follow "Go to Preferences"
    Then I should not see "Forum preferences"
    And I follow "Arrange my preferences"
    And I press "Restore the default layout"
    And I wait until the page is ready
    And I follow "Go to Preferences"
    And I should see "Forum preferences"

  Scenario: A user without the capability keeps the page of the site
    Given the following "permission overrides" exist:
      | capability                       | permission | role | contextlevel | reference |
      | local/extendednav:personalise    | Prohibit   | user | System       |           |
    When I log in as "student1"
    And I follow "Preferences" in the user menu
    Then I should not see "Arrange my preferences"
