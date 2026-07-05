$(function () {
    ConductorLedger.setupAjaxCsrf();

    var meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    var comparativaTable = null;

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
                var data = (res.data || []).map(function (row) {
                    var ingresos = parseFloat(row.total_indrive) + parseFloat(row.total_otros) + parseFloat(row.total_propinas);
                    return {
                        mes: meses[row.mes],
                        indrive: parseFloat(row.total_indrive).toFixed(2),
                        otros: parseFloat(row.total_otros).toFixed(2),
                        propinas: parseFloat(row.total_propinas).toFixed(2),
                        alquiler: parseFloat(row.total_alquiler).toFixed(2),
                        total: ingresos.toFixed(2)
                    };
                });

                if (comparativaTable) {
                    comparativaTable.clear().rows.add(data).draw();
                    return;
                }

                comparativaTable = $('#tblComparativa').DataTable($.extend(true, {}, ConductorLedger.simpleDataTableOptions, {
                    data: data,
                    columns: [
                        { data: 'mes' },
                        {
                            data: 'indrive',
                            className: 'text-income',
                            render: function (d) { return '$' + d; }
                        },
                        {
                            data: 'otros',
                            className: 'text-income',
                            render: function (d) { return '$' + d; }
                        },
                        {
                            data: 'propinas',
                            className: 'text-income',
                            render: function (d) { return '$' + d; }
                        },
                        {
                            data: 'alquiler',
                            className: 'text-expense',
                            render: function (d) { return '$' + d; }
                        },
                        {
                            data: 'total',
                            className: 'text-income',
                            render: function (d) { return '<strong>$' + d + '</strong>'; }
                        }
                    ]
                }));
            });
    }

    cargarResumen();
    cargarComparativa();

    setInterval(function () {
        $.get(APLICATIVO_API.AUTHENTICATION.GET.ACTUALIZAR_SESION);
    }, 300000);
});
