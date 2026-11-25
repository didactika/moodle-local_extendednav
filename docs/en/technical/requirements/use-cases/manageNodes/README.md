---
id: UC-001
name: manageNodes
title: Add, change or delete an item
actor: Administrator
package: Navigation
status: draft
origin: code
sourceOfTruth: code
requirement: RF-001
includes: 
extends: 
---

# UC-001 Add, change or delete an item

| Overview | Requirements | Analysis | Design | Implementation | Tests |
| --- | --- | --- | --- | --- | --- |
| [Use cases](../../use-case-overview.svg) | **Requirements** | [Analysis](../../../analysis/use-cases/manageNodes/README.md) | [Design](../../../design/use-cases/manageNodes/README.md) | [Implementation](../../../implementation/use-cases/manageNodes/README.md) | [Tests](../../../implementation/use-cases/manageNodes/README.md#tests) |

## Use-case information

| Field | Value |
| --- | --- |
| Actor | Administrator |
| Package | Navigation |
| Goal | Shape the primary navigation and the user menu item by item. |
| Type | Primary, essential |
| Level | User goal |
| Requirement | RF-001 |
| Domain entities | Node, Menu, Visibility, Role, Language, ActivityRecord |

## Context

An administrator adds items to a menu, changes the items of Moodle or removes items, and decides who sees each one and whether its page is blocked.

## Preconditions

- The administrator holds `local/extendednav:manage`, or is a site administrator.

## Postconditions

- Success: the item is saved, the menu shows it at once and the change is logged.
- Failure: nothing is saved and the form shows what to fix.

## Main flow

| Step | Actor | System |
| --- | --- | --- |
| 1 | Opens the management page and chooses **Add node**, or the edit icon of an item. | Shows the form, with the values of Moodle as placeholders for its items. |
| 2 | Fills in the key, title, URL, icon, visibility, roles, languages and parent, and saves. | Validates the item against the others of its menu. |
| 3 |  | Saves it in `local_extendednav` and triggers `node_created` or `node_updated`. |
| 4 | Deletes an item. | Deletes it and triggers `node_deleted`. |

## Alternative flows

| ID | Starts at step | Condition | Behaviour | Ends |
| --- | --- | --- | --- | --- |
| A1 | 2 | The key is repeated or not valid, or the URL or the title is missing. | Shows the error under the field; nothing is saved. | Step 1 |
| A2 | 2 | The change would leave every user without a landing page. | Refuses it. | Step 1 |
| A3 | 2 | The item is Site administration and is hidden or nested. | Refuses it. | Step 1 |
| A4 | 1 | The item is of the primary navigation or of the user menu. | **Type** offers Link or Submenu. A submenu has no URL and holds links; only a submenu is offered as the parent of a link, and with none the parent is not shown. | Step 2 |
| A5 | 1 | The item is of the site administration. | **Type** offers Tab, Category or Page, and the parent offered depends on the type; a tab that holds pages only shows them in a section of its own, and while it holds nothing it is listed with a note. | Step 2 |
| A6 | 1 | The item is a section or an option of the Preferences page. | **Type** offers Link or Section; an option goes in any section. | Step 2 |
| A7 | 1 | The navigation is a secondary navigation. | The form has no icon: those navigations draw none. | Step 2 |
| A8 | 4 | The item is hidden. | The dialog also offers to hide it from administrators and to block its URL, for whoever may restrict it. | Step 4 |

## Business rules

| ID | Rule |
| --- | --- |
| RN-002 | A node key is unique within its menu. |
| RN-003 | A change that would leave every user without a landing page is refused. |
| RN-008 | An item of Moodle with empty fields keeps the values of Moodle. |
| RN-034 | A node without a URL groups others: a submenu, a section, a category or a tab; only a group is offered as the parent of another node. |
| RN-035 | A search or a filter of the list looks in every level of the folders. |

## Specification

Behaviour source of truth: [specification.puml](./specification.puml)

![Specification](./specification.svg)

## Wireframes

![Wireframes](./wireframes.svg)
