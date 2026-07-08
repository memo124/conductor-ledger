@extends('layouts.app')

@section('title', 'Descifrado de emergencia')

@section('content')
<div class="cl-page-header">
    <h1><i class="fa-solid fa-unlock-keyhole text-primary"></i> Descifrado de emergencia</h1>
    <p>Acceso auditado con Llave Maestra para incidentes legales o técnicos</p>
</div>

<div class="cl-card">
    <form id="formEmergencyDecrypt">
        <div class="mb-3">
            <label class="form-label">ID de usuario</label>
            <input type="number" name="user_id" class="form-control" required min="1">
        </div>
        <div class="mb-3">
            <label class="form-label">Referencia de ticket / incidente</label>
            <input type="text" name="ticket_reference" class="form-control" required maxlength="100">
        </div>
        <div class="mb-3">
            <label class="form-label">Motivo (mínimo 10 caracteres)</label>
            <textarea name="reason" class="form-control" rows="3" required minlength="10"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Su contraseña de administrador</label>
            <input type="password" name="admin_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-warning">
            <i class="fa-solid fa-key"></i> Ejecutar descifrado
        </button>
    </form>
    <pre id="emergencyResult" class="mt-3 p-3 bg-dark text-light rounded d-none"></pre>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    ConductorLedger.setupAjaxCsrf();

    $('#formEmergencyDecrypt').on('submit', function (e) {
        e.preventDefault();

        if (!confirm('¿Confirma el descifrado de emergencia? Esta acción queda auditada.')) {
            return;
        }

        $.post(APLICATIVO_API.ADMIN.POST.EMERGENCY_DECRYPT, $(this).serialize())
            .done(function (res) {
                ConductorLedger.showAlert(res.message, 'success');
                $('#emergencyResult').removeClass('d-none').text(JSON.stringify(res.data, null, 2));
            })
            .fail(function (xhr) {
                ConductorLedger.showAlert(xhr.responseJSON?.message || 'Error en descifrado.', 'danger');
            });
    });
});
</script>
@endpush
