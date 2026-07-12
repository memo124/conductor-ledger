$(function () {
    ConductorLedger.setupAjaxCsrf();

    ConductorLedger.initSelect2Paginated($('#selectCategory'), {
        url: APLICATIVO_API.GASTOS.GET.SELECT2_CATEGORIES,
        placeholder: 'Buscar categoría...',
        dropdownParent: $('#modalNuevoGasto')
    });

    ConductorLedger.initSelect2Paginated($('#selectVehicleGasto'), {
        url: APLICATIVO_API.GASTOS.GET.SELECT2_VEHICLES,
        placeholder: 'Buscar vehículo (opcional)...',
        dropdownParent: $('#modalNuevoGasto')
    });

    ConductorLedger.initSelect2Paginated($('#filterVehicle'), {
        url: APLICATIVO_API.GASTOS.GET.SELECT2_VEHICLES,
        placeholder: 'Todos los vehículos...',
        allowClear: true
    });

    function filterParams() {
        return {
            fecha_desde: $('#filterFechaDesde').val(),
            fecha_hasta: $('#filterFechaHasta').val(),
            category_id: $('#filterCategory').val(),
            vehicle_id: $('#filterVehicle').val()
        };
    }

    function updateTotals(totals) {
        if (!totals) {
            $('#gastosTotals').hide();
            return;
        }
        $('#gastosTotals').show();
        $('#totalMonto').text('$' + totals.monto);
    }

    var table = $('#tblGastos').DataTable($.extend(true, {}, ConductorLedger.defaultDataTableOptions, {
        ajax: {
            url: APLICATIVO_API.GASTOS.GET.DATATABLE,
            type: 'GET',
            data: function (d) {
                return $.extend({}, d, filterParams());
            },
            dataSrc: function (json) {
                updateTotals(json.totals);
                return json.data;
            }
        },
        order: [[1, 'desc']],
        columns: [
            { data: 'expense_number' },
            { data: 'fecha' },
            { data: 'categoria' },
            { data: 'vehicle' },
            { data: 'monto', className: 'text-expense' },
            { data: 'descripcion' }
        ]
    }));

    $('#formFiltrosGastos').on('submit', function (e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#formNuevoGasto').on('submit', function (e) {
        e.preventDefault();
        if (!ConductorLedger.validateMoneyForm($(this))) {
            ConductorLedger.showAlert('El monto no puede ser negativo.', 'danger');
            return;
        }

        $.post(APLICATIVO_API.GASTOS.POST.STORE, $(this).serialize())
            .done(function (res) {
                ConductorLedger.showAlert(res.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalNuevoGasto')).hide();
                $('#formNuevoGasto')[0].reset();
                $('#selectCategory, #selectVehicleGasto').val(null).trigger('change');
                table.ajax.reload(null, false);
            })
            .fail(function (xhr) {
                var msg = 'Error al guardar.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                ConductorLedger.showAlert(msg, 'danger');
            });
    });
});
