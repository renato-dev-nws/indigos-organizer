<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('event_type_id')->nullable()->constrained('event_types')->nullOnDelete();
            $table->foreignUuid('venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->string('title');
            $table->enum('attendance_mode', ['participant', 'audience'])->default('participant');
            $table->boolean('is_online')->default(false);
            $table->longText('description')->nullable();
            $table->date('event_date');
            $table->time('event_time')->nullable();
            $table->string('ticket_link')->nullable();
            $table->decimal('ticket_price_first_batch', 10, 2)->nullable();
            $table->decimal('ticket_price_second_batch', 10, 2)->nullable();
            $table->decimal('ticket_price_third_batch', 10, 2)->nullable();
            $table->decimal('ticket_price_door', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};