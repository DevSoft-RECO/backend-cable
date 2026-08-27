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
        Schema::create('sobre_nosotros', function (Blueprint $table) {
            $table->id();
            $table->string('tipo'); // 'colegio' (unidad/sucursal) o 'fundador' (equipo/liderazgo)
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->string('titulo');
            $table->text('descripcion');
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sobre_nosotros');
    }
};
