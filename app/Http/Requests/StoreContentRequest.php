<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idea_id' => ['nullable', 'uuid', 'exists:ideas,id'],
            'title' => ['required', 'string', 'max:255'],
            'script' => ['nullable', 'string'],
            'content_platform_id' => ['nullable', 'uuid', 'exists:content_platforms,id'],
            'content_type_id' => ['nullable', 'uuid', 'exists:content_types,id'],
            'content_category_ids' => ['nullable', 'array'],
            'content_category_ids.*' => ['uuid', 'exists:content_categories,id'],
            'status' => ['required', 'in:queued,in_production,cancelled,paused,published'],
            'planned_publish_at' => ['nullable', 'date', 'after:now'],
            'published_at' => ['nullable', 'date', 'after_or_equal:planned_publish_at'],
            'links' => ['nullable', 'array'],
            'links.*.title' => ['required_with:links', 'string', 'max:255'],
            'links.*.url' => ['required_with:links', 'url', 'max:255'],
        ];
    }
}
