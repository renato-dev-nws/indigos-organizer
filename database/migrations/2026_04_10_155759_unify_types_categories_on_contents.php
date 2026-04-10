<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the many-to-many pivot for content categories
        Schema::dropIfExists('content_content_category');

        Schema::table('contents', function (Blueprint $table) {
            // Drop old content-specific type FK
            $table->dropForeign(['content_type_id']);
            $table->dropColumn('content_type_id');

            // Add unified type and category FKs (same as ideas)
            $table->foreignUuid('idea_type_id')->nullable()->constrained('idea_types')->nullOnDelete()->after('content_platform_id');
            $table->foreignUuid('idea_category_id')->nullable()->constrained('idea_categories')->nullOnDelete()->after('idea_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropForeign(['idea_type_id']);
            $table->dropColumn('idea_type_id');
            $table->dropForeign(['idea_category_id']);
            $table->dropColumn('idea_category_id');

            $table->foreignUuid('content_type_id')->nullable()->constrained('content_types')->nullOnDelete();
        });

        Schema::create('content_content_category', function (Blueprint $table) {
            $table->foreignUuid('content_id')->constrained('contents')->cascadeOnDelete();
            $table->foreignUuid('content_category_id')->constrained('content_categories')->cascadeOnDelete();
            $table->primary(['content_id', 'content_category_id']);
        });
    }
};
