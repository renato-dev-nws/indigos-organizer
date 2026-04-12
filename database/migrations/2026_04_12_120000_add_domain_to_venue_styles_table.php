<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venue_styles', function (Blueprint $table) {
            $table->string('domain', 20)->default('venues')->after('icon');
            $table->index('domain');
        });
    }

    public function down(): void
    {
        Schema::table('venue_styles', function (Blueprint $table) {
            $table->dropIndex(['domain']);
            $table->dropColumn('domain');
        });
    }
};
