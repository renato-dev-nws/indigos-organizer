<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_id' => ['nullable', 'uuid', 'exists:contents,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:content,administrative'],
            'task_status_id' => ['required', 'uuid', 'exists:task_statuses,id'],
            'assignee' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'due_date' => ['nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('type') === 'content' && ! $this->input('content_id')) {
                    $validator->errors()->add('content_id', 'Content is required when type is content.');
                }
            },
        ];
    }
}
