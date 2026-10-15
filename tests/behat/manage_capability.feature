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
    And I visit "/local/extendednav/manage.php"
    Then I should see "Access denied"

  Scenario: Managers with the capability manage the nodes but not the settings
    Given the following "permission overrides" exist:
      | capability               | permission | role    | contextlevel | reference |
      | local/extendednav:manage | Allow      | manager | System       |           |
    When I log in as "manager1"
    And I navigate to "Appearance > Extended navigation > Manage navigation" in site administration
    Then I should see "Primary navigation"
    And I should see "myhome" in the "local-extendednav-manage" "table"
    And I visit "/admin/settings.php?section=local_extendednav_settings"
    And I should not see "Apply the navigation nodes and URL blocking"
