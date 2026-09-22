# Changelog

All notable changes to this plugin are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial release of the Extended Navigation plugin.
- Seamless Moodle 4.2 through 4.5 support via the PSR-14 Hooks API (`core\hook\navigation\primary_extend`).
- Management interface (`manage.php`) allowing Site Administrators to list, view and sort custom navigation nodes from the database.
- Node editing capabilities (`edit.php`) to configure component keys, URLs, display text, and visual icons dynamically.
- Core Moodle forms support to handle node hierarchy bindings (specifying exact `parentkey` and `beforekey` parameters).
- Advanced visibility engine enabling three explicit states: Visible to all, Hidden entirely, or Visible only to mapped roles.
- `target="_blank"` javascript and HTML injection framework ensuring external navigation links open in new tabs securely.
- Strict URL blocking rules in `lib.php` (`local_extendednav_extend_navigation`) ensuring users without the necessary permissions are intercepted before page rendering.
- Intelligent automatic fallbacks ensuring unauthorized route access redirects to `/my/index.php`, `/?redirect=0` or a manually defined custom `fallbackurl`.
- Cache structures (`\cache::make('local_extendednav', 'nodes')`) established to prevent redundant database querying on every page load.
- Exclusions and bypass rules preventing AJAX queries (`AJAX_SCRIPT`), console executions (`CLI_SCRIPT`) and Site Administrators from being affected by URL navigation limits.
- Core English language support (`lang/en/local_extendednav.php`).
