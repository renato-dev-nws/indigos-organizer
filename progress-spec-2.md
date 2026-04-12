# PROGRESS SPEC 2 — Evolução e Refatoração

## 1) Objetivo

Este documento define as especificações técnicas completas para implementar a evolução solicitada em .copilot-docs/evolucao-2.md no projeto Band Organizer.

O objetivo é orientar execução por agente Copilot com decisões de arquitetura, escopo por módulo, estrutura de dados, backend, frontend, filas, notificações e critérios de aceite.

## 2) Baseline do Projeto (estado atual)

- Backend: Laravel 13 + Inertia
- Frontend: Vue 3 + PrimeVue + Tailwind
- Banco: PostgreSQL
- Docker: Laravel Sail (compose.yaml com app + pgsql; sem Redis atualmente)
- Queue atual: config preparada para database/redis, default em database
- Dashboard já possui votação de ideias no quadro e lista de tarefas recentes
- Tarefas já possuem lista + kanban + swimlane
- Conteúdos já possuem lista + calendário semanal simples
- Módulo de venues existe com CRUD básico e nome visual “Casas de Show”
- Não existe sistema de notificações implementado
- Não existe feature de transcrição por voz

## 3) Restrições Obrigatórias

- Todos os comandos de desenvolvimento devem usar Sail.
- Não criar migrations de alteração de tabela (Schema::table para tabelas existentes).
- Como o fluxo será migrate:fresh, mudanças em tabelas existentes devem ser feitas diretamente nas migrations create já existentes.
- Novas entidades/tabelas podem ser criadas com migrations create novas.

## 4) Comandos Padrão (Sail)

- Instalar dependências JS: `./vendor/bin/sail npm install`
- Rodar dev: `./vendor/bin/sail npm run dev`
- Build: `./vendor/bin/sail npm run build`
- Recriar banco com seed: `./vendor/bin/sail artisan migrate:fresh --seed`
- Worker: `./vendor/bin/sail artisan queue:work --tries=3 --timeout=120`
- Scheduler loop local: `./vendor/bin/sail artisan schedule:work`

## 5) Redis (Infra)

### 5.1 Docker / Sail

Atualizar compose.yaml adicionando serviço redis:

- service: redis
- image: redis:7-alpine
- porta: `${FORWARD_REDIS_PORT:-6379}:6379`
- volume persistente: sail-redis
- network: sail

Adicionar depends_on de redis em laravel.test.

### 5.2 Ambiente

Atualizar .env.example para modo Redis em cache/session/queue:

- `REDIS_HOST=redis`
- `REDIS_PORT=6379`
- `QUEUE_CONNECTION=redis`
- `CACHE_STORE=redis`
- `SESSION_DRIVER=redis`

### 5.3 Config Laravel

- Manter config/database.php e config/queue.php como base (já suportam redis).
- Validar phpredis no container (já esperado pela config).

## 6) Workers + Notificações

## 6.1 Escopo dos disparos

Disparar notificação quando:

1. tarefa atribuída ao usuário
2. falta menos de 24h para prazo da tarefa
3. lembrete próprio da tarefa (novo campo reminder_at)
4. ideia muda para status on_board
5. ideia votada (somente criador da ideia)

## 6.2 Estrutura de dados

### Alterar migration create de tasks

Arquivo existente: database/migrations/2026_04_10_052159_create_tasks_table.php

Adicionar colunas:

- `reminder_at` timestamp nullable
- `assignment_notified_at` timestamp nullable
- `due_soon_notified_at` timestamp nullable
- `reminder_notified_at` timestamp nullable

Observação:
- manter assigned_user_id nullable com semântica “Todos”.

### Nova migration create (tabela de notificações deduplicadas por usuário)

Criar: create_task_user_notification_logs_table

Campos:
- id (uuid pk)
- task_id (fk)
- user_id (fk)
- event_type enum: assigned, due_soon, reminder
- sent_at timestamp
- unique(task_id,user_id,event_type)

Motivo:
- evitar duplicidade para tarefas compartilhadas (assigned_user_id null => todos).

## 6.3 Notificações Laravel

Criar classes em app/Notifications:

- TaskAssignedNotification
- TaskDueSoonNotification
- TaskReminderNotification
- IdeaOnBoardNotification
- IdeaVotedNotification

Canal inicial:
- database

Opcional futuro:
- mail

Pré-requisito:
- criar migration notifications table (create_notifications_table) se ainda não existir.

## 6.4 Eventos/Observers/Jobs

- TaskObserver:
  - on created/updated: quando assigned_user_id definido/alterado, enfileirar job de notificação de atribuição
- IdeaObserver:
  - on updated: se status mudou para on_board, notificar votantes elegíveis
  - on voted: notificar criador

Jobs (ShouldQueue):
- DispatchTaskAssignedNotificationsJob
- DispatchIdeaOnBoardNotificationsJob
- DispatchIdeaVotedNotificationJob
- DispatchDueSoonTasksNotificationsJob
- DispatchTaskReminderNotificationsJob

## 6.5 Scheduler

Registrar tarefas agendadas em routes/console.php:

- a cada 15 min: DispatchDueSoonTasksNotificationsJob
- a cada 5 min: DispatchTaskReminderNotificationsJob

Regras:
- due soon: due_date dentro de 24h e não notificado para usuário alvo
- reminder: reminder_at <= now e não notificado para usuário alvo

## 7) Transcrição de Voz para Descrições

## 7.1 Escopo UI

Adicionar ícone de microfone no canto superior direito acima de textarea de descrição em:

- Ideias (Create/Edit)
- Conteúdos (Create/Edit)
- Tarefas (form modal)

## 7.2 Arquitetura recomendada

Fase 1 (rápida):
- Web Speech API no browser (pt-BR)
- componente reutilizável: AppSpeechTextareaAssist.vue

Fase 2 (fallback servidor, opcional):
- endpoint POST /speech/transcriptions
- upload de áudio + provider externo (ex.: OpenAI Whisper)
- fila para processamento quando áudio grande

## 7.3 Componente reutilizável

Criar componente com props:

- modelValue
- language default pt-BR
- disabled

Features:
- botão microfone iniciar/parar
- append incremental no texto
- estado visual “gravando”
- tratamento de erro de permissão

## 8) Refatoração de Venues para “Locais”

## 8.1 Nomenclatura

- Visual: trocar “Casas de Show” para “Locais”
- Técnico: manter entidade/tabela principal venues

## 8.2 Taxonomias novas

Criar tabelas:

- venue_types (tipo de local: casa de show, bar, teatro, estúdio, etc.)
- venue_categories (categorias de local)
- venue_styles (estilos musicais do local)

Campos padrão para cada tabela:
- id uuid pk
- user_id fk users
- name string
- color string nullable (opcional)
- timestamps

## 8.3 Alterar create_venues_table (direto)

Arquivo: database/migrations/2026_04_10_052203_create_venues_table.php

Além dos campos atuais, incluir:

- venue_type_id fk nullable
- venue_category_id fk nullable
- venue_style_id fk nullable
- place_id string nullable
- address_line string nullable
- address_number string nullable
- neighborhood string nullable
- city string nullable
- state string nullable
- postal_code string nullable
- country string nullable
- latitude decimal(10,7) nullable
- longitude decimal(10,7) nullable
- status enum: undefined, not_relevant, contacted, vetoed, negotiating, open_doors (default undefined)
- performances_count unsignedInteger default 0
- equipment_tags string nullable (csv)
- rating tinyInteger unsigned nullable (1-5)

Regra derivada:
- has_performed = performances_count > 0 (não precisa coluna física)

## 8.4 Backend Venues

- Venue model: atualizar fillable/casts/relations com type/category/style
- Requests Store/UpdateVenue: validar novos campos
- VenueController index:
  - filtros por type, status, city, has_performed, rating
  - retornar dados para mapa (lat,lng,tipo)

## 8.5 Google Maps

- Frontend usar Google Maps JS API + Places Autocomplete
- Variáveis de ambiente:
  - VITE_GOOGLE_MAPS_API_KEY
- Form de venue:
  - autocomplete de endereço
  - preencher campos estruturados + lat/lng
- Index de venues:
  - tabs verticais à esquerda: Lista | Mapa
  - mapa com markers customizados por tipo

## 9) Swimlane (temporário)

- Manter código swimlane no projeto.
- Remover apenas opção visual/tab/botão “Swimlane” da UI de tarefas.
- Não remover lógicas internas para facilitar reativação futura.

## 10) Conteúdos — Calendário Completo

## 10.1 Requisitos

- Tab atual continua existindo, renomeada para “Programação da semana”.
- Semana deve iniciar no domingo.
- Adicionar tab nova “Calendário completo”.
- Clique em item do calendário deve abrir página do conteúdo.

## 10.2 Implementação

Dependências frontend:
- @fullcalendar/vue3
- @fullcalendar/core
- @fullcalendar/daygrid
- @fullcalendar/timegrid
- @fullcalendar/interaction

Em Contents/Index.vue:
- manter Lista
- Programação da semana (domingo -> sábado)
- Calendário completo (month/week)
- evento click => route('contents.show', id)

## 11) Botões de Voltar (PWA UX)

Adicionar botão voltar nas páginas de Edit e Show que ainda não têm.

Prioridade de auditoria:
- Venues/Show.vue
- Contents/Show.vue
- SharedInfos/Show.vue
- Ideas/Show.vue (validar)
- Plans/Show.vue (validar)

Padrão:
- botão com ícone left
- no mobile: ícone sem label

## 12) Tarefas em Modal + Visualização

## 12.1 Novo comportamento

- Formulário de tarefas deve abrir em modal (lista e kanban).
- Além do form, existir modal de visualização (read-only) da tarefa.

## 12.2 Rotas

- Reativar show de tasks para payload completo de visualização
- manter create/update/destroy/status atuais

## 12.3 Frontend

Tasks/Index.vue:
- remover navegação para página create/edit
- botão Nova tarefa abre Dialog/Drawer com form
- editar no card/list abre modal preenchido
- visualizar abre modal read-only

Criar componentes:
- TaskFormModal.vue
- TaskViewModal.vue

## 13) “Planos” => “Planejamentos”

Trocar apenas nomenclatura visual:
- menu
- títulos de páginas
- labels de botões/headers

Não renomear:
- rotas plans.*
- model Plan
- tabela plans

## 14) Ideias Privadas com Status Trash

## 14.1 Regras

- is_private permitido para status in_drawer e trash.
- fora desses status, is_private deve ser false.

## 14.2 Ajustes

- StoreIdeaRequest after validation
- watchers em Ideas/Create.vue e Ideas/Edit.vue
- labels explicativas no formulário

## 15) Kanban de Ideias

## 15.1 Requisito visual

Cards compactos (2 linhas):
- linha 1: título
- linha 2: categoria (small, esquerda) e tipo (small, direita)

## 15.2 Comportamento

- Desktop: exibir toggle Lista/Kanban
- Mobile: ocultar opções/tab de Kanban

## 15.3 Backend

- endpoint index já atende; ordenar por updated_at desc
- suporte drag-and-drop opcional nesta fase (pode ficar apenas leitura inicialmente)

## 16) Kanban somente desktop

Aplicar para:
- Tarefas
- Ideias

Regra de UI:
- controles de modo kanban ocultos em md-down
- fallback mobile em cards/lista

## 17) Mobile — Botões apenas com ícone

Escopo:
- ações primárias/secundárias em telas mobile não devem exibir texto

Padrão técnico:
- encapsular em utilitário/component wrapper com:
  - label visível em md+
  - icon-only em < md

Aplicar em:
- páginas de listagem (ações de header e linhas)
- formulários (Criar, Salvar, Voltar)

## 18) Settings com Tabs por Domínio

## 18.1 Tipos

Tabs:
- Ideias e Conteúdos (usa tabela atual idea_types)
- Locais (usa venue_types)

## 18.2 Categorias

Tabs:
- Ideias e Conteúdos (usa tabela atual idea_categories)
- Locais (usa venue_categories)

## 18.3 Estilos

Tabs:
- Conteúdo (usar tabela atual content_categories, já existente)
- Locais (usar venue_styles)

Observação:
- o texto original repete “venue_types” para categorias/estilos; nesta spec foi normalizado para venue_categories e venue_styles para manter consistência funcional.

## 18.4 UI/Rotas

- Criar páginas/tabs específicas de settings taxonômicos
- manter padrão visual de CrudPage
- adicionar controllers/resources de venue_types, venue_categories, venue_styles

## 19) Ordem de Menu

Nova ordem obrigatória:

1. Tarefas
2. Ideias
3. Conteúdos
4. Planejamentos
5. Locais
6. Informações Úteis
7. Configurações
8. Usuários

Detalhes de label:
- “Planos” -> “Planejamentos”
- “Casas de Show” -> “Locais”
- “Informações” -> “Informações Úteis”

## 20) Arquivos principais impactados

## 20.1 Backend

- compose.yaml
- .env.example
- routes/web.php
- routes/console.php
- config/queue.php (somente se necessário)
- app/Models/Task.php
- app/Models/Idea.php
- app/Models/Venue.php
- app/Http/Controllers/TaskController.php
- app/Http/Controllers/IdeaController.php
- app/Http/Controllers/VenueController.php
- app/Http/Controllers/Settings/SettingsController.php
- app/Http/Requests/StoreTaskRequest.php
- app/Http/Requests/UpdateTaskRequest.php
- app/Http/Requests/StoreIdeaRequest.php
- app/Http/Requests/UpdateIdeaRequest.php
- app/Http/Requests/StoreVenueRequest.php
- app/Http/Requests/UpdateVenueRequest.php
- app/Observers/TaskObserver.php (novo)
- app/Observers/IdeaObserver.php (novo)
- app/Notifications/* (novos)
- app/Jobs/* (novos)

Migrations create (editar existentes):
- database/migrations/2026_04_10_052159_create_tasks_table.php
- database/migrations/2026_04_10_052203_create_venues_table.php

Migrations create (novas tabelas):
- create_notifications_table
- create_task_user_notification_logs_table
- create_venue_types_table
- create_venue_categories_table
- create_venue_styles_table

## 20.2 Frontend

- resources/js/Layouts/AppShell.vue
- resources/js/Pages/Tasks/Index.vue
- resources/js/Pages/Ideas/Index.vue
- resources/js/Pages/Contents/Index.vue
- resources/js/Pages/Venues/Index.vue
- resources/js/Pages/Venues/Create.vue
- resources/js/Pages/Venues/Edit.vue
- resources/js/Pages/Venues/Show.vue
- resources/js/Pages/Settings/CrudPage.vue ou nova página tabs
- resources/js/Components/AppKanbanCard.vue (reuso)
- resources/js/Components/ui/BoEntityDrawer.vue (reuso)
- resources/js/Components/AppSpeechTextareaAssist.vue (novo)
- resources/js/Components/tasks/TaskFormModal.vue (novo)
- resources/js/Components/tasks/TaskViewModal.vue (novo)

## 21) Ordem de Execução Recomendada

1. Infra Redis (compose + env + validação)
2. Migrations create atualizadas (tasks, venues)
3. Novas tabelas taxonômicas e logs/notificações
4. Modelos/requests/controllers backend
5. Notificações, jobs, observers e scheduler
6. Refatoração UI de Venues para Locais com mapa
7. Tarefas em modal + view modal
8. Conteúdos com calendário completo
9. Kanban de ideias + regras desktop/mobile
10. Ajustes mobile icon-only
11. Settings em tabs por domínio
12. Ordem final de menu e labels
13. Testes finais + build

## 22) Critérios de Aceite

- Redis operacional no Sail e queue worker consumindo fila redis
- Notificações disparam para todos os 5 cenários exigidos
- Lembrete de tarefa funciona por schedule sem duplicidade
- Voz/transcrição disponível em ideias, conteúdos e tarefas
- Venues refatorado para Locais com tipo/status/endereço geolocalizado/mapa
- Swimlane oculto da UI, sem remover código
- Conteúdos com Programação da semana (domingo primeiro) + Calendário completo clicável
- Tarefas com criação/edição/visualização em modal na listagem/kanban
- “Planos” exibido como “Planejamentos” em todo o front
- Ideias trash podem ser privadas
- Kanban só aparece em desktop
- Mobile com botões de ação apenas por ícone
- Settings com tabs para Tipos, Categorias e Estilos por domínio
- Ordem do menu conforme requisito

## 23) Notas de Risco e Mitigação

- Google Maps API: dependerá de chave válida; prever fallback de endereço manual.
- Web Speech API: compatibilidade de navegador; manter fallback textual sem bloqueio.
- Notificações em tarefas com assigned_user_id null: sempre deduplicar por user+task+evento.
- Como há regra de migrate:fresh, consolidar qualquer migration de alteração antiga no create correspondente antes de rodar em produção local.

## 24) Checklist de Execução por Fases (Fase 1 a Fase 6)

Use esta seção como checklist operacional durante a implementação.

### Fase 1 — Infra, Redis e Base de Dados

- [ ] Adicionar serviço redis em compose.yaml com volume persistente e rede sail.
- [ ] Ajustar depends_on da aplicação para incluir redis.
- [ ] Atualizar .env.example para REDIS_HOST=redis, QUEUE_CONNECTION=redis, CACHE_STORE=redis, SESSION_DRIVER=redis.
- [ ] Validar queue redis local com `./vendor/bin/sail artisan queue:work`.
- [ ] Alterar create_tasks_table para incluir reminder_at e timestamps de controle de notificação.
- [ ] Alterar create_venues_table para novo escopo de Locais (tipo, endereço, geo, status, performances_count, equipment_tags, rating).
- [ ] Criar migration create_notifications_table (se não existir no projeto).
- [ ] Criar create_task_user_notification_logs_table com unique(task_id,user_id,event_type).
- [ ] Criar create_venue_types_table.
- [ ] Criar create_venue_categories_table.
- [ ] Criar create_venue_styles_table.
- [ ] Executar `./vendor/bin/sail artisan migrate:fresh --seed` sem erro.

Critério de pronto da Fase 1:
- [ ] Banco recriado com todas as tabelas e Redis operacional no container.

### Fase 2 — Backend de Notificações e Agendamentos

- [ ] Criar classes em app/Notifications: TaskAssigned, TaskDueSoon, TaskReminder, IdeaOnBoard, IdeaVoted.
- [ ] Criar jobs ShouldQueue para os cinco fluxos de notificação.
- [ ] Implementar TaskObserver para gatilho de atribuição.
- [ ] Implementar IdeaObserver para gatilho de ida ao quadro e voto.
- [ ] Registrar observers no AppServiceProvider.
- [ ] Agendar jobs em routes/console.php (due soon e reminder).
- [ ] Garantir deduplicação por task+user+event_type no log de notificações.
- [ ] Garantir regra “assigned_user_id null => todos os usuários elegíveis”.

Critério de pronto da Fase 2:
- [ ] Todos os 5 eventos disparam notificação de forma assíncrona, sem duplicidade.

### Fase 3 — Refatoração de Locais (Venues) + Settings Taxonômicos

- [ ] Atualizar Venue model (fillable, casts, relations com type/category/style).
- [ ] Atualizar StoreVenueRequest e UpdateVenueRequest com novas validações.
- [ ] Atualizar VenueController index com filtros: type, status, city, has_performed, rating.
- [ ] Implementar Google Places autocomplete no formulário de locais.
- [ ] Persistir place_id, endereço estruturado e lat/lng.
- [ ] Refatorar Venues/Index para tabs verticais à esquerda: Lista | Mapa.
- [ ] Implementar mapa Google com markers customizados por tipo de local.
- [ ] Renomear textos visuais de “Casas de Show” para “Locais”.
- [ ] Criar tabs em Settings para Tipos: Ideias e Conteúdos | Locais.
- [ ] Criar tabs em Settings para Categorias: Ideias e Conteúdos | Locais.
- [ ] Criar tabs em Settings para Estilos: Conteúdo | Locais.

Critério de pronto da Fase 3:
- [ ] Módulo Locais funcional com mapa e filtros, e Settings taxonômicos por domínio.

### Fase 4 — UX de Tarefas, Ideias e Conteúdos

- [ ] Remover temporariamente a opção visual de Swimlane (sem apagar código).
- [ ] Transformar formulário de tarefas em modal na tela de Tasks/Index.
- [ ] Criar TaskFormModal reutilizável para criar/editar tarefa.
- [ ] Criar TaskViewModal read-only para visualização de tarefa.
- [ ] Ajustar ações de lista e kanban para abrir modal de form/view.
- [ ] Reativar endpoint show de tasks para payload de visualização detalhada.
- [ ] Em conteúdos, renomear tab atual para “Programação da semana”.
- [ ] Ajustar programação da semana para iniciar no domingo.
- [ ] Adicionar tab “Calendário completo” com FullCalendar.
- [ ] Implementar click em evento do calendário => abrir Contents/Show.

Critério de pronto da Fase 4:
- [ ] Tarefas em modal (form e view) e Conteúdos com dois calendários funcionais.

### Fase 5 — Voz, Kanban de Ideias e Regras de Privacidade

- [ ] Criar componente AppSpeechTextareaAssist para Web Speech API.
- [ ] Integrar componente nas descrições de Ideias Create/Edit.
- [ ] Integrar componente nas descrições de Conteúdos Create/Edit.
- [ ] Integrar componente nas descrições de Tarefas (modal).
- [ ] Ajustar regras de ideia privada para aceitar status in_drawer e trash.
- [ ] Atualizar validação backend (Store/UpdateIdeaRequest) para nova regra.
- [ ] Atualizar watchers do frontend de ideias para nova regra de privacidade.
- [ ] Implementar modo Kanban em Ideias/Index (cards compactos de 2 linhas).
- [ ] Esconder controls de Kanban em mobile (kanban somente desktop).

Critério de pronto da Fase 5:
- [ ] Transcrição por voz disponível nos três módulos e Kanban de ideias ativo no desktop.

### Fase 6 — Padronização Mobile, Navegação e Finalização

- [ ] Aplicar padrão icon-only em mobile para botões de ação (Criar, Salvar, Voltar, etc.).
- [ ] Manter labels visíveis em md+.
- [ ] Auditar e incluir botão Voltar em todas as páginas Edit/Show faltantes.
- [ ] Renomear visualmente “Planos” para “Planejamentos” em menu e headers.
- [ ] Renomear visualmente “Informações” para “Informações Úteis”.
- [ ] Reordenar menu: Tarefas, Ideias, Conteúdos, Planejamentos, Locais, Informações Úteis, Configurações, Usuários.
- [ ] Executar `./vendor/bin/sail artisan migrate:fresh --seed`.
- [ ] Executar `./vendor/bin/sail artisan test`.
- [ ] Executar `./vendor/bin/sail npm run build`.

Critério de pronto da Fase 6:
- [ ] Fluxos principais em desktop/mobile funcionando e build/test sem regressões bloqueantes.

### Checklist de Entrega Final

- [ ] Conferir todos os critérios de aceite da seção 22.
- [ ] Validar notificações em cenário real com worker e scheduler ativos.
- [ ] Validar mapa e autocomplete com chave Google válida.
- [ ] Validar experiência PWA mobile (ações, botões, navegação).
- [ ] Registrar pendências não críticas em backlog técnico.
