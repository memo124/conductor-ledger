$(function () {
    ConductorLedger.setupAjaxCsrf();

    var table = $('#tblMaestro').DataTable($.extend(true, {}, ConductorLedger.defaultDataTableOptions, {
        ajax: {
            url: APLICATIVO_API.TIPOS_VIAJE.GET.DATATABLE,
            type: 'GET'
        },
        columns: [
            { data: 'id' },
            { data: 'code' },
            { data: 'name' },
            { data: 'allowed_modes' },
            { data: 'is_active' },
            {
                data: null,
                orderable: false,
                render: function (data) {
                    return '<button class="btn btn-sm btn-outline-primary btn-edit" ' +
                        'data-id="' + data.id + '" ' +
                        'data-code="' + $('<div>').text(data.code).html() + '" ' +
                        'data-name="' + $('<div>').text(data.name).html() + '" ' +
                        'data-modes="' + $('<div>').text(data.allowed_modes_raw).html() + '" ' +
                        'data-active="' + (data.is_active_bool ? '1' : '0') + '">' +
                        '<i class="fa-solid fa-pen"></i></button>';
                }
            }
        ]
    }));

    $('#tblMaestro').on('click', '.btn-edit', function () {
        $('#recordId').val($(this).data('id'));
        $('#inputCode').val($(this).data('code'));
        $('#inputName').val($(this).data('name'));
        $('#inputAllowedModes').val($(this).data('modes'));
        $('#inputIsActive').val($(this).data('active'));
        $('#activeField').show();
        $('#modalTitle').text('Editar Tipo de Viaje');
        new bootstrap.Modal(document.getElementById('modalNuevo')).show();
    });

    $('[data-bs-target="#modalNuevo"]').on('click', function () {
        $('#recordId').val('');
        $('#inputCode').val('');
        $('#inputName').val('');
        $('#inputAllowedModes').val('');
        $('#inputIsActive').val('1');
        $('#activeField').hide();
        $('#modalTitle').text('Nuevo Tipo de Viaje');
    });

    $('#formMaestro').on('submit', function (e) {
        e.preventDefault();
        var id = $('#recordId').val();
        var url = id
            ? APLICATIVO_API.TIPOS_VIAJE.PUT.UPDATE + '/' + id
            : APLICATIVO_API.TIPOS_VIAJE.POST.STORE;
        var method = id ? 'PUT' : 'POST';
        var payload = {
            code: $('#inputCode').val(),
            name: $('#inputName').val(),
            allowed_modes: $('#inputAllowedModes').val()
        };

        if (id) {
            payload.is_active = $('#inputIsActive').val() === '1';
        }

        $.ajax({
            url: url,
            method: method,
            data: payload
        })
            .done(function (res) {
                ConductorLedger.showAlert(res.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalNuevo')).hide();
                table.ajax.reload(null, false);
            })
            .fail(function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                ConductorLedger.showAlert(msg, 'danger');
            });
    });
});
