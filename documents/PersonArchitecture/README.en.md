# Person Architecture

## Context

The current data model treats different "kinds of people" inconsistently:

- **Pastor** is a first-class entity with its own `pastors` table and a time-bounded `pastor_assignments` join to churches.
- **Member, Interested, etc.** are just rows in the `users` table differentiated by a `member_type` enum column.
- **Children, Teenagers, Visitors** don't exist as concepts at all.
- **User** carries authentication identity, but the same human can be (a) a member of one church, (b) an admin of another, (c) the parent of two children who don't have logins, and the schema has no language for any of that.
- **Family relationships** between people aren't modeled — the church organization is fundamentally about families and we have no way to express "this person is the spouse of that person" or "these three users are siblings."
- **Councils, Ministries, Commissions** — the organizational structures these people belong to and the functions they hold within them — don't exist either.
- **Districts** — a structural level between Ecclesiastical Region and individual Church — don't exist either, but the church wants to model them so that ministries and councils can operate at a district scope.

The `template-new-galileo` project already solves the first half of this problem with a `Person` + `PersonRelationship` model and a JSON-based `natures` discriminator. That convention is the one we'll mirror here, extending it for methodist-specific needs (4-level org hierarchy, family-tree query helpers, parental supervision).

This document is the architectural plan; it does not implement anything. Phases 1–7 below are the implementation sequence we'll execute against this plan.

---

## Decisions already aligned

These are the eight big calls — locked in across three rounds of conversation before this plan rewrite:

0. **No data to migrate; one migration file per table (standard Laravel pattern).** The project is at the very beginning; the only row that needs to survive across schema changes is a single global-admin user. Every other table can be dropped, recreated, or have columns removed outright. There are no backfill commands, no "keep the old column for transition" choreography, no rollback paths. Each table gets its own create migration; deprecated tables (`pastors`, `pastor_assignments`) get a drop migration; deprecated user columns (`users.member_type`, `users.phone`, `users.birthdate`, `users.church_id`) are removed via a single modify migration that also adds `users.person_id`. The whole batch ships in Phase 1 so the schema is correct end-state from day one even though most tables won't have UI until later phases. Run `php artisan migrate:fresh --seed` after applying.

1. **`users.person_id` is the canonical link** and **NOT NULL** in v1. Every authenticated User has exactly one Person; the `User` row carries the FK. People can still exist without a User (visitors, family members who only appear in someone else's tree) — the Person row is the source of truth, the User row is the optional credential layer. Permissions stay on User; identity context comes from `User->person`.

2. **Per-role data is JSON on the `Person` row** — same as the template. `persons.natures` is a JSON array (`["member", "pastor"]`); `persons.additional_data` is a JSON object keyed by nature (`{ "member": {...}, "pastor": {...} }`). No separate table per role.

3. **Functions and roles are first-class managed tables.** `functions` lists named positions (Lead, Co-Lead, Secretary, Treasurer, Member, Main Pastor, Auxiliary Pastor, Seminarist, …) with metadata (which contexts the function applies to, max concurrent holders). `roles` is reserved for the role qualifier inside an assignment when relevant (currently: pastor roles). Both are referenced by FK from `person_role_assignments`.

4. **`person_role_assignments` is the time-bounded layer** for any role/function holding. Generalizes the existing `pastor_assignments` pattern. Each row carries the four hierarchy-level FKs (region, district, church) so its scope is unambiguous; group_id when the assignment is "function within a group"; church_id alone when the assignment is "pastor at a church."

5. **Person belongs to one church (data ownership).** Each Person has a `managing_church_id` that says which church owns the record and is allowed to manage it. Multi-church involvement (a person who attends two congregations) is expressed via assignments and group memberships, not by editing the managing church.

6. **No `parent_id` self-FK on Person, no UUID column.** The template's `parent_id` was for organizational hierarchy (subsidiary companies); methodist doesn't need that — person hierarchy is family, and family lives in `person_relationships`. UUID was for external/URL use; methodist-app uses bigint IDs throughout, no need to deviate.

7. **Family relationships store only the explicit primitives**; derived relationships (siblings, grandparents, uncles, etc.) are computed by query helpers on Person. The observer's job is to set `inverse_type` and reject self/duplicate rows — same as the template, no auto-materialization of derived rows.

8. **Phased build** — this document is the plan; Phases 1–7 below are the deliverables, one PR per phase.

---

## Architecture overview

```
                                 ┌──────────────────────────────────────────┐
                                 │              users                       │
                                 │ ─────────────                            │
                                 │  id  email  password  appearance  …      │
                                 │  person_id  ───────────────┐             │
                                 └────────────────────────────┼─────────────┘
                                                              │ 1:1 (nullable)
                                                              ▼
   ┌──────────────────────────────────────────────────────────────────────┐
   │                              persons                                 │
   │  id  person_type  name  display_name  tax_id  tax_id_type            │
   │  birthdate  visible  managing_church_id  natures (JSON[])            │
   │  additional_data (JSON{})                                            │
   └──────────────────────────────────────────────────────────────────────┘
        │              │              │              │            │
        │ has many     │ has many     │ has many     │ has many   │ has many
        ▼              ▼              ▼              ▼            ▼
  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐  ┌─────────────────────┐
  │ contacts │  │ addresses│  │ documents│  │ relationships│  │  role_assignments   │
  │ (typed)  │  │          │  │ (Spatie  │  │ (parent_of,  │  │  (function +        │
  │          │  │          │  │  media)  │  │  spouse,     │  │   optional role +   │
  │          │  │          │  │          │  │  godparent,  │  │   group/church +    │
  │          │  │          │  │          │  │  guardian)   │  │   started/ended)    │
  └──────────┘  └──────────┘  └──────────┘  └──────────────┘  └─────────┬───────────┘
                                                                         │ refers to
                                              ┌──────────────────────────┼───────────────────┐
                                              ▼                          ▼                   ▼
                                   ┌────────────────┐       ┌────────────────────┐  ┌────────────────┐
                                   │   functions    │       │      groups        │  │     roles      │
                                   │  ────────────  │       │  ────────────      │  │  ────────────  │
                                   │  name slug     │       │  kind (council/    │  │  name slug     │
                                   │  applies_to    │       │   ministry/        │  │  applies_to    │
                                   │  max_holders   │       │   commission)      │  │  (pastor)      │
                                   └────────────────┘       │  region_id (null?) │  └────────────────┘
                                                            │  district_id (?)   │
                                                            │  church_id (?)     │
                                                            │  → 4 levels via    │
                                                            │    nullable FKs    │
                                                            └────────────────────┘

  Org hierarchy (one church belongs to a district belongs to a region):
        ecclesiastical_regions  ←  districts  ←  churches
                                               (district_id, FK on churches)
```

Key shapes:

- One `Person` is linked from zero or one `User` rows.
- A `Person` has any combination of natures (Pastor + Member + Treasurer of a Council = three concurrent things).
- `PersonRelationship` is one row per pair (no mirror), with the inverse type derived by an observer.
- `PersonRoleAssignment` is the unifier for "this person held function F (with optional role R) in scope (region/district/church/group) from started_at to ended_at."
- `Group` is a single table with a `kind` enum (`council` / `ministry` / `commission`); the same row carries optional `region_id`, `district_id`, `church_id` to express the level — the highest non-null FK is the level.

---

## Schema

### `persons` (Phase 1)

| column | type | notes |
|---|---|---|
| id | bigint PK | |
| person_type | enum | `individual` / `organization` |
| name | string(255) | required |
| display_name | string(255) nullable | UI override of `name` |
| tax_id | string(20) nullable | CPF/CNPJ; format-validated by observer |
| tax_id_type | string(20) nullable | `cpf`/`cnpj`/`other` |
| birthdate | date nullable | drives child/teenager/adult inference |
| country_code | char(2) | default `BR` |
| natures | json | `["member","pastor"]` |
| additional_data | json | `{"member":{…},"pastor":{…}}` |
| visible | boolean | default true |
| managing_church_id | bigint FK | which church owns this person's record (nullable in v1 for backfill rows where the church isn't yet known) |
| created_at, updated_at | timestamps | |
| deleted_at | timestamp nullable | soft deletes |

Indexes: `(name)` for search, `(tax_id)` unique-where-not-null, `(birthdate)` for age-based queries, `(managing_church_id)` for tenant scoping.

### `users` (modify in Phase 1)

- Add: `person_id bigint NOT NULL` FK to `persons.id` `ON DELETE CASCADE`. Index on `(person_id)`. Unique to enforce 1:1.
- Drop: `member_type` (now lives in `persons.natures`).
- Drop: `phone` (now in `person_contacts` with `type=phone`, `is_primary=true`).
- Drop: `birthdate` (moved to `persons.birthdate`).
- Drop: `church_id` (membership now expressed via `persons.managing_church_id` for ownership and `person_role_assignments` for affiliations).

The `MakeSuperUser` console command (and the `RolesAndPermissionsSeeder`) updates to create both the Person and the User in one shot.

### `person_contacts` (Phase 2)

| column | notes |
|---|---|
| id PK | |
| person_id FK | |
| type enum | `email` / `phone` / `whatsapp` / `social` / `website` |
| value | the actual address/number/URL |
| label | string nullable | "Personal", "Work", etc. |
| is_primary | boolean | one per type per person, enforced by observer |
| sort_order | int | |
| metadata | json nullable | platform for social, line type for phone, etc. |
| timestamps + soft deletes | |

### `person_addresses` (Phase 2)

| column | notes |
|---|---|
| id PK | |
| person_id FK | |
| label | "Home", "Office" |
| street, number, complement, neighborhood, city, state, zip, country | strings |
| is_primary | boolean |
| sort_order | int |
| timestamps + soft deletes | |

### `person_documents` (Phase 2)

| column | notes |
|---|---|
| id PK | |
| person_id FK | |
| kind | `id`, `passport`, `driver_license`, `birth_certificate`, `baptism_certificate`, etc. |
| number, issuer, issued_at, expires_at | document fields |
| media (via Spatie MediaLibrary) | for the scanned image — NOT a column, uses `HasMedia` |
| metadata | json nullable |
| timestamps + soft deletes | |

### `person_relationships` (Phase 3)

| column | notes |
|---|---|
| id PK | |
| person_id FK | the subject |
| related_person_id FK | the object |
| relationship_type | enum (PersonRelationshipType) |
| inverse_type | enum (PersonRelationshipType) | auto-set by observer |
| started_at, ended_at | dates nullable | for time-bounded ties |
| context_data | json nullable | per-type metadata |
| is_primary | boolean | canonical relationship of its kind |
| sort_order | int | |
| timestamps + soft deletes | |

Unique constraint on `(person_id, related_person_id, relationship_type)` to block duplicates.

`PersonRelationshipType` enum cases (only the **explicit primitives**):

| Asymmetric pair |
|---|
| `parent_of` ↔ `child_of` |
| `godparent_of` ↔ `godchild_of` |
| `guardian_of` ↔ `ward_of` |

| Symmetric (point to themselves) |
|---|
| `spouse` |

**Derived relationships (not stored):**

- **Sibling**: persons sharing a parent. `Person::siblings()` queries for `child_of` rows where another person has the same parent.
- **Brother / Sister**: filtered `siblings()` by gender (if we model gender on Person; deferred decision).
- **Grandparent / Grandchild**: walk `parent_of` chain twice.
- **Uncle / Aunt**: parent's sibling. Walk to parent, then to siblings.
- **Nephew / Niece**: sibling's child. Walk to siblings, then to their children.
- **Cousin**: parent's sibling's child. Walk to parent, sibling, child.
- **Step-parent / step-child**: derived from spouse + parent_of with explicit "biological vs step" flag in `context_data` if the user wants to distinguish. v1 doesn't distinguish.

Query helpers on Person (Phase 3):
- `parents(): Collection<Person>`
- `children(): Collection<Person>`
- `spouse(): ?Person` — returns the most recent active spouse (`ended_at IS NULL OR ended_at > today`)
- `siblings(): Collection<Person>`
- `grandparents(): Collection<Person>`
- `grandchildren(): Collection<Person>`
- `aunts(): Collection<Person>`, `uncles(): Collection<Person>`
- `nieces(): Collection<Person>`, `nephews(): Collection<Person>`
- `cousins(): Collection<Person>`
- `descendants(): Collection<Person>` — all parent_of walked recursively
- `ancestors(): Collection<Person>` — all child_of walked recursively

Observer (`PersonRelationshipObserver`):
- Auto-sets `inverse_type` from `relationship_type` via a `PersonRelationshipType::inverse()` enum method.
- Prevents self-relationships (`person_id !== related_person_id`).
- Prevents duplicates (same triple already exists).
- Does NOT auto-create derived rows; the query helpers handle that on read.

### `districts` (Phase 5)

| column | notes |
|---|---|
| id PK | |
| ecclesiastical_region_id FK | required |
| name | string(255) |
| slug | string unique within region |
| description | text nullable |
| is_active | boolean |
| timestamps + soft deletes | |

### `churches` (modify in Phase 5)

Add: `district_id bigint nullable` FK to `districts.id`. Index on `(district_id)`.

Nullable for transition; in steady state every church belongs to a district. Backfill happens via a one-shot command after the user has created the district records.

### `functions` (Phase 4)

The shared catalog of named positions a person can hold.

| column | notes |
|---|---|
| id PK | |
| name | string(120) | display name, e.g., "Treasurer" |
| slug | string(60) unique | machine name, e.g., `treasurer` |
| applies_to | string set | which contexts this function can be used in: any combination of `pastor`, `council`, `ministry`, `commission` |
| max_holders | int nullable | how many people can hold this concurrently in one context (1 for treasurer, NULL = unlimited for `member`) |
| display_order | int | |
| description | text nullable | |
| timestamps + soft deletes | |

Seeded with: Main Pastor, Auxiliary Pastor, Seminarist, Lead, Co-Lead, Secretary, Treasurer, Member, Adviser.

### `roles` (Phase 4)

The qualifier that distinguishes flavors of an assignment when one function isn't enough. In v1 this is mostly for pastor roles (Main / Auxiliary / Seminarist). The user's "function" column may already capture this via separate function rows ("Main Pastor" vs "Auxiliary Pastor"); roles is a finer-grained second axis that we may collapse into functions if we don't need it in practice.

| column | notes |
|---|---|
| id PK | |
| name | string(120) |
| slug | string(60) unique |
| applies_to | string | which `function` slugs this role qualifier is valid for |
| description | text nullable |
| display_order | int |
| timestamps + soft deletes | |

**Open call:** if "Main Pastor" / "Auxiliary Pastor" / "Seminarist" are modeled as three `functions` (different rows in the functions table), we may not need a `roles` table at all. Decision deferred to Phase 4 — start without it; add later only if a real two-axis case appears.

### `groups` (Phase 6)

| column | notes |
|---|---|
| id PK | |
| name | string(255) |
| slug | string(80) | unique within scope; see "Scope uniqueness" below |
| kind | enum | `council` / `ministry` / `commission` |
| ecclesiastical_region_id | bigint nullable FK | |
| district_id | bigint nullable FK | must be NULL if region is NULL |
| church_id | bigint nullable FK | must be NULL if district is NULL |
| description | text nullable | |
| is_active | boolean | |
| started_at, ended_at | dates nullable | for time-bounded groups |
| timestamps + soft deletes | |

**Level rules** (validated by an observer):
- All three FKs NULL → `national`
- Region set, district NULL, church NULL → `region`
- Region + district set, church NULL → `district`
- Region + district + church set → `church`
- Anything else (e.g., district set without region) → invalid; observer rejects.

**Scope uniqueness:** `slug` is unique within the same scope. So a "Ministry of Women" can exist at national, at every region, every district, and every church — they're distinct rows with different scope tuples.

### `person_role_assignments` (Phase 4 — Pastor migration; extended in Phase 6 for Group functions)

The unified time-bounded role/function table. A row says "person P held function F, optionally qualified by role R, in scope (church or group), from started_at to ended_at."

| column | notes |
|---|---|
| id PK | |
| person_id FK | |
| function_id FK | required — what they're doing |
| role_id nullable FK | optional qualifier (e.g., for pastor: Main / Auxiliary / Seminarist) |
| group_id nullable FK | for group function holders (treasurer of Council X); NULL for non-group assignments |
| church_id nullable FK | for pastor assignments at a church; NULL when group_id is set (church is derived from group) |
| ecclesiastical_region_id nullable FK | derived from group/church but stored for indexing/scoping efficiency |
| district_id nullable FK | derived from group/church but stored for indexing/scoping efficiency |
| started_at, ended_at | dates nullable | active = ended_at IS NULL OR ended_at > today |
| display_order | int | for sort within a person's assignments or within a group's roster |
| context_data | json nullable | extra metadata per assignment |
| timestamps + soft deletes | |

The region/district/church FKs are **denormalized** (technically derivable from group_id or set directly for pastor assignments). Storing them lets us index efficiently for "find all assignments at District X" queries without joining through groups.

Indexes: `(person_id)`, `(group_id, function_id)`, `(church_id, function_id)`, `(ecclesiastical_region_id, started_at, ended_at)`, `(district_id, started_at, ended_at)`.

**Constraint via observer:**
- Either `group_id` OR `church_id` must be set (not both, unless we want to model "treasurer of group X scoped also to a church Y" — currently not needed; observer enforces XOR).
- For group_id assignments, the region/district/church FKs are populated from the group's scope.
- For church_id assignments, region_id and district_id are populated from the church.
- function's `applies_to` must include the assignment context (pastor for church-only, council/ministry/commission for groups). Observer rejects mismatches.

Migration note: the existing `pastor_assignments` data backfills into `person_role_assignments` rows with `function_id` = the row representing "Main Pastor" / "Auxiliary Pastor" / "Seminarist" in the seeded `functions` table; `group_id` = NULL; `church_id` = legacy value; `started_at`/`ended_at` copied. The legacy `pastor_assignments` table can be dropped after Phase 4 ships and the UI is verified.

---

## User ↔ Person link mechanics

- `users.person_id` is nullable — existing users without a Person backfill into a Person on first migration touch (Phase 1).
- `Person` does NOT have a back-reference column to User. Define the relationship as `Person::user(): HasOne` with FK on the users table.
- Auth still uses `User`. Permissions still use `spatie/laravel-permission` on the User row. `User->person` is the identity context.
- For the admin/members editor: we operate on Person directly (with optional User attached). Member records that get added without an account → Person row only, no User row. If they later sign up, an existing Person can be claimed via email match.

### Visitor without User

Per the user's description, `visitor` is the one nature that may not have a User. A visitor row is created by an admin (e.g., guest who attended a service). They get a Person row with `nature=visitor`, no User. If they later choose to sign up, the registration flow looks for a Person matching the email/tax_id and links the new User to that Person.

### Child + parental supervision

Children under a configurable age (default 13, matching COPPA) are NOT given their own User account. Instead:

- The child is a Person with `nature=child`, no `User`.
- A `parent_of`/`child_of` PersonRelationship ties the child Person to a parent Person.
- The parent's User account, when authenticated, can switch context to "act as" their child for the limited features children can use (signing up for prayer slots, marking fasting entries, etc.).
- The "act as" mechanic is implemented as a session key `acting_as_person_id` set via a small selector in the user-menu when the user has child relationships. All write paths check `auth()->user()->canActAs($personId)` which validates the parent_of relationship.

When a child crosses the age threshold (configurable), the system can optionally email the parent to invite the child to claim their own User account, which then takes over as the canonical login for that Person.

---

## Authorization integration

The current `spatie/laravel-permission` setup stays in place but the **role catalog expands to mirror the 4-level org hierarchy**.

### Spatie roles (the WHAT)

The 3 existing spatie roles are replaced/expanded by 5:

| Role (spatie) | Scope-of-authority | Replaces |
|---|---|---|
| `national_admin` | every region / district / church | `global_manager` |
| `regional_admin` | one region (and all its districts/churches) | (new) |
| `district_admin` | one district (and all its churches) | (new) |
| `local_admin` | one church | `local_manager` |
| `user` | regular member (no admin powers) | `user` |

The role tells the system **what kind of admin** the user is. Permissions (the verbs they can call: edit posts, moderate comments, etc.) hang off these roles via spatie's permission catalog as today.

### Admin scope (the WHERE)

The role does NOT carry the scope; the scope lives in `person_role_assignments`. Each admin user has a PRA row with:

- `function_id` = the matching admin function (`national_admin` / `regional_admin` / `district_admin` / `local_admin`) — these are seeded into `functions` alongside the pastoral / group functions.
- `church_id` / `district_id` / `ecclesiastical_region_id` set to the appropriate scope (NULL for `national_admin`).
- No `group_id` (admin scope is not a group).

A `national_admin` row has all three scope FKs NULL — the same "all NULLs = national" pattern that groups use.

### Permission gates compose role + scope

- `Gate::define('manage-church', fn (User $u, Church $c) => $u->canManage($c))` — `User->canManage(Church)` checks the user has a role permitting church management AND a PRA row whose scope covers that church.
- `User->scopedRegions()` / `scopedDistricts()` / `scopedChurches()` return the entities a user has authority over (recursive — a regional_admin's scopedChurches() returns every church in their region).
- The existing `User->manageableChurches()` / `manageableChurchIds()` methods get rewritten to use this composition. Their public surface stays the same.

### Functions catalog: full Phase 1 seed

| function slug | applies_to | max_holders | notes |
|---|---|---|---|
| `national_admin` | `admin` | NULL | scope: national (all FKs NULL on the PRA row) |
| `regional_admin` | `admin` | NULL | scope: one region |
| `district_admin` | `admin` | NULL | scope: one district |
| `local_admin` | `admin` | NULL | scope: one church |
| `main_pastor` | `pastor` | 1 | one per church |
| `auxiliary_pastor` | `pastor` | NULL | |
| `seminarist` | `pastor` | NULL | |
| `lead` | `council`, `ministry`, `commission` | 1 | one per group |
| `co_lead` | `council`, `ministry`, `commission` | NULL | |
| `secretary` | `council`, `ministry`, `commission` | 1 | |
| `treasurer` | `council`, `ministry`, `commission` | 1 | |
| `member` | `council`, `ministry`, `commission` | NULL | |
| `adviser` | `council`, `ministry`, `commission` | NULL | |

`FunctionAppliesTo` enum: `Admin`, `Pastor`, `Council`, `Ministry`, `Commission`.

### Gates that need to know "is this a member of this church"

These ask `Person->hasNature('member')` and `Person->isMemberOf($church)` (via the church_user pivot OR `managing_church_id`, decided per-gate). These are nature checks, not admin scope checks.

### Naming heads-up: spatie `roles` vs our `assignment_roles`

Spatie's table is `roles` (Laravel convention). Our `assignment_roles` is the qualifier-axis on assignments (currently empty in v1; reserved for cases where a single function needs a sub-role distinction). The two tables coexist with different namespaces (`Spatie\Permission\Models\Role` vs `App\Models\AssignmentRole`).

---

## Phased rollout

Each phase is a standalone PR. Each phase can be merged independently. Tests and translation audit gate every commit (per the existing project rules).

### Phase 1 — All schema + Person foundation + User link

This phase ships the entire end-state schema (all tables for all later phases) plus the Person model, User link, and seeder updates. Later phases are code-and-UI only.

1. **Migrations (one file per table, standard Laravel):**
   - `create_persons_table.php`
   - `create_person_contacts_table.php`
   - `create_person_addresses_table.php`
   - `create_person_documents_table.php`
   - `create_person_relationships_table.php`
   - `create_districts_table.php`
   - `create_functions_table.php`
   - `create_assignment_roles_table.php`
   - `create_groups_table.php`
   - `create_person_role_assignments_table.php`
   - `modify_users_table_for_persons.php` — drop `member_type`, `phone`, `birthdate`, `church_id`; add `person_id` (NOT NULL) FK to persons.
   - `modify_churches_table_for_districts.php` — add `district_id` (nullable in v1, but settable from day one).
   - `drop_pastors_and_pastor_assignments_tables.php` — those die in favor of `person_role_assignments`.
2. **Models for everything created** (Person, PersonContact, PersonAddress, PersonDocument, PersonRelationship, District, FunctionRole [class name avoiding the PHP `function` keyword], AssignmentRole, Group, PersonRoleAssignment).
3. **Enums:** `PersonType`, `PersonNature` (Member/Pastor/Child/Teenager/Visitor), `PersonContactType`, `PersonRelationshipType` (with `inverse()` method), `GroupKind` (council/ministry/commission), `FunctionAppliesTo` (pastor/council/ministry/commission).
4. **Observers:** `PersonObserver` (tax_id checksum), `PersonRelationshipObserver` (inverse derivation, no self/dupes), `GroupObserver` (level-rule validation), `PersonRoleAssignmentObserver` (denormalize region/district from group/church, validate function.applies_to vs context).
5. **Seeders:**
   - `FunctionsSeeder` — seeds the standard set (Main Pastor / Auxiliary Pastor / Seminarist / Lead / Co-Lead / Secretary / Treasurer / Member / Adviser).
   - Update `RolesAndPermissionsSeeder` and the `MakeSuperUser` console command to create the global-admin's Person row alongside the User row.
6. **`User::person(): BelongsTo`** + `Person::user(): HasOne` relationships.
7. **Update admin/members editor + profile pages** to read/write Person fields through `User->person`. The Member nature is automatically present on every backfilled / newly-created member Person.
8. **Tests:** Person factory, User factory updated (creates a Person), MemberForm tests updated, observer tests for inverse derivation + level rules.

**Verification:** `migrate:fresh --seed` produces a clean schema with one global-admin user (and matching Person). All 171 existing tests adapted to the new schema and stay green; new tests prove the Person/User invariants and observer behaviors.

### Phase 2 — Person satellites UI

Schema is already in place from Phase 1. This phase wires the UI for contacts, addresses, documents.

1. Forms: `PersonContactForm`, `PersonAddressForm`, `PersonDocumentForm` under `app/Livewire/Forms/`.
2. `Person implements HasMedia` for `person_documents` scanned-image attachments via Spatie MediaLibrary.
3. Profile UI: new tabs `Contacts`, `Addresses`, `Documents` (or fold into existing tabs).
4. Admin/members editor: same — let admins manage satellite data on member records.
5. Tests: satellite CRUD + uniqueness rules (one primary per type).

### Phase 3 — Family tree UI

Schema in place. This phase wires the family-tree experience.

1. UI: a "Family" tab on profile.show + admin/members editor. List of related Persons with their relationship type + start/end. Add/remove relationships via inline form (using `PersonRelationshipForm`).
2. Query helpers on Person (computed via graph traversal): `parents()`, `children()`, `spouse()`, `siblings()`, `grandparents()`, `grandchildren()`, `aunts()`, `uncles()`, `nieces()`, `nephews()`, `cousins()`, `descendants()`, `ancestors()`.
3. A "Family tree" computed property on Person returns a structured depth-bounded tree for the UI — uses the same query helpers, composed.
4. Tests: relationship insertion derives inverse correctly; duplicates blocked; self-rels blocked; family-tree query helpers return the right people for known fixtures.

### Phase 4 — Pastor as PersonRoleAssignment

Schema is in place; the existing `pastors` / `pastor_assignments` tables were dropped in Phase 1. This phase rewires the admin/churches/pastors UI to use the new generic assignment table.

1. `PastorAssignmentForm` (already extracted in Stage 3) → updated/replaced by `PersonRoleAssignmentForm` filtered to pastor functions.
2. admin/churches/pastors UI reads from `person_role_assignments` where `function.applies_to ∋ pastor` AND `church_id IS NOT NULL` AND `group_id IS NULL`.
3. The `Pastor` model and its factory go away (or repurposed as a thin Person-based proxy if it simplifies callers).
4. Tests: new assignment flow, function-uniqueness for "Main Pastor" within a church (via `functions.max_holders=1`).

### Phase 5 — Districts UI

Schema in place. Just wire CRUD and the church editor selector.

1. Admin UI for district CRUD (under the existing Settings/Churches area) + district selector on the church editor.
2. Update church-related forms to require `district_id` once the user has at least one district seeded (configurable; v1 keeps it nullable so existing single-church setups don't get blocked).
3. Tests for district CRUD + district→church FK.

### Phase 6 — Groups (Councils, Ministries, Commissions) at 4 levels

Schema in place. This phase wires the group management UI.

1. Admin section: "Groups" with sub-pages per kind (Councils, Ministries, Commissions). Reuses the table + sort + edit-icon convention from Stages 4-6 (and the `HasSortableColumns` trait). Filter dropdowns for Region / District / Church scope.
2. Group editor (`GroupForm`) with a level selector (national/region/district/church) that conditionally requires the appropriate FK.
3. Group-member UI: assign Person to Group with a function via `PersonRoleAssignmentForm` (`group_id` = group, `function_id` = function from seeded list, region/district/church denormalized from group's scope by the observer).
4. Function holder queries: `$group->functionHolder('treasurer')`, `$group->members()`, `$person->groupsAsLeader()`, `$church->groupsByKind('ministry')`, `$region->nationalGroups()`, etc.
5. Tests: group CRUD per level, level-rule enforcement, assignment lifecycle, function-uniqueness when `max_holders=1`.
6. **Functions CRUD — decide here.** If the seeded function list (4 admin + 3 pastor + 6 group functions from Phase 1) covers every real group case, leave `functions` as a seeded-only config table. If a real need surfaces in Phase 6 to let church staff add custom functions (e.g. "Worship Leader"), grow a small `/admin/functions` CRUD as part of this phase. `assignment_roles` stays empty until a genuine two-axis case appears — its CRUD comes only if/when that table starts holding rows.

### Phase 7 — Children/Teenagers/Visitors + parental supervision

The natures already exist from Phase 1; this phase activates them in the UI and adds the act-as supervision mechanic.

1. Birth-date-driven nature inference: helper `Person->inferAgeBasedNatures()` returns `child`/`teenager`/`adult` based on `birthdate`. Optional cron auto-promote when a child crosses the threshold.
2. Profile: new "My family" section showing related Persons (from Phase 3) with an "Act as" button next to each Person who has a `child_of` relation to the current user.
3. `User->canActAs(Person)`: verifies the `parent_of` relationship and that the target Person has `nature=child` or `nature=teenager`.
4. Session middleware: when `acting_as_person_id` is set, certain controllers (prayer signup, fasting entries) write rows scoped to the acted-as Person instead of the parent.
5. Visitor flow: admin "Add visitor" creates a Person with `nature=visitor`, no User. Optional later registration claims the Person.
6. Tests: act-as authorization, visitor creation without User, child age threshold.

---

## Migration strategy

The project is pre-launch with a single global-admin user as the only row that matters. The migration story is therefore minimal:

- **All schema lands in Phase 1.** One migration file per table (standard Laravel pattern). Deprecated columns and tables are dropped in their own modify/drop migration files within the same batch.
- **No backfill commands.** Code paths read/write the new schema from day one; the old shape is gone.
- **No rollback path.** If something goes wrong, fix forward with another migration. The DB is recreatable via `php artisan migrate:fresh --seed`.
- **Seeders rebuild the global admin** (`MakeSuperUser` + `RolesAndPermissionsSeeder`) to create both Person and User in one shot.

Each subsequent phase (2–7) is **code-and-UI only**, with no further schema work needed (the schema is already in place from Phase 1).

---

## Naming + conventions

- Models live in `app/Models/` (flat — no `app/Models/Person/` subnamespace) since methodist-app doesn't use module subdirectories.
- Enums live in `app/Enums/`. Existing convention: `MemberType.php`, `PastorRole.php` etc. — follow.
- Form classes live in `app/Livewire/Forms/` per the Stage 3 sweep — `PersonForm`, `PersonRelationshipForm`, `PersonRoleAssignmentForm`, `GroupForm`, `DistrictForm`.
- Concerns/traits live in `app/Livewire/Concerns/` per Stage 6 — if a `HasNatures` or `HasRelationships` trait emerges, it goes there.
- The new `roles` table is renamed to `assignment_roles` to avoid colliding with spatie/laravel-permission's `roles`.

---

## Out of scope for this plan

- Multi-tenancy beyond Church scoping (already handled).
- Bulk import of legacy people from spreadsheets (separate operational concern).
- Communications (email/SMS) sent to People — Phase 8+ if needed.
- Calendar/event scheduling tied to People — separate domain.
- Person merge/dedupe UI for handling duplicates — Phase 8+ if needed.
- The `PersonFieldDefinition` dynamic-schema system from the template — deferred to Phase 8+ if methodist needs per-tenant field configurability. Otherwise hardcode the fields per nature in the Form classes.
- Step-relationships modeled as a distinct enum value — v1 uses `context_data` boolean to mark step vs biological if needed.

---

## Verification per phase

- `php8.4 vendor/bin/pint --dirty --format agent` passes.
- `php8.4 artisan test --compact` stays green and new tests added per phase.
- Translation audit clean: `grep __() | diff against en/pt_BR/es.json` matches the project's pre-commit rule.
- Backfill command per phase runs against a freshly-seeded DB and produces expected counts.
- Manual UI walk per phase.

---

## Open questions to confirm before Phase 1 ships

1. **Tax ID validation:** do we validate Brazilian CPF/CNPJ checksums on save (reject invalid), or just store whatever the user types? Default: validate on save.
2. **`person_type` for organizations:** do we need the `Organization` enum case at all in v1, or can it be deferred? Default: include it (cheap, future-proof).
3. **`PersonFieldDefinition`:** ship in v1 (more flexibility but more code), defer (hardcode fields per nature, cleaner v1)? Default: defer.
4. **Pastor Function vs Role split:** model "Main Pastor" / "Auxiliary Pastor" / "Seminarist" as three rows in `functions`, OR as one `function=Pastor` + an `assignment_role` qualifier? Default: three separate functions; ship `assignment_roles` table empty / unused in v1 and revisit later if a real two-axis case appears.
5. **Spouse one-or-many:** allow multiple spouse rows over time (started/ended). Should we enforce at most one *active* spouse via observer? Default: yes — observer rejects a new active spouse if one already exists.
6. **Parental act-as for adults:** does an adult-child Person (over 18) still allow a parent to "act as" them? Default: no — the act-as mechanism only applies when the target Person has `nature=child` or `nature=teenager`.

7. **Membership change approval:** when a user (regular member, teenager, child, etc.) edits their own `Person.managing_church_id` / `nature` from the Profile or Register screen, should the change require approval from the receiving church's pastor (or local admin / district admin) before taking effect, or apply immediately? Default in Phase 1 ships **immediate** so the form behaves like every other profile field — but evaluate before going live whether a receiving-side approval flow is needed (e.g. a `pending_membership_changes` table the pastor confirms, mirroring the pattern of new-member intake forms in church admin systems). Same question applies to children/teenagers under parental act-as: does the parent's edit need approval, or only the adult's? Capture the decision before opening membership editing to non-staff users in production.
