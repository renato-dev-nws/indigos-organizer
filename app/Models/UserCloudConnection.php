<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCloudConnection extends Model
{
    use HasUuids;

    public const PROVIDER_GOOGLE = 'google';

    public const PROVIDER_DROPBOX = 'dropbox';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'provider',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'account_name',
        'account_email',
        'base_folder',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isConnected(): bool
    {
        return filled($this->refresh_token) || filled($this->access_token);
    }
}
