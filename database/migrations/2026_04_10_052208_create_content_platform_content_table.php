<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_platform_content', function (Blueprint $table) {
            $table->foreignUuid('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignUuid('content_platform_id')->constrained('content_platforms')->cascadeOnDelete();
            $table->primary(['content_id', 'content_platform_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_platform_content');
    }
};
