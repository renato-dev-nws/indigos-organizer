<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idea_venue_style', function (Blueprint $table) {
            $table->foreignUuid('idea_id')->constrained('ideas')->cascadeOnDelete();
            $table->foreignUuid('venue_style_id')->constrained('venue_styles')->cascadeOnDelete();

            $table->primary(['idea_id', 'venue_style_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idea_venue_style');
    }
};
