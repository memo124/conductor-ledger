@extends('layouts.app')

@section('title', ui('pages.microbus_rutas.title'))

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-bus text-primary"></i> {{ ui('pages.microbus_rutas.heading') }}</h1>
        <p>{{ ui('pages.microbus_rutas.subtitle') }}</p>
    </div>
    <button class="btn btn-primary" type="button" id="btnNuevaRuta">
        <i class="fa-solid fa-plus"></i> {{ ui('pages.microbus_rutas.new_route') }}
    </button>
</div>

<div class="cl-card mb-3">
    <div class="table-responsive">
        <table id="tblRutas" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>{{ ui('common.id') }}</th>
                    <th>{{ ui('pages.microbus_rutas.col_name') }}</th>
                    <th>{{ ui('common.vehicle') }}</th>
                    <th>{{ ui('pages.microbus_rutas.col_passengers') }}</th>
                    <th>{{ ui('common.status') }}</th>
                    <th>{{ ui('common.actions') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalRuta" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRutaTitle">{{ ui('pages.microbus_rutas.modal_title_create') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formRuta">
                <input type="hidden" name="route_id" id="routeId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.microbus_rutas.col_name') }}</label>
                        <input type="text" name="name" class="form-control" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('common.vehicle') }}</label>
                        <select name="vehicle_id" id="selectRouteVehicle" class="form-select" required>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->displayLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.microbus_rutas.field_notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3" id="routeActiveField" style="display:none;">
                        <label class="form-label">{{ ui('common.status') }}</label>
                        <select name="is_active" class="form-select">
                            <option value="1">{{ ui('common.active') }}</option>
                            <option value="0">{{ ui('common.inactive') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ ui('actions.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ ui('actions.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPasajeros" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down modal-xl">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPasajerosTitle">{{ ui('pages.microbus_rutas.passengers_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-6 col-md-3">
                        <label class="form-label">{{ ui('pages.microbus_rutas.period_year') }}</label>
                        <input type="number" id="filterPeriodYear" class="form-control" value="{{ date('Y') }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">{{ ui('pages.microbus_rutas.period_month') }}</label>
                        <select id="filterPeriodMonth" class="form-select">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected($m == (int) date('n'))>{{ ui('months.'.$m) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" class="btn btn-primary btn-sm" id="btnReloadPassengers">{{ ui('common.filter') }}</button>
                    </div>
                    <div class="col-12 col-md-auto ms-md-auto">
                        <button type="button" class="btn btn-success btn-sm" id="btnAddPassenger">
                            <i class="fa-solid fa-plus"></i> {{ ui('pages.microbus_rutas.add_passenger') }}
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped" id="tblPasajeros">
                        <thead>
                            <tr>
                                <th>{{ ui('profile.name') }}</th>
                                <th>{{ ui('pages.microbus_rutas.col_monthly_fee') }}</th>
                                <th>{{ ui('pages.microbus_rutas.col_paid') }}</th>
                                <th>{{ ui('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPasajero" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ ui('pages.microbus_rutas.passenger_modal_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPasajero">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.microbus_rutas.field_passenger') }} <span class="text-danger">*</span></label>
                        <select id="selectPassengerClient" class="form-select"></select>
                        <small class="text-muted d-block mt-1">{{ ui('pages.viajes.client_picker_hint') }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.viajes.field_dependent') }}</label>
                        <select id="selectPassengerDependent" class="form-select"></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.microbus_rutas.col_monthly_fee') }}</label>
                        <input type="number" step="0.01" min="0" name="monthly_fee" class="form-control" value="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.microbus_rutas.field_pickup_notes') }}</label>
                        <input type="text" name="pickup_notes" class="form-control" maxlength="1000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ ui('actions.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ ui('actions.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/common/select2-paginated.js') }}"></script>
<script src="{{ asset('js/common/client-picker.js') }}"></script>
<script src="{{ asset('js/microbus-rutas/index.js') }}"></script>
@endpush
