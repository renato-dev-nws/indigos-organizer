<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables() as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->uuid('user_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables() as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->uuid('user_id')->nullable(false)->change();
            });
        }
    }

    /**
     * @return array<int, string>
     */
    private function tables(): array
    {
        return [
            'idea_types',
            'idea_categories',
            'content_types',
            'content_categories',
            'content_platforms',
            'venue_types',
            'venue_categories',
            'venue_styles',
            'task_statuses',
            'event_types',
            'shared_info_categories',
        ];
    }
};