# PR stack — code review through Orgs-as-Persons unification

Ten stacked PRs ship the entire trajectory from `main` up through the
Orgs-as-Persons unification (the architectural extension that landed between
Phase 5 and Phase 6 of the Person Architecture). They are stacked (each PR's
base is the head of the next one down), not parallel, because each builds on
its predecessor. Trying to merge them out of order will produce conflicts.

## Merge order (bottom up)

| # | Branch | Base | Scope |
|---|---|---|---|
| 2 | `code-review-stages-1-3` | `main` | Security + correctness + Livewire idiom + 9 Form Objects |
| 3 | `stage-4-flux-tables` | `code-review-stages-1-3` | 8 admin tables → `flux:table` |
| 4 | `stage-5-flux-pickers` | `stage-4-flux-tables` | Flux pickers, file-upload, modal, comments kanban |
| 5 | `stage-6-cleanup` | `stage-5-flux-pickers` | Cleanup sweep + ⌘K command palette + Person plan doc |
| 1 | `persons-phase-1` | `stage-6-cleanup` | Person Architecture Phase 1 (schema + foundation + admin scopes + districts) |
| 6 | `persons-phase-2` | `persons-phase-1` | Person Architecture Phase 2 (`/admin/people` editor with tabs + `ManagesPersons` trait + satellites UI) |
| 7 | `persons-phase-3` | `persons-phase-2` | Person Architecture Phase 3 (Family tab + Person family-tree query helpers) |
| 8 | `persons-phase-4` | `persons-phase-3` | Person Architecture Phase 4 (generic `PersonRoleAssignmentForm` + `max_holders` enforcement + Roles tab) |
| 9 | `persons-phase-5` | `persons-phase-4` | Person Architecture Phase 5 (conditional `district_id` required on church forms + dedicated CRUD tests) |
| 10 | `persons-orgs-unification` | `persons-phase-5` | Orgs-as-Persons unification (Region / District / Church each backed by an Org-Person; National HQ as a special ER row) |

**Merge order: #2 → #3 → #4 → #5 → #1 → #6 → #7 → #8 → #9 → #10.** As each PR
merges, GitHub will auto-retarget the next one in the chain to `main` (or to
whatever the new base is). Do not squash-merge — preserve the commit history
so the layered intent stays legible in `git log`.

PR URLs:

- https://github.com/newtongamajr/methodist-app/pull/2
- https://github.com/newtongamajr/methodist-app/pull/3
- https://github.com/newtongamajr/methodist-app/pull/4
- https://github.com/newtongamajr/methodist-app/pull/5
- https://github.com/newtongamajr/methodist-app/pull/1
- https://github.com/newtongamajr/methodist-app/pull/6
- https://github.com/newtongamajr/methodist-app/pull/7
- https://github.com/newtongamajr/methodist-app/pull/8
- https://github.com/newtongamajr/methodist-app/pull/9
- https://github.com/newtongamajr/methodist-app/pull/10

## Why stacked, not one big PR

`main` had drifted ~48 commits behind the work-in-progress branches by the
time Phase 1 finished, because stages 4 / 5 / 6 were pushed but never opened
as PRs at the time. Bundling everything into one PR would be unreadable.
Splitting along the existing branch boundaries gives reviewers the same
chunks the work was actually executed in.

## What's *not* in this stack

The Orgs-as-Persons PR adds 4 new natures (`national_headquarters`,
`ecclesiastical_region`, `district`, `church`), adds a `person_id` FK
(NOT NULL + unique) on each org table backfilled with one Org-Person per
existing row, adds `code` to `churches`, and seeds the National HQ as a
special ER row using a new `RegionKind::NationalHeadquarters`. The People
index hides org-Persons by default; an "Include organizations" toggle
opts them in. Region/District/Church editors gain an "Open as Person"
button that links to `/admin/people/{personId}/edit` so admins can manage
the Org Person's contacts / addresses / documents through the existing
tabs without inline composition.

Still deferred to Phase 6 onwards:

- **Drop the duplicated `name` / `email` / `phone` / `address` columns on org tables** — currently they're a denormalized cache kept in sync by the editor `save()` paths; full migration to "Person is the only source" can ship later
- **Inline composition of Person tabs into org editors** — currently you click "Open as Person" to switch context; future iteration could embed Contacts / Addresses / Documents inside the church editor itself
- **Group (Council / Ministry / Commission) admin UI** + group-scoped assignments (Phase 6 — the Roles modal currently shows a "coming in Phase 6" callout when a group function is selected)
- **Functions CRUD** — decision deferred to Phase 6 per the plan doc note
- **Children / Teenagers / Visitors UI + parental act-as flow** (Phase 7)
- **Family tab on `/profile`** (Phase 7, alongside act-as)

See `documents/PersonArchitecture/README.en.md` § "Phased rollout" for the full breakdown.

## Verification before merging the chain

- [ ] All ten PRs are open, in the right order, against the right base
- [ ] CI green on each (or at minimum on the topmost one — once merging starts the bases will retarget and CI re-runs)
- [ ] `php artisan migrate:fresh --seed` succeeds against the **head of the topmost PR** (#10) — proves the whole chain composes
- [ ] `php artisan test --compact` is green at HEAD of #10 (215 tests / 515 assertions at last run)
- [ ] `vendor/bin/pint --test --format agent` clean at HEAD of #10
- [ ] Translation parity: `en.json` / `pt_BR.json` / `es.json` all 525 keys at HEAD of #10
