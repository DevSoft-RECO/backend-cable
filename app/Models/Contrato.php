<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    use HasFactory;

    protected $table = 'contratos';

    protected $fillable = [
        'cliente_id',
        'plan_id',
        'precio_mensual_pactado',
        'costo_instalacion',
        'fecha_inicio',
        'estado',
        'campana_descuento_id',
    ];

    protected $casts = [
        'precio_mensual_pactado' => 'decimal:2',
        'costo_instalacion'      => 'decimal:2',
        'fecha_inicio'           => 'date:Y-m-d',
    ];

    /**
     * Obtiene la campaña de descuento asociada.
     */
    public function campanaDescuento()
    {
        return $this->belongsTo(CampanaDescuento::class, 'campana_descuento_id');
    }

    /**
     * Obtiene el cliente asociado a este contrato.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Obtiene el plan asociado a este contrato.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * Obtiene los cargos generados por este contrato.
     */
    public function cargos()
    {
        return $this->hasMany(Cargo::class, 'contrato_id');
    }

    public function detalleTecnico()
    {
        return $this->hasOne(DetalleTecnico::class, 'contrato_id');
    }
}
