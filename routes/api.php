<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Publico\WebFront\PublicDataController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Publico\WebAdmin\SliderController;
use App\Http\Controllers\Publico\WebAdmin\WebsiteSettingsController;
use App\Http\Controllers\Publico\WebAdmin\NosotrosController;
use App\Http\Controllers\Publico\WebAdmin\ServiciosController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\ContratoController;
use App\Http\Controllers\Admin\CampanaDescuentoController;
use App\Http\Controllers\Admin\CargoController;
use App\Http\Controllers\Admin\PagoController;
use App\Http\Controllers\Admin\ReporteController;

/*
|--------------------------------------------------------------------------
| API Routes V1
|--------------------------------------------------------------------------
*/

Route::prefix('alianza')->group(function () {

    // --- Rutas Públicas (No requieren Token) ---
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/slider', [SliderController::class, 'index']);
    Route::get('/website/settings', [WebsiteSettingsController::class, 'index']);
    Route::get('/public/init', [PublicDataController::class, 'init']);
    Route::get('/servicios', [ServiciosController::class, 'indexPublic']);

    // --- Rutas Protegidas (Requieren Token Bearer) ---
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']); 
        Route::get('/user', [AuthController::class, 'me']); // Alias
        
        // --- Dashboard Administrativo ---
        Route::get('/dashboard', [DashboardController::class, 'index']);
        
        // Actualización de perfil
        Route::post('/profile/update', [UserController::class, 'updateProfile']); 

        // --- Gestión Unificada de Slider (Banners) ---
        Route::post('/admin/slider', [SliderController::class, 'store']);
        Route::post('/admin/slider/{id}', [SliderController::class, 'update']);
        Route::delete('/admin/slider/{id}', [SliderController::class, 'destroy']);

        // --- Configuraciones del Sitio (Footer, etc.) ---
        Route::match(['post', 'put'], '/website/settings', [WebsiteSettingsController::class, 'update']);

        // --- Gestión de Sobre Nosotros ---
        Route::get('/admin/nosotros', [NosotrosController::class, 'getAdminData']);
        Route::post('/admin/nosotros/encabezado', [NosotrosController::class, 'saveEncabezado']);
        Route::post('/admin/nosotros/registros', [NosotrosController::class, 'storeRegistro']);
        Route::match(['post', 'put'], '/admin/nosotros/registros/{id}', [NosotrosController::class, 'updateRegistro']);
        Route::delete('/admin/nosotros/registros/{id}', [NosotrosController::class, 'destroyRegistro']);

        // --- Gestión de Servicios ---
        Route::get('/admin/servicios', [ServiciosController::class, 'getAdminData']);
        Route::post('/admin/servicios/encabezado', [ServiciosController::class, 'saveEncabezado']);
        Route::post('/admin/servicios/categoria', [ServiciosController::class, 'storeCategoria']);
        Route::put('/admin/servicios/categoria/{id}', [ServiciosController::class, 'updateCategoria']);
        Route::delete('/admin/servicios/categoria/{id}', [ServiciosController::class, 'destroyCategoria']);
        Route::post('/admin/servicios/plan', [ServiciosController::class, 'storePlan']);
        Route::put('/admin/servicios/plan/{id}', [ServiciosController::class, 'updatePlan']);
        Route::delete('/admin/servicios/plan/{id}', [ServiciosController::class, 'destroyPlan']);

        // --- Gestión de Usuarios (Super Admin) ---
        Route::prefix('admin/usuarios')->group(function () {
            Route::get('/',          [UserController::class, 'index']);
            Route::post('/',         [UserController::class, 'store']);
            Route::put('/{id}',      [UserController::class, 'update']);
            Route::delete('/{id}',   [UserController::class, 'destroy']);
            Route::put('/{id}/permisos', [UserController::class, 'togglePermiso']);
        });

        // --- Gestión de Planes de Cobro ---
        Route::prefix('admin/planes')->group(function () {
            Route::get('/',        [PlanController::class, 'index']);
            Route::post('/',       [PlanController::class, 'store']);
            Route::put('/{id}',    [PlanController::class, 'update']);
            Route::delete('/{id}', [PlanController::class, 'destroy']);
        });

        // --- Gestión de Clientes ---
        Route::prefix('admin/clientes')->group(function () {
            Route::get('/',        [ClienteController::class, 'index']);
            Route::post('/',       [ClienteController::class, 'store']);
            Route::put('/{id}',    [ClienteController::class, 'update']);
            Route::delete('/{id}', [ClienteController::class, 'destroy']);
            Route::get('/{id}/pagos', [ClienteController::class, 'getPagos']);
        });

        // --- Gestión de Contratos ---
        Route::prefix('admin/contratos')->group(function () {
            Route::get('/',        [ContratoController::class, 'index']);
            Route::post('/',       [ContratoController::class, 'store']);
            Route::put('/{id}',    [ContratoController::class, 'update']);
            Route::post('/preview',[ContratoController::class, 'preview']);
            Route::post('/facturar-mes',[ContratoController::class, 'facturarMensualidades']);
        });

        // --- Gestión de Campañas de Descuento ---
        Route::prefix('admin/campanas-descuento')->group(function () {
            Route::get('/',        [CampanaDescuentoController::class, 'index']);
            Route::post('/',       [CampanaDescuentoController::class, 'store']);
            Route::put('/{id}',    [CampanaDescuentoController::class, 'update']);
            Route::delete('/{id}', [CampanaDescuentoController::class, 'destroy']);
        });

        // --- Auditoría de Cargos ---
        Route::prefix('admin/cargos')->group(function () {
            Route::get('/',        [CargoController::class, 'index']);
        });

        // --- Cobro Móvil y Pagos ---
        Route::prefix('admin/pagos')->group(function () {
            Route::get('/',              [PagoController::class, 'index']);
            Route::get('/buscar-cliente',[PagoController::class, 'buscarCliente']);
            Route::post('/',             [PagoController::class, 'registrarPago']);
            Route::get('/{id}',          [PagoController::class, 'show']);
            Route::get('/{id}/recibo',   [PagoController::class, 'descargarRecibo']);
        });

        // --- Reportes Financieros ---
        Route::get('/admin/reportes/cuadre-caja', [ReporteController::class, 'cuadreCaja']);
    });

});
