$(function () {
    ConductorLedger.setupAjaxCsrf();

    ConductorLedger.initSelect2Paginated($('#selectVehicle'), {
        url: APLICATIVO_API.VIAJES.GET.SELECT2,
        placeholder: 'Buscar vehículo...',
        dropdownParent: $('#modalNuevoViaje')
    });

    var $alquiler = $('input[name="alquiler"]');
    var $suggestion = $('#rentalSuggestion');

    function updateRentalSuggestion() {
        var vehicleId = $('#selectVehicle').val();
        var fecha = $('input[name="fecha"]').val();

        if (!vehicleId) {
            $alquiler.val(0).prop('readonly', true);
            $suggestion.text('');
            return;
        }

        $.get(APLICATIVO_API.VIAJES.GET.RENTAL_SUGGESTION, {
            vehicle_id: vehicleId,
            fecha: fecha
        }).done(function (res) {
            if (!res.success) return;
            var data = res.data;

            if (data.alquiler_editable) {
                $alquiler.prop('readonly', false).val(data.suggested_alquiler.toFixed(2));
                $suggestion.text('Sugerido: $' + data.suggested_alquiler.toFixed(2) +
                    ' (cuota ' + data.rental_period_label + ' $' + data.rental_fee.toFixed(2) + ')');
            } else {
                $alquiler.val(0).prop('readonly', true);
                $suggestion.text('Vehículo ' + data.ownership_type + ': el alquiler no aplica.');
            }
        });
    }

    $('#selectVehicle, input[name="fecha"]').on('change', updateRentalSuggestion);

    var table = $('#tblViajes').DataTable($.extend(true, {}, ConductorLedger.defaultDataTableOptions, {
        ajax: {
            url: APLICATIVO_API.VIAJES.GET.DATATABLE,
            type: 'GET'
        },
        order: [[1, 'desc']],
        columns: [
            { data: 'trip_number' },
            { data: 'fecha' },
            { data: 'dia_semana' },
            { data: 'vehicle' },
            { data: 'indrive', className: 'text-income' },
            { data: 'otros_viajes', className: 'text-income' },
            { data: 'propina', className: 'text-income' },
            { data: 'alquiler', className: 'text-expense' },
            { data: 'ingresos', className: 'text-income' },
            { data: 'neto', className: 'text-primary' }
        ]
    }));

    $('#formNuevoViaje').on('submit', function (e) {
        e.preventDefault();
        if (!ConductorLedger.validateMoneyForm($(this))) {
            ConductorLedger.showAlert('Los montos no pueden ser negativos.', 'danger');
            return;
        }

        $.post(APLICATIVO_API.VIAJES.POST.STORE, $(this).serialize())
            .done(function (res) {
                ConductorLedger.showAlert(res.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalNuevoViaje')).hide();
                $('#formNuevoViaje')[0].reset();
                $('#selectVehicle').val(null).trigger('change');
                $alquiler.prop('readonly', true).val(0);
                $suggestion.text('');
                table.ajax.reload(null, false);
            })
            .fail(function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Error al guardar el viaje.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                ConductorLedger.showAlert(msg, 'danger');
            });
    });
});
