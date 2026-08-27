<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SobreNosotros extends Model
{
    use HasFactory;

    protected $table = 'sobre_nosotros';

    protected $fillable = [
        'tipo', // 'colegio' (representa unidad/sucursal) o 'fundador' (representa equipo/liderazgo)
        'nombre', // ej. "Sucursal Central" o "Juan Pérez"
        'direccion', // nullable, ej. "Ciudad de Guatemala"
        'titulo', // ej. "Punto de Atención" o "Gerente General"
        'descripcion', // reseña o historia
        'foto' // nullable
    ];
}
