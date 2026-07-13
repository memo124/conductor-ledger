@extends('layouts.app')

@section('title', ui('dashboard.title'))

@section('content')
<div class="cl-page-header">
    <h1><i class="fa-solid fa-gauge-high text-primary"></i> {{ ui('dashboard.title') }}</h1>
    <p>{{ ui('dashboard.subtitle') }}</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="cl-card">
            <div class="cl-card-title"><i class="fa-solid fa-arrow-trend-up text-income"></i> {{ ui('dashboard.income') }}</div>
            <div class="cl-stat-value text-success" id="statIngresos">—</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cl-card">
            <div class="cl-card-title"><i class="fa-solid fa-car text-expense"></i> {{ ui('dashboard.rent') }}</div>
            <div class="cl-stat-value text-danger" id="statAlquiler">—</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cl-card">
            <div class="cl-card-title"><i class="fa-solid fa-gas-pump text-expense"></i> {{ ui('dashboard.expenses') }}</div>
            <div class="cl-stat-value text-danger" id="statGastos">—</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="cl-card">
            <div class="cl-card-title"><i class="fa-solid fa-sack-dollar text-primary"></i> {{ ui('dashboard.net_profit') }}</div>
            <div class="cl-stat-value text-primary" id="statNeto">—</div>
        </div>
    </div>
</div>

<div class="cl-card">
    <div class="cl-card-title">{{ ui('dashboard.monthly_comparison', ['year' => date('Y')]) }}</div>
    <div class="table-responsive">
        <table class="table table-striped table-sm w-100 mb-0" id="tblComparativa">
            <thead>
                <tr>
                    <th>{{ ui('dashboard.month') }}</th>
                    <th>{{ ui('dashboard.income') }}</th>
                    <th>{{ ui('dashboard.commission') }}</th>
                    <th>{{ ui('dashboard.tips') }}</th>
                    <th>{{ ui('dashboard.rent') }}</th>
                    <th>{{ ui('dashboard.total') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/dashboard/index.js') }}"></script>
@endpush
