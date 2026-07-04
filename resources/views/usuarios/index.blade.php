@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-users-gear text-primary"></i> Gestión de Usuarios</h1>
        <p>Administración de cuentas del sistema</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario">
        <i class="fa-solid fa-plus"></i> Nuevo Usuario
    </button>
</div>

<div class="cl-card">
    <div class="table-responsive">
        <table id="tblUsuarios" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>DUI</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUsuarioTitle">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUsuario">
                <input type="hidden" name="user_id" id="userId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">DUI</label>
                        <input type="text" name="dui" class="form-control" maxlength="10" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" id="userPassword" class="form-control" minlength="8">
                        <small class="text-muted" id="passwordHint">Obligatoria al crear usuario.</small>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Rol</label>
                            <select name="role" class="form-select" required>
                                <option value="user">Conductor</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Estado</label>
                            <select name="is_active" class="form-select" required>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
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
<script src="{{ asset('js/common/datatables-config.js') }}"></script>
<script src="{{ asset('js/usuarios/index.js') }}"></script>
@endpush
