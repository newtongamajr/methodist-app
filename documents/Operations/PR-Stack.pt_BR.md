# Stack de PRs — code review até a Phase 8 da Person Architecture

Quatorze PRs empilhados entregam toda a trajetória entre `main` e a Phase 8
da Person Architecture (a fase de cleanup). Eles estão **empilhados** (a
base de cada PR é o head do PR de baixo), não são paralelos, porque cada um
depende do anterior. Tentar mergear fora de ordem vai gerar conflitos.

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

**Ordem de merge: #2 → #3 → #4 → #5 → #1 → #6 → #7 → #8 → #9 → #10 → #11 → #12 → #13 → #14.**
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
- **Wirar prayer signups + fasting entries para escrever rows scoped em `User::effectivePerson()`** — precisa schema (`for_person_id`) + mudanças de controller
- **CRUD de Functions** — fica seeded-only a menos que apareça demanda real

Detalhes em `documents/PersonArchitecture/README.en.md` § "Phased rollout".

## Verificação antes de mergear a stack

- [ ] Os quatorze PRs estão abertos, na ordem certa, contra a base certa
- [ ] CI verde em cada um (ou no mínimo no topo — assim que o merge começa, as bases re-apontam e o CI roda de novo)
- [ ] `php artisan migrate:fresh --seed` roda do início ao fim no **head do PR do topo** (#14) — prova que a stack inteira compõe
- [ ] `php artisan test --compact` verde no HEAD do #14 (260 tests / 604 assertions na última execução)
- [ ] `vendor/bin/pint --test --format agent` limpo no HEAD do #14
- [ ] Paridade de traduções: `en.json` / `pt_BR.json` / `es.json` todos com 573 keys no HEAD do #14
