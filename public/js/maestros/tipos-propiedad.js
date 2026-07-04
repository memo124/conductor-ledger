$(function () {
    ConductorLedger.setupAjaxCsrf();

    var table = $('#tblMaestro').DataTable($.extend(true, {}, ConductorLedger.defaultDataTableOptions, {
        ajax: {
            url: APLICATIVO_API.TIPOS_PROPIEDAD.GET.DATATABLE,
            type: 'GET'
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            {
                data: null,
                orderable: false,
                render: function (data) {
                    return '<button class="btn btn-sm btn-outline-primary btn-edit" data-id="' + data.id + '" data-name="' + data.name + '"><i class="fa-solid fa-pen"></i></button>';
                }
            }
        ]
    }));

    $('#tblMaestro').on('click', '.btn-edit', function () {
        $('#recordId').val($(this).data('id'));
        $('#inputName').val($(this).data('name'));
        $('#modalTitle').text('Editar Tipo');
        new bootstrap.Modal(document.getElementById('modalNuevo')).show();
    });

    $('[data-bs-target="#modalNuevo"]').on('click', function () {
        $('#recordId').val('');
        $('#inputName').val('');
        $('#modalTitle').text('Nuevo Tipo');
    });

    $('#formMaestro').on('submit', function (e) {
        e.preventDefault();
        var id = $('#recordId').val();
        var url = id
            ? APLICATIVO_API.TIPOS_PROPIEDAD.PUT.UPDATE + '/' + id
            : APLICATIVO_API.TIPOS_PROPIEDAD.POST.STORE;
        var method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            method: method,
            data: { name: $('#inputName').val() }
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
