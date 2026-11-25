@local @local_extendednav
Feature: Arrange one's own preferences
  In order to find quickly the preferences I use
  As a user
  I need to hide and order the options of my Preferences page, without changing anyone else's

  Background:
    Given the guided tours of extended navigation are off
    And the following "users" exist:
      | username | firstname | lastname |
      | student1 | Sam       | Student  |
      | student2 | Sue       | Student  |

  @javascript
  Scenario: A user hides an option of a section and shows it again
    Given I log in as "student1"
    And I follow "Preferences" in the user menu
    And I should see "Forum preferences"
    And I should see "Extended navigation"
    When I follow "Arrange my preferences"
    And I click on "Open User account" "button"
    And I click on "Hide Forum preferences" "button"
    And I click on "Hide" "button" in the "Hide Forum preferences?" "dialogue"
    And I wait until the page is ready
    And I visit "/user/preferences.php"
    Then I should not see "Forum preferences"
    And I log out
    And I log in as "student2"
    And I follow "Preferences" in the user menu
    And I should see "Forum preferences"
    And I log out
    And I log in as "student1"
    And I follow "Preferences" in the user menu
    And I follow "Arrange my preferences"
    And I click on "Open User account" "button"
    And I click on "Show Forum preferences" "button"
    And I click on "Show" "button" in the "Show Forum preferences?" "dialogue"
    And I wait until the page is ready
    And I visit "/user/preferences.php"
    And I should see "Forum preferences"

  Scenario: A user without the capability keeps the page of the site
    Given the following "permission overrides" exist:
      | capability                       | permission | role | contextlevel | reference |
      | local/extendednav:personalise    | Prohibit   | user | System       |           |
    When I log in as "student1"
    And I follow "Preferences" in the user menu
    Then I should not see "Arrange my preferences"

  Scenario: An administrator arranges the preferences of a user from that user
    Given I log in as "admin"
    When I visit the preferences page of "student1"
    Then I should see "Arrange the preferences of Sam Student"
