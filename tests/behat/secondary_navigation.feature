@local @local_extendednav
Feature: Customise the navigation of a course
  In order to give the people of a course the navigation that fits it
  As an administrator
  I need to hide, rename and order the nodes of the navigation of a course

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "local_extendednav > nodes" exist:
      | menu   | nodekey      | visibility | title |
      | course | participants | 0          |       |
      | course | grades       | 1          | Marks |

  Scenario: A node is hidden and another renamed for a teacher
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then I should see "Marks" in the ".secondary-navigation" "css_element"
    And I should not see "Participants" in the ".secondary-navigation" "css_element"

  Scenario: An administrator sees every node, as the plugin says it by default
    When I log in as "admin"
    And I am on "Course 1" course homepage
    Then I should see "Participants" in the ".secondary-navigation" "css_element"

  Scenario: An administrator is held to the rules when the node says so
    Given the following "local_extendednav > nodes" exist:
      | menu   | nodekey      | visibility | hideadmins |
      | course | editsettings | 0     | 1          |
    When I log in as "admin"
    And I am on "Course 1" course homepage
    Then I should not see "Settings" in the ".secondary-navigation" "css_element"

  Scenario: A navigation that is turned off is shown as Moodle draws it
    Given the following config values are set as admin:
      | enabled_course | 0 | local_extendednav |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then I should see "Participants" in the ".secondary-navigation" "css_element"
