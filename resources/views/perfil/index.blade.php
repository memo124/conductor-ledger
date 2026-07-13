@extends('layouts.app')

@section('title', ui('profile.title'))

@section('content')
<div class="cl-page-header">
    <h1><i class="fa-solid fa-user text-primary"></i> {{ ui('profile.title') }}</h1>
    <p>{{ ui('profile.subtitle') }}</p>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="cl-card">
            <div class="cl-card-title">{{ ui('profile.personal_data') }}</div>
            <form id="formPerfil">
                <div class="mb-3">
                    <label class="form-label">{{ ui('profile.name') }}</label>
                    <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ ui('profile.email') }}</label>
                    <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ ui('profile.dui') }}</label>
                    <input type="text" name="dui" class="form-control" maxlength="10" value="{{ $user->dui }}" placeholder="{{ ui('profile.dui_optional') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ ui('profile.language') }}</label>
                    <select name="locale_preference" class="form-select">
                        @foreach($locales as $code => $meta)
                            <option value="{{ $code }}" @selected(($user->locale_preference ?? 'es') === $code)>{{ $meta['label'] ?? strtoupper($code) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ ui('profile.display_currency') }}</label>
                    <select name="currency_preference" class="form-select">
                        <optgroup label="{{ ui('profile.currency_fiat') }}">
                            @foreach($fiatCurrencies as $currency)
                                <option value="{{ $currency->code }}" @selected(($user->currency_preference ?? 'USD') === $currency->code)>
                                    {{ $currency->code }} — {{ $currency->displayName($user->locale_preference ?? 'es') }}
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="{{ ui('profile.currency_crypto') }}">
                            @foreach($cryptoCurrencies as $currency)
                                <option value="{{ $currency->code }}" @selected(($user->currency_preference ?? 'USD') === $currency->code)>
                                    {{ $currency->code }} — {{ $currency->displayName($user->locale_preference ?? 'es') }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                    <small class="text-muted">{{ ui('profile.currency_hint') }}</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ ui('profile.theme') }}</label>
                    <select name="theme_preference" class="form-select">
                        <option value="light" @selected($user->theme_preference === 'light')>{{ ui('theme.light') }}</option>
                        <option value="dark" @selected($user->theme_preference === 'dark')>{{ ui('theme.dark') }}</option>
                        <option value="auto" @selected(($user->theme_preference ?? 'auto') === 'auto')>{{ ui('profile.theme_auto_short') }}</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">{{ ui('profile.save') }}</button>
            </form>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="cl-card">
            <div class="cl-card-title">{{ ui('profile.change_password') }}</div>
            <form id="formPassword">
                <div class="mb-3">
                    <label class="form-label">{{ ui('profile.current_password') }}</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ ui('profile.new_password') }}</label>
                    <input type="password" name="password" class="form-control" minlength="8" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ ui('profile.confirm_password') }}</label>
                    <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-warning">{{ ui('profile.update_password') }}</button>
            </form>
        </div>
        <div class="cl-card">
            <div class="cl-card-title">{{ ui('profile.export_data', ['year' => date('Y')]) }}</div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('export.resumen', ['format' => 'csv']) }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-file-csv"></i> {{ ui('profile.export_excel') }}</a>
                <a href="{{ route('export.resumen', ['format' => 'pdf']) }}" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-file-pdf"></i> {{ ui('profile.export_pdf') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/perfil/index.js') }}"></script>
@endpush
