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
            $table->uuid('idea_id')->nullable();
            $table->string('title');
            $table->longText('script')->nullable();
            $table->foreignUuid('idea_type_id')->nullable()->constrained('idea_types')->nullOnDelete();
            $table->foreignUuid('idea_category_id')->nullable()->constrained('idea_categories')->nullOnDelete();
            $table->enum('status', ['queued', 'in_production', 'finalized', 'cancelled', 'paused', 'published'])->default('queued');
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
