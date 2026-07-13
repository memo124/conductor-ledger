$(function () {
    ConductorLedger.setupAjaxCsrf();

    var chartMensual = null;
    var chartPlataformas = null;

    function loadMetrics() {
        var anio = $('#selectAnio').val();
        $.get(APLICATIVO_API.GRAFICOS.GET.METRICS, { anio: anio }).done(function (res) {
            if (!res.success) return;
            var d = res.data;

            $('#totalIngresos').text(ConductorLedger.Money.formatFromBase(d.totals.ingresos));
            $('#totalAlquiler').text(ConductorLedger.Money.formatFromBase(d.totals.alquiler));
            $('#totalGastos').text(ConductorLedger.Money.formatFromBase(d.totals.gastos));
            $('#totalNeto').text(ConductorLedger.Money.formatFromBase(d.totals.neto));

            if (chartMensual) chartMensual.destroy();
            chartMensual = new Chart(document.getElementById('chartMensual'), {
                type: 'line',
                data: {
                    labels: d.meses,
                    datasets: [
                        { label: ConductorLedger.I18n.t('pages.graficos.chart_income'), data: d.ingresos, borderColor: '#22c55e', tension: 0.3 },
                        { label: ConductorLedger.I18n.t('pages.graficos.chart_expenses'), data: d.gastos, borderColor: '#ef4444', tension: 0.3 },
                        { label: ConductorLedger.I18n.t('pages.graficos.chart_net'), data: d.netos, borderColor: '#3b82f6', tension: 0.3 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: true }
            });

            if (chartPlataformas) chartPlataformas.destroy();
            chartPlataformas = new Chart(document.getElementById('chartPlataformas'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(d.plataformas),
                    datasets: [{
                        data: Object.values(d.plataformas),
                        backgroundColor: ['#22c55e', '#06b6d4', '#f59e0b']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true }
            });
        });
    }

    $('#selectAnio').on('change', loadMetrics);
    loadMetrics();
});
