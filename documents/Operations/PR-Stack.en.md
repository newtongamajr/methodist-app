# PR stack — code review through Person Architecture Phase 7

Thirteen stacked PRs ship the entire trajectory from `main` up through
Phase 7 of the Person Architecture. They are stacked (each PR's base is
the head of the next one down), not parallel, because each builds on its
predecessor. Trying to merge them out of order will produce conflicts.

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

**Merge order: #2 → #3 → #4 → #5 → #1 → #6 → #7 → #8 → #9 → #10 → #11 → #12 → #13.**
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

## Why stacked, not one big PR

`main` had drifted ~48 commits behind the work-in-progress branches by the
time Phase 1 finished, because stages 4 / 5 / 6 were pushed but never opened
as PRs at the time. Bundling everything into one PR would be unreadable.
Splitting along the existing branch boundaries gives reviewers the same
chunks the work was actually executed in.

## What's *not* in this stack

Phase 7 ships the children / teenagers / visitors UI plus the parental
act-as mechanic. `Person::inferAgeBasedNature()` returns Child for ages
0–11 and Teenager for 12–17; adults get no auto-suggest. `User::canActAs()`
gates parental act-as: requires a parent_of (or guardian_of) link to a
target Person who is a minor (by birthdate or by carrying child/teenager
nature when birthdate is missing). The session var `acting_as_person_id`
holds the target; an `acting-as-banner` Livewire component sits in the
app layout and surfaces "Acting on behalf of …" with a Stop button. A new
Family tab on `/profile` shows parents / spouse / children / wards /
godchildren and renders an "Act as" button next to each Person the
current user can act on. Visitor quick-add adds a "New visitor" button
on `/admin/people` that pre-seeds nature=visitor on the editor.

Still deferred to Phase 8:

- **Wire prayer signups + fasting entries to write rows scoped to the effective Person** (Phase 8) — Phase 7 ships the act-as session toggle + Family UI; the controllers that consume it via `User::effectivePerson()` are a follow-up
- **Cron auto-promote** when a child crosses the teenager threshold (Phase 8 — operations job)
- **Drop the duplicated cached columns** on org tables (Phase 8 — see plan doc)
- **Inline composition of Person tabs** into Region / District / Church editors (Phase 8 — currently the "Open as Person" button is the bridge)
- **Functions CRUD** — leave seeded-only unless a real demand surfaces; revisit if needed

See `documents/PersonArchitecture/README.en.md` § "Phased rollout" for the full breakdown.

## Verification before merging the chain

- [ ] All thirteen PRs are open, in the right order, against the right base
- [ ] CI green on each (or at minimum on the topmost one — once merging starts the bases will retarget and CI re-runs)
- [ ] `php artisan migrate:fresh --seed` succeeds against the **head of the topmost PR** (#13) — proves the whole chain composes
- [ ] `php artisan test --compact` is green at HEAD of #13 (251 tests / 589 assertions at last run)
- [ ] `vendor/bin/pint --test --format agent` clean at HEAD of #13
- [ ] Translation parity: `en.json` / `pt_BR.json` / `es.json` all 570 keys at HEAD of #13
