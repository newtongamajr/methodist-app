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

These are the seven big calls — locked in across two rounds of conversation before this plan rewrite:

1. **`users.person_id` is the canonical link.** A `User` row points at a `Person` row. People can exist without users (visitors, family members who'll only ever appear in someone else's family tree). One `Person` is linked from at most one `User`. Permissions stay on User; identity context comes from `User->person`.

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

Add: `person_id bigint nullable` FK to `persons.id` `ON DELETE SET NULL`. Index on `(person_id)`.

The existing `member_type` column on users gets backfilled into `persons.natures` and is dropped in Phase 1's cleanup commit.

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

The current `spatie/laravel-permission` setup stays in place. Permissions are User-scoped and remain so. What changes:

- Permission gates that today check `User->member_type` (none currently) or `User->isAdmin()` continue to work — those are User-level concepts.
- Gates that need to know "is this a member of this church" now ask `Person->hasNature('member')` and `Person->isMemberOf($church)` instead of inferring from a membership pivot.
- New gate examples: `view-children`, `act-as-child`, `manage-group-members`, `assign-function`. These compose User permission checks with Person nature/relationship checks.

No need to migrate the existing `roles` table from spatie; the three roles (`global_manager`, `local_manager`, `user`) stay as User-level credential roles, NOT confused with Person natures or the new `roles` table for assignment qualifiers.

**Naming conflict heads-up:** spatie's table is `roles` (Laravel convention). The new schema also has a `roles` table for assignment qualifiers. Two options:
- Rename ours → `assignment_roles` (avoids ambiguity).
- Leave ours as `roles` and rely on namespace separation (`App\Models\Role` vs `Spatie\Permission\Models\Role`).

**Decision:** rename ours to `assignment_roles` in v1 to keep tables clearly separated. The `roles` table column described in the schema section above should be read as `assignment_roles` in the actual migration.

---

## Phased rollout

Each phase is a standalone PR. Each phase can be merged independently. Tests and translation audit gate every commit (per the existing project rules).

### Phase 1 — Person foundation + User link + Member migration

1. Migrations: `persons` table, `users.person_id` column.
2. Models: `Person`, plus `App\Enums\PersonType` and `App\Enums\PersonNature` enum (initial cases: `Member`, `Pastor`, `Child`, `Teenager`, `Visitor`).
3. `PersonObserver` for tax_id format validation (Brazilian CPF/CNPJ checksum).
4. Hook `Person` from `User` via `User::person(): BelongsTo`.
5. Backfill command: `php artisan persons:backfill-from-users` — for every existing User, create a matching Person, copy `name`/`birthdate`, set `nature=member` (or `nature=pastor` if a Pastor row exists for that User), set `users.person_id`, set `persons.managing_church_id` from the user's primary church.
6. Update admin/members editor + profile pages to read/write through `User->person` for shared fields (name, birthdate). The `users.member_type` column stays in place this phase but reads default to Person-side values where present.
7. Tests: factory for Person, MemberForm test updates.

**Verification:** all 171 existing tests stay green; new tests prove Person creation, User-Person linking, backfill command idempotency.

### Phase 2 — Person satellites (contacts, addresses, documents)

1. Migrations for `person_contacts`, `person_addresses`, `person_documents`.
2. Models with `BelongsTo Person` + `is_primary` enforcement (only one primary per type via observer).
3. Add `Person implements HasMedia` for `person_documents` scanned-image attachments via Spatie MediaLibrary.
4. Profile UI: new tab `Contacts` and `Addresses` (or fold into existing tabs). Admin/members editor: same.
5. Migrate the existing `users.phone` column data into a `person_contacts` row with `type=phone` `is_primary=true`. Drop `users.phone` column at end of phase.
6. Tests for satellite CRUD + uniqueness rules.

### Phase 3 — Family tree (PersonRelationship)

1. Migration for `person_relationships`.
2. `PersonRelationshipType` enum with the explicit primitives (`parent_of`/`child_of`, `spouse`, `godparent_of`/`godchild_of`, `guardian_of`/`ward_of`); `inverse()` method on the enum.
3. `PersonRelationshipObserver` derives `inverse_type` and prevents self-rels + duplicates.
4. UI: a "Family" tab on profile.show + admin/members editor. List of related Persons with their relationship type + start/end. Add/remove relationships via inline form.
5. Query helpers on Person (computed via graph traversal): `parents()`, `children()`, `spouse()`, `siblings()`, `grandparents()`, `grandchildren()`, `aunts()`, `uncles()`, `nieces()`, `nephews()`, `cousins()`, `descendants()`, `ancestors()`.
6. A "Family tree" computed property on Person returns a structured tree (depth-bounded) for the UI — uses the same query helpers but composed.
7. Tests: relationship insertion derives inverse correctly; duplicates blocked; self-rels blocked; family-tree query helpers return the right people for known fixtures.

### Phase 4 — Functions / assignment_roles tables + Pastor migration

1. Migrations for `functions` and `assignment_roles` tables, plus `person_role_assignments`.
2. Seeders for the initial function set: Main Pastor, Auxiliary Pastor, Seminarist, Lead, Co-Lead, Secretary, Treasurer, Member, Adviser. (The pastor functions ship in this phase even though groups don't exist yet — they're needed for the pastor migration.)
3. Backfill command: each existing `Pastor` → Person with `nature=pastor`. Each `PastorAssignment` → `PersonRoleAssignment` with `function_id` = matching pastor function (Main/Auxiliary/Seminarist), `church_id` from legacy column, `region_id` denormalized from the church, `started_at`/`ended_at` copied.
4. Update admin/churches/pastors UI to operate on `PersonRoleAssignment` rows (filtered by `function.applies_to` includes `pastor` + `church_id IS NOT NULL` + `group_id IS NULL`).
5. Keep `pastors` and `pastor_assignments` tables in place for the duration of the phase as a fallback. Drop them after one stable release with the new schema running.
6. Tests: pastor backfill + new assignment flow.

### Phase 5 — Districts schema

1. Migration for `districts` table.
2. Migration adding `churches.district_id` (nullable).
3. Admin UI for district CRUD (under the existing Settings menu) + district selector on the church editor.
4. Backfill: optionally seed one "default district" per region so existing churches can be assigned. Alternative: leave `district_id` NULL until a church admin claims one.
5. Tests for district CRUD.

This phase is small but foundational for Phase 6 — groups need districts to anchor district-level scope.

### Phase 6 — Groups (Councils, Ministries, Commissions) at 4 levels

1. Migration for `groups` table with the 4-level scope FKs.
2. `GroupObserver` validates the level rules (no district without region; no church without district; etc.) and computes the `level()` accessor.
3. Admin section: "Groups" with sub-pages per kind (Councils, Ministries, Commissions). Reuses the table + sort + edit-icon convention from Stage 4-6. Filter dropdowns for Region / District / Church scope.
4. Group-member UI: assign Person to Group with a function via a new `PersonRoleAssignment` row (`group_id` = group, `function_id` = function from the seeded list, region/district/church denormalized from group's scope).
5. Function holder queries: `$group->functionHolder('treasurer')`, `$group->members()`, `$person->groupsAsLeader()`, `$church->groupsByKind('ministry')`, `$region->nationalGroups()`, etc.
6. Tests: group CRUD per level, level-rule enforcement, assignment lifecycle, function-uniqueness when `max_holders=1`.

### Phase 7 — Children/Teenagers/Visitors + parental supervision

1. Add `Child`, `Teenager`, `Visitor` cases to `PersonNature` (already declared in Phase 1; activate UI here).
2. Birth-date-driven nature inference: helper `Person->inferAgeBasedNatures()` returns `child`/`teenager`/`adult` based on `birthdate`. Optional auto-promote on cron when a child crosses the threshold.
3. Profile: new "My family" section showing related Persons (from Phase 3) with an "Act as" button next to each Person who has a `child_of` relation to the current user.
4. `User->canActAs(Person)`: verifies the parent_of relationship.
5. Session middleware: when `acting_as_person_id` is set, certain controllers (prayer signup, fasting entries) write rows scoped to the acted-as Person instead of the parent.
6. Visitor flow: admin "Add visitor" creates a Person with `nature=visitor`, no User. Optional later registration claims the Person.
7. Tests: act-as authorization, visitor creation without User, child age threshold.

---

## Migration strategy

Two principles for moving from current schema:

1. **No big-bang migrations.** Each phase migration adds new tables/columns, optional backfill commands, and only DROPS legacy columns (like `users.member_type` or `users.phone`) at the END of the phase after the new code has been deployed and verified.

2. **Backfill commands are idempotent.** `php artisan persons:backfill-from-users` can be re-run safely after rollbacks; it skips users that already have `person_id`.

Rollback path per phase:
- Phase 1: drop `persons`, `users.person_id`. No data loss (member_type still on users).
- Phase 2: drop satellite tables, restore `users.phone` from a backup of person_contacts before drop.
- Phase 3: drop `person_relationships`. No source-of-truth loss (relationships are new data).
- Phase 4: keep `pastors`/`pastor_assignments` until stable; rollback by re-pointing UI at legacy tables.
- Phase 5: drop `districts`, `churches.district_id`. No source-of-truth loss.
- Phase 6: drop `groups`. Loses any group data created post-deploy.
- Phase 7: drop new natures, drop session helper. No structural rollback needed.

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
3. **Member nature defaults:** when migrating existing `users.member_type='member'` to `persons.natures=['member']`, do we also seed the per-member fields (`joined_at`, etc.)? Default: no — leave Person.additional_data['member'] as `{}`.
4. **Drop deprecated columns immediately or wait?** Dropping `users.member_type` in Phase 1 is decisive but irreversible; keeping it for a release as a no-op safety net is more conservative. Default: keep through Phase 1, drop in Phase 2 cleanup.
5. **`PersonFieldDefinition`:** ship in v1 (more flexibility but more code), defer (hardcode fields per nature, cleaner v1)? Default: defer.
6. **Pastor Function vs Role split:** model "Main Pastor" / "Auxiliary Pastor" / "Seminarist" as three rows in `functions`, OR as one `function=Pastor` + a `role` qualifier? Default: three separate functions; defer the `assignment_roles` table until a real two-axis case appears.
7. **Default district for backfill:** when adding `churches.district_id`, do we seed a "default district" per region so existing churches auto-fill, or leave NULL and require admin action? Default: leave NULL; admin assigns explicitly.
8. **Spouse one-or-many:** current schema allows multiple spouse rows over time (started/ended). Should we enforce at most one *active* spouse via observer? Default: yes — observer rejects a new active spouse if one already exists.
9. **Parental act-as for adults:** does an adult-child Person (over 18) still allow a parent to "act as" them? Default: no — the act-as mechanism only applies when the child Person has nature=child OR nature=teenager.
