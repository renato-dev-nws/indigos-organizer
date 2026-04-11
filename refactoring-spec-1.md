# SPEC — Band Organizer: Refactoring & New Modules

## Overview

This document specifies all changes, new features, and bug fixes to be implemented in the **Band Organizer** application. It is intended for use by an AI coding agent (Copilot) and provides exact file paths, field names, relationships, UI patterns, and code conventions to follow.

---

## Tech Stack & Conventions

- **Backend**: Laravel 11, PHP 8.3+
- **Frontend**: Vue 3 + Inertia.js + PrimeVue 4 + Tailwind CSS 3 + Ziggy (route helpers)
- **Database**: PostgreSQL (`ilike` for case-insensitive search)
- **UUIDs**: All primary keys use `HasUuids` trait + `uuid('id')->primary()`
- **Soft Deletes**: All main entity models use `SoftDeletes`
- **Ownership**: All user-owned entities have a `user_id` FK to `users`
- **Pagination**: `paginate(15)->withQueryString()`
- **Inertia render**: `Inertia::render('ModuleName/Page', [...props])`
- **Form validation**: Dedicated `App\Http\Requests\` classes per action
- **UI base components**: `BoPageHeader`, `BoFilterBar`, `BoFormSection`, `BoConfirmButton`, `BoDataTableEmpty`, `BoStatusTag`, `BoPriorityTag`, `BoDateText`, `AppRichText`, `AppFileUpload`
- **Icons**: Phosphor Icons via `@iconify/vue` (`ph:*-bold` pattern)
- **Flash messages**: `redirect()->with('success', '...')` → shown via Toast (AppShell handles `page.props.flash`)
- **File uploads**: Follow pattern in `ContentFileController.php` + `AppFileUpload.vue`

---

## Section 1 — New Module: Plans (`plans`)

### 1.1 Database Migrations

**Migration 1: `create_plans_table`**

```
php artisan make:migration create_plans_table
```

Table: `plans`

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid PK | `HasUuids` |
| `user_id` | uuid FK → users | cascadeOnDelete |
| `title` | string | required |
| `description` | text | nullable |
| `start_date` | date | nullable |
| `end_date` | date | nullable |
| `progress` | integer | default 0, range 0–100 |
| `status` | enum: `queued`, `running`, `cancelled`, `completed` | default `queued` |
| `timestamps` | | |
| `softDeletes` | | |

**Migration 2: `create_plan_phases_table`**

```
php artisan make:migration create_plan_phases_table
```

Table: `plan_phases`

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid PK | `HasUuids` |
| `plan_id` | uuid FK → plans | cascadeOnDelete |
| `user_id` | uuid FK → users | cascadeOnDelete |
| `title` | string | required |
| `description` | text | nullable |
| `order` | integer | default 0 |
| `timestamps` | | |
| `softDeletes` | | |

### 1.2 Models

**`app/Models/Plan.php`** — new file

```php
// HasUuids, SoftDeletes
// fillable: user_id, title, description, start_date, end_date, progress, status
// casts: start_date => 'date', end_date => 'date', progress => 'integer'
// Relations:
//   user(): BelongsTo User
//   phases(): HasMany PlanPhase (orderBy 'order')
//   tasks(): HasMany Task (via plan_id FK on tasks)
```

Status accessor: add a `statusLabel()` helper or use a `readonly` computed property for display:
- `queued` → "Na fila"
- `running` → "Em execução"
- `cancelled` → "Cancelado"
- `completed` → "Concluído"

**`app/Models/PlanPhase.php`** — new file

```php
// HasUuids, SoftDeletes
// fillable: plan_id, user_id, title, description, order
// Relations:
//   plan(): BelongsTo Plan
//   user(): BelongsTo User
//   tasks(): HasMany Task (via plan_phase_id FK on tasks)
```

### 1.3 Controller

**`app/Http/Controllers/PlanController.php`** — new file

Implement full CRUD:

- `index()`: paginate plans with `['user', 'phases']`, pass `filters` (status, search)
- `create()`: render `Plans/Create`
- `store(StorePlanRequest $request)`: create Plan, then create each `phases[]` entry
- `show(Plan $plan)`: render `Plans/Show` with `['phases.tasks.status', 'phases.tasks.assignedUser', 'tasks.status', 'tasks.assignedUser', 'user']`
- `edit(Plan $plan)`: render `Plans/Edit` with plan loaded with `phases`
- `update(UpdatePlanRequest $request, Plan $plan)`: update plan, sync phases (delete missing, update/create)
- `destroy(Plan $plan)`: soft delete, redirect to index

### 1.4 Form Requests

**`app/Http/Requests/StorePlanRequest.php`**

```php
rules: [
    'title' => ['required', 'string', 'max:255'],
    'description' => ['nullable', 'string'],
    'start_date' => ['nullable', 'date'],
    'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
    'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
    'status' => ['required', 'in:queued,running,cancelled,completed'],
    'phases' => ['nullable', 'array'],
    'phases.*.title' => ['required_with:phases', 'string', 'max:255'],
    'phases.*.description' => ['nullable', 'string'],
    'phases.*.order' => ['nullable', 'integer'],
]
```

**`app/Http/Requests/UpdatePlanRequest.php`** — same rules as `StorePlanRequest`

### 1.5 Routes

Add to `routes/web.php` inside `auth` middleware group:

```php
Route::resource('plans', PlanController::class);
```

### 1.6 Navigation

Add "Planos" link to `AppShell.vue` menu items (after "Tarefas"):

```js
{ label: 'Planos', icon: 'ph:map-trifold-bold', href: route('plans.index') },
```

### 1.7 Frontend Pages (`resources/js/Pages/Plans/`)

Create 4 files: `Index.vue`, `Create.vue`, `Edit.vue`, `Show.vue`

**`Index.vue`**

Props: `plans: Object, filters: Object`

- `BoPageHeader` with "Planos" title and "+ Novo plano" button
- `BoFilterBar` with search and status filter
- **Desktop**: `DataTable` with columns: Título, Status, Progresso (ProgressBar 0–100), Início, Fim, Fases (count), Actions (Edit, Delete)
- **Mobile** (< `md`): Cards — see Section 6 for mobile card spec  
- Pagination via `AppPagination` or PrimeVue `Paginator`
- Status labels: `queued` → "Na fila", `running` → "Em execução", `cancelled` → "Cancelado", `completed` → "Concluído"
- Delete via `BoConfirmButton`

**`Create.vue`**

Props: none

Form fields in this order:
1. `title` — InputText (required)
2. `description` — Textarea
3. `start_date` — DatePicker
4. `end_date` — DatePicker
5. `progress` — InputNumber (min 0, max 100) or Slider
6. `status` — Select (queued/running/cancelled/completed)
7. **Fases** repeater: Button "Adicionar fase" creates rows with `title` (InputText) + `description` (Textarea) + `order` (auto-increment) + delete button

**`Edit.vue`**

Same as Create.vue but pre-populated. Load `plan` with `phases`.

**`Show.vue`**

Display plan details and **list of phases** with their tasks:
- Each phase shows its tasks in a list
- Also show tasks related directly to the plan (not to a phase) in a separate section

### 1.8 Seeder

**`database/seeders/PlanSeeder.php`** — new file

Simulate recording an album with these phases and tasks:

```
Plan: "Gravação do Álbum Indie"
  status: running
  start_date: 2026-04-01
  end_date: 2026-12-31
  progress: 25

Phases (in order):
  1. Captação (order: 1)
     Tasks:
       - Reservar estúdio de captação
       - Afinar instrumentos e testar equipamentos
       - Gravação das bases (bateria, baixo)
       - Gravação de guitarras e teclados
       - Gravação de vocais principais

  2. Mixagem (order: 2)
     Tasks:
       - Seleção e organização das takes
       - Mixagem das faixas com engenheiro
       - Revisão e aprovação da mixagem pela banda

  3. Masterização (order: 3)
     Tasks:
       - Envio das mixagens para masterização
       - Revisão do master e aprovação final

  4. Lançamento (order: 4)
     Tasks:
       - Distribuição digital (Spotify, Apple Music, etc.)
       - Criação de arte da capa
       - Campanha de lançamento nas redes sociais
       - Live de lançamento do álbum
```

Tasks in seeder should use `assigned_user_id` = null (assigned to all) and task_status_id = first status available. The seeder should reference real users from UserSeeder.

Add `PlanSeeder` to `DatabaseSeeder::run()` after `DemoDataSeeder`.

---

## Section 2 — New Module: Shared Information (`shared_infos`)

### 2.1 Database Migrations

**Migration 1: `create_shared_infos_table`**

Table: `shared_infos`

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid PK | |
| `user_id` | uuid FK → users | cascadeOnDelete |
| `title` | string | required |
| `description` | text | nullable |
| `timestamps` | | |
| `softDeletes` | | |

**Migration 2: `create_shared_info_links_table`**

Table: `shared_info_links`

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid PK | |
| `shared_info_id` | uuid FK → shared_infos | cascadeOnDelete |
| `title` | string | required |
| `url` | string | required |
| `description` | text | nullable |
| `timestamps` | | |

**Migration 3: `create_shared_info_documents_table`**

Table: `shared_info_documents`

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid PK | |
| `shared_info_id` | uuid FK → shared_infos | cascadeOnDelete |
| `file_path` | string | stored path in `storage/app/public/shared-docs/` |
| `original_name` | string | original filename |
| `mime_type` | string | nullable |
| `size` | unsignedBigInteger | nullable (bytes) |
| `timestamps` | | |

### 2.2 Models

**`app/Models/SharedInfo.php`** — new file

```php
// HasUuids, SoftDeletes
// fillable: user_id, title, description
// Relations:
//   user(): BelongsTo User
//   links(): HasMany SharedInfoLink
//   documents(): HasMany SharedInfoDocument
```

**`app/Models/SharedInfoLink.php`** — new file

```php
// HasUuids
// fillable: shared_info_id, title, url, description
// Relations:
//   info(): BelongsTo SharedInfo
```

**`app/Models/SharedInfoDocument.php`** — new file

```php
// HasUuids
// fillable: shared_info_id, file_path, original_name, mime_type, size
// Append 'url' attribute: Storage::url($this->file_path)
// Relations:
//   info(): BelongsTo SharedInfo
```

### 2.3 Controller

**`app/Http/Controllers/SharedInfoController.php`** — new file

- `index()`: paginate with `['user', 'documents', 'links']`, filters: search
- `create()`: render `SharedInfos/Create`
- `store(StoreSharedInfoRequest)`: create info, sync links (repeater), handle file uploads (multiple files)
- `show(SharedInfo $sharedInfo)`: render `SharedInfos/Show` with all relations
- `edit(SharedInfo $sharedInfo)`: render `SharedInfos/Edit`
- `update(UpdateSharedInfoRequest, SharedInfo $sharedInfo)`: update, sync links, handle new uploads
- `destroy(SharedInfo $sharedInfo)`: soft delete

**`app/Http/Controllers/SharedInfoDocumentController.php`** — new file (for individual doc delete)

- `destroy(SharedInfo $sharedInfo, SharedInfoDocument $document)`: delete file from storage + delete record

### 2.4 Form Requests

**`app/Http/Requests/StoreSharedInfoRequest.php`**

```php
rules: [
    'title' => ['required', 'string', 'max:255'],
    'description' => ['nullable', 'string'],
    'links' => ['nullable', 'array'],
    'links.*.title' => ['required_with:links', 'string', 'max:255'],
    'links.*.url' => ['required_with:links', 'url', 'max:500'],
    'links.*.description' => ['nullable', 'string'],
    'documents' => ['nullable', 'array'],
    'documents.*' => ['file', 'max:20480'], // 20MB per file
]
```

### 2.5 Routes

```php
Route::resource('shared-infos', SharedInfoController::class);
Route::delete('/shared-infos/{sharedInfo}/documents/{document}', [SharedInfoDocumentController::class, 'destroy'])
    ->name('shared-infos.documents.destroy');
```

### 2.6 Navigation

Add "Informações" to `AppShell.vue` menu (after "Planos"):

```js
{ label: 'Informações', icon: 'ph:info-bold', href: route('shared-infos.index') },
```

### 2.7 Frontend Pages (`resources/js/Pages/SharedInfos/`)

Create: `Index.vue`, `Create.vue`, `Edit.vue`, `Show.vue`

**`Create.vue` / `Edit.vue`** form fields:

1. `title` — InputText (required)
2. `description` — Textarea or AppRichText
3. **Links repeater** (same pattern as ContentLink in content forms): each row has `title`, `url`, `description` + delete button
4. **Documentos** — `AppFileUpload` component (multiple files), shows already-uploaded docs (in edit mode) with delete option

---

## Section 3 — Task Modifications

### 3.1 Database Migration

**Modify directly**: `database/migrations/2026_04_10_052159_create_tasks_table.php`

Replace the current column definitions with the updated schema:

```php
Schema::create('tasks', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignUuid('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->enum('related_type', ['content', 'plan', 'administrative'])->default('administrative');
    $table->foreignUuid('content_id')->nullable()->constrained('contents')->nullOnDelete();
    $table->foreignUuid('plan_id')->nullable()->constrained('plans')->nullOnDelete();
    $table->foreignUuid('plan_phase_id')->nullable()->constrained('plan_phases')->nullOnDelete();
    $table->string('title');
    $table->text('description')->nullable();
    $table->foreignUuid('task_status_id')->constrained('task_statuses')->restrictOnDelete();
    $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
    $table->date('due_date')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

> **Note**: `assignee` (string) is removed. `type` is replaced by `related_type`. `plan_id` and `plan_phase_id` are new nullable FKs. Only one of (`content_id`, `plan_id`, `plan_phase_id`) will be populated at a time based on `related_type`. This migration must run **after** `create_plans_table` and `create_plan_phases_table`.

### 3.2 Model Changes

**`app/Models/Task.php`** — modify

- Replace `'assignee'` with `'assigned_user_id'` in `$fillable`
- Replace `'type'` with `'related_type'` in `$fillable`
- Add `'plan_id'`, `'plan_phase_id'` to `$fillable`
- Add relations:
  ```php
  public function assignedUser(): BelongsTo
  {
      return $this->belongsTo(User::class, 'assigned_user_id');
  }
  
  public function plan(): BelongsTo
  {
      return $this->belongsTo(Plan::class);
  }
  
  public function planPhase(): BelongsTo
  {
      return $this->belongsTo(PlanPhase::class);
  }
  ```

### 3.3 Controller Changes

**`app/Http/Controllers/TaskController.php`** — modify

- `index()`:
  - Load `with(['status', 'content', 'subtasks', 'assignedUser', 'plan', 'planPhase'])`
  - Filter by `related_type` (rename from `type`)
  - Pass `plans` → `Plan::whereIn('status', ['queued', 'running'])->with('phases')->get(['id', 'title', 'status'])`
  - Pass `users` → `User::orderBy('name')->get(['id', 'name'])`
  - Replace `contents` filter key with same

- `create()`:
  - Pass `contents` → `Content::whereIn('status', ['queued', 'in_production'])->orderBy('title')->get(['id', 'title'])`
  - Pass `plans` → `Plan::whereIn('status', ['queued', 'running'])->with('phases')->orderBy('title')->get()`
  - Pass `users` → `User::orderBy('name')->get(['id', 'name'])`

- `store(StoreTaskRequest)`:
  - User_id = Auth::id()
  - Handle null `assigned_user_id` (null = all users)
  - Clear unrelated FK fields before saving:
    - If `related_type !== 'content'`: set `content_id = null`
    - If `related_type !== 'plan'`: set `plan_id = null`, `plan_phase_id = null`

- `edit(Task $task)`: same props as `create()`, preload `task->load('subtasks')`

- `update(UpdateTaskRequest)`: same FK clearing logic as `store`

### 3.4 Form Requests

**`app/Http/Requests/StoreTaskRequest.php`** — replace rules:

```php
rules: [
    'assigned_user_id' => ['nullable', 'uuid', 'exists:users,id'],
    'title' => ['required', 'string', 'max:255'],
    'description' => ['nullable', 'string'],
    'related_type' => ['required', 'in:content,plan,administrative'],
    'content_id' => ['nullable', 'uuid', 'exists:contents,id'],
    'plan_id' => ['nullable', 'uuid', 'exists:plans,id'],
    'plan_phase_id' => ['nullable', 'uuid', 'exists:plan_phases,id'],
    'task_status_id' => ['required', 'uuid', 'exists:task_statuses,id'],
    'priority' => ['required', 'in:low,medium,high,urgent'],
    'due_date' => ['nullable', 'date'],
    'subtasks' => ['nullable', 'array'],
    'subtasks.*.title' => ['required_with:subtasks', 'string', 'max:255'],
    'subtasks.*.completed' => ['boolean'],
]
// After validator: if related_type=content → content_id required; if related_type=plan → plan_id required
```

**`app/Http/Requests/UpdateTaskRequest.php`** — same rules

### 3.5 Frontend Changes

**`resources/js/Pages/Tasks/Create.vue`** and **`Edit.vue`** — full rewrite of form

Field order as specified:
1. **Relacionada a** (`related_type`) — Select: "Conteúdo", "Plano", "Administrativo" (1st field)
2. Conditional field: if `related_type === 'content'` → Select content (active contents: queued/in_production)
3. Conditional field: if `related_type === 'plan'` → Select plan (active plans: queued/running); if plan selected → Select phase (from plan.phases), optional
4. `title` — InputText
5. `description` — Textarea
6. `assigned_user_id` — Select from users list. Add option "Todos" with value `null` at top of list. Default = null.
7. `priority` — Select
8. `due_date` — DatePicker
9. `status` (`task_status_id`) — Select

Vue reactive logic for conditional fields:
```js
const selectedPlan = computed(() => props.plans?.find(p => p.id === form.plan_id));
watch(() => form.related_type, () => {
    form.content_id = null;
    form.plan_id = null;
    form.plan_phase_id = null;
});
watch(() => form.plan_id, () => {
    form.plan_phase_id = null;
});
```

**`resources/js/Pages/Tasks/Index.vue`** — update:
- Replace `assignee` filter with `assigned_user_id` (Select from users)
- Replace `type` filter label with `related_type`
- In table/kanban, show assignee as user name (or "Todos" if null)
- **Mobile**: replace table with cards (see Section 6)

---

## Section 4 — Idea Modifications

### 4.1 Database Migration

**Modify directly**: `database/migrations/2026_04_10_052145_create_ideas_table.php`

Replace the current column definitions with the updated schema:

```php
Schema::create('ideas', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('title');
    $table->text('description')->nullable();
    $table->foreignUuid('idea_type_id')->nullable()->constrained('idea_types')->nullOnDelete();
    $table->foreignUuid('idea_category_id')->nullable()->constrained('idea_categories')->nullOnDelete();
    $table->enum('status', ['in_drawer', 'on_table', 'on_board', 'executing', 'executed', 'trash'])->default('in_drawer');
    $table->enum('related_type', ['new_content', 'new_plan', 'existing_content', 'existing_plan', 'administrative', 'none'])->default('none');
    $table->foreignUuid('content_id')->nullable()->constrained('contents')->nullOnDelete();
    $table->foreignUuid('plan_id')->nullable()->constrained('plans')->nullOnDelete();
    $table->foreignUuid('plan_phase_id')->nullable()->constrained('plan_phases')->nullOnDelete();
    $table->boolean('is_private')->default(false);
    $table->timestamps();
    $table->softDeletes();
});
```

> **Note**: Status values are completely replaced (new enum values). `related_type`, `content_id`, `plan_id`, `plan_phase_id`, and `is_private` are new columns. This migration must run **after** `create_plans_table`, `create_plan_phases_table`, and `create_contents_table`.

**Migration: `create_idea_voter_users_table`** (authorized voters per idea)

Table: `idea_voter_users`

| Column | Type | Notes |
|--------|------|-------|
| `idea_id` | uuid FK → ideas | cascadeOnDelete |
| `user_id` | uuid FK → users | cascadeOnDelete |
| Primary key: composite (`idea_id`, `user_id`) | | |

**Migration: `create_idea_votes_table`** (votes cast by users)

Table: `idea_votes`

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid PK | |
| `idea_id` | uuid FK → ideas | cascadeOnDelete |
| `user_id` | uuid FK → users | cascadeOnDelete |
| `vote` | enum: `on_table`, `in_drawer`, `trash` | |
| `timestamps` | | |
| Unique constraint: (`idea_id`, `user_id`) — one vote per user per idea | | |

### 4.2 Model Changes

**`app/Models/Idea.php`** — modify

- Update `$fillable`: add `related_type`, `content_id`, `plan_id`, `plan_phase_id`, `is_private`
- Update `$fillable` `status`: remove old values (now `in_drawer`, `on_table`, `on_board`, `executing`, `executed`, `trash`)
- Add `'is_private' => 'boolean'` to `casts()`
- Add relations:
  ```php
  public function content(): BelongsTo { return $this->belongsTo(Content::class); }
  public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
  public function planPhase(): BelongsTo { return $this->belongsTo(PlanPhase::class); }
  public function voterUsers(): BelongsToMany { return $this->belongsToMany(User::class, 'idea_voter_users'); }
  public function votes(): HasMany { return $this->hasMany(IdeaVote::class); }
  ```

**`app/Models/IdeaVote.php`** — new file

```php
// HasUuids
// fillable: idea_id, user_id, vote
// Relations: idea(): BelongsTo, user(): BelongsTo
```

### 4.3 Status Constants & Labels

In the frontend (and wherever needed):

```js
const statusLabels = {
    in_drawer: 'Na gaveta',
    on_table: 'Na mesa',
    on_board: 'No quadro',
    executing: 'Em execução',
    executed: 'Executada',
    trash: 'No lixo',
};
```

Status color suggestions for `BoStatusTag`:
- `in_drawer` → slate/gray
- `on_table` → blue
- `on_board` → purple/amber
- `executing` → indigo
- `executed` → green
- `trash` → red

### 4.4 Automatic Status Transitions (Observers)

Create `app/Observers/ContentObserver.php`:

```php
// On Content updated:
// if status changed to 'in_production' → update linked ideas where content_id = content->id + status NOT IN ['executed','trash'] → set status = 'executing'
// if status changed to 'published' → update linked ideas where content_id = content->id → set status = 'executed'
```

Create `app/Observers/PlanObserver.php`:

```php
// On Plan updated:
// if status changed to 'running' → update linked ideas where plan_id = plan->id + status NOT IN ['executed','trash'] → set status = 'executing'
// if status changed to 'completed' → update linked ideas where plan_id = plan->id → set status = 'executed'
```

Create `app/Observers/PlanPhaseObserver.php`:

```php
// On PlanPhase updated:
// if status... wait — PlanPhase doesn't have a status column
// However: if plan is completed and ALL phases are done ... TBD
// For phase: track via plan's status transition only
// PHASE-SPECIFIC: no observer needed for phases unless phases get their own status
```

> **Note**: Plan phases do not have their own status. The plan's status transition drives the idea transitions. Register observers in `AppServiceProvider::boot()`.

### 4.5 Controller Changes

**`app/Http/Controllers/IdeaController.php`** — modify

- `index()`:
  - Update status filter options to new values
  - Do NOT show `is_private` ideas from other users:
    ```php
    ->where(function ($q) {
        $q->where('is_private', false)
          ->orWhere('user_id', Auth::id());
    })
    ```
  - Update `with()` to include `content`, `plan`, `planPhase`

- `create()`:
  - Pass `plans` → active plans (queued/running) with phases
  - Pass `contents` → active contents (queued/in_production)
  - Pass `users` → `User::orderBy('name')->get(['id', 'name'])`

- `store(StoreIdeaRequest)`:
  - Save `voter_users` from request → sync `voterUsers()` relation after create
  - Clear unrelated FK fields based on `related_type`

- `show(Idea $idea)`:
  - Load `['type', 'category', 'references', 'user', 'content', 'plan', 'planPhase', 'votes.user', 'voterUsers']`
  - Pass current user's vote if on_board: `$userVote = $idea->votes()->where('user_id', Auth::id())->first()`

- `edit(Idea $idea)`:
  - Pass same data as create, plus `voterUsers` IDs

- `update(UpdateIdeaRequest)`:
  - Sync voterUsers
  - Clear unrelated FKs
  - If `is_private` is true but status !== `in_drawer`, force `is_private = false`

- Add new method `vote(Request $request, Idea $idea)`:
  - Authorize: idea must be `on_board` and current user must be in `voterUsers` (or `voterUsers` is empty = all allowed)
  - Validate: `vote` in `['on_table', 'in_drawer', 'trash']`
  - `IdeaVote::updateOrCreate(['idea_id' => $idea->id, 'user_id' => Auth::id()], ['vote' => $request->vote])`
  - Return `back()->with('success', 'Voto registrado.')`

### 4.6 Form Requests

**`app/Http/Requests/StoreIdeaRequest.php`** — full rewrite:

```php
rules: [
    'title' => ['required', 'string', 'max:255'],
    'description' => ['nullable', 'string'],
    'idea_type_id' => ['nullable', 'uuid', 'exists:idea_types,id'],
    'idea_category_id' => ['nullable', 'uuid', 'exists:idea_categories,id'],
    'status' => ['required', 'in:in_drawer,on_table,on_board,executing,executed,trash'],
    'related_type' => ['required', 'in:new_content,new_plan,existing_content,existing_plan,administrative,none'],
    'content_id' => ['nullable', 'uuid', 'exists:contents,id'],
    'plan_id' => ['nullable', 'uuid', 'exists:plans,id'],
    'plan_phase_id' => ['nullable', 'uuid', 'exists:plan_phases,id'],
    'is_private' => ['boolean'],
    'voter_users' => ['nullable', 'array'],
    'voter_users.*' => ['uuid', 'exists:users,id'],
    'references' => ['nullable', 'array'],
    'references.*.title' => ['required_with:references', 'string', 'max:255'],
    'references.*.description' => ['nullable', 'string'],
    'references.*.url' => ['required_with:references', 'url', 'max:255'],
]
// After: if related_type=existing_content → content_id required; if related_type=existing_plan → plan_id required
// After: if is_private=true and status != in_drawer → add error 'is_private can only be true when status is in_drawer'
```

### 4.7 Routes

Add to `routes/web.php`:

```php
Route::post('/ideas/{idea}/vote', [IdeaController::class, 'vote'])->name('ideas.vote');
```

Remove `ideas.execute` route (it referenced the old ExecuteIdeaAction flow; functionality is now replaced by the `related_type` linking system).

### 4.8 Frontend Changes

**`resources/js/Pages/Ideas/Create.vue`** and **`Edit.vue`** — rewrite form:

Field order:
1. **Relacionada a** (`related_type`) — Select (1st field):
   - Options: `new_content` → "Conteúdo novo", `new_plan` → "Plano novo", `existing_content` → "Conteúdo existente", `existing_plan` → "Plano existente", `administrative` → "Administrativa", `none` → "Nenhuma"
2. Conditional: if `related_type === 'existing_content'` → Select content (active)
3. Conditional: if `related_type === 'existing_plan'` → Select plan (active); if plan selected → optional Select phase
4. `title` — InputText
5. `description` — Textarea
6. `idea_type_id` — Select
7. `idea_category_id` — Select
8. `status` — Select (with new status values/labels)
9. Conditional: if `status === 'in_drawer'` → ToggleSwitch `is_private` labeled "Ideia privada"
10. Conditional: if `status === 'on_board'` → MultiSelect `voter_users` with users list. Label: "Usuários que podem votar" (empty = todos)

Vue watchers:
```js
watch(() => form.related_type, () => {
    form.content_id = null;
    form.plan_id = null;
    form.plan_phase_id = null;
});
watch(() => form.plan_id, () => { form.plan_phase_id = null; });
watch(() => form.status, (newStatus) => {
    if (newStatus !== 'in_drawer') form.is_private = false;
    if (newStatus !== 'on_board') form.voter_users = [];
});
```

**`resources/js/Pages/Ideas/Index.vue`** — update:
- Update status filter options to new status values
- **Mobile**: replace table with cards (see Section 6)
- Hide `is_private` ideas owned by others (backend already filters, frontend should just display)

**`resources/js/Pages/Ideas/Show.vue`** — update:
- Show voting UI if `idea.status === 'on_board'` and current user is eligible voter (or voterUsers is empty):
  ```
  Buttons: "Na mesa" / "Na gaveta" / "No lixo"
  POST route: ideas.vote with { vote: '...' }
  ```
- Show all votes cast (who voted for what) — visible to the idea creator

### 4.9 Dashboard Update

**`resources/js/Pages/Dashboard/Dashboard.vue`** (create if it doesn't exist, otherwise check page routing)

> **Note**: The current `/dashboard` route renders `Dashboard.vue` which sits at `resources/js/Pages/Dashboard.vue`. Create the real Dashboard controller/page.

**`app/Http/Controllers/DashboardController.php`** — modify to pass:
```php
return Inertia::render('Dashboard', [
    // Ideas on board awaiting current user's vote
    'boardIdeas' => Idea::where('status', 'on_board')
        ->where(function ($q) {
            $q->whereDoesntHave('voterUsers') // all users eligible
              ->orWhereHas('voterUsers', fn($q2) => $q2->where('user_id', Auth::id()));
        })
        ->whereDoesntHave('votes', fn($q) => $q->where('user_id', Auth::id())) // hasn't voted yet
        ->with(['user', 'votes'])
        ->get(),
    // Recent tasks assigned to current user or all
    'myTasks' => Task::where(function($q) {
            $q->whereNull('assigned_user_id')
              ->orWhere('assigned_user_id', Auth::id());
        })
        ->with('status')
        ->whereHas('status') // only if status loaded
        ->latest()
        ->take(5)
        ->get(),
]);
```

**`resources/js/Pages/Dashboard.vue`** — rewrite with AppLayout to show:
1. Section "Ideias para votação" — cards for each `boardIdea` with vote buttons (on_table / in_drawer / trash)
2. Section "Minhas tarefas recentes" — compact list of 5 tasks

---

## Section 5 — Content: Multiple Platforms

### 5.1 Database Migrations

**Modify directly**: `database/migrations/2026_04_10_052153_create_contents_table.php`

Remove the `content_platform_id` column from the `contents` table schema:

```php
// Remove this line:
$table->foreignUuid('content_platform_id')->nullable()->constrained('content_platforms')->nullOnDelete();
```

**Create new migration**: `create_content_platform_content_table` (pivot table)

```php
Schema::create('content_platform_content', function (Blueprint $table) {
    $table->foreignUuid('content_id')->constrained('contents')->cascadeOnDelete();
    $table->foreignUuid('content_platform_id')->constrained('content_platforms')->cascadeOnDelete();
    $table->primary(['content_id', 'content_platform_id']);
});
```

This migration must run after both `create_contents_table` and `create_content_platforms_table`.

### 5.2 Model Changes

**`app/Models/Content.php`** — modify

- Remove `platform(): BelongsTo` method
- Replace with:
  ```php
  public function platforms(): BelongsToMany
  {
      return $this->belongsToMany(ContentPlatform::class, 'content_platform_content');
  }
  ```
- Remove `'content_platform_id'` from `$fillable`

**`app/Models/ContentPlatform.php`** — modify

- Remove `contents(): HasMany`
- Replace with:
  ```php
  public function contents(): BelongsToMany
  {
      return $this->belongsToMany(Content::class, 'content_platform_content');
  }
  ```

### 5.3 Controller Changes

**`app/Http/Controllers/ContentController.php`** — modify

- `index()`: replace `with(['platform', ...])` with `with(['platforms', ...])`
- `store()`: after creating content, sync platforms:
  ```php
  $content->platforms()->sync($request->input('content_platform_ids', []));
  ```
- `update()`: same sync
- Queries filtering by `content_platform_id` → change to `whereHas('platforms', fn($q) => $q->where('content_platforms.id', $platformId))`

### 5.4 Form Requests

**`app/Http/Requests/StoreContentRequest.php`** — modify:
- Replace `'content_platform_id'` with:
  ```php
  'content_platform_ids' => ['nullable', 'array'],
  'content_platform_ids.*' => ['uuid', 'exists:content_platforms,id'],
  ```

### 5.5 Frontend Changes

**`resources/js/Pages/Contents/Create.vue`** and **`Edit.vue`** — modify:
- Replace `Select` for platform with `MultiSelect`:
  ```html
  <MultiSelect
    id="content-platforms"
    v-model="form.content_platform_ids"
    :options="platforms"
    option-label="name"
    option-value="id"
    placeholder="Selecionar plataformas"
    fluid
  />
  ```
- Change `form.content_platform_id` → `form.content_platform_ids` (array, default `[]`)

**`resources/js/Pages/Contents/Index.vue`** — modify:
- Platform display: iterate `content.platforms` (array) and show tags/badges for each
- Filter: keep single Select for platform filter (filters by any matching platform)

**`resources/js/Pages/Contents/Show.vue`** — display all platforms as tags

---

## Section 6 — Mobile Responsive Cards

For modules: **Contents**, **Ideas**, **Plans** (and optionally Tasks)

### Pattern

In the Index.vue of each module, use CSS breakpoints to toggle:
- `<div class="hidden md:block">` wrapping the `<DataTable>`
- `<div class="block md:hidden space-y-3">` wrapping the card list

### Card Specification

Each card should be a PrimeVue `Card` or a `div` styled with Tailwind:

```html
<div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900 shadow-sm">
  <!-- Top row: title + status badge -->
  <!-- Middle row: relevant metadata (type, category, date, platform, etc.) -->
  <!-- Bottom row: actions aligned to the right (edit, delete, view) -->
</div>
```

### Content Card fields:
- Title (bold), Status badge (BoStatusTag), Platforms (tags), Type, Category, Planned publish date
- Actions: Edit, Delete (BoConfirmButton)

### Ideas Card fields:
- Title (bold), Status badge, Type, Category, Related (if any), Created date
- Actions: Edit, Delete, Vote (if on_board and eligible)

### Plans Card fields:
- Title (bold), Status badge, Progress (ProgressBar), Start/End date, Phase count
- Actions: Edit, Delete, View

---

## Section 7 — SVG Logo/Icon Support

> This may already be partially implemented. Verify current state before making changes.

In `resources/js/Pages/Settings/System.vue` and the system settings form:
- The `logo_url` and `icon_url` stored in `system_settings` may contain either a regular image URL or an SVG data URL / path
- In `resources/js/Layouts/GuestLayout.vue` (login/auth pages) and `resources/js/Layouts/partials/AppTopbar.vue`, logo display:

**Change**:
```html
<!-- Before -->
<img :src="logoUrl" ... />

<!-- After: detect SVG by checking file extension or mime in the stored value -->
<!-- Use <img> for both regular images and SVGs — <img> handles SVG files natively -->
<!-- If the stored value is an inline SVG string (starts with <svg), render with v-html in a safe wrapper -->
```

In `AppTopbar.vue` (and wherever the icon is used):
- If `icon_url` ends in `.svg` or is a data URI: keep `<img>` tag — it works for SVG files
- If needed, add a note to the System Settings form that SVG files are accepted

> **Primary action**: Ensure the file upload for logo/icon in `Settings/System.vue` accepts `.svg` files. Check the `SystemSettingController` update method and add `mimes:jpeg,png,gif,svg,webp` validation.

---

## Section 8 — Seeds Updates

### 8.1 UserSeeder

**File**: `database/seeders/UserSeeder.php` — **full rewrite**

Remove `demo@band.com`. Create 4 band members:

```php
$members = [
    ['name' => 'João Silva',    'email' => 'joao@band.com',    'role' => 'guitarrista'],
    ['name' => 'Maria Souza',   'email' => 'maria@band.com',   'role' => 'vocalista'],
    ['name' => 'Carlos Lima',   'email' => 'carlos@band.com',  'role' => 'baterista'],
    ['name' => 'Ana Oliveira',  'email' => 'ana@band.com',     'role' => 'baixista'],
];
// All with password: 'password', theme: 'system'
// Use User::updateOrCreate(['email' => ...], [...])
```

### 8.2 Update All Seeders That Reference `demo@band.com`

The following seeders reference `User::where('email', 'demo@band.com')->firstOrFail()`:
- `IdeaTypeSeeder`
- `IdeaCategorySeeder`
- `ContentPlatformSeeder`
- `TaskStatusSeeder`
- `DemoDataSeeder`
- `ContentTypeSeeder` (if called)

**Change in all**: Replace `User::where('email', 'demo@band.com')->firstOrFail()` with `User::first()` or the first user by email `joao@band.com`.

### 8.3 IdeaTypeSeeder

**File**: `database/seeders/IdeaTypeSeeder.php` — **rewrite data array**:

```php
$data = [
    ['name' => 'Video',              'color' => '#ef4444'],
    ['name' => 'Reel',               'color' => '#8b5cf6'],
    ['name' => 'Story',              'color' => '#f59e0b'],
    ['name' => 'Post',               'color' => '#3b82f6'],
    ['name' => 'Produção musical',   'color' => '#10b981'],
    ['name' => 'Identidade visual',  'color' => '#ec4899'],
];
```

### 8.4 IdeaCategorySeeder

**File**: `database/seeders/IdeaCategorySeeder.php` — **rewrite data array**:

```php
$names = ['Divulgação', 'Marketing', 'Informativo', 'Série', 'Humor', 'História'];
```

### 8.5 DemoDataSeeder

**File**: `database/seeders/DemoDataSeeder.php` — **update**:
- Replace `demo@band.com` user reference with `User::first()`
- Update task creation: use `assigned_user_id` (set to random user ID or null) instead of `assignee`
- Update `type` → `related_type` in task creation
- Update idea statuses to new values:
  - `pending` → `in_drawer`
  - `maturing` → `on_table`
  - `cancelled` → `trash`
  - `in_production` → `executing`
  - `executed` → `executed`

### 8.6 DatabaseSeeder

**File**: `database/seeders/DatabaseSeeder.php` — add `PlanSeeder` to the call list (after `DemoDataSeeder`).

Remove `ContentTypeSeeder` from the call list if it references the old user email (it's also unused by the main models).

---

## Section 9 — Login Dark Mode Fix

### Problem

The `Login.vue` page uses legacy `TextInput` component which applies `bg-white dark:bg-gray-700` (light background in dark mode — fields appear white with light text).

### Solution

**`resources/js/Pages/Auth/Login.vue`** — rewrite using PrimeVue components (consistent with the rest of the app):

- Replace `TextInput` with PrimeVue `InputText`
- Replace `InputLabel` with `<label>` tags
- Replace `PrimaryButton` with PrimeVue `Button`
- Replace `InputError` with PrimeVue `Message` (severity="error", variant="simple")
- Replace `Checkbox` with PrimeVue `Checkbox`
- Keep `GuestLayout` wrapper
- Keep `Head` from Inertia

The `GuestLayout.vue` wraps content in a `dark:bg-slate-900` card which provides correct dark background. The rewritten form inputs (PrimeVue `InputText`) will inherit the dark theme from PrimeVue's theme configuration.

Also check and apply the same fix to `Register.vue`, `ForgotPassword.vue`, and `ResetPassword.vue` for consistency.

---

## Section 10 — Policies & Authorization

All new modules should have Policy classes:

- `app/Policies/PlanPolicy.php` — restrict update/delete to `user_id === auth()->id()`
- `app/Policies/SharedInfoPolicy.php` — same pattern

Register policies in `AppServiceProvider` or by convention (Laravel auto-discovers policies for models in `App\Models`).

---

## Implementation Order (Recommended)

Execute in this order to avoid FK constraint violations:

1. **Seeds**: `UserSeeder` (new 4 members)
2. **Plans Module** (migrations → models → controller → requests → routes → frontend pages)
3. **Task migrations** (requires plans table to exist)
4. **Idea migrations** (requires plans + contents + plan_phases)
5. **Content platform pivot migration**
6. **SharedInfo Module**
7. **Update seeders** (IdeaTypeSeeder, IdeaCategorySeeder, DemoDataSeeder, PlanSeeder)
8. **Frontend**: Tasks form rewrite
9. **Frontend**: Ideas form rewrite + voting
10. **Frontend**: Content form (MultiSelect platforms)
11. **Frontend**: Mobile cards (Contents, Ideas, Plans)
12. **Frontend**: Dashboard rewrite
13. **Login dark mode fix**
14. **Observers** (ContentObserver, PlanObserver)

---

## Files to Create (Summary)

### Backend
| File | Type |
|------|------|
| `app/Models/Plan.php` | New Model |
| `app/Models/PlanPhase.php` | New Model |
| `app/Models/IdeaVote.php` | New Model |
| `app/Models/SharedInfo.php` | New Model |
| `app/Models/SharedInfoLink.php` | New Model |
| `app/Models/SharedInfoDocument.php` | New Model |
| `app/Http/Controllers/PlanController.php` | New Controller |
| `app/Http/Controllers/SharedInfoController.php` | New Controller |
| `app/Http/Controllers/SharedInfoDocumentController.php` | New Controller |
| `app/Http/Requests/StorePlanRequest.php` | New Request |
| `app/Http/Requests/UpdatePlanRequest.php` | New Request |
| `app/Http/Requests/StoreSharedInfoRequest.php` | New Request |
| `app/Http/Requests/UpdateSharedInfoRequest.php` | New Request |
| `app/Policies/PlanPolicy.php` | New Policy |
| `app/Policies/SharedInfoPolicy.php` | New Policy |
| `app/Observers/ContentObserver.php` | New Observer |
| `app/Observers/PlanObserver.php` | New Observer |
| `database/migrations/..._create_plans_table.php` | Migration |
| `database/migrations/..._create_plan_phases_table.php` | Migration |
| `database/migrations/..._create_idea_voter_users_table.php` | Migration |
| `database/migrations/..._create_idea_votes_table.php` | Migration |
| `database/migrations/..._create_content_platform_content_table.php` | Migration (pivot) |
| `database/migrations/..._create_shared_infos_table.php` | Migration |
| `database/migrations/..._create_shared_info_links_table.php` | Migration |
| `database/migrations/..._create_shared_info_documents_table.php` | Migration |
| `database/seeders/PlanSeeder.php` | Seeder |

### Frontend
| File | Type |
|------|------|
| `resources/js/Pages/Plans/Index.vue` | New Page |
| `resources/js/Pages/Plans/Create.vue` | New Page |
| `resources/js/Pages/Plans/Edit.vue` | New Page |
| `resources/js/Pages/Plans/Show.vue` | New Page |
| `resources/js/Pages/SharedInfos/Index.vue` | New Page |
| `resources/js/Pages/SharedInfos/Create.vue` | New Page |
| `resources/js/Pages/SharedInfos/Edit.vue` | New Page |
| `resources/js/Pages/SharedInfos/Show.vue` | New Page |

## Files to Modify (Summary)

### Backend
| File | Change |
|------|--------|
| `app/Models/Task.php` | Add new fields/relations |
| `app/Models/Idea.php` | Add new fields/relations, update statuses |
| `app/Models/Content.php` | Change platform to BelongsToMany |
| `app/Models/ContentPlatform.php` | Change to BelongsToMany |
| `app/Models/User.php` | Add `plans()`, `sharedInfos()` HasMany |
| `app/Http/Controllers/TaskController.php` | Full update |
| `app/Http/Controllers/IdeaController.php` | Full update + vote method |
| `app/Http/Controllers/ContentController.php` | Update platform sync |
| `app/Http/Controllers/DashboardController.php` | Pass board ideas & tasks |
| `app/Http/Requests/StoreTaskRequest.php` | Replace rules |
| `app/Http/Requests/UpdateTaskRequest.php` | Replace rules |
| `app/Http/Requests/StoreIdeaRequest.php` | Replace rules |
| `app/Http/Requests/UpdateIdeaRequest.php` | Replace rules |
| `app/Http/Requests/StoreContentRequest.php` | Update platform field |
| `app/Http/Requests/UpdateContentRequest.php` | Update platform field |
| `app/Providers/AppServiceProvider.php` | Register observers |
| `routes/web.php` | Add plan/shared-info/vote routes, update menu |
| `database/migrations/2026_04_10_052159_create_tasks_table.php` | Add new columns, remove old ones |
| `database/migrations/2026_04_10_052145_create_ideas_table.php` | New status enum + new columns |
| `database/migrations/2026_04_10_052153_create_contents_table.php` | Remove content_platform_id column |
| `database/seeders/UserSeeder.php` | Replace with 4 members |
| `database/seeders/IdeaTypeSeeder.php` | New types list |
| `database/seeders/IdeaCategorySeeder.php` | New categories list |
| `database/seeders/DemoDataSeeder.php` | Update user ref + field names |
| `database/seeders/ContentPlatformSeeder.php` | Update user ref |
| `database/seeders/TaskStatusSeeder.php` | Update user ref |
| `database/seeders/DatabaseSeeder.php` | Add PlanSeeder |

### Frontend
| File | Change |
|------|--------|
| `resources/js/Layouts/AppShell.vue` | Add menu items: Planos, Informações |
| `resources/js/Pages/Dashboard.vue` | Full rewrite with board ideas + tasks |
| `resources/js/Pages/Tasks/Create.vue` | Full form rewrite |
| `resources/js/Pages/Tasks/Edit.vue` | Full form rewrite |
| `resources/js/Pages/Tasks/Index.vue` | Update filters, add mobile cards |
| `resources/js/Pages/Ideas/Create.vue` | Full form rewrite |
| `resources/js/Pages/Ideas/Edit.vue` | Full form rewrite |
| `resources/js/Pages/Ideas/Index.vue` | Update status labels, add mobile cards |
| `resources/js/Pages/Ideas/Show.vue` | Add voting UI |
| `resources/js/Pages/Contents/Create.vue` | MultiSelect for platforms |
| `resources/js/Pages/Contents/Edit.vue` | MultiSelect for platforms |
| `resources/js/Pages/Contents/Index.vue` | Update platform display, add mobile cards |
| `resources/js/Pages/Contents/Show.vue` | Show multiple platforms |
| `resources/js/Pages/Auth/Login.vue` | PrimeVue components for dark mode |
| `resources/js/Pages/Auth/Register.vue` | PrimeVue components for dark mode |
| `resources/js/Pages/Auth/ForgotPassword.vue` | PrimeVue components for dark mode |
| `resources/js/Pages/Auth/ResetPassword.vue` | PrimeVue components for dark mode |
