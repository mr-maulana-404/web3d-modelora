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
        Schema::create('customization_textures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_customization_id')
                ->constrained('model_customizations')
                ->cascadeOnDelete();
            $table->foreignId('model_part_id')
                ->constrained('model_parts')
                ->cascadeOnDelete();
            $table->enum('texture_type', ['admin', 'user', 'color']);
            $table->string('texture_path')->nullable();
            $table->string('color_value')->nullable();
            $table->timestamps();
            $table->unique(
                ['model_customization_id', 'model_part_id'],
                'uniq_custom_part'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customization_textures');
    }
};
