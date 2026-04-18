<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'venue_type_id' => ['nullable', 'uuid', 'exists:venue_types,id'],
            'venue_category_id' => ['nullable', 'uuid', 'exists:venue_categories,id'],
            'venue_style_id' => ['nullable', 'uuid', 'exists:venue_styles,id'],
            'venue_style_ids' => ['nullable', 'array'],
            'venue_style_ids.*' => ['uuid', 'exists:venue_styles,id'],
            'place_id' => ['nullable', 'string', 'max:255'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:255'],
            'address_complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['nullable', 'in:undefined,not_relevant,contacted,vetoed,negotiating,open_doors'],
            'performances_count' => ['nullable', 'integer', 'min:0'],
            'equipment_tags' => ['nullable', 'array'],
            'equipment_tags.*' => ['string', 'max:80'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ];
    }
}
