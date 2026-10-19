@local @local_extendednav
Feature: Change the primary navigation
  In order to give users a navigation that fits the site
  As an administrator
  I need to add, hide, restrict and rename primary navigation items

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
      | teacher1 | Teacher   | One      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |

  Scenario: An administrator adds a custom item from the management page
    Given I log in as "admin"
    And I navigate to "Appearance > Extended navigation > Manage navigation" in site administration
    And I follow "Add node"
    And I set the following fields to these values:
      | Node key | help           |
      | Title    | Help centre    |
      | URL      | /my/courses.php |
    And I press "Save changes"
    Then I should see "Node saved."
    And I should see "help" in the "local-extendednav-manage" "table"
    And I log out
    And I log in as "student1"
    And I should see "Help centre" in the ".primary-navigation" "css_element"

  Scenario: A custom item is marked active on its page
    Given the following "local_extendednav > nodes" exist:
      | nodekey    | title       | url                |
      | mycalendar | My calendar | /calendar/view.php |
    When I log in as "student1"
    And I follow "Calendar" in the user menu
    Then I should see "My calendar" in the ".primary-navigation a.nav-link.active" "css_element"
    And I should not see "Dashboard" in the ".primary-navigation a.nav-link.active" "css_element"

  @javascript
  Scenario: Items open in a new window or a pop-up
    Given the following "local_extendednav > nodes" exist:
      | nodekey | title | url                  | display | popupwidth | popupheight |
      | docs    | Docs  | /badges/mybadges.php | 1       |            |             |
      | chat    | Chat  | /calendar/view.php   | 2       | 800        | 600         |
    And I change window size to "large"
    When I log in as "student1"
    And I click on "Docs" "link" in the ".primary-navigation" "css_element"
    Then I switch to a second window
    And I close all opened windows
    And I click on "Chat" "link" in the ".primary-navigation" "css_element"
    And I switch to a second window
    And I close all opened windows
    And I should see "Chat" in the ".primary-navigation" "css_element"

  Scenario: Core items keep their Moodle title until they are renamed
    Given the following "local_extendednav > nodes" exist:
      | nodekey | title     |
      | myhome  | My learning |
    When I log in as "student1"
    Then I should see "My learning" in the ".primary-navigation" "css_element"
    And I should see "My courses" in the ".primary-navigation" "css_element"
    And I should not see "Dashboard" in the ".primary-navigation" "css_element"

  Scenario: Hidden and restricted items are only shown to the right users
    Given the following "local_extendednav > nodes" exist:
      | nodekey   | title        | url                  | visibility | roles          |
      | mycourses |              |                      | 0          |                |
      | staffroom | Staff room   | /mod/page/view.php   | 2          | editingteacher |
    When I log in as "student1"
    Then ".primary-navigation a[href$='/my/courses.php']" "css_element" should not exist
    And I should not see "Staff room" in the ".primary-navigation" "css_element"
    And I log out
    And I log in as "teacher1"
    And I should see "Staff room" in the ".primary-navigation" "css_element"
    And ".primary-navigation a[href$='/my/courses.php']" "css_element" should not exist

  Scenario: Items limited to languages are only shown in those languages
    Given the following "local_extendednav > nodes" exist:
      | nodekey  | title        | url       | langs |
      | help_en  | Help         | /help.php | en    |
      | help_fr  | Aide         | /help.php | fr    |
    When I log in as "student1"
    Then I should see "Help" in the ".primary-navigation" "css_element"
    And I should not see "Aide" in the ".primary-navigation" "css_element"

  Scenario: Items appear in the submenu of their parent
    Given the following "local_extendednav > nodes" exist:
      | nodekey   | title     | url            | parentkey |
      | resources | Resources | /resources.php |           |
      | library   | Library   | /library.php   | resources |
    When I log in as "student1"
    Then "Library" "link" should exist in the ".primary-navigation .dropdown-menu" "css_element"

  Scenario: Nothing changes while the plugin is disabled
    Given the following "local_extendednav > nodes" exist:
      | nodekey   | visibility |
      | mycourses | 0          |
    And the following config values are set as admin:
      | enable_plugin | 0 | local_extendednav |
    When I log in as "student1"
    Then I should see "My courses" in the ".primary-navigation" "css_element"
