<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_idea_category', function (Blueprint $table) {
            $table->uuid('content_id');
            $table->uuid('idea_category_id');
            $table->primary(['content_id', 'idea_category_id']);
            $table->foreign('content_id')->references('id')->on('contents')->cascadeOnDelete();
            $table->foreign('idea_category_id')->references('id')->on('idea_categories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_idea_category');
    }
};
