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
        Schema::create('model3ds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('age_category')->nullable();
            $table->string('gender_category')->nullable();
            $table->string('model_path');      // models/Bambang_sangar/model.gltf
            $table->enum('model_format', ['gltf', 'glb']);
            $table->boolean('is_published')->default(false);
            $table->string('thumbnail_path')->nullable();  // model_thumbnails/bambang_sangar.png
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model3_d_s');
    }
};
