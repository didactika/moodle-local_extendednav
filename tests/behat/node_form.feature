@local @local_extendednav
Feature: Edit navigation nodes
  In order to configure nodes without mistakes
  As an administrator
  I need the node form to guide me and check what I enter

  Background:
    Given I log in as "admin"
    And I change window size to "large"
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

  @javascript
  Scenario: The pop-up size is only asked for pop-ups
    When I follow "Add node"
    Then I should not see "Pop-up width (in pixels)"
    And I set the field "Display" to "Pop-up"
    And the field "Pop-up width (in pixels)" matches value "620"
    And the field "Pop-up height (in pixels)" matches value "450"
    And I set the following fields to these values:
      | Node key                 | chat               |
      | Title                    | Chat               |
      | URL                      | /calendar/view.php |
      | Pop-up width (in pixels) | 50                 |
    And I press "Save changes"
    And I should see "Enter a whole number from 100 to 9999."
    And I set the field "Pop-up width (in pixels)" to "800"
    And I press "Save changes"
    And I should see "Node saved."
    And I click on "Edit" "link" in the "chat" "table_row"
    And the field "Display" matches value "Pop-up"
    And the field "Pop-up width (in pixels)" matches value "800"

  @javascript
  Scenario: An icon is chosen from the icon picker
    When I follow "Add node"
    And I click on "Choose icon" "button"
    And I click on "FontAwesome" "button" in the "Choose an icon" "dialogue"
    And I set the field "Search icons" to "home"
    And I click on "[data-value='fa-house']" "css_element" in the "Choose an icon" "dialogue"
    Then the "value" attribute of "input[name='icon']" "css_element" should contain "fa-house"
    And I should see "fa-house" in the "[data-region='iconopen']" "css_element"
    And I click on "[data-region='iconopen']" "css_element"
    And I click on "Moodle icons" "button" in the "Choose an icon" "dialogue"
    And I set the field "Search icons" to "i/home"
    And I click on "[data-value='core:i/home']" "css_element" in the "Choose an icon" "dialogue"
    And the "value" attribute of "input[name='icon']" "css_element" should contain "core:i/home"
    And I click on "[data-region='iconopen']" "css_element"
    And I set the field "Search icons" to "fa-hippo"
    And I click on "[data-value='fa-hippo']" "css_element" in the "Choose an icon" "dialogue"
    And the "value" attribute of "input[name='icon']" "css_element" should contain "fa-hippo"
    And I click on "Remove the icon" "button"
    And I should see "Choose icon" in the "[data-region='iconopen']" "css_element"
    And I should not see "Remove the icon"

  @javascript
  Scenario: Site administration cannot be hidden
    When I click on "Edit" "link" in the "siteadminnode" "table_row"
    And I set the field "Visibility" to "Not visible"
    And I press "Save changes"
    Then I should see "Site administration cannot be hidden, restricted, blocked or limited to some languages."

  @javascript
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

  @javascript
  Scenario: Custom nodes can be deleted and core nodes cannot
    Given the following "local_extendednav > nodes" exist:
      | nodekey | title | url       |
      | help    | Help  | /help.php |
    And I reload the page
    Then "Delete" "button" should exist in the "help" "table_row"
    And "Delete" "button" should not exist in the "myhome" "table_row"
    And I click on "Delete" "button" in the "help" "table_row"
    And I click on "Delete" "button" in the "Delete Help?" "dialogue"
    And I should see "1 changed"
    And I should not see "help" in the "[data-region='tablewrap']" "css_element"

  @javascript
  Scenario: Moodle items that Moodle no longer adds can be deleted
    Given the following config values are set as admin:
      | enabledashboard | 0 |
    And I reload the page
    Then I should see "Unavailable" in the "myhome" "table_row"
    And "Delete" "button" should not exist in the "siteadminnode" "table_row"
    And I click on "Delete" "button" in the "myhome" "table_row"
    And I click on "Delete" "button" in the "Delete myhome?" "dialogue"
    And I should see "1 changed"
    And I should not see "myhome" in the "[data-region='tablewrap']" "css_element"

  @javascript
  Scenario: A node of the site does not offer the conditions that need the activities of a course
    When I follow "Add node"
    And I click on "Add restriction..." "button"
    Then "Date" "button" should exist in the "Add restriction..." "dialogue"
    And "Grade" "button" should not exist in the "Add restriction..." "dialogue"
