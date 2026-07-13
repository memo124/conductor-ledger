$(function () {
    ConductorLedger.setupAjaxCsrf();

    var comparativaTable = null;

    function cargarResumen() {
        $.get(APLICATIVO_API.DASHBOARD.GET.RESUMEN)
            .done(function (res) {
                if (!res.success) return;
                var d = res.data;
                $('#statIngresos').text(ConductorLedger.Money.formatFromBase(d.ingresos));
                $('#statAlquiler').text(ConductorLedger.Money.formatFromBase(d.alquiler));
                $('#statGastos').text(ConductorLedger.Money.formatFromBase(d.gastos));
                $('#statNeto').text(ConductorLedger.Money.formatFromBase(d.neto));
            });
    }

    function cargarComparativa() {
        $.get(APLICATIVO_API.VIAJES.GET.COMPARATIVA_MENSUAL, { anio: new Date().getFullYear() })
            .done(function (res) {
                var data = (res.data || []).map(function (row) {
                    var ingresos = parseFloat(row.total_ingresos || 0);
                    return {
                        mes: ConductorLedger.I18n.monthName(row.mes),
                        ingresos: ingresos,
                        comision: parseFloat(row.total_comision || 0),
                        propinas: parseFloat(row.total_propinas || 0),
                        alquiler: parseFloat(row.total_alquiler || 0),
                        total: ingresos
                    };
                });

                if (comparativaTable) {
                    comparativaTable.clear().rows.add(data).draw();
                    return;
                }

                comparativaTable = $('#tblComparativa').DataTable($.extend(true, {}, ConductorLedger.buildSimpleDataTableOptions(), {
                    data: data,
                    columns: [
                        { data: 'mes' },
                        $.extend({ data: 'ingresos', className: 'text-income' }, ConductorLedger.moneyColumn()),
                        $.extend({ data: 'comision', className: 'text-expense' }, ConductorLedger.moneyColumn()),
                        $.extend({ data: 'propinas', className: 'text-income' }, ConductorLedger.moneyColumn()),
                        $.extend({ data: 'alquiler', className: 'text-expense' }, ConductorLedger.moneyColumn()),
                        $.extend({ data: 'total', className: 'text-income' }, ConductorLedger.moneyColumn())
                    ]
                }));
            });
    }

    cargarResumen();
    cargarComparativa();

    setInterval(function () {
        $.ajax({
            url: APLICATIVO_API.AUTHENTICATION.GET.ACTUALIZAR_SESION,
            method: 'GET',
            clSilent: true
        });
    }, 300000);
});
