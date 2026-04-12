<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_user_notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('event_type', ['assigned', 'due_soon', 'reminder']);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['task_id', 'user_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_user_notification_logs');
    }
};
