@extends('layouts.app')

@section('title', ui('pages.permisos.title'))

@section('content')
<div class="cl-page-header">
    <h1><i class="fa-solid fa-shield-halved text-primary"></i> {{ ui('pages.permisos.heading') }}</h1>
    <p>{{ ui('pages.permisos.subtitle') }}</p>
</div>

<div class="cl-card mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label" for="selectRole">{{ ui('pages.permisos.field_role') }}</label>
            <select id="selectRole" class="form-select">
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-auto">
            <button type="button" id="btnSavePermisos" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> {{ ui('pages.permisos.save_permissions') }}
            </button>
        </div>
    </div>
</div>

<div class="cl-card">
    <div class="table-responsive">
        <table id="tblPermisos" class="table table-striped w-100">
            <thead>
                <tr>
                    <th>{{ ui('pages.permisos.col_option') }}</th>
                    <th class="text-center">{{ ui('pages.permisos.col_view') }}</th>
                    <th class="text-center">{{ ui('pages.permisos.col_create') }}</th>
                    <th class="text-center">{{ ui('pages.permisos.col_edit') }}</th>
                    <th class="text-center">{{ ui('pages.permisos.col_delete') }}</th>
                </tr>
            </thead>
            <tbody id="permisosBody">
                <tr>
                    <td colspan="5" class="text-center text-muted">{{ ui('pages.permisos.empty_select_role') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/permisos.js') }}"></script>
@endpush
