@local @local_extendednav
Feature: Shape the site administration
  In order to give the administrators a site administration that fits the site
  As an administrator
  I need to rename, hide and order its categories and pages, at any depth, and add links of my own

  Background:
    Given I log in as "admin"
    And I change window size to "large"
    And I navigate to "Appearance > Extended navigation > Manage navigation" in site administration
    And I click on "Site administration" "link" in the "[data-region='origins']" "css_element"

  @javascript
  Scenario: The tree is browsed one level at a time
    Then I should see "Users" in the "[data-region='tablewrap']" "css_element"
    And I click on "Open Users" "button"
    And I should see "Accounts" in the "[data-region='tablewrap']" "css_element"
    And I should see "Users" in the "[data-region='path']" "css_element"
    And I click on "Site administration" "button" in the "[data-region='path']" "css_element"
    And I should see "Courses" in the "[data-region='tablewrap']" "css_element"

  @javascript
  Scenario: A category is renamed and the site administration shows the new name
    When I click on "Open Users" "button"
    And I click on "Edit Accounts" "link"
    And I set the field "Title" to "User accounts"
    And I press "Save changes"
    And I visit "/admin/category.php?category=users"
    Then I should see "User accounts"

  @javascript
  Scenario: A link of the plugin needs its category
    When I follow "Add node"
    And I set the following fields to these values:
      | Node key | staffguide   |
      | Title    | Staff guide  |
      | URL      | /my/         |
    And I set the field "Parent" to "Users"
    And I press "Save changes"
    And I visit "/admin/category.php?category=users"
    Then I should see "Staff guide"

  @javascript
  Scenario: A node without a link is a category of the plugin, and Add node starts in the level open
    When I follow "Add node"
    And I set the following fields to these values:
      | Node key | didactika       |
      | Title    | Didactika tools |
    And I press "Save changes"
    And I click on "Open Users" "button"
    And I follow "Add node"
    Then the field "Parent" matches value "Users"
    And I set the following fields to these values:
      | Node key | handbook |
      | Title    | Handbook |
      | URL      | /my/     |
      | Parent   | Didactika tools |
    And I press "Save changes"
    And I visit "/admin/category.php?category=didactika"
    And I should see "Didactika tools"
    And I should see "Handbook"
