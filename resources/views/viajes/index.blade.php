@extends('layouts.app')

@section('title', 'Viajes')

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-road text-primary"></i> Registro de Viajes</h1>
        <p>Ingresos por plataforma — paginación server-side optimizada</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="btn-group">
            <a href="{{ route('export.viajes', ['format' => 'csv', 'anio' => date('Y')]) }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-file-csv"></i> Excel</a>
            <a href="{{ route('export.viajes', ['format' => 'pdf', 'anio' => date('Y')]) }}" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-file-pdf"></i> PDF</a>
        </div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoViaje">
            <i class="fa-solid fa-plus"></i> Nuevo Viaje
        </button>
    </div>
</div>

<div class="cl-card">
    <div class="table-responsive">
        <table id="tblViajes" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Día</th>
                    <th>Vehículo</th>
                    <th>InDrive</th>
                    <th>Otros</th>
                    <th>Propina</th>
                    <th>Alquiler</th>
                    <th>Ingresos</th>
                    <th>Neto</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalNuevoViaje" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Viaje</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoViaje">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Vehículo</label>
                        <select name="vehicle_id" id="selectVehicle" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label text-income">InDrive ($)</label>
                            <input type="number" step="0.01" min="0" name="indrive" class="form-control" value="0" required>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label text-income">Otros Viajes ($)</label>
                            <input type="number" step="0.01" min="0" name="otros_viajes" class="form-control" value="0" required>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label text-income">Propina ($)</label>
                            <input type="number" step="0.01" min="0" name="propina" class="form-control" value="0" required>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label text-expense">Alquiler ($)</label>
                            <input type="number" step="0.01" min="0" name="alquiler" class="form-control" value="0" readonly required>
                            <small id="rentalSuggestion" class="text-muted d-block mt-1"></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/common/select2-paginated.js') }}"></script>
<script src="{{ asset('js/viajes/index.js') }}"></script>
@endpush
