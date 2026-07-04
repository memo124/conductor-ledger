<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\CategoriasGastoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GastosController;
use App\Http\Controllers\GraficosController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\TiposPropiedadController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\VehiculosController;
use App\Http\Controllers\ViajesController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::match(['get', 'post'], '/Authentication/Login', [AuthenticationController::class, 'login'])->name('login');
Route::match(['get', 'post'], '/Authentication/ForgotPassword', [AuthenticationController::class, 'forgotPassword'])->name('password.request');
Route::match(['get', 'post'], '/Authentication/ResetPassword/{token?}', [AuthenticationController::class, 'resetPassword'])->name('password.reset');
Route::post('/Authentication/Logout', [AuthenticationController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/Authentication/GetUserById', [AuthenticationController::class, 'getUserById']);
    Route::post('/Authentication/UpdateThemePreference', [AuthenticationController::class, 'updateThemePreference']);
    Route::get('/Authentication/ActualizarSesion', [AuthenticationController::class, 'actualizarSesion']);

    Route::get('/Perfil', [PerfilController::class, 'index'])->name('perfil.index');
    Route::put('/Perfil/Update', [PerfilController::class, 'update'])->name('perfil.update');
    Route::put('/Perfil/UpdatePassword', [PerfilController::class, 'updatePassword'])->name('perfil.update-password');

    Route::get('/Dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/Dashboard/GetResumen', [DashboardController::class, 'getResumen']);

    Route::get('/Graficos', [GraficosController::class, 'index'])->name('graficos.index');
    Route::get('/Graficos/GetMetrics', [GraficosController::class, 'getMetrics']);

    Route::get('/Export/Viajes', [ExportController::class, 'viajes'])->name('export.viajes');
    Route::get('/Export/Gastos', [ExportController::class, 'gastos'])->name('export.gastos');
    Route::get('/Export/Resumen', [ExportController::class, 'resumen'])->name('export.resumen');

    Route::get('/Viajes', [ViajesController::class, 'index'])->name('viajes.index');
    Route::get('/Viajes/GetDatatableServerSide', [ViajesController::class, 'getDatatableServerSide']);
    Route::get('/Viajes/GetComparativaMensual', [ViajesController::class, 'getComparativaMensual']);
    Route::get('/Viajes/GetRentalSuggestion', [ViajesController::class, 'getRentalSuggestion']);
    Route::get('/Viajes/Select2Paginated', [ViajesController::class, 'select2Paginated']);
    Route::post('/Viajes/Store', [ViajesController::class, 'store']);

    Route::get('/Gastos', [GastosController::class, 'index'])->name('gastos.index');
    Route::get('/Gastos/GetDatatableServerSide', [GastosController::class, 'getDatatableServerSide']);
    Route::get('/Gastos/Select2Categories', [GastosController::class, 'select2Categories']);
    Route::get('/Gastos/Select2Vehicles', [GastosController::class, 'select2Vehicles']);
    Route::post('/Gastos/Store', [GastosController::class, 'store']);

    Route::get('/Vehiculos', [VehiculosController::class, 'index'])->name('vehiculos.index');
    Route::get('/Vehiculos/GetDatatableServerSide', [VehiculosController::class, 'getDatatableServerSide']);
    Route::get('/Vehiculos/Select2Paginated', [VehiculosController::class, 'select2Paginated']);
    Route::post('/Vehiculos/Store', [VehiculosController::class, 'store']);
    Route::put('/Vehiculos/Update/{id}', [VehiculosController::class, 'update']);

    Route::get('/Maestros/TiposPropiedad', [TiposPropiedadController::class, 'index'])->name('tipos-propiedad.index');
    Route::get('/Maestros/TiposPropiedad/GetDatatableServerSide', [TiposPropiedadController::class, 'getDatatableServerSide']);
    Route::post('/Maestros/TiposPropiedad/Store', [TiposPropiedadController::class, 'store']);
    Route::put('/Maestros/TiposPropiedad/Update/{id}', [TiposPropiedadController::class, 'update']);

    Route::get('/Maestros/CategoriasGasto', [CategoriasGastoController::class, 'index'])->name('categorias-gasto.index');
    Route::get('/Maestros/CategoriasGasto/GetDatatableServerSide', [CategoriasGastoController::class, 'getDatatableServerSide']);
    Route::post('/Maestros/CategoriasGasto/Store', [CategoriasGastoController::class, 'store']);
    Route::put('/Maestros/CategoriasGasto/Update/{id}', [CategoriasGastoController::class, 'update']);

    Route::middleware('admin')->group(function () {
        Route::get('/Usuarios', [UsuariosController::class, 'index'])->name('usuarios.index');
        Route::get('/Usuarios/GetDatatableServerSide', [UsuariosController::class, 'getDatatableServerSide']);
        Route::post('/Usuarios/Store', [UsuariosController::class, 'store']);
        Route::put('/Usuarios/Update/{id}', [UsuariosController::class, 'update']);
        Route::delete('/Usuarios/Delete/{id}', [UsuariosController::class, 'destroy']);
    });
});
