@extends('layouts.app')

@section('title', ui('login.title'))

@section('content')
<div class="cl-login-card">
    <div class="cl-login-logo">
        <i class="fa-solid fa-chart-line"></i>
        <h2>{{ ui('app.brand') }}</h2>
        <p>{{ ui('login.subtitle') }}</p>
    </div>

    <form id="formLogin">
        <div class="mb-3">
            <label for="email" class="form-label">{{ ui('login.email') }}</label>
            <input type="email" class="form-control" id="email" name="email" required autocomplete="email" placeholder="conductor@conductorledger.local">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">{{ ui('login.password') }}</label>
            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label" for="remember">{{ ui('login.remember') }}</label>
        </div>
        <button type="submit" class="btn btn-primary w-100" id="btnLogin">
            <i class="fa-solid fa-right-to-bracket"></i> {{ ui('login.submit') }}
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('password.request') }}">{{ ui('login.forgot') }}</a>
        <span class="mx-1">·</span>
        <a href="{{ route('register') }}">{{ ui('login.register') }}</a>
    </div>

    <div class="cl-theme-picker mt-3">
        <label for="selectThemePreference" class="form-label mb-1">{{ ui('sidebar.theme') }}</label>
        <select id="selectThemePreference" class="form-select form-select-sm">
            <option value="light">{{ ui('theme.light') }}</option>
            <option value="dark">{{ ui('theme.dark') }}</option>
            <option value="auto">{{ ui('theme.auto') }}</option>
        </select>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/auth/login.js') }}"></script>
@endpush
