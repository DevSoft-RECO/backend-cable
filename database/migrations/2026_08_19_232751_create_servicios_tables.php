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
        Schema::create('servicio_categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('label')->nullable();
            $table->text('descripcion_web')->nullable();
            $table->string('theme')->nullable()->default('blue');
            $table->timestamps();
        });

        Schema::create('servicio_planes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_categoria_id')->constrained('servicio_categorias')->onDelete('cascade');
            $table->string('nombre');
            $table->string('subtitulo')->nullable();
            $table->string('velocidad')->nullable();
            $table->string('badge')->nullable();
            $table->string('icon')->nullable()->default('wifi');
            $table->json('detalles')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicio_planes');
        Schema::dropIfExists('servicio_categorias');
    }
};
