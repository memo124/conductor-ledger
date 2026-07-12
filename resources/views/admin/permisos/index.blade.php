@extends('layouts.app')

@section('title', 'Permisos')

@section('content')
<div class="cl-page-header">
    <h1><i class="fa-solid fa-shield-halved text-primary"></i> Permisos por Rol</h1>
    <p>Matriz de permisos view / create / update / delete por opción del menú</p>
</div>

<div class="cl-card mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label" for="selectRole">Rol</label>
            <select id="selectRole" class="form-select">
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-auto">
            <button type="button" id="btnSavePermisos" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Guardar permisos
            </button>
        </div>
    </div>
</div>

<div class="cl-card">
    <div class="table-responsive">
        <table id="tblPermisos" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>Opción</th>
                    <th class="text-center">Ver</th>
                    <th class="text-center">Crear</th>
                    <th class="text-center">Editar</th>
                    <th class="text-center">Eliminar</th>
                </tr>
            </thead>
            <tbody id="permisosBody">
                <tr>
                    <td colspan="5" class="text-center text-muted">Seleccione un rol para cargar permisos</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/permisos.js') }}"></script>
@endpush
