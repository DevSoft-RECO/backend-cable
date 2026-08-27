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
        Schema::create('slider_images', function (Blueprint $table) {
            $table->id();
            $table->string('image_path'); // La ruta de la imagen
            $table->string('category')->nullable();
            $table->string('main_title')->nullable(); // Título opcional
            $table->string('subtitle')->nullable();   // Descripción/Subtítulo opcional
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slider_images');
    }
};
