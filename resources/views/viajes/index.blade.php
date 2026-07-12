@extends('layouts.app')

@section('title', 'Viajes')

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-road text-primary"></i> Registro de Viajes</h1>
        <p>Ingresos por plataforma, resumen diario/mensual o viaje individual</p>
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

<div class="cl-card mb-3">
    <form id="formFiltrosViajes" class="row g-2 align-items-end">
        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label">Desde</label>
            <input type="date" name="fecha_desde" id="filterFechaDesde" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label">Hasta</label>
            <input type="date" name="fecha_hasta" id="filterFechaHasta" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label">Plataforma</label>
            <select name="platform_id" id="filterPlatform" class="form-select form-select-sm">
                <option value="">Todas</option>
                @foreach($platforms as $platform)
                    <option value="{{ $platform->id }}">{{ $platform->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label">Tipo de viaje</label>
            <select name="trip_type_id" id="filterTripType" class="form-select form-select-sm">
                <option value="">Todos</option>
                @foreach($tripTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label">Modo</label>
            <select name="registration_mode" id="filterRegistrationMode" class="form-select form-select-sm">
                <option value="">Todos</option>
                <option value="per_trip">Viaje</option>
                <option value="daily">Día</option>
                <option value="monthly">Mes</option>
            </select>
        </div>
        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label">Vehículo</label>
            <select name="vehicle_id" id="filterVehicle" class="form-select form-select-sm"></select>
        </div>
        @if($isAdmin)
        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label">Conductor</label>
            <select name="target_user_id" id="filterConductor" class="form-select form-select-sm">
                <option value="">Mis viajes (admin)</option>
                @foreach($conductors as $conductor)
                    <option value="{{ $conductor->id }}">{{ $conductor->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="col-12 col-lg-auto">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </div>
    </form>
</div>

<div id="viajesTotals" class="cl-card mb-3" style="display:none;">
    <div class="row g-2 text-center">
        <div class="col-6 col-md-3">
            <small class="text-muted d-block">Ingresos</small>
            <strong class="text-income" id="totalIngresos">$0.00</strong>
        </div>
        <div class="col-6 col-md-3">
            <small class="text-muted d-block">Comisiones</small>
            <strong class="text-expense" id="totalComision">$0.00</strong>
        </div>
        <div class="col-6 col-md-3">
            <small class="text-muted d-block">Alquiler</small>
            <strong class="text-expense" id="totalAlquiler">$0.00</strong>
        </div>
        <div class="col-6 col-md-3">
            <small class="text-muted d-block">Neto</small>
            <strong class="text-primary" id="totalNeto">$0.00</strong>
        </div>
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
                    <th>Tipo</th>
                    <th>Plataforma</th>
                    <th>Modo</th>
                    <th>Bruto</th>
                    <th>Comisión</th>
                    <th>Cobrado</th>
                    <th>Propina</th>
                    <th>Alquiler</th>
                    <th>Ingresos</th>
                    <th>Neto</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalNuevoViaje" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down modal-lg">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Viaje</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoViaje">
                <input type="hidden" name="registration_mode" id="inputRegistrationMode" value="daily">
                <input type="hidden" name="edit_uuid" id="editUuid" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Vehículo</label>
                        <select name="vehicle_id" id="selectVehicle" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de viaje</label>
                        <select name="trip_type_id" id="selectTripType" class="form-select" required>
                            @foreach($tripTypes as $type)
                                <option value="{{ $type->id }}" data-code="{{ $type->code }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3" id="fieldRegistrationMode">
                        <label class="form-label">Modo de registro</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="registration_mode_radio" id="modePerTrip" value="per_trip">
                                <label class="form-check-label" for="modePerTrip">Viaje individual</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="registration_mode_radio" id="modeDaily" value="daily" checked>
                                <label class="form-check-label" for="modeDaily">Resumen del día</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="registration_mode_radio" id="modeMonthly" value="monthly">
                                <label class="form-check-label" for="modeMonthly">Resumen del mes</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="fieldPlatform" style="display:none;">
                        <label class="form-label">Plataforma</label>
                        <select name="platform_id" id="selectPlatform" class="form-select">
                            <option value="">Seleccione...</option>
                            @foreach($platforms as $platform)
                                <option value="{{ $platform->id }}">{{ $platform->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3" id="fieldFecha">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="row" id="fieldPeriod" style="display:none;">
                        <div class="col-6 mb-3">
                            <label class="form-label">Año</label>
                            <input type="number" name="period_year" class="form-control" min="2000" max="{{ date('Y') }}" value="{{ date('Y') }}">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Mes</label>
                            <select name="period_month" class="form-select">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" @selected($m == (int) date('n'))>{{ $m }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3" id="fieldMontoBruto" style="display:none;">
                            <label class="form-label text-income">Monto bruto ($)</label>
                            <input type="number" step="0.01" min="0" name="monto_bruto" class="form-control" value="0">
                        </div>
                        <div class="col-12 col-md-6 mb-3" id="fieldComisionApp" style="display:none;">
                            <label class="form-label text-expense">Comisión app ($)</label>
                            <input type="number" step="0.01" min="0" name="comision_app" class="form-control" value="0">
                        </div>
                        <div class="col-12 col-md-6 mb-3" id="fieldMontoCobrado" style="display:none;">
                            <label class="form-label text-income">Monto cobrado ($)</label>
                            <input type="number" step="0.01" min="0" name="monto_cobrado" class="form-control" value="0">
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label text-income">Propina ($)</label>
                            <input type="number" step="0.01" min="0" name="propina" class="form-control" value="0">
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">% cuota vehículo</label>
                            <input type="number" step="0.01" min="0" max="100" name="porcentaje_cuota" class="form-control" value="0">
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label text-expense">Alquiler / cuota ($)</label>
                            <input type="number" step="0.01" min="0" name="alquiler" class="form-control" value="0" readonly>
                            <small id="rentalSuggestion" class="text-muted d-block mt-1"></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnSubmitViaje">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.CL_TRIP_TYPES = @json($tripTypesJson);
</script>
<script src="{{ asset('js/common/select2-paginated.js') }}"></script>
<script src="{{ asset('js/viajes/index.js') }}"></script>
@endpush
