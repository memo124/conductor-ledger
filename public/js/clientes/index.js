$(function () {
    ConductorLedger.setupAjaxCsrf();

    var $form = $('#formCliente');
    var modalEl = document.getElementById('modalCliente');
    var modal = new bootstrap.Modal(modalEl);
    var editingId = null;
    var map = null;
    var marker = null;
    var dependentIndex = 0;

    function defaultCoords() {
        return { lat: 13.6929, lng: -89.2182 };
    }

    function initMap() {
        if (map) {
            map.invalidateSize();
            return;
        }

        var coords = defaultCoords();
        map = L.map('clientMap').setView([coords.lat, coords.lng], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        map.on('click', function (event) {
            setLocation(event.latlng.lat, event.latlng.lng);
        });
    }

    function setLocation(lat, lng) {
        $('#inputLatitude').val(lat);
        $('#inputLongitude').val(lng);

        if (!marker) {
            marker = L.marker([lat, lng]).addTo(map);
        } else {
            marker.setLatLng([lat, lng]);
        }
    }

    function clearLocation() {
        $('#inputLatitude').val('');
        $('#inputLongitude').val('');
        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
    }

    function dependentTemplate(index, data) {
        data = data || {};
        return '<div class="border rounded p-2 mb-2 dependent-item" data-index="' + index + '">' +
            '<input type="hidden" name="dependents[' + index + '][id]" value="' + (data.id || '') + '">' +
            '<div class="row g-2 align-items-end">' +
            '<div class="col-md-4">' +
            '<label class="form-label form-label-sm mb-1">' + ConductorLedger.I18n.t('profile.name') + '</label>' +
            '<input type="text" class="form-control form-control-sm dependent-name" value="' + (data.name || '') + '">' +
            '</div>' +
            '<div class="col-md-3">' +
            '<label class="form-label form-label-sm mb-1">' + ConductorLedger.I18n.t('pages.clientes.field_relationship') + '</label>' +
            '<input type="text" class="form-control form-control-sm dependent-relationship" value="' + (data.relationship_label || '') + '">' +
            '</div>' +
            '<div class="col-md-3">' +
            '<label class="form-label form-label-sm mb-1">' + ConductorLedger.I18n.t('pages.clientes.col_phone') + '</label>' +
            '<input type="text" class="form-control form-control-sm dependent-phone" value="' + (data.phone || '') + '">' +
            '</div>' +
            '<div class="col-md-2 text-end">' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-dependent"><i class="fa-solid fa-trash"></i></button>' +
            '</div>' +
            '</div></div>';
    }

    function resetDependents() {
        dependentIndex = 0;
        $('#dependentsList').empty();
    }

    function addDependent(data) {
        $('#dependentsList').append(dependentTemplate(dependentIndex, data));
        dependentIndex++;
    }

    function collectDependents() {
        var dependents = [];

        $('.dependent-item').each(function () {
            var $item = $(this);
            var name = $.trim($item.find('.dependent-name').val() || '');
            var relationship = $.trim($item.find('.dependent-relationship').val() || '');
            var phone = $.trim($item.find('.dependent-phone').val() || '');

            if (!name && !relationship && !phone) {
                return;
            }

            if (!name) {
                return { error: ConductorLedger.I18n.t('pages.clientes.dependent_name_required') };
            }

            dependents.push({
                id: $.trim($item.find('input[type="hidden"]').val() || '') || null,
                name: name,
                relationship_label: relationship || null,
                phone: phone || null,
                is_active: true
            });
        });

        return { dependents: dependents };
    }

    function buildPayload() {
        var payload = {};
        $form.serializeArray().forEach(function (item) {
            payload[item.name] = item.value;
        });

        var dependentResult = collectDependents();
        if (dependentResult.error) {
            return dependentResult;
        }

        payload.dependents = dependentResult.dependents;

        if (editingId) {
            payload.is_active = payload.is_active === '1';
        } else {
            delete payload.is_active;
            delete payload.client_id;
        }

        ['phone', 'email', 'address', 'notes'].forEach(function (field) {
            if (payload[field] === '') {
                payload[field] = null;
            }
        });

        if (payload.latitude === '') {
            payload.latitude = null;
        }
        if (payload.longitude === '') {
            payload.longitude = null;
        }

        return { payload: payload };
    }

    $('#btnAddDependent').on('click', function () {
        addDependent({});
    });

    $('#dependentsList').on('click', '.btn-remove-dependent', function () {
        $(this).closest('.dependent-item').remove();
    });

    $('#btnClearLocation').on('click', clearLocation);

    modalEl.addEventListener('shown.bs.modal', function () {
        initMap();
        var lat = parseFloat($('#inputLatitude').val());
        var lng = parseFloat($('#inputLongitude').val());
        if (!isNaN(lat) && !isNaN(lng)) {
            setLocation(lat, lng);
            map.setView([lat, lng], 15);
        } else {
            var coords = defaultCoords();
            map.setView([coords.lat, coords.lng], 12);
        }
    });

    var table = $('#tblClientes').DataTable($.extend(true, {}, ConductorLedger.defaultDataTableOptions, {
        ajax: { url: APLICATIVO_API.CLIENTES.GET.DATATABLE, type: 'GET' },
        order: [[0, 'desc']],
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'phone' },
            { data: 'email' },
            { data: 'dependents_count' },
            {
                data: 'has_location',
                render: function (value) {
                    return value
                        ? '<i class="fa-solid fa-location-dot text-success"></i>'
                        : '<span class="text-muted">—</span>';
                }
            },
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

    function openCreate() {
        editingId = null;
        $('#modalClienteTitle').text(ConductorLedger.I18n.t('pages.clientes.modal_title_create'));
        $form[0].reset();
        $('#clientId').val('');
        resetDependents();
        clearLocation();
        $('#clientActiveField').hide();
        modal.show();
    }

    $('#btnNuevoCliente').on('click', openCreate);

    $('#tblClientes').on('click', '.btn-edit', function () {
        var row = table.row($(this).closest('tr')).data();
        editingId = row.id;

        $.get(APLICATIVO_API.CLIENTES.GET.SHOW + '/' + editingId).done(function (res) {
            var client = res.data;
            $('#modalClienteTitle').text(ConductorLedger.I18n.t('pages.clientes.modal_title_edit'));
            $('#clientId').val(client.id);
            $form.find('input[name="name"]').val(client.name);
            $form.find('input[name="phone"]').val(client.phone || '');
            $form.find('input[name="email"]').val(client.email || '');
            $form.find('input[name="address"]').val(client.address || '');
            $form.find('textarea[name="notes"]').val(client.notes || '');
            $form.find('select[name="is_active"]').val(client.is_active ? '1' : '0');
            $('#inputLatitude').val(client.latitude || '');
            $('#inputLongitude').val(client.longitude || '');
            resetDependents();
            (client.dependents || []).forEach(addDependent);
            $('#clientActiveField').show();
            modal.show();
        });
    });

    $form.on('submit', function (e) {
        e.preventDefault();

        var name = $.trim($form.find('input[name="name"]').val() || '');
        if (!name) {
            ConductorLedger.showAlert(ConductorLedger.I18n.t('pages.clientes.name_required'), 'danger');
            return;
        }

        var built = buildPayload();
        if (built.error) {
            ConductorLedger.showAlert(built.error, 'danger');
            return;
        }

        var payload = built.payload;
        var url = editingId
            ? APLICATIVO_API.CLIENTES.PUT.UPDATE + '/' + editingId
            : APLICATIVO_API.CLIENTES.POST.STORE;

        $.ajax({
            url: url,
            type: editingId ? 'PUT' : 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload)
        }).done(function (res) {
            ConductorLedger.showAlert(res.message, 'success');
            modal.hide();
            table.ajax.reload(null, false);
        }).fail(function (xhr) {
            var msg = xhr.responseJSON?.message || ConductorLedger.I18n.t('pages.clientes.save_error');
            if (xhr.responseJSON?.errors) {
                msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
            }
            ConductorLedger.showAlert(msg, 'danger');
        });
    });
});
