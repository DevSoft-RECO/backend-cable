<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modificar el ENUM para aceptar 'parcial'
        DB::statement("ALTER TABLE cargos MODIFY COLUMN estado ENUM('pendiente', 'parcial', 'pagado', 'anulado') NOT NULL DEFAULT 'pendiente'");

        // 2. Agregar la columna saldo_pendiente
        Schema::table('cargos', function (Blueprint $table) {
            $table->decimal('saldo_pendiente', 10, 2)->after('monto')->nullable();
        });

        // 3. Rellenar los saldos existentes
        DB::statement("UPDATE cargos SET saldo_pendiente = monto WHERE estado = 'pendiente'");
        DB::statement("UPDATE cargos SET saldo_pendiente = 0 WHERE estado = 'pagado' OR estado = 'anulado'");
    }

    public function down(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            $table->dropColumn('saldo_pendiente');
        });
        
        DB::statement("ALTER TABLE cargos MODIFY COLUMN estado ENUM('pendiente', 'pagado', 'anulado') NOT NULL DEFAULT 'pendiente'");
    }
};
