---
id: DOM
status: draft
origin: code
sourceOfTruth: code
---

# Domain model

## Purpose

The menus of Moodle are the same for everyone and only partly configurable. Extended navigation lets an administrator shape the two menus every user sees and decide, item by item, who sees it and who may open its page. This model names the concepts behind that.

## Hierarchy of entities

The vertical position of an entity expresses its importance to the project objective. It is not UML inheritance or composition.

| Level | Meaning | Entities | Why they are here |
| --- | --- | --- | --- |
| 1 | Core: the project cannot exist without them | Node | The plugin exists to describe an item of a menu and its rules. |
| 2 | Structural: depend directly on the core | Menu, Visibility | A node belongs to a menu and has a visibility. |
| 3 | Detail: qualify or specialise level 2 | Role, Language, Submenu | They limit who sees a node and how nodes nest. |
| 4 | Support: catalogues, audit, configuration | ActivityRecord, ImportFile | They record what changed and carry navigations between sites. |

## Diagrams

![Domain model](./domain-model.svg)

![Simplified domain model](./domain-model-simple.svg)

## Entity dictionary

| Entity | Level | Definition | Key attributes | Omitted attributes and why |
| --- | --- | --- | --- | --- |
| Node | 1 | An item of the primary navigation or of the user menu, added by the plugin or an item of Moodle that the plugin changes. | `nodekey`, `menu`, `title`, `url`, `visibility`, `blockaccess` | The rest are in the [data model](../design/data-model/data-model.svg). |
| Menu | 2 | One of the two menus: the primary navigation or the user menu. | `primary`, `usermenu` | It is a class of the plugin, not a table. |
| Visibility | 2 | Who sees a node: everyone, the users with some roles, or nobody. | `visibility` | |
| Role | 3 | A role that may see a restricted node. | role shortname | It is Moodle's `role`. |
| Condition | 3 | An access restriction of Moodle on a node: a date, a group, a grouping, a profile field or a condition of a plugin. | `availability` | It is Moodle's availability tree, kept as JSON in the node. |
| Language | 3 | A language to which a node is limited. | language code | It is a list of codes in the node. |
| Submenu | 3 | The nodes shown under a parent node, one level deep. | `parentkey` | |
| ActivityRecord | 4 | An event of the log of Moodle written when a node is created, changed or deleted. | event, user, time | It is Moodle's log. |
| ImportFile | 4 | A YAML file that carries nodes between sites. | the list `nodes` | It is not stored. |

## Business rules

| ID | Rule | Entities | Source |
| --- | --- | --- | --- |
| RN-001 | A node is shown to a user when its visibility allows them and its languages include their current language; a hidden parent hides its submenu. | Node, Role, Language | `access::is_shown` |
| RN-002 | A node key is unique within its menu, and only letters, numbers, underscores and hyphens are allowed. | Node | `validator::validate` |
| RN-003 | A change that would leave every user without a landing page is refused, and Site administration can never be hidden or nested. | Node | `validator::validate` |
| RN-004 | A blocked URL sends the user to the first fallback page they may open; when none is allowed, to an explanation page. | Node | `access_guard::evaluate` |
| RN-005 | Site administrators, external URLs and the pages of administration, login and plugins are never blocked. | Node | `access_guard::is_immune` |
| RN-006 | The whole of an import file is checked before anything is saved. | ImportFile | `importer::import` |
| RN-007 | Reordering keeps a parent with its submenu, and a submenu item moves only among its siblings. | Node, Submenu | `manager::reorder` |
| RN-008 | An item of Moodle with empty fields keeps the values of Moodle. | Node | `text` |
| RN-009 | A node of a secondary navigation is found when a page shows it, once, and from then on it has a row that can be changed. | Node, Menu | `secondary::record_seen` |
| RN-010 | In the site administration every category and page, at any depth, can be renamed, hidden and ordered; a link of the plugin goes in a category; its nodes are hidden from administrators too by default. | Node, Menu | `admintree::apply` |
| RN-011 | A site administrator sees every node, unless the plugin or the node hides it from administrators too. | Node, Role | `access::is_allowed` |
| RN-012 | The nearest scope to a page wins for each node: the course, then the categories around it, then the site. | Node, Scope | `repository::get_scoped_nodes` |
| RN-013 | When a scope changes the order of any node, it sets the order of its whole navigation. | Node, Scope | `repository::get_scoped_nodes` |
| RN-014 | Deleting a node of the site deletes its exceptions. | Node, Scope | `manager::delete` |
| RN-015 | A node is shown only when its visibility, its roles and its access restrictions (Moodle's Restrict access) all let the user see it; a node of a course is checked in that course. | Node, Condition | `availability::allows` |
| RN-016 | The dates of the restrictions make a node scheduled before its start and ended after its end; it appears and disappears on its own. | Node, Condition | `availability::period` |
| RN-017 | The exceptions of a course travel in the backup of the course; the file of the site holds the nodes of the site and the exceptions of its categories. | Node, Scope | `backup_local_extendednav_plugin`, `exporter::export_file` |
| RN-018 | A category or a course changes its navigations on its own page, by whoever manages the navigation of the site or holds local/extendednav:managecontext in that context; the site administration only lists them. | Node, Scope | `context_scope::require_manage`, `node_service::require_scope` |

## Lifecycles

### Node

![Lifecycle of a node](./entity-states/entity-states.svg)
