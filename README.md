# Extended Navigation for Moodle

[![Moodle Plugin CI](https://github.com/didactika/moodle-local_extendednav/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/didactika/moodle-local_extendednav/actions/workflows/ci.yml)
[![Moodle 4.2 to 4.5](https://img.shields.io/badge/Moodle-4.2%20to%205.2-f98012.svg)](https://moodledev.io/general/releases)
[![Maturity: Stable](https://img.shields.io/badge/maturity-stable-2e7d32.svg)](version.php)
[![License: GPL v3 or later](https://img.shields.io/badge/license-GPLv3%2B-blue.svg)](LICENSE)

**Extended navigation** allows Moodle site administrators to deeply customise the primary
navigation menu. Built tightly upon the PSR-14 Hooks API, it safely modifies Moodle's native
navigation tree to add, hide, redirect, limit by role and re-parent menu items without 
hardcoding theme changes.

Administrators can use its native management interface to deploy custom links, inject
FontAwesome or SVG icons into menu items, and enforce URL-blocking rules that redirect
unauthorised access away from restricted components, establishing a curated user 
experience for targeted cohorts.

## At a glance

| | |
|---|---|
| **Component** | `local_extendednav` |
| **Plugin type** | Local plugin |
| **Supported Moodle releases** | 4.2 through 5.2 |
| **Current maturity** | Stable |
| **Languages** | English |
| **License** | GNU GPL v3 or later |

### Key capabilities

- Granular role-based visibility control to selectively display navigation nodes.
- Full capability to re-parent elements, move them before siblings, or hide native Moodle nodes like Dashboard or Site administration.
- Advanced URL blocking that actively redirects unauthorized access to fallback pages (`/my/`, `/?redirect=0` or a custom configured URL).
- Safe HTML and Javascript injection to open navigation items in a new window (`target="_blank"`) securely.
- Extensive support for adding FontAwesome or core Moodle `pix_icon` visual assets directly into the navigation bar text.
- Administration pages (`manage.php`, `edit.php`) for maintaining the navigation modifications through a Moodle-native UI.
- Immune paths explicitly allow CLI, AJAX, Web Services, and site administrators to bypass any redirection bottlenecks.

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration and Management](#configuration-and-management)
- [Privacy and security](#privacy-and-security)
- [Development](#development)
- [Support](#support)
- [License](#license)

## Requirements

- Moodle 4.2 through 5.2.
- A PHP version supported by the installed Moodle release.
- The new `core\hook\navigation\primary_extend` API (introduced in Moodle 4.2).

The supported range is declared in [`version.php`](version.php). Pull requests
are tested on the endpoints of that range with every PHP and database variant
supported by the project workflow.

## Installation

1. Place the plugin in `local/extendednav` in the Moodle code directory.
2. Sign in as a site administrator.
3. Visit **Site administration > Notifications** and complete the database
   upgrade.
4. Purge caches.

For a command-line installation, run Moodle's normal upgrade command from the
Moodle root:

```bash
php admin/cli/upgrade.php --non-interactive
```

## Configuration and Management

Visit **Site administration > Plugins > Local plugins > Extended navigation** to enable 
or disable the plugin globally and to establish a default global `fallbackurl` for 
blocked queries.

To manage individual nodes, administrators can access the main configuration page (via `manage.php`).
Here, nodes can be defined by specifying:
- **Keys**: Standard or custom keys to target or build the component.
- **Roles**: Selecting exact roles allowed to view or be redirected from specific urls.
- **Before Key / Parent Key**: To attach the element under an existing dropdown or move it before a sibling.
- **Icons**: Adding `fa-` syntax for FontAwesome assets.
- **Target Blank**: Toggling the external link mode safely.

Moodle will cache the resulting navigation tree heavily. When updating complex role assignments
or adding a high amount of URL block rules, ensure the site cache for `local_extendednav` is cleared if changes do not appear immediately.

## Privacy and security

The plugin intercepts navigation and URLs on a structural level but does not collect, 
store or process personal user data. Modifications made to the internal DB structure
only record the rules needed to extend the user interface.

Redirects apply on standard web requests and intentionally ignore system executions like 
Cron, CLI scripts and Web Services to prevent damaging standard platform synchronisation.

## Development

For a local check, use [moodle-plugin-ci](https://github.com/moodlehq/moodle-plugin-ci)
against a checkout of this plugin. Modifying the PSR-14 hooks implementation should be tracked tightly
due to Moodle's aggressive caching of `db/hooks.php`.

## Support

Use [GitHub Issues](https://github.com/didactika/moodle-local_extendednav/issues)
for reproducible bugs and feature proposals. Include the Moodle, PHP and database versions,
a screenshot of the navigation configuration and the exact URL string that fails to redirect.

## License

[GNU GPL v3 or later](LICENSE), the same license used by Moodle.
