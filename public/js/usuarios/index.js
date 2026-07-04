$(function () {
    ConductorLedger.setupAjaxCsrf();

    var modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
    var editingId = null;

    var table = $('#tblUsuarios').DataTable($.extend(true, {}, ConductorLedger.defaultDataTableOptions, {
        ajax: { url: APLICATIVO_API.USUARIOS.GET.DATATABLE, type: 'GET' },
        order: [[0, 'desc']],
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'email' },
            { data: 'dui' },
            { data: 'role' },
            { data: 'is_active' },
            {
                data: null,
                orderable: false,
                render: function (row) {
                    var html = '<button type="button" class="btn btn-sm btn-primary btn-edit me-1" data-id="' + row.id + '"><i class="fa-solid fa-pen"></i></button>';
                    if (!row.is_self) {
                        html += '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="' + row.id + '"><i class="fa-solid fa-trash"></i></button>';
                    }
                    return html;
                }
            }
        ]
    }));

    function openCreate() {
        editingId = null;
        $('#modalUsuarioTitle').text('Nuevo Usuario');
        $('#formUsuario')[0].reset();
        $('#userId').val('');
        $('#userPassword').prop('required', true);
        $('#passwordHint').text('Obligatoria al crear usuario.');
        modal.show();
    }

    $('[data-bs-target="#modalUsuario"]').on('click', openCreate);

    $('#tblUsuarios').on('click', '.btn-edit', function () {
        var row = table.row($(this).closest('tr')).data();
        editingId = row.id;
        $('#modalUsuarioTitle').text('Editar Usuario');
        $('#userId').val(row.id);
        $('input[name="name"]').val(row.name);
        $('input[name="email"]').val(row.email);
        $('input[name="dui"]').val(row.dui);
        $('select[name="role"]').val(row.role_code);
        $('select[name="is_active"]').val(row.is_active_bool ? '1' : '0');
        $('#userPassword').val('').prop('required', false);
        $('#passwordHint').text('Dejar vacío para mantener la contraseña actual.');
        modal.show();
    });

    $('#tblUsuarios').on('click', '.btn-delete', function () {
        var id = $(this).data('id');
        if (!confirm('¿Eliminar este usuario?')) return;

        $.ajax({
            url: APLICATIVO_API.USUARIOS.DELETE.DELETE + '/' + id,
            method: 'DELETE'
        }).done(function (res) {
            ConductorLedger.showAlert(res.message, 'success');
            table.ajax.reload(null, false);
        }).fail(function (xhr) {
            ConductorLedger.showAlert(xhr.responseJSON?.message || 'No se pudo eliminar.', 'danger');
        });
    });

    $('#formUsuario').on('submit', function (e) {
        e.preventDefault();
        var data = $(this).serializeArray();
        var payload = {};
        data.forEach(function (item) { payload[item.name] = item.value; });
        payload.is_active = payload.is_active === '1';

        var request = editingId
            ? $.ajax({ url: APLICATIVO_API.USUARIOS.PUT.UPDATE + '/' + editingId, method: 'PUT', data: payload })
            : $.post(APLICATIVO_API.USUARIOS.POST.STORE, payload);

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
