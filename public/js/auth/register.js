$(function () {
    ConductorLedger.setupAjaxCsrf();

    $('#formRegister').on('submit', function (e) {
        e.preventDefault();

        $.post(APLICATIVO_API.AUTHENTICATION.POST.REGISTER, $(this).serialize())
            .done(function (res) {
                ConductorLedger.showAlert(res.message, 'success');
                if (res.redirect) {
                    setTimeout(function () { window.location.href = res.redirect; }, 1500);
                }
            })
            .fail(function (xhr) {
                var msg = xhr.responseJSON?.message || 'No se pudo completar el registro.';
                if (xhr.responseJSON?.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                ConductorLedger.showAlert(msg, 'danger');
            });
    });
});
