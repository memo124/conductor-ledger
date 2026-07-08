window.ConductorLedger = window.ConductorLedger || {};

ConductorLedger.Theme = {
    STORAGE_KEY: 'cl_theme_preference',
    DARK_START_HOUR: 19,
    LIGHT_START_HOUR: 6,

    resolveEffective: function (preference) {
        if (preference === 'dark') {
            return 'dark';
        }
        if (preference === 'light') {
            return 'light';
        }

        var hour = new Date().getHours();
        return (hour >= this.DARK_START_HOUR || hour < this.LIGHT_START_HOUR) ? 'dark' : 'light';
    },

    apply: function (theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.style.colorScheme = theme;
        document.documentElement.style.backgroundColor = theme === 'dark' ? '#0d0f12' : '#f0f2f5';
    },

    getPreference: function () {
        var serverPref = document.body.getAttribute('data-user-theme');
        if (serverPref) {
            return serverPref;
        }

        try {
            return localStorage.getItem(this.STORAGE_KEY) || 'auto';
        } catch (e) {
            return 'auto';
        }
    },

    setPreference: function (preference, options) {
        options = options || {};
        preference = preference || 'auto';

        try {
            localStorage.setItem(this.STORAGE_KEY, preference);
        } catch (e) {
            // ignore storage errors
        }

        if (document.body.hasAttribute('data-user-theme')) {
            document.body.setAttribute('data-user-theme', preference);
        }

        this.apply(this.resolveEffective(preference));

        if (options.persistToServer !== false
            && document.body.hasAttribute('data-user-theme')
            && typeof APLICATIVO_API !== 'undefined') {
            ConductorLedger.setupAjaxCsrf();
            $.ajax({
                url: APLICATIVO_API.AUTHENTICATION.POST.UPDATE_THEME,
                method: 'POST',
                data: { theme_preference: preference },
                clSilent: true
            });
        }
    },

    init: function () {
        var self = this;
        var preference = this.getPreference();
        this.apply(this.resolveEffective(preference));

        var $select = $('#selectThemePreference');
        if ($select.length) {
            $select.val(preference);
            $select.on('change', function () {
                self.setPreference($(this).val());
            });
        }

        setInterval(function () {
            if (self.getPreference() === 'auto') {
                self.apply(self.resolveEffective('auto'));
            }
        }, 60000);
    }
};

$(function () {
    ConductorLedger.Theme.init();
});
