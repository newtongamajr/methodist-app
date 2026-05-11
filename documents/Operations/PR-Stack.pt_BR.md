# Stack de PRs — code review até a Phase 3 da Person Architecture

Sete PRs empilhados entregam toda a trajetória entre `main` e a Phase 3 da
Person Architecture. Eles estão **empilhados** (a base de cada PR é o head do
PR de baixo), não são paralelos, porque cada um depende do anterior. Tentar
mergear fora de ordem vai gerar conflitos.

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

**Ordem de merge: #2 → #3 → #4 → #5 → #1 → #6 → #7.** À medida que cada PR é
mergeado, o GitHub re-aponta automaticamente a base do próximo da fila para
`main` (ou para a nova base, se aplicável). Não use squash-merge — preserve
o histórico de commits para que a intenção em camadas continue legível no
`git log`.

URLs dos PRs:

- https://github.com/newtongamajr/methodist-app/pull/2
- https://github.com/newtongamajr/methodist-app/pull/3
- https://github.com/newtongamajr/methodist-app/pull/4
- https://github.com/newtongamajr/methodist-app/pull/5
- https://github.com/newtongamajr/methodist-app/pull/1
- https://github.com/newtongamajr/methodist-app/pull/6
- https://github.com/newtongamajr/methodist-app/pull/7

## Por que empilhado, e não um PR único

A `main` ficou ~48 commits atrás dos branches de trabalho quando a Phase 1
foi concluída, porque os stages 4 / 5 / 6 foram pushados mas nunca abertos
como PR na época. Juntar tudo num PR só ficaria ilegível. Dividir nos limites
dos branches que já existiam dá ao revisor exatamente as mesmas fatias em
que o trabalho foi executado.

## O que *não* está nesta stack

A Phase 3 adiciona a tab Family no editor `/admin/people` e os helpers de
family-tree no Person (`parents`, `children`, `siblings`, `spouse`,
`grandparents`, `grandchildren`, `auntsAndUncles`, `niecesAndNephews`,
`cousins`, `godparents`, `godchildren`, `guardians`, `wards`, `familyTree`).
Continuam adiados para as Phases 4–7:

- **Tab Family no `/profile`** — entry point admin-only entra na Phase 3; a versão para o usuário final vem junto com o act-as parental da Phase 7
- **Admin de Pastor** reescrito sobre `PersonRoleAssignment` (Phase 4)
- **Admin de Groups** (Council / Ministry / Commission) (Phase 6)
- **Children / Teenagers / Visitors** com UI ativa + fluxo de act-as parental (Phase 7)
- **Campos additional per-nature** (`PersonFieldDefinition`) — adiado por padrão da Q3; a v1 mantém a tab Identity genérica

Detalhes em `documents/PersonArchitecture/README.en.md` § "Phased rollout".

## Verificação antes de mergear a stack

- [ ] Os sete PRs estão abertos, na ordem certa, contra a base certa
- [ ] CI verde em cada um (ou no mínimo no topo — assim que o merge começa, as bases re-apontam e o CI roda de novo)
- [ ] `php artisan migrate:fresh --seed` roda do início ao fim no **head do PR do topo** (#7) — prova que a stack inteira compõe
- [ ] `php artisan test --compact` verde no HEAD do #7 (191 tests / 458 assertions na última execução)
- [ ] `vendor/bin/pint --test --format agent` limpo no HEAD do #7
- [ ] Paridade de traduções: `en.json` / `pt_BR.json` / `es.json` todos com 507 keys no HEAD do #7
