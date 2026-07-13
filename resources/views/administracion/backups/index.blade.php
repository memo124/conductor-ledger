@extends('layouts.app')

@section('title', ui('pages.backups.title'))

@section('content')
<div class="cl-page-header">
    <h1><i class="fa-solid fa-database text-primary"></i> {{ ui('pages.backups.heading') }}</h1>
    <p>{{ ui('pages.backups.subtitle') }}</p>
</div>

<div class="cl-card">
    <div class="cl-card-title">{{ ui('pages.backups.actions_heading') }}</div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-primary" id="btnGenerateBackup" data-cl-loader
            data-cl-loading-text="{{ ui('pages.backups.generating') }}" data-cl-loader-message="{{ ui('pages.backups.generating_message') }}">
            <i class="fa-solid fa-cloud-arrow-down"></i> {{ ui('pages.backups.generate_now') }}
        </button>
    </div>
    <div id="backupResult" class="mt-3"></div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    ConductorLedger.setupAjaxCsrf();

    var i18n = {
        generating: @json(ui('pages.backups.generating_fallback')),
        fileLabel: @json(ui('pages.backups.file_label')),
        checksumLabel: @json(ui('pages.backups.checksum_label')),
        getLink: @json(ui('pages.backups.get_download_link')),
        linkError: @json(ui('pages.backups.link_error')),
        generateError: @json(ui('pages.backups.generate_error'))
    };

    $('#btnGenerateBackup').on('click', function () {
        var $btn = $(this);

        $.ajax({
            url: APLICATIVO_API.ADMIN.POST.BACKUP_GENERATE,
            method: 'POST',
            clButton: $btn[0],
            clLoadingText: $btn.data('cl-loading-text') || i18n.generating,
            clLoaderMessage: $btn.data('cl-loader-message') || i18n.generating
        })
            .done(function (res) {
                ConductorLedger.showAlert(res.message, 'success');
                var data = res.data;
                var html = '<div class="alert alert-success mb-2">' +
                    '<strong>' + i18n.fileLabel + '</strong> ' + data.filename + '<br>' +
                    '<strong>' + i18n.checksumLabel + '</strong> <code>' + data.checksum + '</code>' +
                    '</div>' +
                    '<button type="button" class="btn btn-outline-primary btn-sm" id="btnIssueLink">' + i18n.getLink + '</button>';
                $('#backupResult').html(html);

                $('#btnIssueLink').on('click', function () {
                    $.post(APLICATIVO_API.ADMIN.POST.BACKUP_ISSUE_LINK, {
                        filename: data.filename,
                        checksum: data.checksum
                    }).done(function (linkRes) {
                        window.location.href = linkRes.download_url;
                    }).fail(function (xhr) {
                        ConductorLedger.showAlert(xhr.responseJSON?.message || i18n.linkError, 'danger');
                    });
                });
            })
            .fail(function (xhr) {
                ConductorLedger.showAlert(xhr.responseJSON?.message || i18n.generateError, 'danger');
            });
    });
});
</script>
@endpush
