<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicioPlan extends Model
{
    use HasFactory;

    protected $table = 'servicio_planes';

    protected $fillable = [
        'servicio_categoria_id',
        'nombre',
        'subtitulo',
        'velocidad',
        'badge',
        'icon',
        'detalles',
    ];

    protected $casts = [
        'detalles' => 'array',
    ];

    /**
     * Obtiene la categoría a la que pertenece este plan.
     */
    public function categoria()
    {
        return $this->belongsTo(ServicioCategoria::class, 'servicio_categoria_id');
    }
}
