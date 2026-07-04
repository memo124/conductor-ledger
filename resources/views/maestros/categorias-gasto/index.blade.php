@extends('layouts.app')

@section('title', 'Categorías de Gasto')

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-tags text-primary"></i> Categorías de Gasto</h1>
        <p>Tabla maestra — GASOLINA, ALQUILER, COMIDA, MANTENIMIENTO, OTROS</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevo">
        <i class="fa-solid fa-plus"></i> Nueva Categoría
    </button>
</div>

<div class="cl-card">
    <table id="tblMaestro" class="table table-striped w-100">
        <thead><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr></thead>
    </table>
</div>

<div class="modal fade" id="modalNuevo" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formMaestro">
                <input type="hidden" name="id" id="recordId">
                <div class="modal-body">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" id="inputName" class="form-control" maxlength="50" required>
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
<script src="{{ asset('js/common/datatables-config.js') }}"></script>
<script src="{{ asset('js/maestros/categorias-gasto.js') }}"></script>
@endpush
