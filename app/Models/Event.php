<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'event_type_id',
        'venue_id',
        'title',
        'attendance_mode',
        'is_online',
        'description',
        'event_date',
        'event_time',
        'ticket_link',
        'ticket_price_first_batch',
        'ticket_price_second_batch',
        'ticket_price_third_batch',
        'ticket_price_door',
    ];

    protected $appends = ['starts_at'];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'is_online' => 'boolean',
            'ticket_price_first_batch' => 'decimal:2',
            'ticket_price_second_batch' => 'decimal:2',
            'ticket_price_third_batch' => 'decimal:2',
            'ticket_price_door' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function extraInfos(): HasMany
    {
        return $this->hasMany(EventExtraInfo::class)->orderBy('order');
    }

    public function links(): HasMany
    {
        return $this->hasMany(EventLink::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->where('related_type', 'event');
    }

    public function getStartsAtAttribute(): ?string
    {
        if (! $this->event_date) {
            return null;
        }

        $date = $this->event_date instanceof Carbon
            ? $this->event_date->copy()
            : Carbon::parse($this->event_date);

        if (filled($this->event_time)) {
            [$hours, $minutes, $seconds] = array_pad(explode(':', (string) $this->event_time), 3, '00');
            $date->setTime((int) $hours, (int) $minutes, (int) $seconds);
        } else {
            $date->startOfDay();
        }

        return $date->toIso8601String();
    }
}