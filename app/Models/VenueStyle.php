<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VenueStyle extends Model
{
    use HasUuids;

    public const DOMAIN_CONTENT = 'content';

    public const DOMAIN_VENUES = 'venues';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['user_id', 'name', 'color', 'icon', 'domain'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function ideas(): BelongsToMany
    {
        return $this->belongsToMany(Idea::class, 'idea_venue_style');
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class, 'content_venue_style');
    }
}
