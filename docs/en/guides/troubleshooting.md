---
title: Troubleshooting
summary: What to check when something does not work as expected.
order: 70
---

| Problem | Possible cause |
|---|---|
| The menus are unchanged | The plugin is disabled, or the theme is not based on Boost |
| An item does not appear | Its visibility, roles or languages exclude the user, or its parent is hidden. A new item also needs a title and a URL |
| A page is not blocked | **Block URL access** is not set, the user is a site administrator, the URL is external, or another item that the user can see uses the same URL |
| A user is sent to the Dashboard | An item that they cannot see blocks the page they opened. Look for the items with the badge **URL blocked** |
| A user sees "Page not available" | Every fallback page (Dashboard, Site home, My courses and the default home page) is blocked for them |
| The icons of the user menu are missing | The user menu only shows FontAwesome icons; an icon of Moodle without a FontAwesome icon, such as an image, is not shown |
| The FontAwesome tabs of the icon picker are empty | The plugin reads the FontAwesome list from where Moodle 5.3 keeps it; type the class in the field instead |
| An import is refused | The page lists each invalid item and why. Nothing is saved until the whole file is valid |
