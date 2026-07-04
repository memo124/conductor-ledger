$(function () {
    ConductorLedger.setupAjaxCsrf();

    var meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    function cargarResumen() {
        $.get(APLICATIVO_API.DASHBOARD.GET.RESUMEN)
            .done(function (res) {
                if (!res.success) return;
                var d = res.data;
                $('#statIngresos').text('$' + d.ingresos);
                $('#statAlquiler').text('$' + d.alquiler);
                $('#statGastos').text('$' + d.gastos);
                $('#statNeto').text('$' + d.neto);
            });
    }

    function cargarComparativa() {
        $.get(APLICATIVO_API.VIAJES.GET.COMPARATIVA_MENSUAL, { anio: new Date().getFullYear() })
            .done(function (res) {
                var html = '';
                (res.data || []).forEach(function (row) {
                    var ingresos = parseFloat(row.total_indrive) + parseFloat(row.total_otros) + parseFloat(row.total_propinas);
                    html += '<tr>' +
                        '<td>' + meses[row.mes] + '</td>' +
                        '<td class="text-income">$' + parseFloat(row.total_indrive).toFixed(2) + '</td>' +
                        '<td class="text-income">$' + parseFloat(row.total_otros).toFixed(2) + '</td>' +
                        '<td class="text-income">$' + parseFloat(row.total_propinas).toFixed(2) + '</td>' +
                        '<td class="text-expense">$' + parseFloat(row.total_alquiler).toFixed(2) + '</td>' +
                        '<td class="text-income"><strong>$' + ingresos.toFixed(2) + '</strong></td>' +
                        '</tr>';
                });
                $('#tbodyComparativa').html(html || '<tr><td colspan="6" class="text-muted">Sin datos</td></tr>');
            });
    }

    cargarResumen();
    cargarComparativa();

    setInterval(function () {
        $.get(APLICATIVO_API.AUTHENTICATION.GET.ACTUALIZAR_SESION);
    }, 300000);
});
