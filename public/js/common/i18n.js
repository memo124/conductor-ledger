window.ConductorLedger = window.ConductorLedger || {};

ConductorLedger.I18n = {
    messages: function () {
        return window.CL_I18N || {};
    },

    t: function (key, replace) {
        replace = replace || {};
        var parts = key.split('.');
        var value = this.messages();

        for (var i = 0; i < parts.length; i++) {
            if (value && Object.prototype.hasOwnProperty.call(value, parts[i])) {
                value = value[parts[i]];
            } else {
                return key;
            }
        }

        if (typeof value !== 'string') {
            return key;
        }

        Object.keys(replace).forEach(function (token) {
            value = value.replace(':' + token, replace[token]);
        });

        return value;
    },

    monthName: function (monthNumber) {
        return this.t('months.' + String(monthNumber));
    },

    datatablesLanguage: function () {
        return {
            processing: '<div class="cl-dt-processing"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + this.t('datatables.processing') + '</div>',
            emptyTable: this.t('datatables.empty_table'),
            info: this.t('datatables.info'),
            infoEmpty: this.t('datatables.info_empty'),
            infoFiltered: this.t('datatables.info_filtered'),
            lengthMenu: this.t('datatables.length_menu'),
            loadingRecords: this.t('datatables.loading_records'),
            search: this.t('datatables.search'),
            zeroRecords: this.t('datatables.zero_records'),
            paginate: {
                first: this.t('datatables.paginate_first'),
                last: this.t('datatables.paginate_last'),
                next: this.t('datatables.paginate_next'),
                previous: this.t('datatables.paginate_previous')
            },
            aria: {
                sortAscending: this.t('datatables.sort_asc'),
                sortDescending: this.t('datatables.sort_desc')
            }
        };
    },

    simpleDatatablesLanguage: function () {
        return {
            emptyTable: this.t('datatables.no_data'),
            zeroRecords: this.t('datatables.no_data')
        };
    },

    applyDocument: function () {
        document.querySelectorAll('[data-i18n]').forEach(function (el) {
            var key = el.getAttribute('data-i18n');
            var text = ConductorLedger.I18n.t(key);
            if (text !== key) {
                el.textContent = text;
            }
        });

        document.querySelectorAll('[data-i18n-placeholder]').forEach(function (el) {
            var key = el.getAttribute('data-i18n-placeholder');
            var text = ConductorLedger.I18n.t(key);
            if (text !== key) {
                el.setAttribute('placeholder', text);
            }
        });
    }
};

ConductorLedger.moneyColumn = function () {
    return {
        render: function (value) {
            if (value === null || value === undefined || value === '') {
                return ConductorLedger.Money.formatFromBase(0);
            }

            return ConductorLedger.Money.formatFromBase(parseFloat(value));
        }
    };
};
