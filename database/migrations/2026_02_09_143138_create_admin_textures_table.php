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
        Schema::create('admin_textures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            // for_textures/metal.jpg (juga dipakai preview)
            $table->string('texture_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_textures');
    }
};
