# Changelog

All notable changes to this plugin are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

<!--
.github/workflows/release.yml reads this file: when $plugin->release changes in version.php on `main` or on a
MOODLE_XXX_STABLE branch, it uses everything under the "## [<that release>]" heading as the GitHub Release body.
Keep an "## [Unreleased]" section above the latest release and rename it when the next release is cut.
-->

## [Unreleased]

### Added

- A management page in the design system: the navigations in a column (a select on a narrow screen), a search, a menu of visibilities with their counts, a list of fixed height with a pager, round action buttons, a floating bar for the selected nodes and a menu to choose the scope. Every action changes the list in place, without reloading the page.
- Nodes are moved with arrow buttons that work with the keyboard, instead of by dragging.
- Site administrators see every node by default, even one that is hidden or restricted to roles they do not have, and their row says so. A setting of the plugin and an option of each node apply the rules to administrators too.
- A node of a secondary navigation can be kept in the More menu or in the bar.
- The export and the import include the exceptions of categories and courses (`scope`), and the new fields `placement` and `hideadmins`.
- The secondary navigations of Moodle can be ordered, renamed, re-linked, hidden, restricted to roles or languages, placed in the More menu or kept out of it, and extended with nodes of your own: the navigation of a course, of the site home, of an activity, of a category and of the site administration (this one only to hide and order).
- A node can be an exception for a category or a course, and everything inside it: the nearest one to the page wins.
- The nodes of each navigation are found when a page shows them, once per node, so listing them adds no page build, no query when nothing is customised and no measurable time to a page.

## [1.0.0-m503] - 2026-10-19

### Added

- First public release for Moodle 5.3, with every feature of 1.0.0-m405.

### Fixed

- Icons and "New window" or "Pop-up" items in the primary navigation: Moodle 5.3 draws it with React, which prints
  the text of top-level items as plain text, so they showed as code. They are shown again in the browser, also when
  they move into the "More" menu.

## [1.0.0-m405] - 2026-10-15

### Added

- First public release.
- Management page (Site administration > Appearance > Extended navigation > Manage navigation) for the primary
  navigation and the user menu: add items, and rename, re-link, reorder, nest or hide Moodle's own items.
- Moodle's own items keep Moodle's translated title and URL until they are overridden; the form shows them as
  placeholders.
- Moodle's items are read from Moodle: the primary navigation items it adds, including other plugins' tabs, and
  the default user menu items. Items Moodle stops adding (for example turned off in Site administration >
  Appearance > Navigation) are marked "Currently unavailable", never added, and can be deleted.
- Items are shown as the active tab on their page, also when the page adds parameters to the URL.
- Visibility per item: visible to everyone, restricted to roles (by role shortname), or not visible.
- "Block URL access": users who cannot see an item are sent away from its URL, to the Dashboard, the Site home,
  My courses or the default home page, or to an explanation page when all of them are blocked.
- Languages per item: show an item only to users of some languages. Languages never affect URL blocking.
- Tooltips, an icon picker with Moodle's icons (including those of its plugins) and the FontAwesome icons Moodle
  ships, and how every item opens: in the current window, a new window or a pop-up of a chosen size, as Moodle's
  URL resource offers.
- One level of submenus in the primary navigation and the user menu.
- Drag-and-drop ordering that keeps submenu items with their parent.
- Synchronisation with Moodle's "Custom menu items" and "User menu items" settings, including tooltips and
  language codes.
- YAML import and export (`.yaml`, also `.yml` on import), validating the whole file before anything is saved and
  keeping the position of every item.
- Activity log of item changes, based on the standard log.
- Capability `local/extendednav:manage`, so roles such as Manager can manage the navigation.
