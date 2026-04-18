<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'event_type_id' => ['nullable', 'uuid', 'exists:event_types,id'],
            'venue_id' => ['nullable', 'uuid', 'exists:venues,id'],
            'attendance_mode' => ['required', 'in:participant,audience'],
            'is_online' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'end_date' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'ticket_link' => ['nullable', 'url', 'max:255'],
            'ticket_price_first_batch' => ['nullable', 'numeric', 'min:0'],
            'ticket_price_second_batch' => ['nullable', 'numeric', 'min:0'],
            'ticket_price_third_batch' => ['nullable', 'numeric', 'min:0'],
            'ticket_price_door' => ['nullable', 'numeric', 'min:0'],
            'extra_infos' => ['nullable', 'array'],
            'extra_infos.*.title' => ['required_with:extra_infos', 'string', 'max:255'],
            'extra_infos.*.information' => ['required_with:extra_infos', 'string', 'max:255'],
            'links' => ['nullable', 'array'],
            'links.*.title' => ['required_with:links', 'string', 'max:255'],
            'links.*.url' => ['required_with:links', 'url', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $isOnline = filter_var($this->input('is_online'), FILTER_VALIDATE_BOOL);

        if ($isOnline) {
            $this->merge([
                'venue_id' => null,
            ]);
        }
    }
}