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
        Schema::table('pagos', function (Blueprint $table) {
            // Eliminar la restricción UNIQUE del campo codigo_recibo si existe
            // para permitir que un mismo recibo agrupe múltiples cargos en una sola transacción.
            try {
                $table->dropUnique('pagos_codigo_recibo_unique');
            } catch (\Exception $e) {
                // Ignorar si el índice no existía
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->unique('codigo_recibo');
        });
    }
};
