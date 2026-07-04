@extends('layouts.app')

@section('title', 'Gastos')

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-wallet text-expense"></i> Registro de Gastos</h1>
        <p>Control de egresos operativos del vehículo</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="btn-group">
            <a href="{{ route('export.gastos', ['format' => 'csv', 'anio' => date('Y')]) }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-file-csv"></i> Excel</a>
            <a href="{{ route('export.gastos', ['format' => 'pdf', 'anio' => date('Y')]) }}" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-file-pdf"></i> PDF</a>
        </div>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalNuevoGasto">
            <i class="fa-solid fa-plus"></i> Nuevo Gasto
        </button>
    </div>
</div>

<div class="cl-card">
    <div class="table-responsive">
        <table id="tblGastos" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Categoría</th>
                    <th>Vehículo</th>
                    <th>Monto</th>
                    <th>Descripción</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalNuevoGasto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Gasto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoGasto">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select name="category_id" id="selectCategory" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vehículo (opcional)</label>
                        <select name="vehicle_id" id="selectVehicleGasto" class="form-select"></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-expense">Monto ($)</label>
                        <input type="number" step="0.01" min="0.01" name="monto" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalle adicional"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/common/select2-paginated.js') }}"></script>
<script src="{{ asset('js/common/datatables-config.js') }}"></script>
<script src="{{ asset('js/gastos/index.js') }}"></script>
@endpush
