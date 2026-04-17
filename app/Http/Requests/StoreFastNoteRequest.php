<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFastNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'related_type' => ['required', 'in:administrative,contents,tasks,planning,others'],
            'list_items' => ['nullable', 'array'],
            'list_items.*.item' => ['required_with:list_items', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'is_priority' => ['nullable', 'boolean'],
        ];
    }
}
