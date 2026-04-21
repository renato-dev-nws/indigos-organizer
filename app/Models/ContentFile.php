<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentFile extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['content_id', 'storage_source', 'original_name', 'path', 'external_url', 'mime_type', 'size'];

    protected $appends = ['url', 'storage_label'];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function getUrlAttribute(): ?string
    {
        if (filled($this->external_url)) {
            return $this->external_url;
        }

        if (blank($this->path)) {
            return null;
        }

        if ($this->storage_source !== 'local') {
            return route('contents.files.open', [$this->content_id, $this->id]);
        }

        return asset('storage/'.$this->path);
    }

    public function getStorageLabelAttribute(): string
    {
        return match ($this->storage_source) {
            'google' => 'Drive',
            'dropbox' => 'Dropbox',
            default => 'Local',
        };
    }
}
