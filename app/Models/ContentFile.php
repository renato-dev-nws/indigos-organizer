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

    protected $fillable = ['content_id', 'original_name', 'path', 'mime_type', 'size'];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
