@extends('layouts.app')

@section('title', 'Respaldos')

@section('content')
<div class="cl-page-header">
    <h1><i class="fa-solid fa-database text-primary"></i> Respaldos de base de datos</h1>
    <p>Generación local de respaldos SQL empaquetados en ZIP (restaurables con psql)</p>
</div>

<div class="cl-card">
    <div class="cl-card-title">Acciones</div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-primary" id="btnGenerateBackup" data-cl-loader
            data-cl-loading-text="Generando..." data-cl-loader-message="Generando respaldo de base de datos...">
            <i class="fa-solid fa-cloud-arrow-down"></i> Generar respaldo ahora
        </button>
    </div>
    <div id="backupResult" class="mt-3"></div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    ConductorLedger.setupAjaxCsrf();

    $('#btnGenerateBackup').on('click', function () {
        var $btn = $(this);

        $.ajax({
            url: APLICATIVO_API.ADMIN.POST.BACKUP_GENERATE,
            method: 'POST',
            clButton: $btn[0],
            clLoadingText: $btn.data('cl-loading-text') || 'Generando...',
            clLoaderMessage: $btn.data('cl-loader-message') || 'Generando respaldo...'
        })
            .done(function (res) {
                ConductorLedger.showAlert(res.message, 'success');
                var data = res.data;
                var html = '<div class="alert alert-success mb-2">' +
                    '<strong>Archivo:</strong> ' + data.filename + '<br>' +
                    '<strong>Checksum:</strong> <code>' + data.checksum + '</code>' +
                    '</div>' +
                    '<button type="button" class="btn btn-outline-primary btn-sm" id="btnIssueLink">Obtener enlace de descarga</button>';
                $('#backupResult').html(html);

                $('#btnIssueLink').on('click', function () {
                    $.post(APLICATIVO_API.ADMIN.POST.BACKUP_ISSUE_LINK, {
                        filename: data.filename,
                        checksum: data.checksum
                    }).done(function (linkRes) {
                        window.location.href = linkRes.download_url;
                    }).fail(function (xhr) {
                        ConductorLedger.showAlert(xhr.responseJSON?.message || 'Error al generar enlace.', 'danger');
                    });
                });
            })
            .fail(function (xhr) {
                ConductorLedger.showAlert(xhr.responseJSON?.message || 'Error al generar respaldo.', 'danger');
            });
    });
});
</script>
@endpush
