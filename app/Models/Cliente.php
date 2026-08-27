<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'codigo_cliente',
        'numero_identificacion',
        'nombre',
        'telefono',
        'telefono_secundario',
        'direccion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Obtiene los contratos asociados al cliente.
     */
    public function contratos()
    {
        return $this->hasMany(Contrato::class, 'cliente_id');
    }
}
