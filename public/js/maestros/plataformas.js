$(function () {
    ConductorLedger.setupAjaxCsrf();

    var table = $('#tblMaestro').DataTable($.extend(true, {}, ConductorLedger.defaultDataTableOptions, {
        ajax: {
            url: APLICATIVO_API.PLATAFORMAS.GET.DATATABLE,
            type: 'GET'
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'is_active' },
            {
                data: null,
                orderable: false,
                render: function (data) {
                    return '<button class="btn btn-sm btn-outline-primary btn-edit" ' +
                        'data-id="' + data.id + '" ' +
                        'data-name="' + $('<div>').text(data.name).html() + '" ' +
                        'data-active="' + (data.is_active_bool ? '1' : '0') + '">' +
                        '<i class="fa-solid fa-pen"></i></button>';
                }
            }
        ]
    }));

    $('#tblMaestro').on('click', '.btn-edit', function () {
        $('#recordId').val($(this).data('id'));
        $('#inputName').val($(this).data('name'));
        $('#inputIsActive').val($(this).data('active'));
        $('#activeField').show();
        $('#modalTitle').text('Editar Plataforma');
        new bootstrap.Modal(document.getElementById('modalNuevo')).show();
    });

    $('[data-bs-target="#modalNuevo"]').on('click', function () {
        $('#recordId').val('');
        $('#inputName').val('');
        $('#inputIsActive').val('1');
        $('#activeField').hide();
        $('#modalTitle').text('Nueva Plataforma');
    });

    $('#formMaestro').on('submit', function (e) {
        e.preventDefault();
        var id = $('#recordId').val();
        var url = id
            ? APLICATIVO_API.PLATAFORMAS.PUT.UPDATE + '/' + id
            : APLICATIVO_API.PLATAFORMAS.POST.STORE;
        var method = id ? 'PUT' : 'POST';
        var payload = { name: $('#inputName').val() };

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
