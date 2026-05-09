# Stack de PRs — code review até a unificação Orgs-as-Persons

Dez PRs empilhados entregam toda a trajetória entre `main` e a unificação
Orgs-as-Persons (a extensão arquitetural que entrou entre a Phase 5 e a
Phase 6 da Person Architecture). Eles estão **empilhados** (a base de cada
PR é o head do PR de baixo), não são paralelos, porque cada um depende do
anterior. Tentar mergear fora de ordem vai gerar conflitos.

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

**Ordem de merge: #2 → #3 → #4 → #5 → #1 → #6 → #7 → #8 → #9 → #10.** À
medida que cada PR é mergeado, o GitHub re-aponta automaticamente a base do
próximo da fila para `main` (ou para a nova base, se aplicável). Não use
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

## Por que empilhado, e não um PR único

A `main` ficou ~48 commits atrás dos branches de trabalho quando a Phase 1
foi concluída, porque os stages 4 / 5 / 6 foram pushados mas nunca abertos
como PR na época. Juntar tudo num PR só ficaria ilegível. Dividir nos limites
dos branches que já existiam dá ao revisor exatamente as mesmas fatias em
que o trabalho foi executado.

## O que *não* está nesta stack

O PR Orgs-as-Persons adiciona 4 natures novas (`national_headquarters`,
`ecclesiastical_region`, `district`, `church`), adiciona uma FK `person_id`
(NOT NULL + unique) em cada tabela de org com backfill criando um Org-Person
por linha existente, adiciona `code` em `churches` e seeda a Sede Nacional
como uma row de ER especial via `RegionKind::NationalHeadquarters`. A index
de People esconde os org-Persons por padrão; um toggle "Include organizations"
permite incluí-los. Os editors de Region/District/Church ganham um botão
"Open as Person" que leva para `/admin/people/{personId}/edit` para que
admins gerenciem contacts / addresses / documents do Org Person pelas tabs
existentes, sem composição inline.

Continuam adiados a partir da Phase 6:

- **Remover as colunas duplicadas** `name` / `email` / `phone` / `address` nas tabelas de org — atualmente são um cache denormalizado que o `save()` dos editors mantém em sync; a migração completa para "Person é a única fonte" pode entrar depois
- **Composição inline das Person tabs nos editors de org** — atualmente você clica "Open as Person" para trocar de contexto; uma iteração futura pode embedar Contacts / Addresses / Documents dentro do editor de church
- **Admin de Groups** (Council / Ministry / Commission) + assignments com escopo de group (Phase 6)
- **CRUD de Functions** — decisão adiada para a Phase 6 conforme a nota no plano
- **Children / Teenagers / Visitors** com UI ativa + fluxo de act-as parental (Phase 7)
- **Tab Family no `/profile`** (Phase 7, junto com act-as)

Detalhes em `documents/PersonArchitecture/README.en.md` § "Phased rollout".

## Verificação antes de mergear a stack

- [ ] Os dez PRs estão abertos, na ordem certa, contra a base certa
- [ ] CI verde em cada um (ou no mínimo no topo — assim que o merge começa, as bases re-apontam e o CI roda de novo)
- [ ] `php artisan migrate:fresh --seed` roda do início ao fim no **head do PR do topo** (#10) — prova que a stack inteira compõe
- [ ] `php artisan test --compact` verde no HEAD do #10 (215 tests / 515 assertions na última execução)
- [ ] `vendor/bin/pint --test --format agent` limpo no HEAD do #10
- [ ] Paridade de traduções: `en.json` / `pt_BR.json` / `es.json` todos com 525 keys no HEAD do #10
