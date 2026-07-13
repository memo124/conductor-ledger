@extends('layouts.app')

@section('title', ui('pages.graficos.title'))

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-chart-pie text-primary"></i> {{ ui('pages.graficos.heading') }}</h1>
        <p>{{ ui('pages.graficos.subtitle') }}</p>
    </div>
    <select id="selectAnio" class="form-select w-100 w-md-auto">
        @for($y = date('Y'); $y >= date('Y') - 4; $y--)
        <option value="{{ $y }}" @selected($y == date('Y'))>{{ $y }}</option>
        @endfor
    </select>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="cl-card"><div class="cl-card-title">{{ ui('dashboard.income') }}</div><div class="cl-stat-value text-success" id="totalIngresos">$0.00</div></div></div>
    <div class="col-6 col-md-3"><div class="cl-card"><div class="cl-card-title">{{ ui('dashboard.rent') }}</div><div class="cl-stat-value text-danger" id="totalAlquiler">$0.00</div></div></div>
    <div class="col-6 col-md-3"><div class="cl-card"><div class="cl-card-title">{{ ui('dashboard.expenses') }}</div><div class="cl-stat-value text-danger" id="totalGastos">$0.00</div></div></div>
    <div class="col-6 col-md-3"><div class="cl-card"><div class="cl-card-title">{{ ui('dashboard.net_profit') }}</div><div class="cl-stat-value text-primary" id="totalNeto">$0.00</div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="cl-card">
            <div class="cl-card-title">{{ ui('pages.graficos.chart_monthly_evolution') }}</div>
            <canvas id="chartMensual" height="120"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="cl-card">
            <div class="cl-card-title">{{ ui('pages.graficos.chart_income_by_platform') }}</div>
            <canvas id="chartPlataformas" height="180"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/graficos/index.js') }}"></script>
@endpush
