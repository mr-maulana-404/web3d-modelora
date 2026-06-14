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
            $table->foreignId('source_project_id')
                ->nullable()
                ->after('processing_status')
                ->constrained('glb_texture_enhancement_projects')
                ->nullOnDelete();
            $table->string('source_type')
                ->nullable()
                ->after('source_project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model3ds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_project_id');
            $table->dropColumn('source_type');
        });
    }
};
