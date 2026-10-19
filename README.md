<div align="center">

<img src="pix/icon.svg" width="96" alt="Extended navigation icon">

# Extended navigation for Moodle

*Shape the primary navigation and the user menu, and keep users on the pages meant for them*

[![Release](https://img.shields.io/github/v/release/didactika/moodle-local_extendednav?style=flat-square)](https://github.com/didactika/moodle-local_extendednav/releases)
[![Moodle](https://img.shields.io/badge/Moodle-4.5_to_5.2-f98012?style=flat-square&logo=moodle&logoColor=white)](https://moodle.org)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777bb4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPL_v3-blue?style=flat-square)](LICENSE)

[Overview](#overview) • [Installation](#installation) • [Usage](#usage) • [Configuration](#configuration) • [Troubleshooting](#troubleshooting)

</div>

Extended navigation (`local_extendednav`) is a Moodle local plugin that lets administrators manage the two menus every user sees: the primary navigation at the top of the page and the user menu under the user's picture. Add items, rename, re-link, reorder, nest or hide Moodle's own items, show items only to some roles or languages, and stop users from opening the pages of items they cannot see.

> [!IMPORTANT]
> **The site needs a theme based on Boost.** The plugin changes the primary navigation and the user menu that Boost and its child themes display. Themes that do not use them, such as Classic, show no changes.

> [!NOTE]
> Moodle's own items stay translated. Leave the title and URL of a Moodle item empty and the plugin keeps using Moodle's values; the form shows them as placeholders.

## Overview

Moodle's menus are the same for everyone and only partly configurable. Extended navigation gives each item its own visibility, roles, languages and blocking rule, so students, teachers and staff each see the navigation that fits them.

### How an item is shown

Every item has a visibility:

| Visibility | In the menu | With **Block URL access** |
|---|---|---|
| Visible to everyone | Shown to every logged-in user | Not available |
| Restricted | Shown to users with one of the allowed roles, in any context | Users without those roles cannot open its URL |
| Not visible | Hidden from everyone | Nobody can open its URL |

An item limited to **languages** is only shown to users whose current language is one of them (Spanish `es` also covers Spanish - Mexico `es_mx`). Languages never block a URL, because users can change their language themselves. To translate an item, create one item per language.

### How blocking works

A user who opens a blocked URL is sent to the first of these pages they are allowed: the Dashboard, the Site home, My courses, then the default home page when it is set to a URL. When all of them are blocked for the user, they see a page explaining that the page is not available.

- An item the user can see opens its URL again, even when another item blocks it.
- A URL with query parameters only blocks pages with those parameters: `/course/view.php?id=5` blocks course 5, not course 6.
- External URLs are never blocked. Site administrators are never blocked, and neither are the admin, login and plugin pages.

### Features

- **Two menus:** the primary navigation and the user menu, each with Moodle's own items listed and editable.
- **Submenus:** one level of submenus in both menus. Dragging a parent moves it with its submenu items; submenu items only move among their siblings.
- **Drag-and-drop ordering:** with a keyboard-accessible move dialog.
- **Per-item options:** title, tooltip, URL, FontAwesome or Moodle icon, and how the link opens: in the current window, a new window or a pop-up of a chosen size, as Moodle's URL resource offers.
- **Safety checks:** the form refuses changes that would leave every user without a landing page, and Site administration can never be hidden or nested.
- **Moodle's menu settings:** the items of **Custom menu items** and **User menu items** are imported, with their tooltips and language codes, and kept in sync.
- **YAML import and export:** copy the navigation between sites. The whole file is checked before anything is saved.
- **Activity log:** who created, changed or deleted each item, and when.
- **Delegation:** the `local/extendednav:manage` capability lets roles such as Manager manage the navigation.

## Installation

**Requirements:** Moodle 4.5 to 5.2, with the PHP version your Moodle release requires, and a theme based on Boost.

**From a release:** download the latest release ZIP, go to **Site administration > Plugins > Install plugins**, upload the file and follow the prompts.

**From Git:**

```bash
cd /path/to/moodle
# Moodle 4.5 and 5.0
git clone https://github.com/didactika/moodle-local_extendednav.git local/extendednav
# Moodle 5.1 and later
git clone https://github.com/didactika/moodle-local_extendednav.git public/local/extendednav
php admin/cli/upgrade.php
```

The installation creates one row for each of Moodle's own items and imports the items already defined in **Custom menu items** and **User menu items**.

## Usage

### 1. Open the management page

Go to **Site administration > Appearance > Extended navigation > Manage navigation**. The **Primary navigation** and **User menu** tabs list the items of each menu, with filters by title, node key or URL, by parent and by visibility.

### 2. Add or change items

Select **Add node**, or the edit icon of an item:

- For a new item, choose a **Node key** (letters, numbers, underscores and hyphens), a **Title** and a **URL**.
- For one of Moodle's items, change only what you need; empty fields keep Moodle's values.
- Choose the **Visibility**, the **Allowed roles** for a restricted item, and whether to **Block URL access**.
- Optionally limit the item to some **Languages**, add a **Tooltip** or an **Icon**, and choose how it opens with **Display**: **Current window**, **New window** or **Pop-up** (with its width and height, 620 × 450 pixels by default).
- Choose a **Parent** to show the item in that item's submenu.

An item is shown as the active tab on its page, also when the page adds parameters to its URL (an item linking to `/user/profile.php` is active on `/user/profile.php?id=2`).

> [!NOTE]
> Moodle's items are the ones Moodle itself adds to the primary navigation, including the tabs other plugins add, and the default **User menu items**. New primary items are found whenever the management page is opened. Items Moodle stops adding, for example when they are turned off in **Site administration > Appearance > Navigation**, removed by the theme, or come from an uninstalled plugin, are marked **Currently unavailable** and are not added by the plugin either. They can be deleted, and come back as new items if Moodle adds them again.

> [!TIP]
> An item with submenu items opens its submenu instead of following its URL. The list marks its URL with a warning icon; the URL still counts for blocking.

### 3. Order the items

Drag an item by the handle in the **Order** column, or select the handle for a list of positions. Moving a parent moves its submenu items with it. An item that leaves its parent stays right below that parent's group. Ordering is off while a filter is applied.

### 4. Copy the navigation to another site

Use **Export all**, or select items and **Export selected**, to download a `.yaml` file. On the other site, use **Import** and upload the file (`.yaml` or `.yml`), choosing whether existing items are kept or replaced. Each item keeps its position (`order`), so a whole menu is imported in the same order; items without one keep their place, or go last when they are new. **See the YAML format** on the import page describes every field and offers an example file.

## Configuration

Settings are located at **Site administration > Appearance > Extended navigation > Settings**.

| Setting | Default | Description |
|---|---|---|
| Enable | On | Apply the items and URL blocking. When off, Moodle's navigation is shown unchanged and no page is blocked |

### Capabilities

| Capability | Default roles | Allows the user to |
|---|---|---|
| `local/extendednav:manage` | None | Manage the items, import, export and see the activity log, in the system context |

Users with this capability find the management page in the same place as administrators. The settings page requires `moodle/site:config`.

> [!WARNING]
> Whoever holds `local/extendednav:manage` decides every user's menu and which pages are blocked. Give it only to people you would trust with the site's navigation.

### Privacy and backup

- **Privacy:** the plugin stores navigation items, not personal data, and implements the Privacy API as a null provider.
- **Activity:** changes to items are recorded as events in the site's log store, which the activity log reads, so they follow your log retention settings.
- **Backup:** items are site configuration and are not part of course backups. Use the YAML export to copy them.

## Troubleshooting

| Problem | Possible cause |
|---|---|
| The menus are unchanged | The plugin is disabled, or the theme is not based on Boost |
| An item does not appear | Its visibility, roles or languages exclude the user, or its parent is hidden. A new item also needs a title and a URL |
| A page is not blocked | **Block URL access** is not set, the user is a site administrator, the URL is external, or another item the user can see uses the same URL |
| A user is sent to the Dashboard | An item they cannot see blocks the page they opened. Look for items with a **URL blocked** badge |
| A user sees "Page not available" | Every fallback page (Dashboard, Site home, My courses and the default home page) is blocked for them |
| User menu icons are missing | The user menu only shows FontAwesome icons (`fa-...`) |
| An import is refused | The page lists each invalid item and why. Nothing is saved until the whole file is valid |

## Getting help

To report a bug or request a feature, please open an [issue](https://github.com/didactika/moodle-local_extendednav/issues). Include your Moodle and PHP versions, the theme, and the items involved (an export helps). Do not include passwords, tokens or personal data.

Report security problems privately through the repository's [security advisory form](https://github.com/didactika/moodle-local_extendednav/security/advisories/new).
