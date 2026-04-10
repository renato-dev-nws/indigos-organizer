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
        Schema::create('contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('idea_id')->nullable()->constrained('ideas')->nullOnDelete();
            $table->string('title');
            $table->longText('script')->nullable();
            $table->foreignUuid('content_platform_id')->nullable()->constrained('content_platforms')->nullOnDelete();
            $table->foreignUuid('content_type_id')->nullable()->constrained('content_types')->nullOnDelete();
            $table->enum('status', ['queued', 'in_production', 'cancelled', 'paused', 'published'])->default('queued');
            $table->timestamp('planned_publish_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
