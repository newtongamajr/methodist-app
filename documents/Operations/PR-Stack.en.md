# PR stack — code review through Alpha-01 baseline

Twenty stacked PRs ship the entire trajectory from `main` up through
Phase 8 of the Person Architecture plus the Admin menu reorg, the Admin
user polish, the Person/profile polish (act-as plumbing for shared
records, profile parity, contact-aware data), the Pastor-asked prayer
changes (terminology refresh, multi-date schedule create, /prayer bulk
sign-up + report, mode filter), the Posts audience model rewrite
(post_scopes table, cropper-backed cover, public list polish, arrow
attribution), and the Alpha-01 baseline (migration squash, Redis-pruned
schema, spatie pivot rename, local Permission model) on top. They are
stacked (each PR's base is the head of the next one down), not parallel,
because each builds on its predecessor. Trying to merge them out of
order will produce conflicts.

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
| 16 | `persons-admin-user-polish` | `persons-admin-reorg` | Admin user editor polish: drop phone, password confirm + view toggle, custom `App\Models\Role` w/ description column, appearance field; church management moved to `/admin/users/{id}/churches` page with searchable add + per-row primary toggle; `ChurchUser` pivot model + observer enforce single-primary |
| 17 | `persons-act-as-and-photos` | `persons-admin-user-polish` | Act-as plumbing for fasting / prayer / posts (`person_id` on the four shared tables, `:author in the name of :person` display); profile parity with People (Identity / Contacts / Addresses / Documents / Family delegate to admin components, gated for the owner); cropper-backed Person photo + avatar→Person mirror; new `Gender` / `BrazilianState` / `Country` enums driving the register form, contact masks, and address state↔country coupling; expanded family-graph derivations (siblings / grandparents / aunts/uncles / nieces/nephews / cousins / parents/children/siblings-in-law / stepparents/stepchildren) with gender-aware labels |
| 18 | `pastor-asked-changes` | `persons-act-as-and-photos` | Prayer-feature pastor-asked polish: terminology refresh (`slot` → `schedule`; `prayers` → `people of praying` in count contexts) across source and translations; multi-date `PrayerSchedule` create via `<flux:pillbox multiple>` (one row per picked date, `DD/MM/YY` tags); `/prayer` bulk sign-up callout + modal that fan-out a single (mode, start time) across a date range with a localized skip-reason report (`not_found` / `full` / `already` / `past` / `out_of_window`); new Mode hard-filter (Any / At the church / From home) on the day calendar; suggestions list now excludes already-joined slots so clicks aren't idempotent no-ops |
| 19 | `new-posts-features` | `pastor-asked-changes` | Posts audience model rewrite: drops `posts.scope` + `posts.church_id` for a many-to-one `post_scopes` table (national / region / district / church shapes, OR-visibility); `church_user` becomes the admin-scope source via nullable `region_id` / `district_id` columns (with `User::manageableRegions/Districts/Churches` reading from it); cropper-backed 16:9 cover image with the cropper factored into a generic `imageCropper(config)` Alpine component (also fixes the avatar / Person photo silent-no-op race); admin posts index gets a 16:9 cover thumb in the title cell + pencil-square edit icon; public `/posts` cards lift on hover with rose-accent border, colored like/comment pills, and a "Read the whole story →" CTA; `Back to posts` button on the show page; arrow attribution (`<author> → <participant>`) replaces the verbose ":author in the name of :person" sentence on comments + prayer signups; shared `<x-galileosoft-footer>` in the app layout |
| 20 | `alpha-01` | `new-posts-features` | v1.00 starting baseline. Squashes the 52-migration history into 33 per-table `0001_01_01_*` files (each is a thin `DB::statement` over the table's final `CREATE TABLE` DDL, framed by `Schema::disable / enableForeignKeyConstraints()` so the circular FK cluster doesn't force a topological order). Drops the six framework tables Redis already covers (`cache` / `cache_locks` / `sessions` / `jobs` / `job_batches` / `failed_jobs`); keeps `password_reset_tokens` because it's the auth-flow store. Renames spatie pivots: `model_has_permissions → user_permissions`, `model_has_roles → user_roles`, `role_has_permissions → role_permissions` (FK + index names renamed in lockstep, `config/permission.php` updated to match). Adds local `App\Models\Permission` (mirrors `App\Models\Role` pattern) so future Permission extensions live in-project without forking the vendor package |

**Merge order: #2 → #3 → #4 → #5 → #1 → #6 → #7 → #8 → #9 → #10 → #11 → #12 → #13 → #14 → #15 → #16 → #17 → #18 → #19 → #20.**
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
- https://github.com/newtongamajr/methodist-app/pull/16
- https://github.com/newtongamajr/methodist-app/pull/17
- https://github.com/newtongamajr/methodist-app/pull/18
- https://github.com/newtongamajr/methodist-app/pull/19
- https://github.com/newtongamajr/methodist-app/pull/20

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
- **Functions CRUD** — leave seeded-only unless a real demand surfaces

PR #17 closes the long-deferred prayer/fasting act-as wiring (now extended to post likes + comments and tracked by a `person_id` column rather than the originally-planned `for_person_id`).

See `documents/PersonArchitecture/README.en.md` § "Phased rollout" for the full breakdown.

## Verification before merging the chain

- [ ] All twenty PRs are open, in the right order, against the right base
- [ ] CI green on each (or at minimum on the topmost one — once merging starts the bases will retarget and CI re-runs)
- [ ] `php artisan migrate:fresh --seed` succeeds against the **head of the topmost PR** (#20) — proves the whole chain composes; after #20 the migration count drops from 52 individual files to 33 consolidated per-table baselines
- [ ] `php artisan test --compact` is green at HEAD of #20 (276 tests / 644 assertions at last run)
- [ ] `vendor/bin/pint --test --format agent` clean at HEAD of #20
- [ ] Translation parity: `en.json` / `pt_BR.json` / `es.json` all 749 keys at HEAD of #20

## Post-Alpha-01 follow-ups (no longer stacked)

Once the Alpha-01 chain above lands on `main`, subsequent PRs sit
directly on `main` again — the stack closes. Tagging convention: each
post-baseline PR cuts an annotated `vX.YZ` tag at the point it's
opened.

| # | Branch | Tag | Base | Scope |
|---|---|---|---|---|
| 21 | `posts-improvements` | `v1.01` | `main` | Editor pending-file previews; PDF + video thumb conversions via `spatie/pdf-to-image` + `php-ffmpeg/php-ffmpeg` (with `install-media-deps.sh` covering the system-side `imagick` / `imagemagick` / `ghostscript` / `ffmpeg` install + ImageMagick PDF-policy patch); `quality(95)` on every image conversion across User / Person / Post; Livewire temp upload cap lifted from the 12 MB default to 100 MB so video uploads stop being silently rejected; `APP_TIMEZONE` defaults to `America/Sao_Paulo` so `datetime-local` `published_at` writes the wall-clock the admin actually picked; public post show document viewer widened to 95vw / 1400px with thumb card grid; cover image swapped to the generic `imageCropper` Alpine factory locked at 16:9 |
| 22 | `assignment-roles` | `v1.02` | `main` | New `Admin → Structure → Assignment roles` CRUD over the existing `assignment_roles` lookup table (gated by `church.manage`, alongside Regions / Districts / Churches / Groups). Three Livewire MFC pages: an index with search + status filter and per-row actions (View → Flux modal with read-only details; Edit; People-with-this-role-and-which-group; Delete via the standard `x-confirm-delete` modal), an editor with auto-slug fallback through `App\Support\GenerateUniqueSlug`, and a People-by-role page that joins `PersonRoleAssignment` → person + group + scope (church / district / region / national) + function with active/ended status filter. New `App\Livewire\Forms\AssignmentRoleForm` (validates name + unique slug + description + is_active). Routes registered under `EnsureCanManageChurches`; menu wired into the desktop dropdown, mobile sidebar, and command palette. Ships pt_BR + es translations for every new key; also fills in `Group` / `Groups` / `Edit group` / `New group` / `All groups` / `No groups yet.` / `No matching groups for this function.` / `Delete this group and end every member assignment?` / `Pick a group…` / `Group created.` / `Group updated.` and a missing `Scope` key that were stubbed in English. Five new Pest tests cover authorization, create-with-auto-slug, update, delete, and the people-by-role listing |
| 23 | `paginator-translations` | `v1.03` | `main` | Translates the Flux paginator summary line. Flux's pagination view (`vendor/livewire/flux/stubs/resources/views/flux/pagination.blade.php`) builds it out of four bare top-level `__()` keys (`Showing` / `to` / `of` / `results`) — none existed in the JSON keysets, so it stayed in English under pt_BR / es. Adds the four keys to en / pt_BR / es ("Exibindo 1 a 3 de 3 resultados" / "Mostrando 1 a 3 de 3 resultados") and translates the prev / next arrow labels in `lang/{pt_BR,es}/pagination.php` (still English from the Laravel stub) |

PR URL: https://github.com/newtongamajr/methodist-app/pull/21
PR URL: https://github.com/newtongamajr/methodist-app/pull/22
PR URL: https://github.com/newtongamajr/methodist-app/pull/23
