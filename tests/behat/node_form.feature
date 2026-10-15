@local @local_extendednav
Feature: Edit navigation nodes
  In order to configure nodes without mistakes
  As an administrator
  I need the node form to guide me and check what I enter

  Background:
    Given I log in as "admin"
    And I navigate to "Appearance > Extended navigation > Manage navigation" in site administration

  @javascript
  Scenario: Core nodes show their Moodle title and URL as placeholders
    When I click on "Edit" "link" in the "myhome" "table_row"
    Then the "placeholder" attribute of "Title" "field" should contain "Dashboard"
    And the "placeholder" attribute of "URL" "field" should contain "/my/"
    And I should see "This is a core Moodle node."

  @javascript
  Scenario: "Block URL access" is only offered where it can apply
    When I follow "Add node"
    Then I should not see "Block URL access"
    And I set the field "Visibility" to "Not visible"
    And I set the field "URL" to "/mod/page/view.php?id=2"
    And I should see "Block URL access"
    And I set the field "URL" to "https://docs.moodle.org/"
    And I should not see "Block URL access"

  @javascript
  Scenario: The form explains that languages have no effect on hidden nodes
    When I follow "Add node"
    And I set the field "Languages" to "English"
    Then I should not see "This item is hidden from everyone"
    And I set the field "Visibility" to "Not visible"
    And I should see "This item is hidden from everyone"

  Scenario: Site administration cannot be hidden
    When I click on "Edit" "link" in the "siteadminnode" "table_row"
    And I set the field "Visibility" to "Not visible"
    And I press "Save changes"
    Then I should see "Site administration cannot be hidden, restricted, blocked or limited to some languages."

  Scenario: Every fallback page cannot be blocked for everyone
    Given the following "local_extendednav > nodes" exist:
      | nodekey | visibility | blockaccess |
      | myhome  | 0          | 1           |
      | home    | 0          | 1           |
    And I reload the page
    When I click on "Edit" "link" in the "mycourses" "table_row"
    And I set the following fields to these values:
      | Visibility       | Not visible |
      | Block URL access | 1           |
    And I press "Save changes"
    Then I should see "leaving users with no page to land on"

  Scenario: Custom nodes need a title and a URL
    When I follow "Add node"
    And I set the field "Node key" to "help"
    And I press "Save changes"
    Then I should see "Required" in the "Title" "form_row"
    And I should see "Required" in the "URL" "form_row"

  Scenario: Dangerous URLs are refused
    When I follow "Add node"
    And I set the following fields to these values:
      | Node key | help                |
      | Title    | Help                |
      | URL      | javascript:alert(1) |
    And I press "Save changes"
    Then I should see "This kind of URL is not allowed."

  Scenario: Custom nodes can be deleted and core nodes cannot
    Given the following "local_extendednav > nodes" exist:
      | nodekey | title | url       |
      | help    | Help  | /help.php |
    And I reload the page
    Then "Delete" "link" should exist in the "help" "table_row"
    And "Delete" "link" should not exist in the "myhome" "table_row"
    And I click on "Delete" "link" in the "help" "table_row"
    And I should see "1 node(s) deleted."
    And I should not see "help" in the "local-extendednav-manage" "table"
