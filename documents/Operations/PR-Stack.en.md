# PR stack — code review through Person Architecture Phase 5

Nine stacked PRs ship the entire trajectory from `main` up through Phase 5 of
the Person Architecture. They are stacked (each PR's base is the head of the
next one down), not parallel, because each builds on its predecessor. Trying
to merge them out of order will produce conflicts.

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

**Merge order: #2 → #3 → #4 → #5 → #1 → #6 → #7 → #8 → #9.** As each PR
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

## Why stacked, not one big PR

`main` had drifted ~48 commits behind the work-in-progress branches by the
time Phase 1 finished, because stages 4 / 5 / 6 were pushed but never opened
as PRs at the time. Bundling everything into one PR would be unreadable.
Splitting along the existing branch boundaries gives reviewers the same
chunks the work was actually executed in.

## What's *not* in this stack

Phase 5 closes out Districts: the CRUD UI and church-editor selector
already shipped in Phase 1, and Phase 5 adds the conditional-required rule
(once a region has at least one active district, every church in that
region must pick one) plus dedicated tests for the District lifecycle
(create / edit / delete / fail-delete-with-churches / region filter / slug
uniqueness scoped per region) and the church-editor required-ness gate.
Still deferred to Phases 6–7:

- **Group (Council / Ministry / Commission) admin UI** + group-scoped assignments (Phase 6 — the Roles modal currently shows a "coming in Phase 6" callout when a group function is selected)
- **Functions CRUD** — decision deferred to Phase 6: leave seeded-only or grow `/admin/functions` then, depending on whether real church-staff demand surfaces
- **Children / Teenagers / Visitors** activated UI + parental act-as flow (Phase 7)
- **Family tab on `/profile`** (Phase 7, alongside act-as)
- **Per-nature additional fields** (`PersonFieldDefinition`) — deferred per Q3 default; v1 keeps the Identity tab generic

See `documents/PersonArchitecture/README.en.md` § "Phased rollout" for the full breakdown.

## Verification before merging the chain

- [ ] All nine PRs are open, in the right order, against the right base
- [ ] CI green on each (or at minimum on the topmost one — once merging starts the bases will retarget and CI re-runs)
- [ ] `php artisan migrate:fresh --seed` succeeds against the **head of the topmost PR** (#9) — proves the whole chain composes
- [ ] `php artisan test --compact` is green at HEAD of #9 (214 tests / 508 assertions at last run)
- [ ] `vendor/bin/pint --test --format agent` clean at HEAD of #9
- [ ] Translation parity: `en.json` / `pt_BR.json` / `es.json` all 521 keys at HEAD of #9
