<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use HasFactory;

    protected $table = 'cargos';

    protected $fillable = [
        'contrato_id',
        'concepto',
        'tipo',
        'monto',
        'fecha_emision',
        'fecha_vencimiento',
        'estado',
        'campana_descuento_id',
        'descuento_aplicado',
    ];

    protected $casts = [
        'monto'              => 'decimal:2',
        'descuento_aplicado' => 'decimal:2',
        'fecha_emision'      => 'date:Y-m-d',
        'fecha_vencimiento'  => 'date:Y-m-d',
    ];

    /**
     * Obtiene la campaña de descuento aplicada en este cobro.
     */
    public function campanaDescuento()
    {
        return $this->belongsTo(CampanaDescuento::class, 'campana_descuento_id');
    }

    /**
     * Obtiene el contrato asociado a este cargo.
     */
    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
