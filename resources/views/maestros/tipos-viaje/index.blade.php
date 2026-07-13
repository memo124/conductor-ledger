@extends('layouts.app')

@section('title', ui('pages.maestros.tipos_viaje.title'))

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-route text-primary"></i> {{ ui('pages.maestros.tipos_viaje.heading') }}</h1>
        <p>{{ ui('pages.maestros.tipos_viaje.subtitle') }}</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="fa-solid fa-plus"></i> {{ ui('pages.maestros.tipos_viaje.new') }}
    </button>
</div>

<div class="cl-card">
    <div class="table-responsive">
        <table id="tblMaestro" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>{{ ui('common.id') }}</th>
                    <th>{{ ui('pages.maestros.tipos_viaje.col_code') }}</th>
                    <th>{{ ui('common.name') }}</th>
                    <th>{{ ui('pages.maestros.tipos_viaje.col_modes') }}</th>
                    <th>{{ ui('common.status') }}</th>
                    <th>{{ ui('common.actions') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalNuevo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">{{ ui('pages.maestros.tipos_viaje.modal_title_create') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formMaestro">
                <input type="hidden" name="id" id="recordId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.maestros.tipos_viaje.field_code') }}</label>
                        <input type="text" name="code" id="inputCode" class="form-control" maxlength="30" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('common.name') }}</label>
                        <input type="text" name="name" id="inputName" class="form-control" maxlength="80" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.maestros.tipos_viaje.field_allowed_modes') }}</label>
                        <input type="text" name="allowed_modes" id="inputAllowedModes" class="form-control" maxlength="100" required
                            placeholder="per_trip,daily,monthly">
                        <small class="text-muted">{{ ui('pages.maestros.tipos_viaje.allowed_modes_hint') }}</small>
                    </div>
                    <div class="mb-3" id="activeField" style="display:none;">
                        <label class="form-label">{{ ui('common.status') }}</label>
                        <select name="is_active" id="inputIsActive" class="form-select">
                            <option value="1">{{ ui('common.active') }}</option>
                            <option value="0">{{ ui('common.inactive') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">{{ ui('actions.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/maestros/tipos-viaje.js') }}"></script>
@endpush
