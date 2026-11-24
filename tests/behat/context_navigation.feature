@local @local_extendednav
Feature: Change the navigations of a course from the course
  In order to give a course the navigation that fits it without the site administration
  As a teacher who may manage the navigation of the course
  I need a page of the course where its navigations are changed

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
    And the following "permission overrides" exist:
      | capability                      | permission | role           | contextlevel | reference |
      | local/extendednav:managecontext | Allow      | editingteacher | Course       | C1        |

  @javascript
  Scenario: A teacher hides a node of their course from the page of the course
    Given I log in as "teacher1"
    And I change window size to "large"
    And I am on "Course 1" course homepage
    When I navigate to "Navigation" in current page administration
    Then I should see "Course navigation" in the "[data-region='origins']" "css_element"
    And I should not see "Import"
    And I click on "Hide Participants" "button" in the "Participants" "table_row"
    And I click on "Hide" "button" in the "Hide Participants?" "dialogue"
    And I should see "Not visible" in the "Participants" "table_row"
    And I should see "Exception" in the "Participants" "table_row"

  Scenario: The page of a course is only for whoever may manage its navigation
    Given I log in as "teacher1"
    When I am on "Course 2" course homepage
    Then I should not see "Navigation" in the ".secondary-navigation" "css_element"

  @javascript
  Scenario: The overview lists the courses that change a navigation
    Given the following "local_extendednav > nodes" exist:
      | menu   | nodekey      | visibility |
      | course | participants | 1          |
    And I log in as "admin"
    And I change window size to "large"
    And I am on "Course 1" course homepage
    And I navigate to "Navigation" in current page administration
    And I click on "Hide Participants" "button" in the "Participants" "table_row"
    And I click on "Hide" "button" in the "Hide Participants?" "dialogue"
    When I navigate to "Appearance > Extended navigation > Exceptions of categories and courses" in site administration
    Then I should see "Course 1" in the "[data-region='tablewrap']" "css_element"
    And I should see "Course navigation · 1" in the "[data-region='tablewrap']" "css_element"
