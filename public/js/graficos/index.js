$(function () {
    ConductorLedger.setupAjaxCsrf();

    var chartMensual = null;
    var chartPlataformas = null;

    function loadMetrics() {
        var anio = $('#selectAnio').val();
        $.get(APLICATIVO_API.GRAFICOS.GET.METRICS, { anio: anio }).done(function (res) {
            if (!res.success) return;
            var d = res.data;

            $('#totalIngresos').text('$' + d.totals.ingresos.toFixed(2));
            $('#totalAlquiler').text('$' + d.totals.alquiler.toFixed(2));
            $('#totalGastos').text('$' + d.totals.gastos.toFixed(2));
            $('#totalNeto').text('$' + d.totals.neto.toFixed(2));

            if (chartMensual) chartMensual.destroy();
            chartMensual = new Chart(document.getElementById('chartMensual'), {
                type: 'line',
                data: {
                    labels: d.meses,
                    datasets: [
                        { label: 'Ingresos', data: d.ingresos, borderColor: '#22c55e', tension: 0.3 },
                        { label: 'Gastos', data: d.gastos, borderColor: '#ef4444', tension: 0.3 },
                        { label: 'Neto', data: d.netos, borderColor: '#3b82f6', tension: 0.3 }
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
