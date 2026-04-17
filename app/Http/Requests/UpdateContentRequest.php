<?php

namespace App\Http\Requests;

class UpdateContentRequest extends StoreContentRequest
{
	public function rules(): array
	{
		return [
			'idea_id' => ['nullable', 'uuid', 'exists:ideas,id'],
			'title' => ['required', 'string', 'max:255'],
			'script' => ['nullable', 'string'],
			'content_platform_ids' => ['nullable', 'array'],
			'content_platform_ids.*' => ['uuid', 'exists:content_platforms,id'],
			'venue_style_ids' => ['nullable', 'array'],
			'venue_style_ids.*' => ['uuid', 'exists:venue_styles,id'],
			'idea_type_id' => ['nullable', 'uuid', 'exists:idea_types,id'],
			'idea_category_id' => ['nullable', 'uuid', 'exists:idea_categories,id'],
			'status' => ['required', 'in:queued,in_production,finalized,cancelled,paused,published'],
			'planned_publish_at' => ['nullable', 'date'],
			'published_at' => ['nullable', 'date'],
			'links' => ['nullable', 'array'],
			'links.*.title' => ['required_with:links', 'string', 'max:255'],
			'links.*.url' => ['required_with:links', 'url', 'max:255'],
		];
	}
}
