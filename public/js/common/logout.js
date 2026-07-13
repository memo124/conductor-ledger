$(function () {
    ConductorLedger.setupAjaxCsrf();

    $('#btnLogout').on('click', function () {
        $.post(APLICATIVO_API.AUTHENTICATION.POST.LOGOUT)
            .done(function (res) {
                window.location.href = res.redirect || APLICATIVO_API.AUTHENTICATION.GET.LOGIN;
            })
            .fail(function () {
                window.location.href = APLICATIVO_API.AUTHENTICATION.GET.LOGIN;
            });
    });
});
