$(function () {
    ConductorLedger.setupAjaxCsrf();

    $('#formResetPassword').on('submit', function (e) {
        e.preventDefault();

        $.post(APLICATIVO_API.AUTHENTICATION.POST.RESET_PASSWORD, $(this).serialize())
            .done(function (res) {
                ConductorLedger.showAlert(res.message, 'success');
                setTimeout(function () {
                    window.location.href = res.redirect || APLICATIVO_API.AUTHENTICATION.GET.LOGIN;
                }, 1200);
            })
            .fail(function (xhr) {
                ConductorLedger.showAlert(xhr.responseJSON?.message || 'No se pudo restablecer la contraseña.', 'danger');
            });
    });
});
