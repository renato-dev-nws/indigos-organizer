<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_info_category_shared_info', function (Blueprint $table) {
            $table->foreignUuid('shared_info_id')->constrained('shared_infos')->cascadeOnDelete();
            $table->foreignUuid('shared_info_category_id')->constrained('shared_info_categories')->cascadeOnDelete();
            $table->primary(['shared_info_id', 'shared_info_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_info_category_shared_info');
    }
};