$(function () {
    ConductorLedger.setupAjaxCsrf();

    $('#formPerfil').on('submit', function (e) {
        e.preventDefault();
        var previousCurrency = ConductorLedger.DisplayPreferences.getCurrency();
        var previousLocale = ConductorLedger.DisplayPreferences.getLocale();

        $.ajax({
            url: APLICATIVO_API.PERFIL.PUT.UPDATE,
            method: 'PUT',
            data: $(this).serialize()
        }).done(function (res) {
            ConductorLedger.showAlert(res.message, 'success');

            if (res.data) {
                if (res.data.theme_preference) {
                    ConductorLedger.Theme.setPreference(res.data.theme_preference, { persistToServer: false });
                    $('#selectThemePreference').val(res.data.theme_preference);
                }

                var currencyChanged = res.data.currency_preference
                    && res.data.currency_preference !== previousCurrency;
                var localeChanged = res.data.locale_preference
                    && res.data.locale_preference !== previousLocale;

                if (currencyChanged || localeChanged) {
                    window.location.reload();
                }
            }
        }).fail(function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error al guardar.';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
            }
            ConductorLedger.showAlert(msg, 'danger');
        });
    });

    $('#formPassword').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: APLICATIVO_API.PERFIL.PUT.UPDATE_PASSWORD,
            method: 'PUT',
            data: $(this).serialize()
        }).done(function (res) {
            ConductorLedger.showAlert(res.message, 'success');
            $('#formPassword')[0].reset();
        }).fail(function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error al cambiar contraseña.';
            ConductorLedger.showAlert(msg, 'danger');
        });
    });
});
