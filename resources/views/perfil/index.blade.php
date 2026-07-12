@extends('layouts.app')

@section('title', 'Mi Perfil')

@section('content')
<div class="cl-page-header">
    <h1><i class="fa-solid fa-user text-primary"></i> Mi Perfil</h1>
    <p>Administra tu información personal y seguridad</p>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="cl-card">
            <div class="cl-card-title">Datos personales</div>
            <form id="formPerfil">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Correo</label>
                    <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">DUI</label>
                    <input type="text" name="dui" class="form-control" maxlength="10" value="{{ $user->dui }}" placeholder="Opcional">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tema</label>
                    <select name="theme_preference" class="form-select">
                        <option value="light" @selected($user->theme_preference === 'light')>Claro</option>
                        <option value="dark" @selected($user->theme_preference === 'dark')>Oscuro</option>
                        <option value="auto" @selected(($user->theme_preference ?? 'auto') === 'auto')>Automático</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="cl-card">
            <div class="cl-card-title">Cambiar contraseña</div>
            <form id="formPassword">
                <div class="mb-3">
                    <label class="form-label">Contraseña actual</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nueva contraseña</label>
                    <input type="password" name="password" class="form-control" minlength="8" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-warning">Actualizar contraseña</button>
            </form>
        </div>
        <div class="cl-card">
            <div class="cl-card-title">Exportar datos {{ date('Y') }}</div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('export.resumen', ['format' => 'csv']) }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-file-csv"></i> Resumen Excel</a>
                <a href="{{ route('export.resumen', ['format' => 'pdf']) }}" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-file-pdf"></i> Resumen PDF</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/perfil/index.js') }}"></script>
@endpush
