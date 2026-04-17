<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'progress',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'progress' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function phases(): HasMany
    {
        return $this->hasMany(PlanPhase::class)->orderBy('order');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function refreshProgress(): int
    {
        $this->loadMissing([
            'phases.tasks.status:id,name',
            'tasks.status:id,name',
        ]);

        $phases = $this->phases->values();
        if ($phases->isEmpty()) {
            $this->update(['progress' => 0]);

            return 0;
        }

        $phaseWeight = 100 / $phases->count();
        $accumulated = 0.0;

        foreach ($phases as $phase) {
            if ($phase->completed) {
                $accumulated += $phaseWeight;
                continue;
            }

            $phaseTasks = $phase->tasks ?? collect();
            if ($phaseTasks->isEmpty()) {
                continue;
            }

            $doneTasks = $phaseTasks->filter(fn (Task $task) => $this->isCompletedTaskStatusName($task->status?->name))->count();
            $accumulated += ($doneTasks / $phaseTasks->count()) * $phaseWeight;
        }

        $progress = (int) max(0, min(100, round($accumulated)));
        $this->update(['progress' => $progress]);

        return $progress;
    }

    private function isCompletedTaskStatusName(?string $name): bool
    {
        $statusName = Str::of($name ?? '')
            ->ascii()
            ->lower()
            ->toString();

        return Str::contains($statusName, ['conclu', 'finaliz', 'done', 'completed']);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'queued' => 'Na fila',
            'running' => 'Em execução',
            'cancelled' => 'Cancelado',
            'completed' => 'Concluído',
            default => $this->status,
        };
    }
}
