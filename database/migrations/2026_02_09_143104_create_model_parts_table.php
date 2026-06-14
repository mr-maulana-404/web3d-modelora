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
        Schema::create('model_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model3d_id')
                ->constrained('model3ds')
                ->cascadeOnDelete();
            $table->string('part_name'); // body, shirt, head
            $table->string('mesh_name'); // harus sama dengan mesh name di Blender
            $table->timestamps();
            // mesh_name tidak boleh dobel dalam 1 model
            $table->unique(['model3d_id', 'mesh_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_parts');
    }
};
