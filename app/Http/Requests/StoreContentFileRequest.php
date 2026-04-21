<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'storage_source' => ['nullable', 'string', 'in:local,google,dropbox'],
            'file' => ['nullable', 'file', 'max:51200'],
            'path' => ['nullable', 'string', 'max:2048'],
            'external_url' => ['nullable', 'url', 'max:2048'],
            'original_name' => ['nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('storage_source')) {
            $this->merge(['storage_source' => 'local']);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $source = (string) $this->input('storage_source', 'local');

            if ($source === 'local' && ! $this->hasFile('file')) {
                $validator->errors()->add('file', 'Selecione um arquivo para envio local.');
            }

            if ($source !== 'local') {
                $hasFile = $this->hasFile('file');
                $hasPath = filled($this->input('path'));
                $hasExternalUrl = filled($this->input('external_url'));
                $hasOriginalName = filled($this->input('original_name'));

                if (! $hasFile && ! $hasExternalUrl && ! $hasPath) {
                    $validator->errors()->add('external_url', 'Informe um link do arquivo ou selecione um arquivo para envio.');
                }

                if (! $hasFile && ! $hasOriginalName) {
                    $validator->errors()->add('original_name', 'Informe o nome do arquivo ao anexar por link externo.');
                }
            }
        });
    }
}
