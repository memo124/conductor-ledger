window.ConductorLedger = window.ConductorLedger || {};

ConductorLedger._ajaxLoaderCount = 0;
ConductorLedger._ajaxLoaderTimer = null;
ConductorLedger._ajaxLoaderSafetyTimer = null;
ConductorLedger._pendingLoaderButton = null;

ConductorLedger.silentAjaxPatterns = [
    'Select2',
    'GetDatatableServerSide',
    'ActualizarSesion',
    'UpdateThemePreference',
    'UpdateLocalePreference',
    'GetRentalSuggestion'
];

ConductorLedger.isSilentAjax = function (settings) {
    if (settings.clSilent) {
        return true;
    }

    var url = settings.url || '';

    return ConductorLedger.silentAjaxPatterns.some(function (pattern) {
        return url.indexOf(pattern) !== -1;
    });
};

ConductorLedger.showLoader = function (message) {
    var $overlay = $('#clGlobalLoader');

    if (!$overlay.length) {
        $('body').append(
            '<div id="clGlobalLoader" class="cl-global-loader" aria-live="polite" aria-busy="true" hidden>' +
                '<div class="cl-global-loader-panel">' +
                    '<div class="spinner-border cl-global-loader-spinner" role="status" aria-hidden="true"></div>' +
                    '<p class="cl-global-loader-message mb-0"></p>' +
                '</div>' +
            '</div>'
        );
        $overlay = $('#clGlobalLoader');
    }

    $overlay.find('.cl-global-loader-message').text(message || 'Cargando...');
    $overlay.removeAttr('hidden');

    requestAnimationFrame(function () {
        $overlay.addClass('is-visible');
    });
};

ConductorLedger.hideLoader = function () {
    var $overlay = $('#clGlobalLoader');

    if (!$overlay.length) {
        return;
    }

    $overlay.removeClass('is-visible');

    window.setTimeout(function () {
        if (!$overlay.hasClass('is-visible')) {
            $overlay.attr('hidden', 'hidden');
        }
    }, 200);
};

ConductorLedger.setButtonLoading = function ($btn, isLoading, loadingText) {
    if (!$btn || !$btn.length) {
        return;
    }

    if (isLoading) {
        if ($btn.data('cl-loading')) {
            return;
        }

        $btn.data('cl-loading', true);
        $btn.data('cl-original-html', $btn.html());
        $btn.prop('disabled', true);
        $btn.html(
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
            (loadingText || 'Procesando...')
        );
        return;
    }

    if (!$btn.data('cl-loading')) {
        return;
    }

    $btn.prop('disabled', false);
    $btn.html($btn.data('cl-original-html'));
    $btn.removeData('cl-loading');
    $btn.removeData('cl-original-html');
};

ConductorLedger.resolveLoaderButton = function (settings) {
    if (settings.clButton) {
        return $(settings.clButton);
    }

    if (ConductorLedger._pendingLoaderButton) {
        var $btn = $(ConductorLedger._pendingLoaderButton);
        ConductorLedger._pendingLoaderButton = null;
        settings.clButton = $btn[0];
        return $btn;
    }

    var $pendingForm = $('form[data-cl-submit-pending="1"]').first();

    if ($pendingForm.length) {
        $pendingForm.removeAttr('data-cl-submit-pending');
        var $submit = $pendingForm.find('[type="submit"]').first();
        settings.clButton = $submit[0];
        return $submit;
    }

    return null;
};

ConductorLedger.finishAjaxLoader = function (settings) {
    if (settings.clLoaderFinished) {
        return;
    }
    settings.clLoaderFinished = true;

    if (ConductorLedger.isSilentAjax(settings)) {
        return;
    }

    ConductorLedger._ajaxLoaderCount = Math.max(0, ConductorLedger._ajaxLoaderCount - 1);

    if (settings.clButton) {
        ConductorLedger.setButtonLoading($(settings.clButton), false);
    }

    if (ConductorLedger._ajaxLoaderCount === 0) {
        window.clearTimeout(ConductorLedger._ajaxLoaderTimer);
        window.clearTimeout(ConductorLedger._ajaxLoaderSafetyTimer);
        ConductorLedger._ajaxLoaderSafetyTimer = null;
        ConductorLedger.hideLoader();
        ConductorLedger._pendingLoaderButton = null;
    }
};

ConductorLedger.resetAjaxLoader = function () {
    ConductorLedger._ajaxLoaderCount = 0;
    window.clearTimeout(ConductorLedger._ajaxLoaderTimer);
    window.clearTimeout(ConductorLedger._ajaxLoaderSafetyTimer);
    ConductorLedger._ajaxLoaderSafetyTimer = null;
    ConductorLedger.hideLoader();
    ConductorLedger._pendingLoaderButton = null;
    $('[data-cl-loading], button, input[type="submit"], a.btn').each(function () {
        if ($(this).data('cl-loading')) {
            ConductorLedger.setButtonLoading($(this), false);
        }
    });
};

ConductorLedger.setupAjaxLoader = function () {
    $(document).on('submit', 'form', function () {
        $(this).attr('data-cl-submit-pending', '1');
    });

    $(document).on('click', '[data-cl-loader]', function () {
        ConductorLedger._pendingLoaderButton = this;
    });

    $(document).ajaxSend(function (event, xhr, settings) {
        if (ConductorLedger.isSilentAjax(settings)) {
            return;
        }

        ConductorLedger._ajaxLoaderCount++;

        var $btn = ConductorLedger.resolveLoaderButton(settings);

        if ($btn && $btn.length) {
            if (!settings.clLoadingText && $btn.data('cl-loading-text')) {
                settings.clLoadingText = $btn.data('cl-loading-text');
            }

            if (!settings.clLoaderMessage && $btn.data('cl-loader-message')) {
                settings.clLoaderMessage = $btn.data('cl-loader-message');
            }

            ConductorLedger.setButtonLoading($btn, true, settings.clLoadingText);
        }

        if (ConductorLedger._ajaxLoaderCount === 1) {
            ConductorLedger._ajaxLoaderTimer = window.setTimeout(function () {
                ConductorLedger.showLoader(settings.clLoaderMessage || 'Cargando...');
            }, 300);

            ConductorLedger._ajaxLoaderSafetyTimer = window.setTimeout(function () {
                if (ConductorLedger._ajaxLoaderCount > 0) {
                    ConductorLedger.resetAjaxLoader();
                }
            }, 30000);
        }
    });

    $(document).ajaxComplete(function (event, xhr, settings) {
        ConductorLedger.finishAjaxLoader(settings);
    });

    $(document).ajaxError(function (event, xhr, settings) {
        ConductorLedger.finishAjaxLoader(settings);
    });
};

$(function () {
    ConductorLedger.setupAjaxLoader();
});
