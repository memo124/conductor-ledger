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
    var $rentalPeriodLabel = $('#rentalPeriodLabel');
    var $rentalFeeLabel = $('#rentalFeeLabel');
    var $rentalFeeHint = $('#rentalFeeHint');
    var $quotaPercentage = $('input[name="quota_percentage"]');
    var $quotaReserve = $('input[name="quota_reserve_amount"]');

    var FEE_TYPES = ['ALQUILADO', 'FINANCIADO'];

    function selectedOwnershipType() {
        var data = $('#selectOwnership').select2('data');
        if (!data || !data[0]) {
            return { name: '', requiresFee: false };
        }

        var name = (data[0].text || '').toUpperCase();
        var requiresFee = typeof data[0].requires_fee !== 'undefined'
            ? !!data[0].requires_fee
            : (typeof data[0].is_rented !== 'undefined'
                ? !!data[0].is_rented
                : FEE_TYPES.indexOf(name) >= 0);

        return { name: name, requiresFee: requiresFee };
    }

    function updateFeeLabels(typeName) {
        if (typeName === 'FINANCIADO') {
            $rentalPeriodLabel.text('Periodo de cuota');
            $rentalFeeLabel.text('Cuota de financiamiento ($)');
            $rentalFeeHint.text('Monto según el periodo seleccionado. En viajes se sugerirá el costo diario equivalente.');
            return;
        }

        $rentalPeriodLabel.text('Periodo de alquiler');
        $rentalFeeLabel.text('Cuota de alquiler ($)');
        $rentalFeeHint.text('Monto según el periodo seleccionado. En viajes se sugerirá la cuota diaria equivalente.');
    }

    function toggleRentalFields(forceRequiresFee) {
        var selection = selectedOwnershipType();
        var requiresFee = typeof forceRequiresFee === 'boolean' ? forceRequiresFee : selection.requiresFee;

        if (requiresFee) {
            updateFeeLabels(selection.name);
            $rentalFields.show();
            $rentalFee.prop({ disabled: false, min: 0 });
            $rentalPeriod.prop({ disabled: false });
            if (!$rentalFee.val() || parseFloat($rentalFee.val()) <= 0) {
                $rentalFee.val('');
            }
            return;
        }

        $rentalFields.hide();
        $rentalFee.prop({ disabled: true, min: 0 }).val(0);
        $rentalPeriod.prop({ disabled: true }).val('daily');
    }

    $('#selectOwnership').on('change select2:select', function () {
        toggleRentalFields();
    });

    function openCreate() {
        editingId = null;
        $('#modalVehiculoTitle').text('Registrar Vehículo');
        $('#vehicleId').val('');
        $('#formVehiculo')[0].reset();
        $('#selectOwnership').val(null).trigger('change');
        $quotaPercentage.val(0);
        $quotaReserve.val(0);
        $activeField.hide();
        toggleRentalFields(false);
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
            { data: 'alias' },
            { data: 'vehicle_kind' },
            {
                data: null,
                render: function (row) {
                    return [row.brand !== '—' ? row.brand : '', row.model !== '—' ? row.model : '', row.model_year !== '—' ? row.model_year : ''].filter(Boolean).join(' ') || '—';
                }
            },
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
        $('input[name="alias"]').val(row.alias);
        $('select[name="vehicle_kind"]').val(row.vehicle_kind_code);
        $('input[name="brand"]').val(row.brand_raw || '');
        $('input[name="model"]').val(row.model_raw || '');
        $('input[name="model_year"]').val(row.model_year_raw || '');
        $('input[name="color"]').val(row.color_raw || '');
        $('textarea[name="notes"]').val(row.notes_raw || '');
        $('select[name="is_active"]').val(row.is_active_bool ? '1' : '0');

        var requiresFee = !!(row.ownership_requires_fee || row.ownership_is_rented);
        var option = new Option(row.ownership_type, row.ownership_type_id, true, true);
        $('#selectOwnership').empty().append(option).val(row.ownership_type_id).trigger('change.select2');

        $activeField.show();
        toggleRentalFields(requiresFee);

        if (requiresFee) {
            $rentalPeriod.val(row.rental_period_code);
            $rentalFee.val(row.rental_fee_raw > 0 ? row.rental_fee_raw : '');
        }

        $quotaPercentage.val(row.quota_percentage_raw ?? 0);
        $quotaReserve.val(row.quota_reserve_raw ?? 0);
        modal.show();
    });

    $('#formVehiculo').on('submit', function (e) {
        e.preventDefault();

        var selection = selectedOwnershipType();

        if (selection.requiresFee) {
            var fee = parseFloat($rentalFee.val() || '0');
            if (isNaN(fee) || fee <= 0) {
                ConductorLedger.showAlert(
                    selection.name === 'FINANCIADO'
                        ? 'Ingrese la cuota de financiamiento para vehículos FINANCIADO.'
                        : 'Ingrese la cuota de alquiler para vehículos ALQUILADO.',
                    'danger'
                );
                $rentalFee.focus();
                return;
            }

            if (!$rentalPeriod.val()) {
                ConductorLedger.showAlert('Seleccione el periodo de pago.', 'danger');
                $rentalPeriod.focus();
                return;
            }
        }

        if (selection.requiresFee && !ConductorLedger.validateMoneyForm($(this))) {
            ConductorLedger.showAlert('La cuota no puede ser negativa.', 'danger');
            return;
        }

        var data = $(this).serializeArray();
        var payload = {};
        data.forEach(function (item) { payload[item.name] = item.value; });

        if (!selection.requiresFee) {
            payload.rental_fee_daily = 0;
            payload.rental_period = 'daily';
        }

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
