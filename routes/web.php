<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\BackupsController;
use App\Http\Controllers\CategoriasGastoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmergencyDecryptController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GastosController;
use App\Http\Controllers\GraficosController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\PlataformasController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\TiposPropiedadController;
use App\Http\Controllers\TiposViajeController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\VehiculosController;
use App\Http\Controllers\ViajesController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::match(['get', 'post'], '/Authentication/Login', [AuthenticationController::class, 'login'])->name('login');
Route::match(['get', 'post'], '/Authentication/ForgotPassword', [AuthenticationController::class, 'forgotPassword'])->name('password.request');
Route::match(['get', 'post'], '/Authentication/ResetPassword/{token?}', [AuthenticationController::class, 'resetPassword'])->name('password.reset');
Route::get('/Registro', [RegistrationController::class, 'show'])->name('register');
Route::post('/Registro/Store', [RegistrationController::class, 'store'])->name('register.store');
Route::post('/Authentication/Logout', [AuthenticationController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/Authentication/GetUserById', [AuthenticationController::class, 'getUserById']);
    Route::post('/Authentication/UpdateThemePreference', [AuthenticationController::class, 'updateThemePreference']);
    Route::get('/Authentication/ActualizarSesion', [AuthenticationController::class, 'actualizarSesion']);

    Route::middleware('permission:perfil')->group(function () {
        Route::get('/Perfil', [PerfilController::class, 'index'])->name('perfil.index');
        Route::put('/Perfil/Update', [PerfilController::class, 'update'])->name('perfil.update');
        Route::put('/Perfil/UpdatePassword', [PerfilController::class, 'updatePassword'])->name('perfil.update-password');
    });

    Route::middleware('permission:dashboard')->group(function () {
        Route::get('/Dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/Dashboard/GetResumen', [DashboardController::class, 'getResumen']);
    });

    Route::middleware('permission:graficos')->group(function () {
        Route::get('/Graficos', [GraficosController::class, 'index'])->name('graficos.index');
        Route::get('/Graficos/GetMetrics', [GraficosController::class, 'getMetrics']);
    });

    Route::middleware('permission:dashboard')->group(function () {
        Route::get('/Export/Viajes', [ExportController::class, 'viajes'])->name('export.viajes');
        Route::get('/Export/Gastos', [ExportController::class, 'gastos'])->name('export.gastos');
        Route::get('/Export/Resumen', [ExportController::class, 'resumen'])->name('export.resumen');
    });

    Route::middleware('permission:viajes')->group(function () {
        Route::get('/Viajes', [ViajesController::class, 'index'])->name('viajes.index');
        Route::get('/Viajes/GetDatatableServerSide', [ViajesController::class, 'getDatatableServerSide']);
        Route::get('/Viajes/GetComparativaMensual', [ViajesController::class, 'getComparativaMensual']);
        Route::get('/Viajes/GetRentalSuggestion', [ViajesController::class, 'getRentalSuggestion']);
        Route::get('/Viajes/Select2Paginated', [ViajesController::class, 'select2Paginated']);
        Route::get('/Viajes/Select2Platforms', [ViajesController::class, 'select2Platforms']);
        Route::get('/Viajes/Show/{uuid}', [ViajesController::class, 'show']);
        Route::post('/Viajes/Store', [ViajesController::class, 'store']);
        Route::put('/Viajes/Update/{uuid}', [ViajesController::class, 'update']);
    });

    Route::middleware('permission:gastos')->group(function () {
        Route::get('/Gastos', [GastosController::class, 'index'])->name('gastos.index');
        Route::get('/Gastos/GetDatatableServerSide', [GastosController::class, 'getDatatableServerSide']);
        Route::get('/Gastos/Select2Categories', [GastosController::class, 'select2Categories']);
        Route::get('/Gastos/Select2Vehicles', [GastosController::class, 'select2Vehicles']);
        Route::post('/Gastos/Store', [GastosController::class, 'store']);
    });

    Route::middleware('permission:vehiculos')->group(function () {
        Route::get('/Vehiculos', [VehiculosController::class, 'index'])->name('vehiculos.index');
        Route::get('/Vehiculos/GetDatatableServerSide', [VehiculosController::class, 'getDatatableServerSide']);
        Route::get('/Vehiculos/Select2Paginated', [VehiculosController::class, 'select2Paginated']);
        Route::post('/Vehiculos/Store', [VehiculosController::class, 'store']);
        Route::put('/Vehiculos/Update/{id}', [VehiculosController::class, 'update']);
    });

    Route::middleware('permission:tipos-propiedad')->group(function () {
        Route::get('/Maestros/TiposPropiedad', [TiposPropiedadController::class, 'index'])->name('tipos-propiedad.index');
        Route::get('/Maestros/TiposPropiedad/GetDatatableServerSide', [TiposPropiedadController::class, 'getDatatableServerSide']);
        Route::post('/Maestros/TiposPropiedad/Store', [TiposPropiedadController::class, 'store']);
        Route::put('/Maestros/TiposPropiedad/Update/{id}', [TiposPropiedadController::class, 'update']);
    });

    Route::middleware('permission:categorias-gasto')->group(function () {
        Route::get('/Maestros/CategoriasGasto', [CategoriasGastoController::class, 'index'])->name('categorias-gasto.index');
        Route::get('/Maestros/CategoriasGasto/GetDatatableServerSide', [CategoriasGastoController::class, 'getDatatableServerSide']);
        Route::post('/Maestros/CategoriasGasto/Store', [CategoriasGastoController::class, 'store']);
        Route::put('/Maestros/CategoriasGasto/Update/{id}', [CategoriasGastoController::class, 'update']);
    });

    Route::middleware('permission:plataformas')->group(function () {
        Route::get('/Maestros/Plataformas', [PlataformasController::class, 'index'])->name('plataformas.index');
        Route::get('/Maestros/Plataformas/GetDatatableServerSide', [PlataformasController::class, 'getDatatableServerSide']);
        Route::post('/Maestros/Plataformas/Store', [PlataformasController::class, 'store']);
        Route::put('/Maestros/Plataformas/Update/{id}', [PlataformasController::class, 'update']);
    });

    Route::middleware('permission:tipos-viaje')->group(function () {
        Route::get('/Maestros/TiposViaje', [TiposViajeController::class, 'index'])->name('tipos-viaje.index');
        Route::get('/Maestros/TiposViaje/GetDatatableServerSide', [TiposViajeController::class, 'getDatatableServerSide']);
        Route::post('/Maestros/TiposViaje/Store', [TiposViajeController::class, 'store']);
        Route::put('/Maestros/TiposViaje/Update/{id}', [TiposViajeController::class, 'update']);
    });

    Route::middleware('permission:usuarios')->group(function () {
        Route::get('/Usuarios', [UsuariosController::class, 'index'])->name('usuarios.index');
        Route::get('/Usuarios/GetDatatableServerSide', [UsuariosController::class, 'getDatatableServerSide']);
        Route::post('/Usuarios/Store', [UsuariosController::class, 'store']);
        Route::put('/Usuarios/Update/{id}', [UsuariosController::class, 'update']);
        Route::delete('/Usuarios/Delete/{id}', [UsuariosController::class, 'destroy']);
    });

    Route::middleware('permission:admin.permisos')->group(function () {
        Route::get('/Administracion/Permisos', [PermisosController::class, 'index'])->name('admin.permisos.index');
        Route::get('/Administracion/Permisos/GetMatrix', [PermisosController::class, 'getMatrix']);
        Route::put('/Administracion/Permisos/Update', [PermisosController::class, 'update']);
    });

    Route::middleware('permission:admin.backups')->group(function () {
        Route::get('/Administracion/Backups', [BackupsController::class, 'index'])->name('admin.backups.index');
        Route::post('/Administracion/Backups/Generar', [BackupsController::class, 'generate'])->name('admin.backups.generate');
        Route::post('/Administracion/Backups/EnlaceDescarga', [BackupsController::class, 'issueDownloadLink'])->name('admin.backups.issue-link');
        Route::get('/Administracion/Backups/Descargar/{token}', [BackupsController::class, 'download'])->name('admin.backups.download');
    });

    Route::middleware('permission:admin.emergency-decrypt')->group(function () {
        Route::get('/Administracion/DescifradoEmergencia', [EmergencyDecryptController::class, 'index'])->name('admin.emergency-decrypt.index');
        Route::post('/Administracion/DescifradoEmergencia/Ejecutar', [EmergencyDecryptController::class, 'decrypt'])->name('admin.emergency-decrypt.execute');
    });
});
