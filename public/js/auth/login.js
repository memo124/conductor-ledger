$(function () {
    ConductorLedger.setupAjaxCsrf();

    $('#formLogin').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#btnLogin');
        $btn.prop('disabled', true);

        $.ajax({
            url: APLICATIVO_API.AUTHENTICATION.POST.LOGIN,
            method: 'POST',
            data: {
                email: $('#email').val(),
                password: $('#password').val(),
                remember: $('#remember').is(':checked') ? 1 : 0
            }
        })
            .done(function (res) {
                if (res.theme_preference) {
                    try {
                        localStorage.setItem('cl_theme_preference', res.theme_preference);
                    } catch (e) {
                        // ignore storage errors
                    }
                }
                ConductorLedger.showAlert(res.message, 'success');
                window.location.href = res.redirect;
            })
            .fail(function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Error al iniciar sesión.';
                ConductorLedger.showAlert(msg, 'danger');
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
    });
});
