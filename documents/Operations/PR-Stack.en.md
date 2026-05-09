# PR stack — code review through Person Architecture Phase 4

Eight stacked PRs ship the entire trajectory from `main` up through Phase 4 of
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

**Merge order: #2 → #3 → #4 → #5 → #1 → #6 → #7 → #8.** As each PR merges,
GitHub will auto-retarget the next one in the chain to `main` (or to whatever
the new base is). Do not squash-merge — preserve the commit history so the
layered intent stays legible in `git log`.

PR URLs:

- https://github.com/newtongamajr/methodist-app/pull/2
- https://github.com/newtongamajr/methodist-app/pull/3
- https://github.com/newtongamajr/methodist-app/pull/4
- https://github.com/newtongamajr/methodist-app/pull/5
- https://github.com/newtongamajr/methodist-app/pull/1
- https://github.com/newtongamajr/methodist-app/pull/6
- https://github.com/newtongamajr/methodist-app/pull/7
- https://github.com/newtongamajr/methodist-app/pull/8

## Why stacked, not one big PR

`main` had drifted ~48 commits behind the work-in-progress branches by the
time Phase 1 finished, because stages 4 / 5 / 6 were pushed but never opened
as PRs at the time. Bundling everything into one PR would be unreadable.
Splitting along the existing branch boundaries gives reviewers the same
chunks the work was actually executed in.

## What's *not* in this stack

Phase 4 retires `PastorAssignmentForm` in favor of a generic
`PersonRoleAssignmentForm`, enforces `functions.max_holders` on the
PersonRoleAssignment observer (e.g. only one active Main Pastor per church),
and adds a Roles tab on the `/admin/people` editor that lets admins manage
**every** assignment a Person has — pastor at a church, admin at a region /
district / church / national. Still deferred to Phases 5–7:

- **Group (Council / Ministry / Commission) admin UI** + group-scoped assignments (Phase 6 — the Roles modal currently shows a "coming in Phase 6" callout when a group function is selected)
- **Children / Teenagers / Visitors** activated UI + parental act-as flow (Phase 7)
- **Family tab on `/profile`** — admin-only entry point ships in Phase 3; the user-facing version follows once Phase 7's parental act-as is wired
- **Per-nature additional fields** (`PersonFieldDefinition`) — deferred per Q3 default; v1 keeps the Identity tab generic

See `documents/PersonArchitecture/README.en.md` § "Phased rollout" for the full breakdown.

## Verification before merging the chain

- [ ] All eight PRs are open, in the right order, against the right base
- [ ] CI green on each (or at minimum on the topmost one — once merging starts the bases will retarget and CI re-runs)
- [ ] `php artisan migrate:fresh --seed` succeeds against the **head of the topmost PR** (#8) — proves the whole chain composes
- [ ] `php artisan test --compact` is green at HEAD of #8 (200 tests / 474 assertions at last run)
- [ ] `vendor/bin/pint --test --format agent` clean at HEAD of #8
- [ ] Translation parity: `en.json` / `pt_BR.json` / `es.json` all 521 keys at HEAD of #8
