<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'avatar_url' => [
                'nullable',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if (is_string($value) && str_starts_with($value, '/storage/')) {
                        return;
                    }

                    if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
                        return;
                    }

                    $fail('O campo :attribute deve ser uma URL válida.');
                },
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:2048'],
            'push_enabled' => ['nullable', 'boolean'],
            'email_enabled' => ['nullable', 'boolean'],
            'whatsapp_enabled' => ['nullable', 'boolean'],
            'notification_preferences' => ['nullable', 'array'],
            'notification_preferences.*' => ['nullable', 'array'],
            'notification_preferences.*.push' => ['nullable', 'boolean'],
            'notification_preferences.*.email' => ['nullable', 'boolean'],
            'notification_preferences.*.whatsapp' => ['nullable', 'boolean'],
        ];
    }
}
