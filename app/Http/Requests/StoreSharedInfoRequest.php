<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSharedInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'shared_info_category_ids' => ['nullable', 'array'],
            'shared_info_category_ids.*' => ['uuid', 'exists:shared_info_categories,id'],
            'description' => ['nullable', 'string'],
            'links' => ['nullable', 'array'],
            'links.*.id' => ['nullable', 'uuid', 'exists:shared_info_links,id'],
            'links.*.title' => ['required_with:links', 'string', 'max:255'],
            'links.*.url' => ['required_with:links', 'url', 'max:500'],
            'links.*.description' => ['nullable', 'string'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'max:20480'],
        ];
    }
}
