<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    use HasFactory, HasUuids, HasPushSubscriptions, Notifiable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'password',
        'theme',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function sharedInfos(): HasMany
    {
        return $this->hasMany(SharedInfo::class);
    }

    public function venueTypes(): HasMany
    {
        return $this->hasMany(VenueType::class);
    }

    public function venueCategories(): HasMany
    {
        return $this->hasMany(VenueCategory::class);
    }

    public function venueStyles(): HasMany
    {
        return $this->hasMany(VenueStyle::class);
    }

    public function taskNotificationLogs(): HasMany
    {
        return $this->hasMany(TaskUserNotificationLog::class);
    }
}
