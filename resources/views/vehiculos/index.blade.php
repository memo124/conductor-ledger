@extends('layouts.app')

@section('title', ui('pages.vehiculos.title'))

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-car text-primary"></i> {{ ui('pages.vehiculos.heading') }}</h1>
        <p>{{ ui('pages.vehiculos.subtitle') }}</p>
    </div>
    <button class="btn btn-primary" type="button" id="btnNuevoVehiculo">
        <i class="fa-solid fa-plus"></i> {{ ui('pages.vehiculos.new_vehicle') }}
    </button>
</div>

<div class="cl-card">
    <div class="table-responsive">
        <table id="tblVehiculos" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>{{ ui('common.id') }}</th>
                    <th>{{ ui('pages.vehiculos.col_plate') }}</th>
                    <th>{{ ui('pages.vehiculos.col_ownership_type') }}</th>
                    <th>{{ ui('pages.vehiculos.col_quota') }}</th>
                    <th>{{ ui('pages.vehiculos.col_period') }}</th>
                    <th>{{ ui('common.status') }}</th>
                    <th>{{ ui('common.actions') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalVehiculo" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVehiculoTitle">{{ ui('pages.vehiculos.modal_title_create') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formVehiculo" novalidate>
                <input type="hidden" name="vehicle_id" id="vehicleId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.vehiculos.field_plate') }}</label>
                        <input type="text" name="plate_number" class="form-control" maxlength="15" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.vehiculos.field_ownership_type') }}</label>
                        <select name="ownership_type_id" id="selectOwnership" class="form-select" required></select>
                    </div>
                    <div id="rentalFields" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label" id="rentalPeriodLabel">{{ ui('pages.vehiculos.field_payment_period') }}</label>
                            <select name="rental_period" class="form-select">
                                <option value="daily">{{ ui('pages.vehiculos.period_daily') }}</option>
                                <option value="weekly">{{ ui('pages.vehiculos.period_weekly') }}</option>
                                <option value="biweekly">{{ ui('pages.vehiculos.period_biweekly') }}</option>
                                <option value="monthly">{{ ui('pages.vehiculos.period_monthly') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" id="rentalFeeLabel">{{ ui('pages.vehiculos.field_quota_amount') }}</label>
                            <input type="number" step="0.01" min="0" name="rental_fee_daily" class="form-control" value="">
                            <small class="text-muted" id="rentalFeeHint">{{ ui('pages.vehiculos.quota_hint') }}</small>
                        </div>
                    </div>
                    <div class="row" id="quotaFields">
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">{{ ui('pages.vehiculos.field_trip_quota_percent') }}</label>
                            <input type="number" step="0.01" min="0" max="100" name="quota_percentage" class="form-control" value="0">
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">{{ ui('pages.vehiculos.field_reserve_amount') }}</label>
                            <input type="number" step="0.01" min="0" name="quota_reserve_amount" class="form-control" value="0">
                            <small class="text-muted">{{ ui('pages.vehiculos.reserve_hint') }}</small>
                        </div>
                    </div>
                    <div class="mb-3" id="activeField" style="display:none;">
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
@endsection

@push('scripts')
<script src="{{ asset('js/common/select2-paginated.js') }}"></script>
<script src="{{ asset('js/vehiculos/index.js') }}"></script>
@endpush
