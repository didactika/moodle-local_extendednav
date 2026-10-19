@local @local_extendednav
Feature: Change the user menu
  In order to give users the links they need
  As an administrator
  I need to add, hide and nest user menu items

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |

  Scenario: Custom items are added and core items hidden
    Given the following "local_extendednav > nodes" exist:
      | menu     | nodekey               | title   | url          | visibility |
      | usermenu | library               | Library | /library.php | 1          |
      | usermenu | umenu_privatefilesmoo |         |              | 0          |
    When I log in as "student1"
    Then "Library" "link" should exist in the user menu
    And "Private files" "link" should not exist in the user menu
    And "Profile" "link" should exist in the user menu

  @javascript
  Scenario: Child items are shown in a submenu of their parent
    Given the following "local_extendednav > nodes" exist:
      | menu     | nodekey   | title     | url            | parentkey |
      | usermenu | library   | Library   | /library.php   |           |
      | usermenu | catalogue | Catalogue | /catalogue.php | library   |
    When I log in as "student1"
    And I click on "#user-menu-toggle" "css_element"
    And I click on "Library" "link" in the "#usermenu-carousel" "css_element"
    Then "Catalogue" "link" should exist in the "#usermenu-carousel" "css_element"
