<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'idea_type_id' => ['nullable', 'uuid', 'exists:idea_types,id'],
            'idea_category_id' => ['nullable', 'uuid', 'exists:idea_categories,id'],
            'status' => ['required', 'in:pending,maturing,cancelled,in_production,executed'],
            'references' => ['nullable', 'array'],
            'references.*.title' => ['required_with:references', 'string', 'max:255'],
            'references.*.description' => ['nullable', 'string'],
            'references.*.url' => ['required_with:references', 'url', 'max:255'],
        ];
    }
}
