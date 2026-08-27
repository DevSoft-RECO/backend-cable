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
        // 1. Crear tabla de campañas de descuento
        Schema::create('campanas_descuento', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('descripcion');
            $table->enum('tipo', ['porcentaje', 'monto_fijo']);
            $table->decimal('valor', 10, 2);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 2. Alterar tabla de contratos
        Schema::table('contratos', function (Blueprint $table) {
            $table->foreignId('campana_descuento_id')->nullable()->after('estado')->constrained('campanas_descuento')->onDelete('set null');
        });

        // 3. Alterar tabla de cargos
        Schema::table('cargos', function (Blueprint $table) {
            $table->foreignId('campana_descuento_id')->nullable()->after('estado')->constrained('campanas_descuento')->onDelete('set null');
            $table->decimal('descuento_aplicado', 10, 2)->default(0.00)->after('campana_descuento_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Alterar cargos
        Schema::table('cargos', function (Blueprint $table) {
            $table->dropForeign(['campana_descuento_id']);
            $table->dropColumn(['campana_descuento_id', 'descuento_aplicado']);
        });

        // Alterar contratos
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropForeign(['campana_descuento_id']);
            $table->dropColumn('campana_descuento_id');
        });

        // Eliminar campañas
        Schema::dropIfExists('campanas_descuento');
    }
};
