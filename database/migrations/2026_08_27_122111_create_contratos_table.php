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
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('planes')->onDelete('cascade');
            $table->decimal('precio_mensual_pactado', 10, 2);
            $table->decimal('costo_instalacion', 10, 2)->default(0.00);
            $table->date('fecha_inicio');
            $table->enum('estado', ['activo', 'suspendido', 'cancelado'])->default('activo');
            $table->string('direccion_servicio')->nullable();
            $table->string('coordenadas_gps')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
