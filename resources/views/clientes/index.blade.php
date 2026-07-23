@extends('layouts.app')

@section('title', ui('pages.clientes.title'))

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-address-book text-primary"></i> {{ ui('pages.clientes.heading') }}</h1>
        <p>{{ ui('pages.clientes.subtitle') }}</p>
    </div>
    <button class="btn btn-primary" type="button" id="btnNuevoCliente">
        <i class="fa-solid fa-plus"></i> {{ ui('pages.clientes.new_client') }}
    </button>
</div>

<div class="cl-card">
    <div class="table-responsive">
        <table id="tblClientes" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>{{ ui('common.id') }}</th>
                    <th>{{ ui('profile.name') }}</th>
                    <th>{{ ui('pages.clientes.col_phone') }}</th>
                    <th>{{ ui('profile.email') }}</th>
                    <th>{{ ui('pages.clientes.col_dependents') }}</th>
                    <th>{{ ui('pages.clientes.col_location') }}</th>
                    <th>{{ ui('common.status') }}</th>
                    <th>{{ ui('common.actions') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalCliente" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down modal-xl">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalClienteTitle">{{ ui('pages.clientes.modal_title_create') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCliente" novalidate>
                <input type="hidden" name="client_id" id="clientId">
                <input type="hidden" name="latitude" id="inputLatitude">
                <input type="hidden" name="longitude" id="inputLongitude">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">{{ ui('profile.name') }}</label>
                                <input type="text" name="name" class="form-control" maxlength="120" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ ui('pages.clientes.col_phone') }}</label>
                                    <input type="text" name="phone" class="form-control" maxlength="30">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">{{ ui('profile.email') }}</label>
                                    <input type="email" name="email" class="form-control" maxlength="150">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ui('pages.clientes.field_address') }}</label>
                                <input type="text" name="address" class="form-control" maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ ui('pages.clientes.field_notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3" id="clientActiveField" style="display:none;">
                                <label class="form-label">{{ ui('common.status') }}</label>
                                <select name="is_active" class="form-select">
                                    <option value="1">{{ ui('common.active') }}</option>
                                    <option value="0">{{ ui('common.inactive') }}</option>
                                </select>
                            </div>
                            <div class="cl-card mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="cl-card-title mb-0">{{ ui('pages.clientes.dependents_title') }}</div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddDependent">
                                        <i class="fa-solid fa-plus"></i> {{ ui('pages.clientes.add_dependent') }}
                                    </button>
                                </div>
                                <div id="dependentsList"></div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label">{{ ui('pages.clientes.map_title') }}</label>
                            <div id="clientMap" class="rounded border" style="height: 320px;"></div>
                            <small class="text-muted d-block mt-2">{{ ui('pages.clientes.map_hint') }}</small>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btnClearLocation">
                                {{ ui('pages.clientes.clear_location') }}
                            </button>
                        </div>
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

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="{{ asset('js/clientes/index.js') }}"></script>
@endpush
