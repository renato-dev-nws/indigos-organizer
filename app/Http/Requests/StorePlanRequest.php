<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
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
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['required', 'in:queued,running,cancelled,completed'],
            'phases' => ['nullable', 'array'],
            'phases.*.id' => ['nullable', 'uuid', 'exists:plan_phases,id'],
            'phases.*.title' => ['required_with:phases', 'string', 'max:255'],
            'phases.*.description' => ['nullable', 'string'],
            'phases.*.order' => ['nullable', 'integer'],
            'phases.*.completed' => ['nullable', 'boolean'],
        ];
    }
}
