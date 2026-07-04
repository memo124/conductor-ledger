@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="cl-page-header">
    <h1><i class="fa-solid fa-gauge-high text-primary"></i> Panel Financiero</h1>
    <p>Resumen del mes actual — estilo trading desk</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="cl-card">
            <div class="cl-card-title"><i class="fa-solid fa-arrow-trend-up text-income"></i> Ingresos</div>
            <div class="cl-stat-value text-success" id="statIngresos">$0.00</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="cl-card">
            <div class="cl-card-title"><i class="fa-solid fa-car text-expense"></i> Alquiler</div>
            <div class="cl-stat-value text-danger" id="statAlquiler">$0.00</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="cl-card">
            <div class="cl-card-title"><i class="fa-solid fa-gas-pump text-expense"></i> Gastos</div>
            <div class="cl-stat-value text-danger" id="statGastos">$0.00</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="cl-card">
            <div class="cl-card-title"><i class="fa-solid fa-sack-dollar text-primary"></i> Ganancia Neta</div>
            <div class="cl-stat-value text-primary" id="statNeto">$0.00</div>
        </div>
    </div>
</div>

<div class="cl-card">
    <div class="cl-card-title">Comparativa mensual de ingresos ({{ date('Y') }})</div>
    <div class="table-responsive">
        <table class="table table-striped table-sm mb-0" id="tblComparativa">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>InDrive</th>
                    <th>Otros</th>
                    <th>Propinas</th>
                    <th>Alquiler</th>
                    <th>Total Ingresos</th>
                </tr>
            </thead>
            <tbody id="tbodyComparativa"></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/dashboard/index.js') }}"></script>
@endpush
