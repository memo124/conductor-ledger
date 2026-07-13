@extends('layouts.app')

@section('title', ui('pages.gastos.title'))

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-wallet text-expense"></i> {{ ui('pages.gastos.heading') }}</h1>
        <p>{{ ui('pages.gastos.subtitle') }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="btn-group">
            <a href="{{ route('export.gastos', ['format' => 'csv', 'anio' => date('Y')]) }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-file-csv"></i> {{ ui('common.export_excel') }}</a>
            <a href="{{ route('export.gastos', ['format' => 'pdf', 'anio' => date('Y')]) }}" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-file-pdf"></i> {{ ui('common.export_pdf') }}</a>
        </div>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalNuevoGasto">
            <i class="fa-solid fa-plus"></i> {{ ui('pages.gastos.new_expense') }}
        </button>
    </div>
</div>

<div class="cl-card mb-3">
    <form id="formFiltrosGastos" class="row g-2 align-items-end">
        <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label">{{ ui('common.from') }}</label>
            <input type="date" name="fecha_desde" id="filterFechaDesde" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label">{{ ui('common.to') }}</label>
            <input type="date" name="fecha_hasta" id="filterFechaHasta" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label">{{ ui('pages.gastos.filter_category') }}</label>
            <select name="category_id" id="filterCategory" class="form-select form-select-sm">
                <option value="">{{ ui('common.all_f') }}</option>
                @foreach(\App\Models\ExpenseCategory::orderBy('name')->get() as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label">{{ ui('common.vehicle') }}</label>
            <select name="vehicle_id" id="filterVehicle" class="form-select form-select-sm"></select>
        </div>
        <div class="col-12 col-lg-auto">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> {{ ui('common.filter') }}</button>
        </div>
    </form>
</div>

<div id="gastosTotals" class="cl-card mb-3" style="display:none;">
    <div class="text-center">
        <small class="text-muted d-block">{{ ui('pages.gastos.total_label') }}</small>
        <strong class="text-expense fs-5" id="totalMonto">$0.00</strong>
    </div>
</div>

<div class="cl-card">
    <div class="table-responsive">
        <table id="tblGastos" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ ui('common.date') }}</th>
                    <th>{{ ui('pages.gastos.col_category') }}</th>
                    <th>{{ ui('common.vehicle') }}</th>
                    <th>{{ ui('pages.gastos.col_amount') }}</th>
                    <th>{{ ui('pages.gastos.col_description') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalNuevoGasto" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ ui('pages.gastos.modal_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoGasto">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.gastos.field_category') }}</label>
                        <select name="category_id" id="selectCategory" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.gastos.field_vehicle_optional') }}</label>
                        <select name="vehicle_id" id="selectVehicleGasto" class="form-select"></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('common.date') }}</label>
                        <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-expense">{{ ui('pages.gastos.field_amount') }}</label>
                        <input type="number" step="0.01" min="0.01" name="monto" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.gastos.field_description') }}</label>
                        <textarea name="descripcion" class="form-control" rows="2" placeholder="{{ ui('pages.gastos.description_placeholder') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ ui('actions.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ ui('actions.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/common/select2-paginated.js') }}"></script>
<script src="{{ asset('js/gastos/index.js') }}"></script>
@endpush
