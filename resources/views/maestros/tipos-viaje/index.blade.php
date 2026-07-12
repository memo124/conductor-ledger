@extends('layouts.app')

@section('title', 'Tipos de Viaje')

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-route text-primary"></i> Tipos de Viaje</h1>
        <p>Plataforma, personal, microbús, escolar, interurbano, internacional</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="fa-solid fa-plus"></i> Nuevo Tipo
    </button>
</div>

<div class="cl-card">
    <div class="table-responsive">
        <table id="tblMaestro" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Modos</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalNuevo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nuevo Tipo de Viaje</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formMaestro">
                <input type="hidden" name="id" id="recordId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Código</label>
                        <input type="text" name="code" id="inputCode" class="form-control" maxlength="30" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" id="inputName" class="form-control" maxlength="80" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Modos permitidos</label>
                        <input type="text" name="allowed_modes" id="inputAllowedModes" class="form-control" maxlength="100" required
                            placeholder="per_trip,daily,monthly">
                        <small class="text-muted">Valores separados por coma: per_trip, daily, monthly</small>
                    </div>
                    <div class="mb-3" id="activeField" style="display:none;">
                        <label class="form-label">Estado</label>
                        <select name="is_active" id="inputIsActive" class="form-select">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/maestros/tipos-viaje.js') }}"></script>
@endpush
