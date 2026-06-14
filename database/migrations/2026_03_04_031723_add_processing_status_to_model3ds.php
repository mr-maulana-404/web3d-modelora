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
        Schema::table('model3ds', function (Blueprint $table) {
            $table->enum('processing_status', ['processing','ready'])
                  ->default('processing')
                  ->after('thumbnail_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model3ds', function (Blueprint $table) {
            $table->dropColumn('processing_status');
        });
    }
};
