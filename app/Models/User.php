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

    public const NOTIFICATION_PREFERENCE_TYPES = [
        'task_assigned' => 'Quando tarefa é atribuída',
        'task_due_soon' => 'Quando tarefa está próxima do prazo',
        'task_reminder' => 'Quando lembrete de tarefa dispara',
        'idea_on_board' => 'Quando nova ideia vai para votação',
        'idea_voted' => 'Quando minha ideia recebe voto',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_super_admin',
        'avatar_url',
        'theme',
        'push_enabled',
        'email_enabled',
        'whatsapp_enabled',
        'whatsapp_phone',
        'notification_preferences',
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
            'is_admin' => 'boolean',
            'is_super_admin' => 'boolean',
            'push_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'notification_preferences' => 'array',
        ];
    }

    public static function notificationTypeOptions(): array
    {
        return collect(self::NOTIFICATION_PREFERENCE_TYPES)
            ->map(fn (string $label, string $key) => [
                'key' => $key,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    public static function defaultNotificationPreferences(?self $user = null): array
    {
        $pushDefault = (bool) ($user?->push_enabled ?? true);
        $emailDefault = (bool) ($user?->email_enabled ?? true);
        $whatsAppDefault = (bool) ($user?->whatsapp_enabled ?? false);

        $defaults = [];

        foreach (array_keys(self::NOTIFICATION_PREFERENCE_TYPES) as $type) {
            $defaults[$type] = [
                'push' => $pushDefault,
                'email' => $emailDefault,
                'whatsapp' => $whatsAppDefault,
            ];
        }

        return $defaults;
    }

    public function mergedNotificationPreferences(): array
    {
        $defaults = self::defaultNotificationPreferences($this);
        $stored = is_array($this->notification_preferences) ? $this->notification_preferences : [];

        foreach ($defaults as $type => $defaultChannels) {
            $storedChannels = is_array($stored[$type] ?? null) ? $stored[$type] : [];

            $defaults[$type] = [
                'push' => (bool) ($storedChannels['push'] ?? $defaultChannels['push']),
                'email' => (bool) ($storedChannels['email'] ?? $defaultChannels['email']),
                'whatsapp' => (bool) ($storedChannels['whatsapp'] ?? $defaultChannels['whatsapp']),
            ];
        }

        return $defaults;
    }

    public function notificationChannelEnabled(string $type, string $channel): bool
    {
        if (! in_array($channel, ['push', 'email', 'whatsapp'], true)) {
            return false;
        }

        if (! array_key_exists($type, self::NOTIFICATION_PREFERENCE_TYPES)) {
            return match ($channel) {
                'push' => (bool) ($this->push_enabled ?? true),
                'email' => (bool) ($this->email_enabled ?? true),
                'whatsapp' => (bool) ($this->whatsapp_enabled ?? false),
            };
        }

        return (bool) ($this->mergedNotificationPreferences()[$type][$channel] ?? false);
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

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function sharedInfos(): HasMany
    {
        return $this->hasMany(SharedInfo::class);
    }

    public function eventTypes(): HasMany
    {
        return $this->hasMany(EventType::class);
    }

    public function sharedInfoCategories(): HasMany
    {
        return $this->hasMany(SharedInfoCategory::class);
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

    public function fastNotes(): HasMany
    {
        return $this->hasMany(FastNote::class);
    }
}
