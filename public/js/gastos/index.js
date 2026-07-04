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

    var table = $('#tblGastos').DataTable($.extend(true, {}, ConductorLedger.defaultDataTableOptions, {
        ajax: {
            url: APLICATIVO_API.GASTOS.GET.DATATABLE,
            type: 'GET'
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
