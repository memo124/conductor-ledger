@extends('layouts.app')

@section('title', 'Restablecer Contraseña')

@section('content')
<div class="cl-login-card">
    <div class="cl-login-logo">
        <i class="fa-solid fa-key"></i>
        <h2>Nueva contraseña</h2>
        <p>Define una contraseña segura para tu cuenta</p>
    </div>

    <form id="formResetPassword">
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="mb-3">
            <label class="form-label">Correo electrónico</label>
            <input type="email" name="email" class="form-control" value="{{ $email }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nueva contraseña</label>
            <input type="password" name="password" class="form-control" minlength="8" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirmar contraseña</label>
            <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">
            <i class="fa-solid fa-check"></i> Restablecer contraseña
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/auth/reset-password.js') }}"></script>
@endpush
