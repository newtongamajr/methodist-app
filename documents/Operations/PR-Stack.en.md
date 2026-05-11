# PR stack — code review through Admin reorg

Fifteen stacked PRs ship the entire trajectory from `main` up through Phase
8 of the Person Architecture plus the Admin menu reorg layered on top. They
are stacked (each PR's base is the head of the next one down), not parallel,
because each builds on its predecessor. Trying to merge them out of order
will produce conflicts.

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
| 11 | `persons-phase-6` | `persons-orgs-unification` | Person Architecture Phase 6 (Groups admin — councils / ministries / commissions at 4 levels with member assignments + helper queries) |
| 12 | `persons-identity-polish` | `persons-phase-6` | Identity tab polish (Youth nature, MaritalStatus enum, type-able dates, conditional Birthdate/Foundation date label, nature filter by person_type) |
| 13 | `persons-phase-7` | `persons-identity-polish` | Person Architecture Phase 7 (age-based nature inference, parental act-as session toggle + banner, Profile Family tab, visitor quick-add) |
| 14 | `persons-phase-8` | `persons-phase-7` | Person Architecture Phase 8 (inline Person tabs into org editors, Person→Org name sync observer, nightly age-promotion command) |
| 15 | `persons-admin-reorg` | `persons-phase-8` | Admin menu reorg (Posts management / Structure / People / Miscellaneous submenus); drop persons.photo_path → MediaLibrary photo collection; User-account tab on Person editor; Schedules action on Prayer Campaign rows |

**Merge order: #2 → #3 → #4 → #5 → #1 → #6 → #7 → #8 → #9 → #10 → #11 → #12 → #13 → #14 → #15.**
As each PR merges, GitHub will auto-retarget the next one in the chain to
`main` (or to whatever the new base is). Do not squash-merge — preserve the
commit history so the layered intent stays legible in `git log`.

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
- https://github.com/newtongamajr/methodist-app/pull/11
- https://github.com/newtongamajr/methodist-app/pull/12
- https://github.com/newtongamajr/methodist-app/pull/13
- https://github.com/newtongamajr/methodist-app/pull/14
- https://github.com/newtongamajr/methodist-app/pull/15

## Why stacked, not one big PR

`main` had drifted ~48 commits behind the work-in-progress branches by the
time Phase 1 finished, because stages 4 / 5 / 6 were pushed but never opened
as PRs at the time. Bundling everything into one PR would be unreadable.
Splitting along the existing branch boundaries gives reviewers the same
chunks the work was actually executed in.

## What's *not* in this stack

Phase 8 closes out the cleanup queue accumulated through Phases 1–7.
Three things ship: (a) the Region / District / Church editors gain a
`flux:tab.group` (Details / Contacts / Addresses / Documents [+
Administrators on Church]) when editing an existing record — admins no
longer round-trip through "Open as Person" for routine satellite edits,
and the Person tabs reuse the existing `livewire:admin.people.{contacts,
addresses,documents}` MFCs by passing `:person-id="$row->person_id"`;
(b) `PersonObserver::updated()` mirrors `Person.name` back to the linked
org row when the Person carries an org nature, closing the drift gap
from the other direction; (c) `php artisan person:promote-minors`
(scheduled nightly at 02:15) walks the people table — child → teenager
past 12, teenager → adult past 18 — with `--dry-run` reporting counts
without writing.

Still deferred to future cleanup PRs:

- **Drop the duplicated cached columns** on org tables — high blast-radius rewrite (see plan doc)
- **Wire prayer signups + fasting entries to write rows scoped to `User::effectivePerson()`** — needs schema (`for_person_id`) + controller changes
- **Functions CRUD** — leave seeded-only unless a real demand surfaces

See `documents/PersonArchitecture/README.en.md` § "Phased rollout" for the full breakdown.

## Verification before merging the chain

- [ ] All fifteen PRs are open, in the right order, against the right base
- [ ] CI green on each (or at minimum on the topmost one — once merging starts the bases will retarget and CI re-runs)
- [ ] `php artisan migrate:fresh --seed` succeeds against the **head of the topmost PR** (#15) — proves the whole chain composes
- [ ] `php artisan test --compact` is green at HEAD of #15 (266 tests / 621 assertions at last run)
- [ ] `vendor/bin/pint --test --format agent` clean at HEAD of #15
- [ ] Translation parity: `en.json` / `pt_BR.json` / `es.json` all 595 keys at HEAD of #15
