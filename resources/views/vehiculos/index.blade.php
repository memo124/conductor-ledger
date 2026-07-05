@extends('layouts.app')

@section('title', 'Vehículos')

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-car text-primary"></i> Mis Vehículos</h1>
        <p>Gestión de flota personal</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoVehiculo">
        <i class="fa-solid fa-plus"></i> Nuevo Vehículo
    </button>
</div>

<div class="cl-card">
    <div class="table-responsive">
        <table id="tblVehiculos" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Placa</th>
                    <th>Tipo Propiedad</th>
                    <th>Cuota</th>
                    <th>Periodo</th>
                    <th>Estado</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalNuevoVehiculo" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Vehículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoVehiculo">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Placa</label>
                        <input type="text" name="plate_number" class="form-control" maxlength="15" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Propiedad</label>
                        <select name="ownership_type_id" id="selectOwnership" class="form-select" required></select>
                    </div>
                    <div id="rentalFields" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label">Periodo de alquiler</label>
                            <select name="rental_period" class="form-select">
                                <option value="daily">Diario</option>
                                <option value="weekly">Semanal</option>
                                <option value="monthly">Mensual</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cuota de alquiler ($)</label>
                            <input type="number" step="0.01" min="0" name="rental_fee_daily" class="form-control" value="0">
                            <small class="text-muted">Monto según el periodo seleccionado. En viajes se sugerirá la cuota diaria equivalente.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/common/select2-paginated.js') }}"></script>
<script src="{{ asset('js/vehiculos/index.js') }}"></script>
@endpush
