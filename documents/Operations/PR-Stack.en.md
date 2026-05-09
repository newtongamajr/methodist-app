# PR stack — code review through Person Architecture Phase 6

Eleven stacked PRs ship the entire trajectory from `main` up through Phase 6
of the Person Architecture. They are stacked (each PR's base is the head of
the next one down), not parallel, because each builds on its predecessor.
Trying to merge them out of order will produce conflicts.

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

**Merge order: #2 → #3 → #4 → #5 → #1 → #6 → #7 → #8 → #9 → #10 → #11.** As
each PR merges, GitHub will auto-retarget the next one in the chain to
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

## Why stacked, not one big PR

`main` had drifted ~48 commits behind the work-in-progress branches by the
time Phase 1 finished, because stages 4 / 5 / 6 were pushed but never opened
as PRs at the time. Bundling everything into one PR would be unreadable.
Splitting along the existing branch boundaries gives reviewers the same
chunks the work was actually executed in.

## What's *not* in this stack

Phase 6 ships the full Groups admin: `/admin/groups` CRUD with kind /
level / scope filters, an editor with conditional level-driven scope
pickers, an embedded Members section with modal add/edit, helper queries
on Group / Person / Church / EcclesiasticalRegion / District (`members`,
`functionHolder`, `groupsAsLeader`, `groupsByKind`, `national` scope), and
the People → Roles tab now picks a real group for council/ministry/commission
functions instead of the Phase 6 placeholder callout. The Functions CRUD
decision (Phase 6 plan §6) lands on **option 2**: the seeded function list
covers every real case so far, no `/admin/functions` UI in this PR; it can
grow later if a real demand surfaces.

Still deferred to Phase 7 + Phase 8:

- **Children / Teenagers / Visitors** activated UI + parental act-as flow (Phase 7)
- **Family tab on `/profile`** (Phase 7, alongside act-as)
- **Drop the duplicated cached columns** on org tables (Phase 8 — see plan doc)
- **Inline composition of Person tabs** into Region / District / Church editors (Phase 8 — currently the "Open as Person" button is the bridge)
- **Functions CRUD** — leave seeded-only unless a real demand surfaces; revisit if needed

See `documents/PersonArchitecture/README.en.md` § "Phased rollout" for the full breakdown.

## Verification before merging the chain

- [ ] All eleven PRs are open, in the right order, against the right base
- [ ] CI green on each (or at minimum on the topmost one — once merging starts the bases will retarget and CI re-runs)
- [ ] `php artisan migrate:fresh --seed` succeeds against the **head of the topmost PR** (#11) — proves the whole chain composes
- [ ] `php artisan test --compact` is green at HEAD of #11 (225 tests / 543 assertions at last run)
- [ ] `vendor/bin/pint --test --format agent` clean at HEAD of #11
- [ ] Translation parity: `en.json` / `pt_BR.json` / `es.json` all 547 keys at HEAD of #11
