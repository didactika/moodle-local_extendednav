@local @local_extendednav
Feature: Let other roles manage the navigation
  In order to delegate the navigation
  As an administrator
  I need to give the manage capability to roles such as Manager

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | manager1 | Manager   | One      |
    And the following "role assigns" exist:
      | user     | role    | contextlevel | reference |
      | manager1 | manager | System       |           |

  Scenario: Managers cannot manage the navigation by default
    When I log in as "manager1"
    And I visit "/admin/search.php?query=navigation"
    Then "Manage navigation" "link" should not exist in the "region-main" "region"

  Scenario: Managers with the capability manage the nodes but not the settings
    Given the following "permission overrides" exist:
      | capability               | permission | role    | contextlevel | reference |
      | local/extendednav:manage | Allow      | manager | System       |           |
    When I log in as "manager1"
    And I visit "/admin/search.php?query=navigation"
    Then "Manage navigation" "link" should exist in the "region-main" "region"
    And I should not see "Apply the navigation nodes and URL blocking"
    And I click on "Manage navigation" "link" in the "region-main" "region"
    And I should see "Primary navigation"
    And I should see "myhome" in the "local-extendednav-manage" "table"
