<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleTecnico extends Model
{
    use HasFactory;

    protected $table = 'detalles_tecnicos';

    protected $fillable = [
        'contrato_id',
        'router_marca',
        'router_modelo',
        'direccion_ip',
        'router_usuario',
        'router_password',
        'wifi_ssid',
        'wifi_password',
        'coordenadas_gps',
        'notas',
    ];

    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }
}
