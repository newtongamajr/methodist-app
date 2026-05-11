# Stack de PRs — code review até o baseline Alpha-01

Vinte PRs empilhados entregam toda a trajetória entre `main` e a
Phase 8 da Person Architecture, mais o reorg do menu Admin, o polish do
admin user, o polish de Person/profile (act-as nos registros
compartilhados, paridade do profile com People, modelo de dados ciente
de contato), as mudanças pedidas pelo pastor no fluxo de oração
(refinamento de terminologia, criação de PrayerSchedule multi-data,
inscrição em lote em /prayer + relatório, filtro de modo), o rewrite
do modelo de audiência de Posts (tabela post_scopes, cover via
cropper, polish da listagem pública, atribuição com setinha) e o
baseline Alpha-01 (squash das migrations, schema enxuto com Redis,
rename dos pivots do spatie, Permission model local) no topo. Eles
estão **empilhados** (a base de cada PR é o head do PR de baixo), não
são paralelos, porque cada um depende do anterior. Tentar mergear fora
de ordem vai gerar conflitos.

## Ordem de merge (de baixo para cima)

| # | Branch | Base | Escopo |
|---|---|---|---|
| 2 | `code-review-stages-1-3` | `main` | Security + correctness + Livewire idiom + 9 Form Objects |
| 3 | `stage-4-flux-tables` | `code-review-stages-1-3` | 8 tabelas admin → `flux:table` |
| 4 | `stage-5-flux-pickers` | `stage-4-flux-tables` | Flux pickers, file-upload, modal, kanban dos comentários |
| 5 | `stage-6-cleanup` | `stage-5-flux-pickers` | Sweep de cleanup + ⌘K command palette + plano da Person Architecture |
| 1 | `persons-phase-1` | `stage-6-cleanup` | Person Architecture Phase 1 (schema + foundation + admin scopes + districts) |
| 6 | `persons-phase-2` | `persons-phase-1` | Person Architecture Phase 2 (editor `/admin/people` com tabs + trait `ManagesPersons` + UI dos satellites) |
| 7 | `persons-phase-3` | `persons-phase-2` | Person Architecture Phase 3 (tab Family + helpers de family-tree no Person) |
| 8 | `persons-phase-4` | `persons-phase-3` | Person Architecture Phase 4 (`PersonRoleAssignmentForm` genérico + enforcement de `max_holders` + tab Roles) |
| 9 | `persons-phase-5` | `persons-phase-4` | Person Architecture Phase 5 (regra condicional de `district_id` required no editor de church + testes dedicados de CRUD) |
| 10 | `persons-orgs-unification` | `persons-phase-5` | Unificação Orgs-as-Persons (Region / District / Church cada um respaldado por um Org-Person; Sede Nacional como uma ER especial) |
| 11 | `persons-phase-6` | `persons-orgs-unification` | Person Architecture Phase 6 (admin de Groups — councils / ministries / commissions em 4 níveis com assignments de membros + helpers) |
| 12 | `persons-identity-polish` | `persons-phase-6` | Polish da Identity tab (nature Youth, enum MaritalStatus, datas com input typeable, label condicional Birthdate/Foundation date, filtro de natures por person_type) |
| 13 | `persons-phase-7` | `persons-identity-polish` | Person Architecture Phase 7 (inferência de nature por idade, toggle de act-as parental + banner, tab Family no /profile, quick-add de visitor) |
| 14 | `persons-phase-8` | `persons-phase-7` | Person Architecture Phase 8 (composição inline das tabs de Person nos editores de org, observer de sync Person→Org, command nightly de promoção por idade) |
| 15 | `persons-admin-reorg` | `persons-phase-8` | Reorg do menu Admin (submenus Posts management / Structure / People / Miscellaneous); drop persons.photo_path → photo collection no MediaLibrary; tab User account no editor de Person; ação Schedules nas linhas de Prayer Campaign |
| 16 | `persons-admin-user-polish` | `persons-admin-reorg` | Polish do editor admin de user: remove phone, confirm password + view toggle, `App\Models\Role` custom com coluna description, campo appearance; gestão de churches movida para a página `/admin/users/{id}/churches` com add por listbox searchable + toggle de primary por linha; pivot `ChurchUser` + observer garantem só uma primary |
| 17 | `persons-act-as-and-photos` | `persons-admin-user-polish` | Plumbing de act-as para fasting / prayer / posts (`person_id` nas quatro tabelas compartilhadas, exibição `:author in the name of :person`); paridade do profile com People (Identity / Contacts / Addresses / Documents / Family delegam aos componentes admin, com gate para o owner); widget de foto da Person com cropper + espelhamento avatar→Person; novos enums `Gender` / `BrazilianState` / `Country` que dirigem o register, máscaras de contato e o coupling state↔country no Address; expansão das relações derivadas no grafo familiar (irmãos / avós / tios / sobrinhos / primos / sogros / cunhados / padrastos / enteados) com labels gender-aware |
| 18 | `pastor-asked-changes` | `persons-act-as-and-photos` | Polish do fluxo de oração pedido pelo pastor: refinamento de terminologia (`slot` → `schedule`; `prayers` → `people of praying` em contextos de contagem) no source e nas traduções; criação de PrayerSchedule multi-data via `<flux:pillbox multiple>` (uma linha por data escolhida, tags `DD/MM/YY`); callout + modal de inscrição em lote em `/prayer` que aplica um par (modo, horário de início) a um intervalo de datas com relatório localizado das datas puladas (`not_found` / `full` / `already` / `past` / `out_of_window`); novo filtro de Modo (Any / At the church / From home) como filtro hard no calendário diário; lista de sugestões agora exclui horários nos quais a Person efetiva já está inscrita para que cliques não virem no-ops idempotentes |
| 19 | `new-posts-features` | `pastor-asked-changes` | Rewrite do modelo de audiência de Posts: remove `posts.scope` + `posts.church_id` e introduz `post_scopes` (formas national / region / district / church, visibilidade OR); `church_user` vira a fonte de scope dos admins com `region_id` / `district_id` nullable (e `User::manageableRegions/Districts/Churches` lendo de lá); cover image com cropper 16:9 e factory Alpine genérica `imageCropper(config)` (que também conserta o silent-no-op do avatar / Person photo); índice admin de posts ganha thumbnail 16:9 na coluna do título e ícone pencil-square de editar; listagem pública `/posts` com cards que sobem no hover, borda rosa, pílulas coloridas de likes/comments e CTA "Read the whole story →"; botão "Back to posts" no detalhe; atribuição com setinha (`<autor> → <participante>`) substitui o "in the name of" em comentários + signups de oração; rodapé `<x-galileosoft-footer>` compartilhado no layout app |
| 20 | `alpha-01` | `new-posts-features` | Baseline da v1.00. Faz squash do histórico de 52 migrations em 33 arquivos `0001_01_01_*` (um por tabela; cada um é um `DB::statement` enxuto sobre o `CREATE TABLE` final, embrulhado em `Schema::disable / enableForeignKeyConstraints()` para que o cluster de FKs circulares não force ordem topológica). Remove as seis tabelas que o Redis já cobre (`cache` / `cache_locks` / `sessions` / `jobs` / `job_batches` / `failed_jobs`); mantém `password_reset_tokens` porque é storage de auth, não cache. Renomeia os pivots do spatie: `model_has_permissions → user_permissions`, `model_has_roles → user_roles`, `role_has_permissions → role_permissions` (FK + index names renomeados em lockstep, `config/permission.php` ajustado). Adiciona `App\Models\Permission` local (espelha o padrão de `App\Models\Role`) para que futuras extensões de Permission morem no projeto sem fork do package |

**Ordem de merge: #2 → #3 → #4 → #5 → #1 → #6 → #7 → #8 → #9 → #10 → #11 → #12 → #13 → #14 → #15 → #16 → #17 → #18 → #19 → #20.**
À medida que cada PR é mergeado, o GitHub re-aponta automaticamente a base
do próximo da fila para `main` (ou para a nova base, se aplicável). Não use
squash-merge — preserve o histórico de commits para que a intenção em
camadas continue legível no `git log`.

URLs dos PRs:

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

## Por que empilhado, e não um PR único

A `main` ficou ~48 commits atrás dos branches de trabalho quando a Phase 1
foi concluída, porque os stages 4 / 5 / 6 foram pushados mas nunca abertos
como PR na época. Juntar tudo num PR só ficaria ilegível. Dividir nos limites
dos branches que já existiam dá ao revisor exatamente as mesmas fatias em
que o trabalho foi executado.

## O que *não* está nesta stack

A Phase 6 entrega o admin completo de Groups: CRUD em `/admin/groups` com
filtros de kind / level / scope, um editor com pickers de scope condicionais
ao level escolhido, uma section embedada de Members com modal de
add/edit, helpers em Group / Person / Church / EcclesiasticalRegion /
District (`members`, `functionHolder`, `groupsAsLeader`, `groupsByKind`,
escopo `national`), e a tab People → Roles agora seleciona um group real
para functions de council/ministry/commission no lugar do callout de
placeholder da Phase 6. A decisão sobre CRUD de Functions (Phase 6 plan §6)
ficou na **opção 2**: o seed cobre todos os casos reais até agora, não
entra `/admin/functions` neste PR; pode crescer depois se aparecer demanda
real.

A Phase 8 fecha a fila de cleanup acumulada nas Phases 1–7. Três coisas
entram: (a) os editores de Region / District / Church ganham um
`flux:tab.group` (Details / Contacts / Addresses / Documents [+
Administrators na Church]) ao editar um registro existente — admins não
precisam mais ir e voltar via "Open as Person" para edits triviais nos
satellites, e as tabs de Person reusam os MFCs existentes
`livewire:admin.people.{contacts,addresses,documents}` passando
`:person-id="$row->person_id"`; (b) `PersonObserver::updated()` espelha
`Person.name` de volta na linha de org vinculada quando a Person carrega
uma org nature, fechando o gap de drift no outro sentido; (c)
`php artisan person:promote-minors` (agendado nightly às 02:15) percorre
a tabela de people — child → teenager passando dos 12, teenager → adult
passando dos 18 — com `--dry-run` reportando contagem sem escrever.

Continuam adiados para PRs de cleanup futuros:

- **Remover as colunas duplicadas em cache** nas tabelas de org — rewrite de alto blast-radius (ver plano)
- **CRUD de Functions** — fica seeded-only a menos que apareça demanda real

O PR #17 fechou o item antigo de wiring de prayer/fasting com act-as (estendido também a post likes + comments e materializado em uma coluna `person_id`, em vez do `for_person_id` originalmente planejado).

Detalhes em `documents/PersonArchitecture/README.en.md` § "Phased rollout".

## Verificação antes de mergear a stack

- [ ] Os vinte PRs estão abertos, na ordem certa, contra a base certa
- [ ] CI verde em cada um (ou no mínimo no topo — assim que o merge começa, as bases re-apontam e o CI roda de novo)
- [ ] `php artisan migrate:fresh --seed` roda do início ao fim no **head do PR do topo** (#20) — prova que a stack inteira compõe; depois do #20 a contagem de migrations cai de 52 arquivos individuais para 33 baselines consolidados por tabela
- [ ] `php artisan test --compact` verde no HEAD do #20 (276 tests / 644 assertions na última execução)
- [ ] `vendor/bin/pint --test --format agent` limpo no HEAD do #20
- [ ] Paridade de traduções: `en.json` / `pt_BR.json` / `es.json` todos com 749 keys no HEAD do #20
