<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idea_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('idea_id')->constrained('ideas')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('vote', ['on_table', 'in_drawer', 'trash']);
            $table->timestamps();

            $table->unique(['idea_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idea_votes');
    }
};
