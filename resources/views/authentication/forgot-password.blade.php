@extends('layouts.app')

@section('title', 'Recuperar Contraseña')

@section('content')
<div class="cl-login-card">
    <div class="cl-login-logo">
        <i class="fa-solid fa-unlock-keyhole"></i>
        <h2>Recuperar contraseña</h2>
        <p>Te enviaremos un enlace a tu correo</p>
    </div>

    <form id="formForgotPassword">
        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
        </div>
        <button type="submit" class="btn btn-primary w-100" id="btnSubmit">
            <i class="fa-solid fa-paper-plane"></i> Enviar enlace
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}">Volver al inicio de sesión</a>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/auth/forgot-password.js') }}"></script>
@endpush
