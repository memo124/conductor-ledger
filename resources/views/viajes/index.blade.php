@extends('layouts.app')

@section('title', ui('pages.viajes.title'))

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-road text-primary"></i> {{ ui('pages.viajes.heading') }}</h1>
        <p>{{ ui('pages.viajes.subtitle') }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <div class="btn-group">
            <a href="{{ route('export.viajes', ['format' => 'csv', 'anio' => date('Y')]) }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-file-csv"></i> {{ ui('common.export_excel') }}</a>
            <a href="{{ route('export.viajes', ['format' => 'pdf', 'anio' => date('Y')]) }}" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-file-pdf"></i> {{ ui('common.export_pdf') }}</a>
        </div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoViaje">
            <i class="fa-solid fa-plus"></i> {{ ui('pages.viajes.new_trip') }}
        </button>
    </div>
</div>

<div class="cl-card mb-3">
    <form id="formFiltrosViajes" class="row g-2 align-items-end">
        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label">{{ ui('common.from') }}</label>
            <input type="date" name="fecha_desde" id="filterFechaDesde" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label">{{ ui('common.to') }}</label>
            <input type="date" name="fecha_hasta" id="filterFechaHasta" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label">{{ ui('pages.viajes.filter_platform') }}</label>
            <select name="platform_id" id="filterPlatform" class="form-select form-select-sm">
                <option value="">{{ ui('common.all_f') }}</option>
                @foreach($platforms as $platform)
                    <option value="{{ $platform->id }}">{{ $platform->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label">{{ ui('pages.viajes.filter_trip_type') }}</label>
            <select name="trip_type_id" id="filterTripType" class="form-select form-select-sm">
                <option value="">{{ ui('common.all_m') }}</option>
                @foreach($tripTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label">{{ ui('pages.viajes.filter_mode') }}</label>
            <select name="registration_mode" id="filterRegistrationMode" class="form-select form-select-sm">
                <option value="">{{ ui('common.all_m') }}</option>
                <option value="per_trip">{{ ui('pages.viajes.mode_trip') }}</option>
                <option value="daily">{{ ui('pages.viajes.mode_day') }}</option>
                <option value="monthly">{{ ui('pages.viajes.mode_month') }}</option>
            </select>
        </div>
        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label">{{ ui('common.vehicle') }}</label>
            <select name="vehicle_id" id="filterVehicle" class="form-select form-select-sm"></select>
        </div>
        @if($isAdmin)
        <div class="col-12 col-md-6 col-lg-2">
            <label class="form-label">{{ ui('pages.viajes.filter_driver') }}</label>
            <select name="target_user_id" id="filterConductor" class="form-select form-select-sm">
                <option value="">{{ ui('pages.viajes.filter_my_trips_admin') }}</option>
                @foreach($conductors as $conductor)
                    <option value="{{ $conductor->id }}">{{ $conductor->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="col-12 col-lg-auto">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> {{ ui('common.filter') }}</button>
        </div>
    </form>
</div>

<div id="viajesTotals" class="cl-card mb-3" style="display:none;">
    <div class="row g-2 text-center">
        <div class="col-6 col-md-3">
            <small class="text-muted d-block">{{ ui('dashboard.income') }}</small>
            <strong class="text-income" id="totalIngresos">$0.00</strong>
        </div>
        <div class="col-6 col-md-3">
            <small class="text-muted d-block">{{ ui('pages.viajes.total_commissions') }}</small>
            <strong class="text-expense" id="totalComision">$0.00</strong>
        </div>
        <div class="col-6 col-md-3">
            <small class="text-muted d-block">{{ ui('dashboard.rent') }}</small>
            <strong class="text-expense" id="totalAlquiler">$0.00</strong>
        </div>
        <div class="col-6 col-md-3">
            <small class="text-muted d-block">{{ ui('pages.viajes.total_net') }}</small>
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
                    <th>{{ ui('common.date') }}</th>
                    <th>{{ ui('pages.viajes.col_day') }}</th>
                    <th>{{ ui('common.vehicle') }}</th>
                    <th>{{ ui('pages.viajes.col_type') }}</th>
                    <th>{{ ui('pages.viajes.filter_platform') }}</th>
                    <th>{{ ui('pages.viajes.col_client') }}</th>
                    <th>{{ ui('pages.viajes.col_mode') }}</th>
                    <th>{{ ui('pages.viajes.col_gross') }}</th>
                    <th>{{ ui('pages.viajes.col_commission') }}</th>
                    <th>{{ ui('pages.viajes.col_collected') }}</th>
                    <th>{{ ui('pages.viajes.col_tip') }}</th>
                    <th>{{ ui('dashboard.rent') }}</th>
                    <th>{{ ui('dashboard.income') }}</th>
                    <th>{{ ui('pages.viajes.total_net') }}</th>
                    <th>{{ ui('common.actions') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalNuevoViaje" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down modal-lg">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ ui('pages.viajes.modal_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNuevoViaje" novalidate>
                <input type="hidden" name="registration_mode" id="inputRegistrationMode" value="daily">
                <input type="hidden" name="edit_uuid" id="editUuid" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ ui('common.vehicle') }}</label>
                        <select name="vehicle_id" id="selectVehicle" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.viajes.filter_trip_type') }}</label>
                        <select name="trip_type_id" id="selectTripType" class="form-select" required>
                            @foreach($tripTypes as $type)
                                <option value="{{ $type->id }}" data-code="{{ $type->code }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3" id="fieldRegistrationMode">
                        <label class="form-label">{{ ui('pages.viajes.registration_mode') }}</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="registration_mode_radio" id="modePerTrip" value="per_trip">
                                <label class="form-check-label" for="modePerTrip">{{ ui('pages.viajes.mode_per_trip') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="registration_mode_radio" id="modeDaily" value="daily" checked>
                                <label class="form-check-label" for="modeDaily">{{ ui('pages.viajes.mode_daily') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="registration_mode_radio" id="modeMonthly" value="monthly">
                                <label class="form-check-label" for="modeMonthly">{{ ui('pages.viajes.mode_monthly') }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="fieldPlatform" style="display:none;">
                        <label class="form-label">{{ ui('pages.viajes.filter_platform') }}</label>
                        <select name="platform_id" id="selectPlatform" class="form-select">
                            <option value="">{{ ui('common.select') }}</option>
                            @foreach($platforms as $platform)
                                <option value="{{ $platform->id }}">{{ $platform->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="client_id" id="inputTripClientId">
                    <input type="hidden" name="client_dependent_id" id="inputTripClientDependentId">
                    <input type="hidden" name="client_display_name" id="inputTripClientDisplayName">
                    <div class="mb-3" id="fieldClient" style="display:none;">
                        <label class="form-label">
                            {{ ui('pages.viajes.field_client') }}
                            <span class="text-danger" id="clientRequiredMark" style="display:none;">*</span>
                        </label>
                        <select id="selectTripClient" class="form-select"></select>
                        <small class="text-muted d-block mt-1">{{ ui('pages.viajes.client_picker_hint') }}</small>
                    </div>
                    <div class="mb-3" id="fieldClientDependent" style="display:none;">
                        <label class="form-label">{{ ui('pages.viajes.field_dependent') }}</label>
                        <select id="selectTripDependent" class="form-select"></select>
                    </div>
                    <div class="mb-3" id="fieldFecha">
                        <label class="form-label">{{ ui('common.date') }}</label>
                        <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="row" id="fieldPeriod" style="display:none;">
                        <div class="col-6 mb-3">
                            <label class="form-label">{{ ui('pages.viajes.field_year') }}</label>
                            <input type="number" name="period_year" class="form-control" min="2000" max="{{ date('Y') }}" value="{{ date('Y') }}">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">{{ ui('pages.viajes.field_month') }}</label>
                            <select name="period_month" class="form-select">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" @selected($m == (int) date('n'))>{{ ui('months.'.$m) }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3" id="fieldMontoBruto" style="display:none;">
                            <label class="form-label text-income">{{ ui('pages.viajes.field_gross_amount') }}</label>
                            <input type="number" step="0.01" min="0" name="monto_bruto" class="form-control" value="0">
                        </div>
                        <div class="col-12 col-md-6 mb-3" id="fieldComisionApp" style="display:none;">
                            <label class="form-label text-expense">{{ ui('pages.viajes.field_app_commission') }}</label>
                            <input type="number" step="0.01" min="0" name="comision_app" class="form-control" value="0">
                        </div>
                        <div class="col-12 col-md-6 mb-3" id="fieldMontoCobrado" style="display:none;">
                            <label class="form-label text-income">{{ ui('pages.viajes.field_collected_amount') }}</label>
                            <input type="number" step="0.01" min="0" name="monto_cobrado" class="form-control" value="0">
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label text-income">{{ ui('pages.viajes.field_tip') }}</label>
                            <input type="number" step="0.01" min="0" name="propina" class="form-control" value="0">
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">{{ ui('pages.viajes.field_quota_percent') }}</label>
                            <input type="number" step="0.01" min="0" max="100" name="porcentaje_cuota" class="form-control" value="0">
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label text-expense">{{ ui('pages.viajes.field_rent_quota') }}</label>
                            <input type="number" step="0.01" min="0" name="alquiler" class="form-control" value="0" readonly>
                            <small id="rentalSuggestion" class="text-muted d-block mt-1"></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ ui('actions.cancel') }}</button>
                    <button type="submit" class="btn btn-success" id="btnSubmitViaje">{{ ui('actions.save') }}</button>
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
<script src="{{ asset('js/common/client-picker.js') }}"></script>
<script src="{{ asset('js/viajes/index.js') }}"></script>
@endpush
