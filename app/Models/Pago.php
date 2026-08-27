<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'cargo_id',
        'usuario_id',
        'codigo_recibo',
        'monto_pagado',
        'metodo_pago',
        'referencia',
        'fecha_pago',
    ];

    protected $casts = [
        'monto_pagado' => 'decimal:2',
        'fecha_pago'   => 'datetime',
    ];

    /**
     * Obtiene el cargo asociado a este pago.
     */
    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    /**
     * Obtiene el usuario/cobrador que registró este pago.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
