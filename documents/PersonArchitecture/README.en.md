# Person Architecture

## Context

The current data model treats different "kinds of people" inconsistently:

- **Pastor** is a first-class entity with its own `pastors` table and a time-bounded `pastor_assignments` join to churches.
- **Member, Interested, etc.** are just rows in the `users` table differentiated by a `member_type` enum column.
- **Children, Teenagers, Visitors** don't exist as concepts at all.
- **User** carries authentication identity, but the same human can be (a) a member of one church, (b) an admin of another, (c) the parent of two children who don't have logins, and the schema has no language for any of that.
- **Family relationships** between people aren't modeled — the church organization is fundamentally about families and we have no way to express "this person is the spouse of that person" or "these three users are siblings."
- **Councils, Ministries, Commissions** — the organizational structures these people belong to and the functions they hold within them — don't exist either.

The `template-new-galileo` project already solves the first half of this problem with a `Person` + `PersonRelationship` model and a JSON-based `natures` discriminator. That convention is the one we'll mirror here, extending it for methodist-specific needs.

This document is the architectural plan; it does not implement anything. Phases 1–6 below are the implementation sequence we'll execute against this plan.

---

## Decisions already aligned

These are the four big calls — each picked from a multi-option question and locked in before this plan was written:

1. **`users.person_id` is the canonical link.** A `User` row points at a `Person` row. People can exist without users (visitors who never log in, family members who'll only ever appear in someone else's family tree). One `Person` is linked from at most one `User`. Permissions stay on User; identity context comes from `User->person`.

2. **Per-role data is JSON on the `Person` row** — same as the template. `persons.natures` is a JSON array (`["member", "pastor"]`); `persons.additional_data` is a JSON object keyed by nature (`{ "member": {...}, "pastor": {...} }`). Per-role field schema is defined in `PersonFieldDefinition` rows that the UI can CRUD. No separate table per role.

3. **`PersonRoleAssignment` is the time-bounded layer** for natures that need historical tracking. Generalizes the existing `pastor_assignments` pattern. Same table also handles council/ministry/commission function holders. Active rows = `ended_at IS NULL OR ended_at > now()`.

4. **Phased build** — this document is the plan; Phases 1–6 below are the deliverables, one PR per phase.

---

## Architecture overview

```
                                ┌──────────────────────────────────────────┐
                                │              users                       │
                                │ ─────────────                            │
                                │  id  email  password  role  appearance…  │
                                │  person_id  ───────────────┐             │
                                └────────────────────────────┼─────────────┘
                                                             │ 1:1 (nullable)
                                                             ▼
   ┌─────────────────────────────────────────────────────────────────────┐
   │                              persons                                │
   │  id  uuid  person_type  name  display_name  tax_id  tax_id_type    │
   │  birthdate  visible  natures (JSON[])  additional_data (JSON{})    │
   │  parent_id (nullable, self-FK for organization hierarchy)          │
   └─────────────────────────────────────────────────────────────────────┘
       │              │             │            │              │
       │ has many     │ has many    │ has many   │ has many     │ has many
       ▼              ▼             ▼            ▼              ▼
   ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────┐ ┌───────────────────────┐
   │ contacts │ │ addresses│ │ documents│ │ relationships│ │ role_assignments      │
   │ ──────── │ │ ──────── │ │ ──────── │ │ (adjacency,  │ │ (time-bounded         │
   │ type val │ │ ……       │ │ kind url │ │  inverse-    │ │  natures + group      │
   │ primary  │ │          │ │          │ │  derived)    │ │  function holders)    │
   └──────────┘ └──────────┘ └──────────┘ └──────────────┘ └───────────────────────┘
                                                                    │
                                                                    │ holds_for
                                                                    ▼
                                                          ┌────────────────────┐
                                                          │ groups             │
                                                          │ ────────────       │
                                                          │ church_id          │
                                                          │ kind (council/     │
                                                          │       ministry/    │
                                                          │       commission)  │
                                                          │ name slug          │
                                                          └─────────┬──────────┘
                                                                    │ has many
                                                                    ▼
                                                          ┌────────────────────┐
                                                          │ group_functions    │
                                                          │ ────────────       │
                                                          │ group_id name      │
                                                          │ (secretary,        │
                                                          │  treasurer, lead,  │
                                                          │  member, …)        │
                                                          └────────────────────┘
```

Key shapes:

- One `Person` is linked from zero or one `User` rows.
- A `Person` has any combination of natures (Pastor + Member + Council Treasurer = three concurrent things).
- `PersonRelationship` is one row per pair (no mirror), with the inverse type derived by an observer.
- `PersonRoleAssignment` is the unifier for "this person held nature X" or "this person held function Y in group Z" — the same table, distinguished by what fields are populated.
- `Group` is a single table with a `kind` enum (`council` / `ministry` / `commission`); the alternative of three tables was rejected because all three share the same fields, members, and assignment pattern.

---

## Schema

### `persons` (Phase 1)

| column | type | notes |
|---|---|---|
| id | bigint PK | |
| uuid | char(36) unique | for external/URL use, matches template convention |
| person_type | enum | `individual` / `organization` |
| name | string(255) | required |
| display_name | string(255) nullable | UI override of `name` |
| tax_id | string(20) nullable | CPF/CNPJ; format-validated by observer |
| tax_id_type | string(20) nullable | `cpf`/`cnpj`/`other` |
| birthdate | date nullable | drives child/teenager/adult inference |
| country_code | char(2) | default `BR` |
| natures | json | `["member","pastor"]` |
| additional_data | json | `{"member":{…},"pastor":{…}}` |
| visible | boolean | default true; for soft-hide without delete |
| parent_id | bigint nullable | self-FK for org hierarchy (subsidiary churches/organizations) |
| created_at, updated_at | timestamps | |
| deleted_at | timestamp nullable | soft deletes |

Indexes: `(name)` for search, `(tax_id)` unique-where-not-null, `(birthdate)` for age-based queries, `(parent_id)` for hierarchy walks.

### `users` (modify in Phase 1)

Add: `person_id bigint nullable` FK to `persons.id` `ON DELETE SET NULL`. Index on `(person_id)`.

The existing `member_type` column on users gets backfilled into `persons.natures` and then dropped in Phase 1 cleanup.

### `person_contacts` (Phase 2)

| column | notes |
|---|---|
| id PK | |
| person_id FK | |
| type enum | `email` / `phone` / `whatsapp` / `social` / `website` |
| value | the actual address/number/URL |
| label | string nullable | "Personal", "Work", etc. |
| is_primary | boolean | one per type per person |
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

`PersonRelationshipType` enum cases for methodist needs:

| Asymmetric pair | |
|---|---|
| `parent_of` | `child_of` |
| `grandparent_of` | `grandchild_of` |
| `uncle_of` / `aunt_of` | `nephew_of` / `niece_of` |
| `guardian_of` | `ward_of` |
| `godparent_of` | `godchild_of` |

| Symmetric (point to themselves) |
|---|
| `spouse` |
| `sibling` |
| `cousin` |

Observer auto-derives `inverse_type` from `relationship_type` via a `PersonRelationshipType::inverse()` enum method.

### `person_role_assignments` (Phase 4)

The unified time-bounded role table. A row says "person P held role R from started_at to ended_at, optionally scoped to context C."

| column | notes |
|---|---|
| id PK | |
| person_id FK | |
| nature | string(40) | the role being held: `pastor`, `member`, `child`, `teenager`, `visitor` |
| context_type | string nullable | morph type: `App\Models\Church` for pastor assignments, `App\Models\Group` for council/ministry function holders, NULL for natures that aren't scoped |
| context_id | bigint nullable | morph id |
| function | string nullable | for `Group` contexts: `lead`, `co_lead`, `secretary`, `treasurer`, `member`, etc. NULL for unscoped natures. |
| role | string nullable | for pastor specifically: `main`, `auxiliary`, `seminarist` (matches existing PastorRole enum) |
| started_at, ended_at | dates nullable | active = ended_at IS NULL OR ended_at > today |
| display_order | int | for sort within a person's assignments or within a group's roster |
| context_data | json nullable | extra metadata per assignment |
| timestamps + soft deletes | |

Indexes: `(person_id, nature)`, `(context_type, context_id, function)`, `(nature, started_at, ended_at)` for active-on-date queries.

Migration note: the existing `pastor_assignments` data backfills into `person_role_assignments` rows with `nature='pastor'`, `context_type='App\Models\Church'`, `role` from `PastorRole`, `function` NULL. The legacy `pastor_assignments` table can be dropped after Phase 4 ships and the UI is verified.

### `groups` (Phase 5)

| column | notes |
|---|---|
| id PK | |
| church_id FK | which church owns the group |
| kind | enum: `council` / `ministry` / `commission` |
| name | string(255) |
| slug | string unique within church |
| description | text nullable |
| is_active | boolean |
| started_at, ended_at | dates nullable | for time-bounded groups |
| timestamps + soft deletes | |

### `group_functions` (Phase 5)

Optional table that lets each group declare which functions exist (so the UI can offer a dropdown of valid functions per group instead of free-text).

| column | notes |
|---|---|
| id PK | |
| group_id FK | |
| name | string(60) | `secretary`, `treasurer`, `lead`, `co_lead`, `member` |
| label | string(120) | display label |
| max_holders | int nullable | how many people can hold this concurrently (1 for treasurer, NULL for `member`) |
| display_order | int | |

If we don't want to manage function definitions per-group, we skip this table and use a global `GroupFunction` enum instead. Decision deferred to Phase 5 — start with the enum, promote to a table only if churches need different function sets.

### `person_field_definitions` (Phase 6, optional)

Lifted from the template. Lets admins define which fields each nature exposes (e.g., Member has `joined_at`, `baptized_at`; Child has `school_grade`; Visitor has `referral_source`). Stored centrally so the form-render code can iterate definitions instead of hardcoding columns.

If we don't need per-tenant configurability, skip this table and just hand-write the fields per nature in the form classes.

---

## User ↔ Person link mechanics

- `users.person_id` is nullable — existing users without a Person backfill into a Person on first migration touch.
- `Person` does NOT have a back-reference column to User. Define the relationship as `Person::user(): HasOne` with FK on the users table.
- Auth still uses `User`. Permissions still use `spatie/laravel-permission` on the User row. `User->person` is the identity context.
- For the admin/members editor: we operate on Person directly (with optional User attached). Member records that get added without an account → Person row only, no User row. If they later sign up, an existing Person can be claimed via email match.

### Visitor without User

Per your description, `visitor` is the one nature that may not have a User. A visitor row is created by an admin (e.g., guest who attended a service). They get a Person row with `nature=visitor`, no User. If they later choose to sign up, the registration flow looks for a Person matching the email/tax_id and links the new User to that Person.

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

No need to migrate the existing `roles` table; the three roles (`global_manager`, `local_manager`, `user`) stay as User-level credential roles, NOT confused with Person natures.

---

## Phased rollout

Each phase is a standalone PR. Each phase can be merged independently. Tests and translation audit gate every commit (per the existing project rules).

### Phase 1 — Person foundation + User link + Member migration

1. Migrations: `persons` table, `users.person_id` column.
2. Models: `Person`, plus an `App\Enums\PersonType` and `App\Enums\PersonNature` enum (initial cases: `Member`, `Pastor`, `Child`, `Teenager`, `Visitor`).
3. `PersonObserver` for tax_id format validation (Brazilian CPF/CNPJ checksum).
4. Hook `Person` from `User` via `User::person(): BelongsTo`.
5. Backfill command: `php artisan persons:backfill-from-users` — for every existing User, create a matching Person, copy `name`/`birthdate`, set `nature=member` (or `nature=pastor` if a Pastor row exists for that User), set `users.person_id`.
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
2. `PersonRelationshipType` enum with the asymmetric pairs + symmetric set above; `inverse()` method on the enum.
3. `PersonRelationshipObserver` derives `inverse_type` and prevents self-rels + duplicates.
4. UI: a "Family" tab on profile.show + admin/members editor. List of related Persons with their relationship type + start/end. Add/remove relationships via inline form.
5. Convenience query helpers on Person: `parents()`, `children()`, `spouse()`, `siblings()`, `descendants()`, `ancestors()`.
6. Tests: relationship insertion derives inverse correctly; duplicates blocked; self-rels blocked; family-tree traversal queries return expected counts.

### Phase 4 — Pastor migration to Person

1. Migration for `person_role_assignments`.
2. Backfill command: each existing `Pastor` → Person with `nature=pastor`. Each `PastorAssignment` → `PersonRoleAssignment` with `nature=pastor`, `context_type='App\Models\Church'`, `context_id=church_id`, `role=pastor_role`, `started_at`/`ended_at` copied.
3. Update admin/churches/pastors UI to operate on `PersonRoleAssignment` rows (filtered by `nature=pastor` + `context_type=Church`).
4. Keep `pastors` and `pastor_assignments` tables in place for the duration of the phase as a fallback. Drop them after one stable release with the new schema running.
5. Tests: pastor backfill + new assignment flow.

### Phase 5 — Councils, Ministries, Commissions + Functions

1. Migration for `groups` table with `kind` enum.
2. Either add `group_functions` table (per-group function definitions) OR a `GroupFunction` global enum — start with enum, promote to table later if needed.
3. New admin section: "Groups" with sub-pages per kind (Councils, Ministries, Commissions). Reuses the table + sort + edit-icon convention from Stage 4-6.
4. Group-member UI: assign Person to Group with a function via a new `PersonRoleAssignment` row (`context_type='App\Models\Group'`).
5. Function holder queries: `$group->functionHolder('treasurer')`, `$group->members()`, `$person->groupsAsLeader()`.
6. Tests: group CRUD, assignment lifecycle, function-uniqueness when `max_holders=1`.

### Phase 6 — Children/Teenagers/Visitors + parental supervision

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
- Phase 5: drop `groups`, `group_functions`. Loses any group data created post-deploy.
- Phase 6: drop new natures, drop session helper. No structural rollback needed.

---

## Naming + conventions

- Models live in `app/Models/` (flat — no `app/Models/Person/` subnamespace) since methodist-app doesn't use module subdirectories.
- Enums live in `app/Enums/`. Existing convention: `MemberType.php`, `PastorRole.php` etc. — follow.
- Form classes live in `app/Livewire/Forms/` per the Stage 3 sweep — `PersonForm`, `PersonRelationshipForm`, `PersonRoleAssignmentForm`, `GroupForm`.
- Concerns/traits live in `app/Livewire/Concerns/` per Stage 6 — if a `HasNatures` or `HasRelationships` trait emerges, it goes there.
- The `UUID` for `Person` uses Laravel's `Str::uuid()` — decided here to avoid the binary-UUID setup from the template since methodist-app's existing tables use bigint IDs.

---

## Out of scope for this plan

- Multi-tenancy (already handled by Church scoping).
- Bulk import of legacy people from spreadsheets (separate operational concern).
- Communications (email/SMS) sent to People — Phase 7+ if needed.
- Calendar/event scheduling tied to People — separate domain.
- Person merge/dedupe UI for handling duplicates — Phase 7+ if needed.
- The `PersonFieldDefinition` dynamic-schema system from the template — deferred to Phase 6+ if methodist needs per-tenant field configurability. Otherwise hardcode the fields per nature.

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
2. **`person_type` for organizations:** the methodist context is people-focused — do we need the `Organization` enum case at all in v1, or can it be deferred? Default: include it (cheap, future-proof).
3. **Member nature defaults:** when migrating existing `users.member_type='member'` to `persons.natures=['member']`, do we also seed the per-member fields (`joined_at`, etc.) from anywhere we have them? Default: no — leave Person.additional_data['member'] as `{}` for backfilled rows.
4. **Drop deprecated columns immediately or wait?** Dropping `users.member_type` in Phase 1 is decisive but irreversible; keeping it for a release as a no-op safety net is more conservative. Default: keep through Phase 1, drop in Phase 2 cleanup.
5. **`PersonFieldDefinition`:** ship in v1 (more flexibility but more code), defer (hardcode fields per nature, cleaner v1)? Default: defer to Phase 6+.
