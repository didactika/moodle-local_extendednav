@local @local_extendednav @_file_upload
Feature: Import and export nodes as YAML
  In order to copy the navigation between sites
  As an administrator
  I need to import YAML files of nodes

  Background:
    Given I log in as "admin"
    And I navigate to "Appearance > Extended navigation > Manage navigation" in site administration

  @javascript
  Scenario: A valid file is imported
    When I follow "Import"
    And I upload "local/extendednav/tests/fixtures/import_valid.yaml" file to "YAML file" filemanager
    And I press "Import"
    Then I should see "Import finished: 2 created, 0 replaced, 0 kept."
    And I should see "help_en" in the "[data-region='tablewrap']" "css_element"
    And I should see "help_es" in the "[data-region='tablewrap']" "css_element"

  @javascript
  Scenario: A file with an invalid node imports nothing
    When I follow "Import"
    And I upload "local/extendednav/tests/fixtures/import_invalid.yml" file to "YAML file" filemanager
    And I press "Import"
    Then I should see "Nothing was imported."
    And I should see "This kind of URL is not allowed."
    And I navigate to "Appearance > Extended navigation > Manage navigation" in site administration
    And I should not see "good_node" in the "[data-region='tablewrap']" "css_element"

  Scenario: The documentation lists every field
    When I follow "Import"
    And I follow "See the YAML format"
    Then I should see "blockaccess"
    And I should see "langs"
    And I should see "tooltip"
