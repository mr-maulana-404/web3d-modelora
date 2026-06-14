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
        Schema::create('glb_texture_enhancement_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('model3d_id')
                ->nullable()
                ->constrained('model3ds')
                ->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('pipeline_type')->default('glb_texture_enhancement');
            $table->string('status')->nullable();
            $table->string('pipeline_stage')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('input_glb_path')->nullable();
            $table->string('output_glb_path')->nullable();
            $table->string('preview_image')->nullable();
            $table->json('enhancement_options')->nullable();
            $table->json('analysis_meta')->nullable();
            $table->longText('processing_log')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glb_texture_enhancement_projects');
    }
};
