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
    ];

    protected $casts = [
        'monto'             => 'decimal:2',
        'fecha_emision'     => 'date:Y-m-d',
        'fecha_vencimiento' => 'date:Y-m-d',
    ];

    /**
     * Obtiene el contrato asociado a este cargo.
     */
    public function contrato()
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
