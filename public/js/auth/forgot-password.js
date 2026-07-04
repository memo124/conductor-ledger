$(function () {
    ConductorLedger.setupAjaxCsrf();

    $('#formForgotPassword').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#btnSubmit');
        $btn.prop('disabled', true);

        $.post(APLICATIVO_API.AUTHENTICATION.POST.FORGOT_PASSWORD, $(this).serialize())
            .done(function (res) {
                ConductorLedger.showAlert(res.message, 'success');
            })
            .fail(function (xhr) {
                ConductorLedger.showAlert(xhr.responseJSON?.message || 'No se pudo enviar el enlace.', 'danger');
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
    });
});
