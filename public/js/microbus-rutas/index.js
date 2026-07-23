$(function () {
    ConductorLedger.setupAjaxCsrf();

    var modalRuta = new bootstrap.Modal(document.getElementById('modalRuta'));
    var modalPasajeros = new bootstrap.Modal(document.getElementById('modalPasajeros'));
    var modalPasajero = new bootstrap.Modal(document.getElementById('modalPasajero'));
    var editingRouteId = null;
    var activeRouteId = null;

    var $selectPassengerClient = $('#selectPassengerClient');
    var $selectPassengerDependent = $('#selectPassengerDependent');
    var $modalPasajeroEl = $('#modalPasajero');

    ConductorLedger.initClientPicker($selectPassengerClient, {
        required: true,
        dropdownParent: $modalPasajeroEl
    });
    ConductorLedger.initDependentPicker($selectPassengerDependent, $selectPassengerClient, {
        dropdownParent: $modalPasajeroEl
    });

    $selectPassengerClient.on('change', function () {
        var hasLinkedClient = $selectPassengerClient.val()
            && String($selectPassengerClient.val()).indexOf(ConductorLedger.CLIENT_TAG_PREFIX) !== 0
            && /^\d+$/.test(String($selectPassengerClient.val()));
        $selectPassengerDependent.closest('.mb-3').toggle(hasLinkedClient);
    });

    var table = $('#tblRutas').DataTable($.extend(true, {}, ConductorLedger.defaultDataTableOptions, {
        ajax: { url: APLICATIVO_API.MICROBUS_RUTAS.GET.DATATABLE, type: 'GET' },
        order: [[0, 'desc']],
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'vehicle_label' },
            { data: 'passengers_count' },
            { data: 'is_active' },
            {
                data: null,
                orderable: false,
                render: function () {
                    return '<button type="button" class="btn btn-sm btn-outline-primary btn-passengers me-1"><i class="fa-solid fa-users"></i></button>' +
                        '<button type="button" class="btn btn-sm btn-primary btn-edit"><i class="fa-solid fa-pen"></i></button>';
                }
            }
        ]
    }));

    function openCreateRoute() {
        editingRouteId = null;
        $('#modalRutaTitle').text(ConductorLedger.I18n.t('pages.microbus_rutas.modal_title_create'));
        $('#formRuta')[0].reset();
        $('#routeId').val('');
        $('#routeActiveField').hide();
        modalRuta.show();
    }

    $('#btnNuevaRuta').on('click', openCreateRoute);

    $('#tblRutas').on('click', '.btn-edit', function () {
        var row = table.row($(this).closest('tr')).data();
        editingRouteId = row.id;
        $('#modalRutaTitle').text(ConductorLedger.I18n.t('pages.microbus_rutas.modal_title_edit'));
        $('#routeId').val(row.id);
        $('input[name="name"]').val(row.name);
        $('select[name="vehicle_id"]').val(row.vehicle_id);
        $('textarea[name="notes"]').val(row.notes || '');
        $('select[name="is_active"]').val(row.is_active_bool ? '1' : '0');
        $('#routeActiveField').show();
        modalRuta.show();
    });

    $('#formRuta').on('submit', function (e) {
        e.preventDefault();
        var payload = {};
        $(this).serializeArray().forEach(function (item) {
            payload[item.name] = item.value;
        });

        if (editingRouteId) {
            payload.is_active = payload.is_active === '1';
        } else {
            delete payload.is_active;
        }

        var request = editingRouteId
            ? $.ajax({ url: APLICATIVO_API.MICROBUS_RUTAS.PUT.UPDATE + '/' + editingRouteId, method: 'PUT', data: payload })
            : $.post(APLICATIVO_API.MICROBUS_RUTAS.POST.STORE, payload);

        request.done(function (res) {
            ConductorLedger.showAlert(res.message, 'success');
            modalRuta.hide();
            table.ajax.reload(null, false);
        }).fail(function (xhr) {
            ConductorLedger.showAlert(xhr.responseJSON?.message || ConductorLedger.I18n.t('pages.microbus_rutas.save_error'), 'danger');
        });
    });

    function loadPassengers() {
        if (!activeRouteId) return;

        $.get(APLICATIVO_API.MICROBUS_RUTAS.GET.SHOW + '/' + activeRouteId, {
            period_year: $('#filterPeriodYear').val(),
            period_month: $('#filterPeriodMonth').val()
        }).done(function (res) {
            var rows = (res.data.passengers || []).map(function (p) {
                var paidBadge = p.payment.is_paid
                    ? '<span class="badge text-bg-success">' + ConductorLedger.I18n.t('pages.microbus_rutas.paid_yes') + '</span>'
                    : '<span class="badge text-bg-warning">' + ConductorLedger.I18n.t('pages.microbus_rutas.paid_no') + '</span>';

                return '<tr>' +
                    '<td>' + p.name + '</td>' +
                    '<td>' + p.monthly_fee + '</td>' +
                    '<td>' + paidBadge + '</td>' +
                    '<td class="text-nowrap">' +
                    '<button type="button" class="btn btn-sm btn-outline-success btn-toggle-paid" data-id="' + p.id + '" data-paid="' + (p.payment.is_paid ? '1' : '0') + '" data-amount="' + p.payment.amount_due_raw + '">' +
                    '<i class="fa-solid fa-dollar-sign"></i></button></td></tr>';
            }).join('');

            $('#tblPasajeros tbody').html(rows || '<tr><td colspan="4" class="text-center text-muted">' + ConductorLedger.I18n.t('common.no_data') + '</td></tr>');
        });
    }

    $('#tblRutas').on('click', '.btn-passengers', function () {
        var row = table.row($(this).closest('tr')).data();
        activeRouteId = row.id;
        $('#modalPasajerosTitle').text(ConductorLedger.I18n.t('pages.microbus_rutas.passengers_title') + ': ' + row.name);
        loadPassengers();
        modalPasajeros.show();
    });

    $('#btnReloadPassengers, #filterPeriodYear, #filterPeriodMonth').on('change click', function (e) {
        if (e.type === 'click' && this.id !== 'btnReloadPassengers') return;
        loadPassengers();
    });

    $('#btnAddPassenger').on('click', function () {
        $('#formPasajero')[0].reset();
        ConductorLedger.resetClientPicker($selectPassengerClient, $selectPassengerDependent);
        $selectPassengerDependent.closest('.mb-3').hide();
        modalPasajero.show();
    });

    $('#formPasajero').on('submit', function (e) {
        e.preventDefault();

        var clientData = ConductorLedger.readClientPicker($selectPassengerClient, $selectPassengerDependent);
        if (!clientData.client_id && !clientData.display_name) {
            ConductorLedger.showAlert(ConductorLedger.I18n.t('pages.clientes.client_reference_required'), 'danger');
            return;
        }

        var payload = {
            client_id: clientData.client_id,
            client_dependent_id: clientData.client_dependent_id,
            display_name: clientData.display_name,
            monthly_fee: parseFloat($('input[name="monthly_fee"]').val() || '0'),
            pickup_notes: $.trim($('input[name="pickup_notes"]').val() || '') || null
        };

        $.ajax({
            url: APLICATIVO_API.MICROBUS_RUTAS.POST.PASSENGER_STORE + '/' + activeRouteId + '/Passengers/Store',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload)
        }).done(function (res) {
            ConductorLedger.showAlert(res.message, 'success');
            modalPasajero.hide();
            loadPassengers();
            table.ajax.reload(null, false);
        }).fail(function (xhr) {
            var msg = xhr.responseJSON?.message || ConductorLedger.I18n.t('pages.microbus_rutas.save_error');
            if (xhr.responseJSON?.errors) {
                msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
            }
            ConductorLedger.showAlert(msg, 'danger');
        });
    });

    $('#tblPasajeros').on('click', '.btn-toggle-paid', function () {
        var passengerId = $(this).data('id');
        var isPaid = $(this).data('paid') !== '1';
        var amount = $(this).data('amount');

        $.ajax({
            url: APLICATIVO_API.MICROBUS_RUTAS.PUT.PASSENGER_PAYMENT + '/' + activeRouteId + '/Passengers/' + passengerId + '/Payment',
            method: 'PUT',
            data: {
                period_year: $('#filterPeriodYear').val(),
                period_month: $('#filterPeriodMonth').val(),
                amount_due: amount,
                is_paid: isPaid ? 1 : 0
            }
        }).done(function (res) {
            ConductorLedger.showAlert(res.message, 'success');
            loadPassengers();
        });
    });
});
