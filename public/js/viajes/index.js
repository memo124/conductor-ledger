$(function () {
    ConductorLedger.setupAjaxCsrf();

    var tripTypes = window.CL_TRIP_TYPES || [];
    var tripTypesById = {};
    tripTypes.forEach(function (t) { tripTypesById[t.id] = t; });

    ConductorLedger.initSelect2Paginated($('#selectVehicle'), {
        url: APLICATIVO_API.VIAJES.GET.SELECT2,
        placeholder: 'Buscar vehículo...',
        dropdownParent: $('#modalNuevoViaje')
    });

    ConductorLedger.initSelect2Paginated($('#filterVehicle'), {
        url: APLICATIVO_API.VIAJES.GET.SELECT2,
        placeholder: 'Todos los vehículos...',
        allowClear: true
    });

    var $alquiler = $('input[name="alquiler"]');
    var $suggestion = $('#rentalSuggestion');
    var $porcentajeCuota = $('input[name="porcentaje_cuota"]');

    function selectedTripType() {
        var id = parseInt($('#selectTripType').val(), 10);
        return tripTypesById[id] || null;
    }

    function selectedMode() {
        return $('input[name="registration_mode"]:checked').val() || '';
    }

    function isPlataforma() {
        var type = selectedTripType();
        return type && type.code === 'PLATAFORMA';
    }

    function updateModeRadios() {
        var type = selectedTripType();
        var $modeWrap = $('#fieldRegistrationMode');
        var $radios = $('input[name="registration_mode"]');

        if (!type) {
            $modeWrap.hide();
            return;
        }

        if (isPlataforma()) {
            $modeWrap.hide();
            $radios.filter('[value="daily"]').prop('checked', true);
            return;
        }

        var allowed = type.allowed_modes || [];
        $radios.each(function () {
            var $radio = $(this);
            var visible = allowed.indexOf($radio.val()) >= 0;
            $radio.closest('.form-check').toggle(visible);
            $radio.prop('disabled', !visible);
        });

        if (!$radios.filter(':checked:enabled').length) {
            $radios.filter(':enabled').first().prop('checked', true);
        }

        if (allowed.length <= 1) {
            $modeWrap.hide();
        } else {
            $modeWrap.show();
        }
    }

    function toggleFormFields() {
        var mode = selectedMode();
        var plataforma = isPlataforma();

        $('#fieldPlatform').toggle(plataforma);
        $('#fieldFecha').toggle(mode !== 'monthly');
        $('#fieldPeriod').toggle(mode === 'monthly');
        $('#fieldMontoBruto').toggle(mode === 'daily' || mode === 'monthly');
        $('#fieldComisionApp').toggle(mode === 'daily' || mode === 'monthly');
        $('#fieldMontoCobrado').toggle(mode === 'per_trip');
    }

    function amountFields() {
        var mode = selectedMode();
        return {
            monto_bruto: mode === 'per_trip' ? 0 : parseFloat($('input[name="monto_bruto"]').val() || '0'),
            comision_app: mode === 'per_trip' ? 0 : parseFloat($('input[name="comision_app"]').val() || '0'),
            monto_cobrado: mode === 'per_trip' ? parseFloat($('input[name="monto_cobrado"]').val() || '0') : 0
        };
    }

    function updateRentalSuggestion() {
        var vehicleId = $('#selectVehicle').val();
        var fecha = $('input[name="fecha"]').val();
        var mode = selectedMode();
        var amounts = amountFields();

        if (!vehicleId) {
            $alquiler.val(0).prop('readonly', true);
            $suggestion.text('');
            return;
        }

        $.get(APLICATIVO_API.VIAJES.GET.RENTAL_SUGGESTION, {
            vehicle_id: vehicleId,
            fecha: fecha,
            registration_mode: mode,
            monto_bruto: amounts.monto_bruto,
            comision_app: amounts.comision_app,
            monto_cobrado: amounts.monto_cobrado,
            porcentaje_cuota: parseFloat($porcentajeCuota.val() || '0')
        }).done(function (res) {
            if (!res.success) return;
            var data = res.data;

            if (typeof data.quota_percentage !== 'undefined' && !$porcentajeCuota.data('user-edited')) {
                $porcentajeCuota.val(data.quota_percentage);
            }

            if (data.alquiler_editable) {
                $alquiler.prop('readonly', false).val(data.suggested_alquiler.toFixed(2));
                $suggestion.text(
                    'Sugerido: $' + data.suggested_alquiler.toFixed(2) +
                    ' (cuota ' + data.rental_period_label + ' $' + data.rental_fee.toFixed(2) + ')'
                );
            } else {
                $alquiler.val(0).prop('readonly', true);
                $suggestion.text('Vehículo ' + (data.ownership_type || 'propio') + ': la cuota no aplica.');
            }
        });
    }

    function updateTotals(totals) {
        if (!totals) {
            $('#viajesTotals').hide();
            return;
        }
        $('#viajesTotals').show();
        $('#totalIngresos').text('$' + totals.ingresos);
        $('#totalComision').text('$' + totals.comision_app);
        $('#totalAlquiler').text('$' + totals.alquiler);
        $('#totalNeto').text('$' + totals.neto);
    }

    function filterParams() {
        return {
            fecha_desde: $('#filterFechaDesde').val(),
            fecha_hasta: $('#filterFechaHasta').val(),
            platform_id: $('#filterPlatform').val(),
            trip_type_id: $('#filterTripType').val(),
            registration_mode: $('#filterRegistrationMode').val(),
            vehicle_id: $('#filterVehicle').val()
        };
    }

    var table = $('#tblViajes').DataTable($.extend(true, {}, ConductorLedger.defaultDataTableOptions, {
        ajax: {
            url: APLICATIVO_API.VIAJES.GET.DATATABLE,
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
            { data: 'trip_number' },
            { data: 'fecha' },
            { data: 'dia_semana' },
            { data: 'vehicle' },
            { data: 'trip_type' },
            { data: 'platform' },
            { data: 'registration_mode' },
            { data: 'monto_bruto', className: 'text-income' },
            { data: 'comision_app', className: 'text-expense' },
            { data: 'monto_cobrado', className: 'text-income' },
            { data: 'propina', className: 'text-income' },
            { data: 'alquiler', className: 'text-expense' },
            { data: 'ingresos', className: 'text-income' },
            { data: 'neto', className: 'text-primary' }
        ]
    }));

    $('#formFiltrosViajes').on('submit', function (e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#selectTripType').on('change', function () {
        updateModeRadios();
        toggleFormFields();
        updateRentalSuggestion();
    });

    $('input[name="registration_mode"]').on('change', function () {
        toggleFormFields();
        updateRentalSuggestion();
    });

    $('#selectVehicle, input[name="fecha"], input[name="monto_bruto"], input[name="comision_app"], input[name="monto_cobrado"]')
        .on('change input', updateRentalSuggestion);

    $porcentajeCuota.on('input', function () {
        $(this).data('user-edited', true);
        updateRentalSuggestion();
    });

    $('#modalNuevoViaje').on('show.bs.modal', function () {
        $porcentajeCuota.removeData('user-edited');
        updateModeRadios();
        toggleFormFields();
        updateRentalSuggestion();
    });

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
                $('input[name="fecha"]').val(new Date().toISOString().slice(0, 10));
                $alquiler.prop('readonly', true).val(0);
                $suggestion.text('');
                $porcentajeCuota.removeData('user-edited');
                updateModeRadios();
                toggleFormFields();
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

    updateModeRadios();
    toggleFormFields();
});
