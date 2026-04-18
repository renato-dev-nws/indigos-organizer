<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanPhase extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'plan_id',
        'user_id',
        'title',
        'description',
        'order',
        'completed',
        'estimated_start_date',
        'estimated_end_date',
    ];

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
                    'estimated_start_date' => 'date',
                    'estimated_end_date' => 'date',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'plan_phase_id');
    }
}
