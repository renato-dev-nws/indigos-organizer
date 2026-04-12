<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'status' => ['required', 'in:in_drawer,on_table,on_board,executing,executed,trash'],
            'related_type' => ['required', 'in:new_content,new_plan,existing_content,existing_plan,administrative,none'],
            'content_id' => ['nullable', 'uuid', 'exists:contents,id'],
            'plan_id' => ['nullable', 'uuid', 'exists:plans,id'],
            'plan_phase_id' => ['nullable', 'uuid', 'exists:plan_phases,id'],
            'is_private' => ['boolean'],
            'voter_users' => ['nullable', 'array'],
            'voter_users.*' => ['uuid', 'exists:users,id'],
            'references' => ['nullable', 'array'],
            'references.*.title' => ['required_with:references', 'string', 'max:255'],
            'references.*.description' => ['nullable', 'string'],
            'references.*.url' => ['required_with:references', 'url', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('related_type') === 'existing_content' && ! $this->input('content_id')) {
                    $validator->errors()->add('content_id', 'Conteúdo é obrigatório para conteúdo existente.');
                }

                if ($this->input('related_type') === 'existing_plan' && ! $this->input('plan_id')) {
                    $validator->errors()->add('plan_id', 'Plano é obrigatório para plano existente.');
                }

                if ($this->boolean('is_private') && ! in_array($this->input('status'), ['in_drawer', 'trash'], true)) {
                    $validator->errors()->add('is_private', 'Ideia privada só pode estar na gaveta ou no lixo.');
                }
            },
        ];
    }
}
