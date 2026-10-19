@local @local_extendednav
Feature: Block the URLs of items users cannot see
  In order to keep users on the pages meant for them
  As an administrator
  I need hidden items to block their URL when I choose to

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

  Scenario: Users who cannot see a blocking item are sent to the Dashboard
    Given the following "local_extendednav > nodes" exist:
      | nodekey   | visibility | roles          | blockaccess |
      | mycourses | 2          | editingteacher | 1           |
    When I log in as "student1"
    And I visit "/my/courses.php"
    Then the url should match "/my/(index\.php)?$"
    And I log out
    And I log in as "teacher1"
    And I visit "/my/courses.php"
    And the url should match "/my/courses\.php"

  Scenario: Hidden items without "Block URL access" only leave the menu
    Given the following "local_extendednav > nodes" exist:
      | nodekey   | visibility | blockaccess |
      | mycourses | 0          | 0           |
    When I log in as "student1"
    And I visit "/my/courses.php"
    Then the url should match "/my/courses\.php"
    And I should not see "My courses" in the ".primary-navigation" "css_element"

  Scenario: Users are sent to the next fallback when the Dashboard is blocked
    Given the following "local_extendednav > nodes" exist:
      | nodekey | visibility | blockaccess |
      | myhome  | 0          | 1           |
    When I log in as "student1"
    And I visit "/my/"
    Then the url should match "redirect=0"

  Scenario: Users see an error when every fallback page is blocked for them
    Given the following "local_extendednav > nodes" exist:
      | nodekey   | visibility | roles          | blockaccess |
      | myhome    | 2          | editingteacher | 1           |
      | home      | 2          | editingteacher | 1           |
      | mycourses | 2          | editingteacher | 1           |
    When I log in as "student1"
    And I visit "/my/courses.php"
    Then I should see "Page not available"
    And I should see "The page you opened is not available for your account"

  Scenario: Administrators are never blocked
    Given the following "local_extendednav > nodes" exist:
      | nodekey   | visibility | blockaccess |
      | mycourses | 0          | 1           |
    When I log in as "admin"
    And I visit "/my/courses.php"
    Then the url should match "/my/courses\.php"
