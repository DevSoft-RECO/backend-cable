<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Devuelve estadísticas vacías para evitar colapsos en la plantilla base.
     */
    public function index()
    {
        return response()->json([
            'kpis' => [
                'active_students' => 0,
                'monthly_revenue' => 0,
                'pending_debts' => 0,
                'active_scholarships' => 0,
            ],
            'chartData' => [
                'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                'datasets' => [
                    [
                        'label' => 'Ingresos 2026',
                        'data' => [0, 0, 0, 0, 0, 0],
                        'borderColor' => '#4F46E5',
                        'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                    ]
                ]
            ],
            'recentPayments' => []
        ]);
    }
}
