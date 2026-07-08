$(function () {
    ConductorLedger.setupAjaxCsrf();

    var modalEl = document.getElementById('modalVehiculo');
    var modal = new bootstrap.Modal(modalEl);
    var editingId = null;

    ConductorLedger.initSelect2Paginated($('#selectOwnership'), {
        url: APLICATIVO_API.VEHICULOS.GET.SELECT2,
        placeholder: 'Buscar tipo...',
        dropdownParent: $('#modalVehiculo')
    });

    var $rentalFields = $('#rentalFields');
    var $rentalFee = $('input[name="rental_fee_daily"]');
    var $rentalPeriod = $('select[name="rental_period"]');
    var $activeField = $('#activeField');

    function isRentedSelection() {
        var data = $('#selectOwnership').select2('data');
        if (data && data[0]) {
            if (typeof data[0].is_rented !== 'undefined') {
                return !!data[0].is_rented;
            }

            return (data[0].text || '').toUpperCase().indexOf('ALQUILADO') >= 0;
        }

        return false;
    }

    function toggleRentalFields(forceRented) {
        var isRented = typeof forceRented === 'boolean' ? forceRented : isRentedSelection();

        if (isRented) {
            $rentalFields.show();
            $rentalFee.prop('required', true);
            $rentalPeriod.prop('required', true);
        } else {
            $rentalFields.hide();
            $rentalFee.prop('required', false).val(0);
            $rentalPeriod.prop('required', false).val('daily');
        }
    }

    $('#selectOwnership').on('change select2:select', toggleRentalFields);

    function openCreate() {
        editingId = null;
        $('#modalVehiculoTitle').text('Registrar Vehículo');
        $('#vehicleId').val('');
        $('#formVehiculo')[0].reset();
        $('#selectOwnership').val(null).trigger('change');
        $activeField.hide();
        toggleRentalFields();
        modal.show();
    }

    $('#btnNuevoVehiculo').on('click', openCreate);

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
            { data: 'is_active' },
            {
                data: null,
                orderable: false,
                render: function () {
                    return '<button type="button" class="btn btn-sm btn-primary btn-edit"><i class="fa-solid fa-pen"></i></button>';
                }
            }
        ]
    }));

    $('#tblVehiculos').on('click', '.btn-edit', function () {
        var row = table.row($(this).closest('tr')).data();
        editingId = row.id;

        $('#modalVehiculoTitle').text('Editar Vehículo');
        $('#vehicleId').val(row.id);
        $('input[name="plate_number"]').val(row.plate_number);
        $('select[name="is_active"]').val(row.is_active_bool ? '1' : '0');
        $('select[name="rental_period"]').val(row.rental_period_code);
        $rentalFee.val(row.rental_fee_raw);

        var option = new Option(row.ownership_type, row.ownership_type_id, true, true);
        $('#selectOwnership').empty().append(option).trigger('change');

        $activeField.show();
        toggleRentalFields(!!row.ownership_is_rented);
        modal.show();
    });

    $('#formVehiculo').on('submit', function (e) {
        e.preventDefault();

        if (!ConductorLedger.validateMoneyForm($(this))) {
            ConductorLedger.showAlert('La cuota de alquiler no puede ser negativa.', 'danger');
            return;
        }

        if (isRentedSelection() && parseFloat($rentalFee.val() || '0') <= 0) {
            ConductorLedger.showAlert('Ingrese la cuota de alquiler para vehículos ALQUILADO.', 'danger');
            return;
        }

        var data = $(this).serializeArray();
        var payload = {};
        data.forEach(function (item) { payload[item.name] = item.value; });

        if (editingId) {
            payload.is_active = payload.is_active === '1';
        } else {
            delete payload.is_active;
        }

        var request = editingId
            ? $.ajax({
                url: APLICATIVO_API.VEHICULOS.PUT.UPDATE + '/' + editingId,
                method: 'PUT',
                data: payload
            })
            : $.post(APLICATIVO_API.VEHICULOS.POST.STORE, payload);

        request.done(function (res) {
            ConductorLedger.showAlert(res.message, 'success');
            modal.hide();
            table.ajax.reload(null, false);
        }).fail(function (xhr) {
            var msg = xhr.responseJSON?.message || 'Error al guardar.';
            if (xhr.responseJSON?.errors) {
                msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
            }
            ConductorLedger.showAlert(msg, 'danger');
        });
    });
});
