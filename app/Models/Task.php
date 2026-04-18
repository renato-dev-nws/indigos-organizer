<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasUuids, SoftDeletes;

    private ?string $legacyAssignedUserId = null;

    private bool $hasLegacyAssignedUserUpdate = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'assigned_user_id',
        'related_type',
        'content_id',
        'plan_id',
        'plan_phase_id',
        'event_id',
        'title',
        'description',
        'task_status_id',
        'priority',
        'archived',
        'scheduled_for',
        'due_date',
        'reminder_at',
        'assignment_notified_at',
        'due_soon_notified_at',
        'reminder_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'due_date' => 'date',
            'reminder_at' => 'datetime',
            'archived' => 'boolean',
            'assignment_notified_at' => 'datetime',
            'due_soon_notified_at' => 'datetime',
            'reminder_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAssignedUserIdAttribute(): ?string
    {
        if ($this->relationLoaded('assignedUsers')) {
            return $this->assignedUsers->first()?->id;
        }

        return $this->assignedUsers()->limit(1)->value('users.id');
    }

    public function setAssignedUserIdAttribute($value): void
    {
        $this->legacyAssignedUserId = filled($value) ? (string) $value : null;
        $this->hasLegacyAssignedUserUpdate = true;
    }

    public function syncLegacyAssignedUsersIfPending(): bool
    {
        if (! $this->hasLegacyAssignedUserUpdate) {
            return false;
        }

        $this->assignedUsers()->sync(array_filter([$this->legacyAssignedUserId]));
        $this->hasLegacyAssignedUserUpdate = false;

        return true;
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function planPhase(): BelongsTo
    {
        return $this->belongsTo(PlanPhase::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'task_status_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class)->orderBy('order');
    }
}
