<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venue extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'contact_name',
        'venue_size_id',
        'venue_type_id',
        'venue_category_id',
        'venue_style_id',
        'place_id',
        'address_line',
        'address_number',
        'neighborhood',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'status',
        'performances_count',
        'equipment_tags',
        'rating',
        'instagram_url',
        'facebook_url',
        'youtube_url',
        'website_url',
        'notes',
        'description',
    ];

    protected $appends = ['has_performed'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'performances_count' => 'integer',
            'rating' => 'integer',
            'equipment_tags' => 'array',
            'has_performed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(VenueSize::class, 'venue_size_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(VenueType::class, 'venue_type_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VenueCategory::class, 'venue_category_id');
    }

    public function style(): BelongsTo
    {
        return $this->belongsTo(VenueStyle::class, 'venue_style_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function getHasPerformedAttribute(): bool
    {
        return (int) ($this->performances_count ?? 0) > 0;
    }
}
