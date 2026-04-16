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
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('related_type', ['content', 'plan', 'event', 'administrative'])->default('administrative');
            $table->foreignUuid('content_id')->nullable()->constrained('contents')->nullOnDelete();
            $table->foreignUuid('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignUuid('plan_phase_id')->nullable()->constrained('plan_phases')->nullOnDelete();
            $table->uuid('event_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('task_status_id')->constrained('task_statuses')->restrictOnDelete();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->boolean('archived')->default(false);
            $table->timestamp('scheduled_for')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('reminder_at')->nullable();
            $table->timestamp('assignment_notified_at')->nullable();
            $table->timestamp('due_soon_notified_at')->nullable();
            $table->timestamp('reminder_notified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
