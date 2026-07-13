@extends('layouts.app')

@section('title', ui('pages.emergency_decrypt.title'))

@section('content')
<div class="cl-page-header">
    <h1><i class="fa-solid fa-unlock-keyhole text-primary"></i> {{ ui('pages.emergency_decrypt.heading') }}</h1>
    <p>{{ ui('pages.emergency_decrypt.subtitle') }}</p>
</div>

<div class="cl-card">
    <form id="formEmergencyDecrypt">
        <div class="mb-3">
            <label class="form-label">{{ ui('pages.emergency_decrypt.field_user') }}</label>
            <select name="user_id" class="form-select" required>
                <option value="">{{ ui('pages.emergency_decrypt.select_driver_placeholder') }}</option>
                @forelse($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>
                @empty
                    <option value="" disabled>{{ ui('pages.emergency_decrypt.no_active_drivers') }}</option>
                @endforelse
            </select>
            <small class="text-muted">{{ ui('pages.emergency_decrypt.drivers_hint') }}</small>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ ui('pages.emergency_decrypt.field_ticket_reference') }}</label>
            <input type="text" name="ticket_reference" class="form-control" required maxlength="100">
        </div>
        <div class="mb-3">
            <label class="form-label">{{ ui('pages.emergency_decrypt.field_reason') }}</label>
            <textarea name="reason" class="form-control" rows="3" required minlength="10"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ ui('pages.emergency_decrypt.field_admin_password') }}</label>
            <input type="password" name="admin_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-warning" @if($users->isEmpty()) disabled @endif>
            <i class="fa-solid fa-key"></i> {{ ui('pages.emergency_decrypt.submit') }}
        </button>
    </form>
    <pre id="emergencyResult" class="mt-3 p-3 bg-dark text-light rounded d-none"></pre>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    ConductorLedger.setupAjaxCsrf();

    var confirmMsg = @json(ui('pages.emergency_decrypt.confirm'));
    var errorMsg = @json(ui('pages.emergency_decrypt.error'));

    $('#formEmergencyDecrypt').on('submit', function (e) {
        e.preventDefault();

        if (!confirm(confirmMsg)) {
            return;
        }

        $.post(APLICATIVO_API.ADMIN.POST.EMERGENCY_DECRYPT, $(this).serialize())
            .done(function (res) {
                ConductorLedger.showAlert(res.message, 'success');
                $('#emergencyResult').removeClass('d-none').text(JSON.stringify(res.data, null, 2));
            })
            .fail(function (xhr) {
                ConductorLedger.showAlert(xhr.responseJSON?.message || errorMsg, 'danger');
            });
    });
});
</script>
@endpush
