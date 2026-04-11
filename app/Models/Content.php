<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'idea_id',
        'title',
        'script',
        'idea_type_id',
        'idea_category_id',
        'status',
        'planned_publish_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'planned_publish_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(ContentPlatform::class, 'content_platform_content');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(IdeaType::class, 'idea_type_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IdeaCategory::class, 'idea_category_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ContentFile::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(ContentLink::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
