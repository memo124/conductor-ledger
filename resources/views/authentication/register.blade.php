@extends('layouts.app')

@section('title', 'Registro')

@section('content')
<div class="cl-login-card">
    <div class="cl-login-logo">
        <i class="fa-solid fa-user-plus"></i>
        <h2>Crear cuenta</h2>
        <p>Solicite acceso a ConductorLedger</p>
    </div>

    <form id="formRegister">
        <div class="mb-3">
            <label class="form-label">Nombre completo</label>
            <input type="text" name="name" class="form-control" required maxlength="255">
        </div>
        <div class="mb-3">
            <label class="form-label">Correo electrónico</label>
            <input type="email" name="email" class="form-control" required maxlength="255">
        </div>
        <div class="mb-3">
            <label class="form-label">DUI</label>
            <input type="text" name="dui" class="form-control" maxlength="10" placeholder="Opcional">
        </div>
        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" required minlength="8">
        </div>
        <div class="mb-3">
            <label class="form-label">Confirmar contraseña</label>
            <input type="password" name="password_confirmation" class="form-control" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary w-100">
            <i class="fa-solid fa-paper-plane"></i> Enviar solicitud
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}">¿Ya tienes cuenta? Inicia sesión</a>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/auth/register.js') }}"></script>
@endpush
