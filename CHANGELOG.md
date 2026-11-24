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

- A management page in the design system: the navigations in a column (a select on a narrow screen), a search, a menu of visibilities with their counts, a list as wide as the page and of fixed height, round action buttons, a floating bar for the selected nodes and a menu to choose the scope. Every action changes the list in place, without reloading the page.
- Nodes are ordered by dragging them by their handle, or with the arrow keys on the handle; the nodes selected among its siblings move with it. The lists have no pages, and the head checkbox selects every row.
- The bar of the selection offers Show, Hide and Delete only when a selected node allows them, and asks before acting, listing the nodes left as they are. Hiding asks whether to hide from administrators too, which is on by default in the site administration.
- Each node says whether Moodle manages it or it was added, and a menu Origin filters by it. The visibility shows a badge for each kind of restriction, with the details in a tooltip.
- An empty secondary navigation finds its nodes from a page of the site, without leaving the management page.
- The site administration groups the items of the General tab of Moodle under General.
- One On and Off button next to the name of each navigation applies its changes or leaves the navigation as Moodle draws it. The settings page of the plugin is gone.
- Site administrators see every node by default, even one that is hidden or restricted to roles they do not have, and their row says so. An option of each node applies its rules to administrators too.
- The secondary navigations of Moodle can be ordered, renamed, re-linked, hidden, restricted to roles or languages, placed in the More menu or kept out of it, and extended with nodes of your own: the navigation of a course, of the site home, of an activity and of a category.
- The whole site administration can be shaped: every category and every page, at any depth, can be renamed, hidden and ordered, and links of your own can be added to a category. The tree is browsed one level at a time, with a breadcrumb, and a search looks in every level.
- A node can be an exception for a category or a course, and everything inside it: the nearest one to the page wins.
- The menu Export offers this navigation or every navigation in one file. Both have one format, which the import reads: the navigations and whether each one is on, the exceptions of categories and courses (`scope`), the nodes of Moodle (`native`), and the fields `placement` and `hideadmins`. A node of Moodle that the site does not have is left out with a warning.
- The form of a node is in sections (the node, its look, who sees it, where it goes) and asks only what applies to the navigation.
- The activity log in the design system: the navigations in a column, a search by node key or person, a menu of the actions with their counts, a menu of the date with presets, the newest changes first, and the same list and pager, without reloading the page.
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
