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
        Schema::create('ideas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('idea_type_id')->nullable()->constrained('idea_types')->nullOnDelete();
            $table->foreignUuid('idea_category_id')->nullable()->constrained('idea_categories')->nullOnDelete();
            $table->enum('status', ['in_drawer', 'on_table', 'on_board', 'executing', 'executed', 'trash'])->default('in_drawer');
            $table->enum('related_type', ['new_content', 'new_plan', 'existing_content', 'existing_plan', 'administrative', 'none'])->default('none');
            $table->foreignUuid('content_id')->nullable()->constrained('contents')->nullOnDelete();
            $table->foreignUuid('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignUuid('plan_phase_id')->nullable()->constrained('plan_phases')->nullOnDelete();
            $table->boolean('is_private')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('contents', function (Blueprint $table) {
            $table->foreign('idea_id')
                ->references('id')
                ->on('ideas')
                ->nullOnDelete();
        });

            Schema::create('idea_collaborator', function (Blueprint $table) {
                $table->uuid('idea_id');
                $table->uuid('user_id');
                $table->primary(['idea_id', 'user_id']);
                $table->foreign('idea_id')->references('id')->on('ideas')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropForeign(['idea_id']);
        });

            Schema::dropIfExists('idea_collaborator');

        Schema::dropIfExists('ideas');
    }
};
