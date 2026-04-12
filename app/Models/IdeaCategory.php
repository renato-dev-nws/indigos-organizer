<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdeaCategory extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['user_id', 'name', 'icon'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }
}
