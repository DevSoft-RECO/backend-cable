<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicioCategoria extends Model
{
    use HasFactory;

    protected $table = 'servicio_categorias';

    protected $fillable = [
        'nombre',
        'label',
        'descripcion_web',
        'theme',
    ];

    /**
     * Obtiene los planes asociados a esta categoría.
     */
    public function planes()
    {
        return $this->hasMany(ServicioPlan::class, 'servicio_categoria_id');
    }
}
