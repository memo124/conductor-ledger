$(function () {
    ConductorLedger.setupAjaxCsrf();

    var loadedCategories = {};

    function formatRate(value, code) {
        var decimalsMap = (window.CL_MONEY_CONFIG && window.CL_MONEY_CONFIG.decimalPlacesMap) || {};
        var decimals = decimalsMap[code] || 8;
        return parseFloat(value).toLocaleString(undefined, {
            minimumFractionDigits: Math.min(decimals, 4),
            maximumFractionDigits: decimals
        });
    }

    function runConvert() {
        var from = $('#selectFrom').val();
        var to = $('#selectTo').val();
        var amount = parseFloat($('#inputAmount').val()) || 0;

        $.get(APLICATIVO_API.CONVERSOR.GET.CONVERT, {
            from: from,
            to: to,
            amount: amount
        }).done(function (res) {
            if (!res.success) return;
            var d = res.data;
            $('#convertResult').show();
            $('#resultValue').text(d.result_formatted);
            $('#resultRate').text(ConductorLedger.I18n.t('pages.conversor.rate_label', {
                from: d.from,
                to: d.to,
                rate: formatRate(d.rate, d.to)
            }));
        }).fail(function (xhr) {
            ConductorLedger.showAlert(xhr.responseJSON?.message || ConductorLedger.I18n.t('pages.conversor.convert_error'), 'danger');
        });
    }

    function loadRates(category) {
        if (loadedCategories[category]) {
            return;
        }

        var $table = $('table[data-rates-table="' + category + '"] tbody');
        $table.html('<tr><td colspan="4" class="text-center text-muted">' + ConductorLedger.I18n.t('datatables.loading_records') + '</td></tr>');

        $.get(APLICATIVO_API.CONVERSOR.GET.RATES, { category: category }).done(function (res) {
            if (!res.success) return;
            loadedCategories[category] = true;

            if (!res.data.length) {
                $table.html('<tr><td colspan="4" class="text-center text-muted">' + ConductorLedger.I18n.t('common.no_data') + '</td></tr>');
                return;
            }

            var rows = res.data.map(function (pair) {
                return '<tr>' +
                    '<td><strong>' + pair.from + '</strong></td>' +
                    '<td><strong>' + pair.to + '</strong></td>' +
                    '<td class="text-end">' + formatRate(pair.rate, pair.to) + '</td>' +
                    '<td class="text-end">' + formatRate(pair.inverse, pair.from) + '</td>' +
                    '</tr>';
            }).join('');

            $table.html(rows);
        });
    }

    $('#formConvertidor').on('submit', function (e) {
        e.preventDefault();
        runConvert();
    });

    $('#btnSwapCurrencies').on('click', function () {
        var from = $('#selectFrom').val();
        var to = $('#selectTo').val();
        $('#selectFrom').val(to);
        $('#selectTo').val(from);
        runConvert();
    });

    $('#selectFrom, #selectTo, #inputAmount').on('change input', function () {
        if ($('#inputAmount').val()) {
            runConvert();
        }
    });

    $('button[data-category]').on('shown.bs.tab', function () {
        loadRates($(this).data('category'));
    });

    loadRates('fiat_fiat');
    runConvert();
});
