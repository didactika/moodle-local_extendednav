---
id: RQ
status: draft
origin: code
sourceOfTruth: code
---

# Requirements catalogue

## Functional

| ID | Requirement | Priority | Origin | Use cases |
| --- | --- | --- | --- | --- |
| RF-001 | An administrator can add, change and delete items of the primary navigation and of the user menu, including the items of Moodle. | Must | `classes/local/node/manager.php` | UC-001 |
| RF-002 | An administrator can reorder the items, keeping submenus together. | Should | `classes/external/reorder_nodes.php` | UC-002 |
| RF-003 | An administrator can export items to a YAML file and import them from one, checking the whole file first. | Should | `classes/local/io/importer.php` | UC-003 |
| RF-004 | Each user sees the items that their roles and language allow, in both menus. | Must | `classes/hooks.php` | UC-004 |
| RF-005 | A page can be blocked for the users who cannot see its item. | Must | `classes/local/navigation/access_guard.php` | UC-005 |
| RF-006 | The changes to the items are recorded in the activity log, each with the fields it touched and their values before and after. | Could | `classes/event/` | UC-001 |
| RF-007 | An administrator can order, rename, hide and extend the navigation of a course, of the site home, of each kind of activity, of a category and of the site administration. | Must | `classes/local/navigation/injector.php` | UC-006 |
| RF-008 | A category or a course can change a navigation for itself, and everything inside it follows. | Should | `classes/local/node/repository.php` | UC-007 |
| RF-009 | A site administrator sees every node unless the plugin or the node hides it from administrators too. | Must | `classes/local/node/access.php` | UC-004 |
| RF-010 | The export and the import of nodes include the exceptions of categories and courses. | Should | `classes/local/io/importer.php` | UC-003 |
| RF-011 | The management page lists, filters (by visibility, origin and kind of restriction), orders and changes nodes without reloading the page. | Must | `amd/src/manage.js` | UC-001, UC-002, UC-006 |
| RF-012 | Each kind of activity has a navigation of its own, whose items are found in the activities of the site, also the ones a plugin adds to some activities only. | Must | `classes/menu/activity.php`, `classes/local/navigation/discovery.php` | UC-006, UC-008 |
| RF-013 | An activity changes its own navigation from its page, and the changes of a course and of its activities travel in their backups. | Should | `classes/local/navigation/context_scope.php`, `backup/moodle2/` | UC-007 |
| RF-014 | A node of the site administration without a link is a category of the plugin, which holds links and other categories; at the top it is a tab when it holds a category. | Should | `classes/menu/admintree.php` | UC-006 |
| RF-015 | A user arranges their own Preferences page, and the site arranges it for everyone, as folders of sections and options. | Should | `classes/local/navigation/preferences_injector.php` | UC-009 |
| RF-016 | The entries of the activity log can be kept for a time the site sets, and a task that follows that setting deletes the older ones. | Could | `classes/task/clean_activity_log.php` | UC-010 |
| RF-017 | The defaults of the backups and of the restores decide whether the changes of a course and its activities are included. | Should | `settings.php`, `backup/moodle2/` | UC-011 |
| RF-018 | Everything the management page does can be done through web services, gathered in one service. | Should | `classes/external/`, `db/services.php` | UC-012 |
| RF-019 | In a category, a course or an activity, seeing the navigation and each kind of change have their own capability. | Should | `db/access.php`, `classes/local/navigation/context_scope.php` | UC-007 |
| RF-020 | What a file holds is chosen among navigations, kinds of activity, users, courses and categories, found ten at a time; an import leaves out, with a warning, what the site does not have. | Should | `classes/form/export_form.php`, `classes/external/search_export.php`, `classes/local/io/importer.php` | UC-003, UC-012 |
| RF-021 | A node without a URL groups others, as a submenu, a section, a category or a tab, and the lists show it as a folder. | Should | `classes/local/listing/nodes.php`, `classes/form/node_form.php` | UC-001, UC-006 |
| RF-022 | Guided tours explain the Navigation page of a course or an activity, the page where a user arranges their preferences and the form of a node; they are imported when the plugin is installed or upgraded and managed on the tours page of Moodle. | Could | `tours/`, `lib.php` | UC-001, UC-009 |
| RF-023 | Hiding items can also block their URLs, for whoever may restrict them. | Should | `classes/local/node/manager.php` | UC-005 |

## Non-functional

| ID | Quality attribute | Requirement | Measure | Priority |
| --- | --- | --- | --- | --- |
| RNF-001 | Security | Managing the navigation of the site needs its own capability, which no role has by default; in a category, a course or an activity, editing teachers and managers hold the capabilities of the context, and every user holds the one that arranges their own Preferences page. | `local/extendednav:manage` with no archetype; `db/access.php` | Must |
| RNF-002 | Availability | The plugin never leaves every user without a landing page. | `validator::validate` | Must |
| RNF-003 | Privacy | The only personal data the plugin keeps is how a user arranged their own Preferences page, which is exported and deleted with the privacy API and with the user. | `classes/privacy/provider.php`, `classes/observer.php` | Must |
| RNF-004 | Integrity | An import changes nothing if any item is invalid. | `importer::import` | Must |
| RNF-005 | Performance | A page that has nothing customised in its navigation reads nothing but the cache of the plugin. The pages of the users never build another page: they record what they show, and the items of the kinds of activity are looked for by a task at night or on request. | `injector::apply`, `secondary::record_seen`, `discovery::kind` | Must |
| RNF-006 | Performance | No query runs inside a loop on a path that grows with the data: restoring, exporting, importing, upgrading and the lists read in bulk, and the activity log is cleaned by time, in batches, through its index. | `tests/performance_test.php` (thousands of nodes), `classes/task/clean_activity_log.php` | Must |
| RNF-007 | Security | The page for a user with no page to go to belongs to that user and needs no capability; a guest is sent to log in. | `restricted.php` | Should |

## Business rules

Rules live in the [domain model dictionary](../domain/README.md#business-rules). Rules that change behaviour are repeated here with their ID.
