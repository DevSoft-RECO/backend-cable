<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cargo;

class CargoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cargos = Cargo::with(['contrato.cliente', 'contrato.plan', 'campanaDescuento'])
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json($cargos);
    }
}
