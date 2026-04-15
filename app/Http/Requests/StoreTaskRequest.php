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
            'assigned_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'related_type' => ['required', 'in:content,plan,event,administrative'],
            'content_id' => ['nullable', 'uuid', 'exists:contents,id'],
            'plan_id' => ['nullable', 'uuid', 'exists:plans,id'],
            'plan_phase_id' => ['nullable', 'uuid', 'exists:plan_phases,id'],
            'event_id' => ['nullable', 'uuid', 'exists:events,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_status_id' => ['required', 'uuid', 'exists:task_statuses,id'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'scheduled_for' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'reminder_at' => ['nullable', 'date'],
            'subtasks' => ['nullable', 'array'],
            'subtasks.*.title' => ['required_with:subtasks', 'string', 'max:255'],
            'subtasks.*.completed' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('related_type') === 'content' && ! $this->input('content_id')) {
                    $validator->errors()->add('content_id', 'Conteúdo é obrigatório quando o tipo é conteúdo.');
                }

                if ($this->input('related_type') === 'plan' && ! $this->input('plan_id')) {
                    $validator->errors()->add('plan_id', 'Plano é obrigatório quando o tipo é plano.');
                }

                if ($this->input('related_type') === 'event' && ! $this->input('event_id')) {
                    $validator->errors()->add('event_id', 'Evento é obrigatório quando o tipo é evento.');
                }
            },
        ];
    }
}
