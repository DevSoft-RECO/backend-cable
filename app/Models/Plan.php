<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'planes';

    protected $fillable = [
        'nombre',
        'categoria',
        'descripcion',
        'precio_base',
        'mora_base',
        'dias_gracia',
        'activo',
    ];

    protected $casts = [
        'precio_base' => 'decimal:2',
        'mora_base'   => 'decimal:2',
        'dias_gracia' => 'integer',
        'activo'      => 'boolean',
    ];
}
