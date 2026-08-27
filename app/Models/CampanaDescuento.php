<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampanaDescuento extends Model
{
    use HasFactory;

    protected $table = 'campanas_descuento';

    protected $fillable = [
        'codigo',
        'descripcion',
        'tipo',
        'valor',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    protected $casts = [
        'valor'        => 'decimal:2',
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin'    => 'date:Y-m-d',
        'activo'       => 'boolean',
    ];

    /**
     * Obtiene los contratos que tienen asignada esta campaña de descuento.
     */
    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'campana_descuento_id');
    }
}
