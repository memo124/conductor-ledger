window.ConductorLedger = window.ConductorLedger || {};

ConductorLedger.Money = {
    config: function () {
        return window.CL_MONEY_CONFIG || {
            baseCurrency: 'USD',
            currency: 'USD',
            locale: 'es-SV',
            rates: { USD: 1 },
            decimalPlaces: 2
        };
    },

    rate: function (currency) {
        var cfg = this.config();
        var code = (currency || cfg.currency || 'USD').toUpperCase();
        var rates = cfg.rates || {};

        return typeof rates[code] === 'number' ? rates[code] : 1;
    },

    convertFromBase: function (amountUsd, currency) {
        var value = parseFloat(amountUsd);

        if (isNaN(value)) {
            return 0;
        }

        return value * this.rate(currency);
    },

    fractionDigits: function (currency) {
        var cfg = this.config();
        var code = (currency || cfg.currency || 'USD').toUpperCase();
        var map = cfg.decimalPlacesMap || {};

        if (typeof map[code] === 'number') {
            return map[code];
        }

        return cfg.decimalPlaces || 2;
    },

    formatFromBase: function (amountUsd, options) {
        options = options || {};
        var cfg = this.config();
        var currency = (options.currency || cfg.currency || 'USD').toUpperCase();
        var locale = options.locale || cfg.locale || 'es-SV';
        var converted = this.convertFromBase(amountUsd, currency);
        var digits = this.fractionDigits(currency);

        try {
            return new Intl.NumberFormat(locale, {
                style: 'currency',
                currency: currency,
                minimumFractionDigits: digits,
                maximumFractionDigits: digits
            }).format(converted);
        } catch (e) {
            return currency + ' ' + converted.toFixed(digits);
        }
    },

    formatDisplay: function (value) {
        var parsed = parseFloat(String(value).replace(/[^\d.-]/g, ''));

        if (isNaN(parsed)) {
            return this.formatFromBase(0);
        }

        return this.formatFromBase(parsed);
    },

    updateConfig: function (partial) {
        window.CL_MONEY_CONFIG = $.extend({}, this.config(), partial || {});
    }
};

ConductorLedger.DisplayPreferences = {
    STORAGE_LOCALE: 'cl_locale_preference',

    getLocale: function () {
        var server = document.body.getAttribute('data-user-locale');
        if (server) {
            return server;
        }

        try {
            return localStorage.getItem(this.STORAGE_LOCALE) || 'es';
        } catch (e) {
            return 'es';
        }
    },

    getCurrency: function () {
        return document.body.getAttribute('data-user-currency')
            || (window.CL_MONEY_CONFIG && window.CL_MONEY_CONFIG.currency)
            || 'USD';
    },

    applyLocale: function (locale) {
        document.documentElement.setAttribute('lang', locale);
    },

    setLocale: function (locale, options) {
        options = options || {};
        locale = locale || this.getLocale();

        try {
            localStorage.setItem(this.STORAGE_LOCALE, locale);
        } catch (e) {
            // ignore storage errors
        }

        if (document.body.hasAttribute('data-user-locale')) {
            document.body.setAttribute('data-user-locale', locale);
        }

        this.applyLocale(locale);

        if (options.persistToServer !== false
            && document.body.hasAttribute('data-user-locale')
            && typeof APLICATIVO_API !== 'undefined') {
            ConductorLedger.setupAjaxCsrf();
            $.ajax({
                url: APLICATIVO_API.AUTHENTICATION.POST.UPDATE_LOCALE,
                method: 'POST',
                data: { locale_preference: locale },
                clSilent: true
            });
        }

        if (options.reload) {
            window.location.reload();
        }
    },

    init: function () {
        var self = this;
        var locale = this.getLocale();
        var currency = this.getCurrency();

        this.applyLocale(locale);
        ConductorLedger.Money.updateConfig({
            currency: currency,
            locale: locale === 'en' ? 'en-US' : 'es-SV'
        });

        if (typeof ConductorLedger.I18n !== 'undefined') {
            ConductorLedger.I18n.applyDocument();
        }

        var $locale = $('#selectLocalePreference');
        if ($locale.length) {
            $locale.val(locale);
            $locale.on('change', function () {
                self.setLocale($(this).val(), { reload: true });
            });
        }
    }
};

$(function () {
    ConductorLedger.DisplayPreferences.init();
});
