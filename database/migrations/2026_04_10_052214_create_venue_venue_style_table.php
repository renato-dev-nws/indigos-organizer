<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_venue_style', function (Blueprint $table) {
            $table->uuid('venue_id');
            $table->uuid('venue_style_id');
            $table->primary(['venue_id', 'venue_style_id']);
            $table->foreign('venue_id')->references('id')->on('venues')->cascadeOnDelete();
            $table->foreign('venue_style_id')->references('id')->on('venue_styles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_venue_style');
    }
};
