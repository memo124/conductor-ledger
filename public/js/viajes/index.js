$(function () {
    ConductorLedger.setupAjaxCsrf();

    var tripTypes = window.CL_TRIP_TYPES || [];
    var tripTypesById = {};
    tripTypes.forEach(function (t) { tripTypesById[t.id] = t; });

    var $modal = $('#modalNuevoViaje');
    var $modalTitle = $modal.find('.modal-title');
    var $form = $('#formNuevoViaje');
    var $editUuid = $('#editUuid');
    var $btnSubmit = $('#btnSubmitViaje');
    var $alquiler = $('input[name="alquiler"]');
    var $suggestion = $('#rentalSuggestion');
    var $porcentajeCuota = $('input[name="porcentaje_cuota"]');
    var todayStr = new Date().toISOString().slice(0, 10);

    $('input[name="fecha"]').attr('max', todayStr);

    function currentYearMonth() {
        var now = new Date();
        return { year: now.getFullYear(), month: now.getMonth() + 1 };
    }

    function updatePeriodMonthOptions() {
        var year = parseInt($('input[name="period_year"]').val(), 10);
        var $month = $('select[name="period_month"]');
        var current = currentYearMonth();
        var maxMonth = year === current.year ? current.month : 12;

        $month.find('option').each(function () {
            var monthValue = parseInt($(this).val(), 10);
            $(this).prop('disabled', monthValue > maxMonth);
        });

        if (parseInt($month.val(), 10) > maxMonth) {
            $month.val(String(maxMonth));
        }
    }

    function validateTripPeriodClient() {
        var mode = syncRegistrationMode();

        if (mode === 'monthly') {
            var year = parseInt($('input[name="period_year"]').val(), 10);
            var month = parseInt($('select[name="period_month"]').val(), 10);
            var current = currentYearMonth();

            if (year * 12 + month > current.year * 12 + current.month) {
                return 'No puede registrar un resumen mensual de un período futuro.';
            }

            return null;
        }

        var fecha = $('input[name="fecha"]').val();
        if (fecha && fecha > todayStr) {
            return 'No puede registrar viajes con fecha futura.';
        }

        return null;
    }

    function ownerUserParams(extra) {
        var params = extra || {};
        var target = $('#filterConductor').length ? $('#filterConductor').val() : '';
        if (target) {
            params.target_user_id = target;
        }
        return params;
    }

    function select2AjaxData() {
        return {
            ajax: {
                data: function (params) {
                    return ownerUserParams({
                        q: params.term || '',
                        page: params.page || 1
                    });
                }
            }
        };
    }

    ConductorLedger.initSelect2Paginated($('#selectVehicle'), {
        url: APLICATIVO_API.VIAJES.GET.SELECT2,
        placeholder: 'Buscar vehículo...',
        dropdownParent: $modal,
        extra: select2AjaxData()
    });

    ConductorLedger.initSelect2Paginated($('#filterVehicle'), {
        url: APLICATIVO_API.VIAJES.GET.SELECT2,
        placeholder: 'Todos los vehículos...',
        allowClear: true,
        extra: select2AjaxData()
    });

    function selectedTripType() {
        var id = parseInt($('#selectTripType').val(), 10);
        return tripTypesById[id] || null;
    }

    function selectedMode() {
        if (isPlataforma()) {
            return 'daily';
        }
        return $('input[name="registration_mode_radio"]:checked').val() || $('#inputRegistrationMode').val() || 'daily';
    }

    function isPlataforma() {
        var type = selectedTripType();
        return type && type.code === 'PLATAFORMA';
    }

    function syncRegistrationMode() {
        var mode = selectedMode();
        if (isPlataforma()) {
            mode = 'daily';
        }
        $('#inputRegistrationMode').val(mode);
        return mode;
    }

    function updateModeRadios() {
        var type = selectedTripType();
        var $modeWrap = $('#fieldRegistrationMode');
        var $radios = $('input[name="registration_mode_radio"]');

        if (!type) {
            $modeWrap.hide();
            syncRegistrationMode();
            return;
        }

        if (isPlataforma()) {
            $modeWrap.hide();
            $radios.prop('disabled', false);
            $radios.filter('[value="daily"]').prop('checked', true);
            syncRegistrationMode();
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
            $radios.filter(':checked').prop('disabled', false);
        } else {
            $modeWrap.show();
        }

        syncRegistrationMode();
    }

    function toggleFormFields() {
        var mode = syncRegistrationMode();
        var plataforma = isPlataforma();

        $('#fieldPlatform').toggle(plataforma);
        $('#fieldFecha').toggle(mode !== 'monthly');
        $('#fieldPeriod').toggle(mode === 'monthly');
        $('#fieldMontoBruto').toggle(mode === 'daily' || mode === 'monthly');
        $('#fieldComisionApp').toggle(mode === 'daily' || mode === 'monthly');
        $('#fieldMontoCobrado').toggle(mode === 'per_trip');

        if (mode === 'monthly') {
            updatePeriodMonthOptions();
        }
    }

    function amountFields() {
        var mode = syncRegistrationMode();
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

        $.get(APLICATIVO_API.VIAJES.GET.RENTAL_SUGGESTION, ownerUserParams({
            vehicle_id: vehicleId,
            fecha: fecha,
            registration_mode: mode,
            monto_bruto: amounts.monto_bruto,
            comision_app: amounts.comision_app,
            monto_cobrado: amounts.monto_cobrado,
            porcentaje_cuota: parseFloat($porcentajeCuota.val() || '0')
        })).done(function (res) {
            if (!res.success) return;
            var data = res.data;

            if (typeof data.quota_percentage !== 'undefined' && !$porcentajeCuota.data('user-edited')) {
                $porcentajeCuota.val(data.quota_percentage);
            }

            if (data.alquiler_editable) {
                $alquiler.prop('readonly', false).val(data.suggested_alquiler.toFixed(2));
                $suggestion.text(
                    'Sugerido: ' + ConductorLedger.Money.formatFromBase(data.suggested_alquiler) +
                    ' (cuota ' + data.rental_period_label + ' ' + ConductorLedger.Money.formatFromBase(data.rental_fee) + ')'
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
        $('#totalIngresos').text(ConductorLedger.Money.formatFromBase(totals.ingresos));
        $('#totalComision').text(ConductorLedger.Money.formatFromBase(totals.comision_app));
        $('#totalAlquiler').text(ConductorLedger.Money.formatFromBase(totals.alquiler));
        $('#totalNeto').text(ConductorLedger.Money.formatFromBase(totals.neto));
    }

    function filterParams() {
        return ownerUserParams({
            fecha_desde: $('#filterFechaDesde').val(),
            fecha_hasta: $('#filterFechaHasta').val(),
            platform_id: $('#filterPlatform').val(),
            trip_type_id: $('#filterTripType').val(),
            registration_mode: $('#filterRegistrationMode').val(),
            vehicle_id: $('#filterVehicle').val()
        });
    }

    function resetTripForm() {
        $editUuid.val('');
        $modalTitle.text('Nuevo viaje');
        $btnSubmit.text('Guardar');
        $form[0].reset();
        $('#selectVehicle').val(null).trigger('change');
        $('input[name="fecha"]').val(new Date().toISOString().slice(0, 10));
        $alquiler.prop('readonly', true).val(0);
        $suggestion.text('');
        $porcentajeCuota.removeData('user-edited');
        updateModeRadios();
        toggleFormFields();
    }

    function setVehicleOption(vehicleId, plate) {
        var $select = $('#selectVehicle');
        $select.find('option').filter(function () {
            return $(this).val() === String(vehicleId);
        }).remove();
        if (vehicleId) {
            var label = plate || ('Vehículo #' + vehicleId);
            var option = new Option(label, vehicleId, true, true);
            $select.append(option);
        }
        $select.trigger('change');
    }

    function openEditModal(uuid) {
        $.get(APLICATIVO_API.VIAJES.GET.SHOW + '/' + uuid)
            .done(function (res) {
                if (!res.success || !res.data) {
                    ConductorLedger.showAlert('No se pudo cargar el viaje.', 'danger');
                    return;
                }

                var trip = res.data;
                resetTripForm();
                $editUuid.val(trip.uuid);
                $modalTitle.text('Editar viaje #' + (trip.trip_number || ''));
                $btnSubmit.text('Actualizar');

                $('#selectTripType').val(trip.trip_type_id);
                $('input[name="registration_mode_radio"]').filter('[value="' + trip.registration_mode + '"]').prop('checked', true);
                syncRegistrationMode();
                updateModeRadios();
                toggleFormFields();

                setVehicleOption(trip.vehicle_id, trip.vehicle_plate);
                $('#selectPlatform').val(trip.platform_id || '');
                $('input[name="fecha"]').val(trip.fecha || '');
                $('input[name="period_year"]').val(trip.period_year || '');
                $('input[name="period_month"]').val(trip.period_month || '');
                $('input[name="monto_bruto"]').val(trip.monto_bruto);
                $('input[name="comision_app"]').val(trip.comision_app);
                $('input[name="monto_cobrado"]').val(trip.monto_cobrado);
                $('input[name="propina"]').val(trip.propina);
                $('input[name="alquiler"]').val(trip.alquiler);
                $porcentajeCuota.val(trip.porcentaje_cuota).data('user-edited', true);

                updateRentalSuggestion();
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalNuevoViaje')).show();
            })
            .fail(function () {
                ConductorLedger.showAlert('No se pudo cargar el viaje.', 'danger');
            });
    }

    var table = $('#tblViajes').DataTable($.extend(true, {}, ConductorLedger.buildDefaultDataTableOptions(), {
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
            $.extend({ data: 'monto_bruto', className: 'text-income' }, ConductorLedger.moneyColumn()),
            $.extend({ data: 'comision_app', className: 'text-expense' }, ConductorLedger.moneyColumn()),
            $.extend({ data: 'monto_cobrado', className: 'text-income' }, ConductorLedger.moneyColumn()),
            $.extend({ data: 'propina', className: 'text-income' }, ConductorLedger.moneyColumn()),
            $.extend({ data: 'alquiler', className: 'text-expense' }, ConductorLedger.moneyColumn()),
            $.extend({ data: 'ingresos', className: 'text-income' }, ConductorLedger.moneyColumn()),
            $.extend({ data: 'neto', className: 'text-primary' }, ConductorLedger.moneyColumn()),
            {
                data: 'uuid',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (uuid) {
                    if (!uuid) return '';
                    return '<button type="button" class="btn btn-sm btn-outline-primary btn-edit-viaje" data-uuid="' + uuid + '" title="Editar">' +
                        '<i class="fa-solid fa-pen"></i></button>';
                }
            }
        ]
    }));

    $('#tblViajes').on('click', '.btn-edit-viaje', function () {
        openEditModal($(this).data('uuid'));
    });

    $('#formFiltrosViajes').on('submit', function (e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#filterConductor').on('change', function () {
        $('#filterVehicle').val(null).trigger('change');
        table.ajax.reload();
    });

    $('input[name="period_year"], select[name="period_month"]').on('change input', updatePeriodMonthOptions);

    $('#selectTripType').on('change', function () {
        updateModeRadios();
        toggleFormFields();
        updateRentalSuggestion();
    });

    $('input[name="registration_mode_radio"]').on('change', function () {
        syncRegistrationMode();
        toggleFormFields();
        updateRentalSuggestion();
    });

    $('#selectVehicle, input[name="fecha"], input[name="monto_bruto"], input[name="comision_app"], input[name="monto_cobrado"]')
        .on('change input', updateRentalSuggestion);

    $porcentajeCuota.on('input', function () {
        $(this).data('user-edited', true);
        updateRentalSuggestion();
    });

    $modal.on('show.bs.modal', function () {
        if (!$editUuid.val()) {
            $porcentajeCuota.removeData('user-edited');
            updateModeRadios();
            toggleFormFields();
            updateRentalSuggestion();
        }
    });

    $modal.on('hidden.bs.modal', function () {
        resetTripForm();
    });

    $form.on('submit', function (e) {
        e.preventDefault();

        var periodError = validateTripPeriodClient();
        if (periodError) {
            ConductorLedger.showAlert(periodError, 'danger');
            return;
        }

        if (!ConductorLedger.validateMoneyForm($(this))) {
            ConductorLedger.showAlert('Los montos no pueden ser negativos.', 'danger');
            return;
        }

        syncRegistrationMode();

        var editUuid = $editUuid.val();
        var request;

        if (editUuid) {
            request = $.ajax({
                url: APLICATIVO_API.VIAJES.PUT.UPDATE + '/' + editUuid,
                type: 'PUT',
                data: $(this).serialize()
            });
        } else {
            request = $.post(APLICATIVO_API.VIAJES.POST.STORE, $(this).serialize());
        }

        request
            .done(function (res) {
                ConductorLedger.showAlert(res.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalNuevoViaje')).hide();
                table.ajax.reload(null, false);
            })
            .fail(function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : (editUuid ? 'Error al actualizar el viaje.' : 'Error al guardar el viaje.');
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                ConductorLedger.showAlert(msg, 'danger');
            });
    });

    updateModeRadios();
    toggleFormFields();
    updatePeriodMonthOptions();
});
