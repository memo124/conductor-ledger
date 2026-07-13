@extends('layouts.app')

@section('title', ui('pages.conversor.title'))

@section('content')
<div class="cl-page-header">
    <h1><i class="fa-solid fa-right-left text-primary"></i> {{ ui('pages.conversor.heading') }}</h1>
    <p>{{ ui('pages.conversor.subtitle') }}</p>
    @if($lastSyncAt)
        <small class="text-muted">{{ ui('pages.conversor.last_sync', ['date' => $lastSyncAt->format('Y-m-d H:i') . ($lastSyncDate ? ' ('.$lastSyncDate->format('Y-m-d').')' : '')]) }}</small>
    @else
        <small class="text-muted">{{ ui('pages.conversor.no_sync') }}</small>
    @endif
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="cl-card">
            <div class="cl-card-title">{{ ui('pages.conversor.calculator') }}</div>
            <form id="formConvertidor" class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="inputAmount">{{ ui('pages.conversor.amount') }}</label>
                    <input type="number" step="any" min="0" id="inputAmount" class="form-control" value="1" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="selectFrom">{{ ui('pages.conversor.from_currency') }}</label>
                    <select id="selectFrom" class="form-select" required>
                        <optgroup label="{{ ui('profile.currency_fiat') }}">
                            @foreach($fiatCurrencies as $currency)
                                <option value="{{ $currency->code }}" @selected($currency->code === 'USD')>{{ $currency->code }} — {{ $currency->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="{{ ui('profile.currency_crypto') }}">
                            @foreach($cryptoCurrencies as $currency)
                                <option value="{{ $currency->code }}">{{ $currency->code }} — {{ $currency->name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="col-12 text-center">
                    <button type="button" id="btnSwapCurrencies" class="btn btn-outline-secondary btn-sm" title="{{ ui('actions.swap') }}">
                        <i class="fa-solid fa-arrows-up-down"></i> {{ ui('actions.swap') }}
                    </button>
                </div>
                <div class="col-12">
                    <label class="form-label" for="selectTo">{{ ui('pages.conversor.to_currency') }}</label>
                    <select id="selectTo" class="form-select" required>
                        <optgroup label="{{ ui('profile.currency_fiat') }}">
                            @foreach($fiatCurrencies as $currency)
                                <option value="{{ $currency->code }}" @selected($currency->code === 'EUR')>{{ $currency->code }} — {{ $currency->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="{{ ui('profile.currency_crypto') }}">
                            @foreach($cryptoCurrencies as $currency)
                                <option value="{{ $currency->code }}">{{ $currency->code }} — {{ $currency->name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-calculator"></i> {{ ui('pages.conversor.convert') }}
                    </button>
                </div>
            </form>
            <div id="convertResult" class="mt-3 text-center" style="display:none;">
                <small class="text-muted d-block">{{ ui('pages.conversor.result') }}</small>
                <div class="fs-4 fw-bold text-primary" id="resultValue">—</div>
                <small class="text-muted" id="resultRate">—</small>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="cl-card">
            <div class="cl-card-title">{{ ui('pages.conversor.rates_explorer') }}</div>
            <ul class="nav nav-tabs mb-3" id="ratesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-fiat-fiat" data-bs-toggle="tab" data-bs-target="#panel-fiat-fiat" type="button" data-category="fiat_fiat">{{ ui('pages.conversor.tab_fiat_fiat') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-crypto-crypto" data-bs-toggle="tab" data-bs-target="#panel-crypto-crypto" type="button" data-category="crypto_crypto">{{ ui('pages.conversor.tab_crypto_crypto') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-cross" data-bs-toggle="tab" data-bs-target="#panel-cross" type="button" data-category="cross">{{ ui('pages.conversor.tab_cross') }}</button>
                </li>
            </ul>
            <div class="tab-content">
                @foreach(['fiat-fiat' => 'fiat_fiat', 'crypto-crypto' => 'crypto_crypto', 'cross' => 'cross'] as $panelId => $category)
                <div class="tab-pane fade @if($category === 'fiat_fiat') show active @endif" id="panel-{{ $panelId }}" role="tabpanel">
                    <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-sm table-striped w-100" data-rates-table="{{ $category }}">
                            <thead>
                                <tr>
                                    <th>{{ ui('pages.conversor.col_from') }}</th>
                                    <th>{{ ui('pages.conversor.col_to') }}</th>
                                    <th class="text-end">{{ ui('pages.conversor.col_rate') }}</th>
                                    <th class="text-end">{{ ui('pages.conversor.col_inverse') }}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/conversor/index.js') }}"></script>
@endpush
