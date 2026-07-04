$(function () {
    ConductorLedger.setupAjaxCsrf();

    ConductorLedger.initSelect2Paginated($('#selectOwnership'), {
        url: APLICATIVO_API.VEHICULOS.GET.SELECT2,
        placeholder: 'Buscar tipo...',
        dropdownParent: $('#modalNuevoVehiculo')
    });

    var $rentalFields = $('#rentalFields');
    var $rentalFee = $('input[name="rental_fee_daily"]');

    function toggleRentalFields() {
        var data = $('#selectOwnership').select2('data');
        var text = (data && data[0] ? data[0].text : '').toUpperCase();
        if (text.indexOf('ALQUILADO') >= 0) {
            $rentalFields.show();
        } else {
            $rentalFields.hide();
            $rentalFee.val(0);
            $('select[name="rental_period"]').val('daily');
        }
    }

    $('#selectOwnership').on('change', toggleRentalFields);
    toggleRentalFields();

    var table = $('#tblVehiculos').DataTable($.extend(true, {}, ConductorLedger.defaultDataTableOptions, {
        ajax: {
            url: APLICATIVO_API.VEHICULOS.GET.DATATABLE,
            type: 'GET'
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'id' },
            { data: 'plate_number' },
            { data: 'ownership_type' },
            { data: 'rental_fee_daily' },
            { data: 'rental_period' },
            { data: 'is_active' }
        ]
    }));

    $('#formNuevoVehiculo').on('submit', function (e) {
        e.preventDefault();
        if (!ConductorLedger.validateMoneyForm($(this))) {
            ConductorLedger.showAlert('La cuota de alquiler no puede ser negativa.', 'danger');
            return;
        }

        $.post(APLICATIVO_API.VEHICULOS.POST.STORE, $(this).serialize())
            .done(function (res) {
                ConductorLedger.showAlert(res.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalNuevoVehiculo')).hide();
                $('#formNuevoVehiculo')[0].reset();
                $('#selectOwnership').val(null).trigger('change');
                toggleRentalFields();
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
