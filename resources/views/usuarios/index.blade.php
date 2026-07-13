@extends('layouts.app')

@section('title', ui('pages.usuarios.title'))

@section('content')
<div class="cl-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><i class="fa-solid fa-users-gear text-primary"></i> {{ ui('pages.usuarios.heading') }}</h1>
        <p>{{ ui('pages.usuarios.subtitle') }}</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario">
        <i class="fa-solid fa-plus"></i> {{ ui('pages.usuarios.new_user') }}
    </button>
</div>

<div class="cl-card">
    <div class="table-responsive">
        <table id="tblUsuarios" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>{{ ui('common.id') }}</th>
                    <th>{{ ui('profile.name') }}</th>
                    <th>{{ ui('profile.email') }}</th>
                    <th>{{ ui('profile.dui') }}</th>
                    <th>{{ ui('pages.usuarios.col_role') }}</th>
                    <th>{{ ui('common.status') }}</th>
                    <th>{{ ui('common.actions') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content cl-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUsuarioTitle">{{ ui('pages.usuarios.modal_title_create') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUsuario">
                <input type="hidden" name="user_id" id="userId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ ui('profile.name') }}</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('profile.email') }}</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('profile.dui') }}</label>
                        <input type="text" name="dui" class="form-control" maxlength="10" placeholder="{{ ui('profile.dui_optional') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ ui('pages.usuarios.field_password') }}</label>
                        <input type="password" name="password" id="userPassword" class="form-control" minlength="8">
                        <small class="text-muted" id="passwordHint">{{ ui('pages.usuarios.password_hint_create') }}</small>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">{{ ui('pages.usuarios.col_role') }}</label>
                            <select name="role" class="form-select" required>
                                <option value="user">{{ ui('roles.conductor') }}</option>
                                <option value="admin">{{ ui('roles.administrador') }}</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="form-label">{{ ui('common.status') }}</label>
                            <select name="is_active" class="form-select" required>
                                <option value="1">{{ ui('common.active') }}</option>
                                <option value="0">{{ ui('common.inactive') }}</option>
                            </select>
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

@push('scripts')
<script src="{{ asset('js/usuarios/index.js') }}"></script>
@endpush
