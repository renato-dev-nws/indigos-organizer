<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Idea extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'idea_type_id',
        'idea_category_id',
        'status',
        'related_type',
        'content_id',
        'plan_id',
        'plan_phase_id',
        'is_private',
    ];

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(IdeaType::class, 'idea_type_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IdeaCategory::class, 'idea_category_id');
    }

    public function references(): HasMany
    {
        return $this->hasMany(IdeaReference::class);
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

    public function voterUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'idea_voter_users');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(IdeaVote::class);
    }

    public function styles(): BelongsToMany
    {
        return $this->belongsToMany(VenueStyle::class, 'idea_venue_style');
    }
}
