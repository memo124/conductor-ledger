$(function () {
    ConductorLedger.setupAjaxCsrf();

    var currentRoleId = null;

    function renderMatrix(rows) {
        var $body = $('#permisosBody');
        $body.empty();

        if (!rows || !rows.length) {
            $body.append('<tr><td colspan="5" class="text-center text-muted">Sin opciones de menú</td></tr>');
            return;
        }

        rows.forEach(function (row) {
            $body.append(
                '<tr data-option-id="' + row.app_option_id + '">' +
                    '<td>' + $('<div>').text(row.label).html() + '<br><small class="text-muted">' + $('<div>').text(row.slug).html() + '</small></td>' +
                    '<td class="text-center"><input type="checkbox" class="form-check-input perm-view" ' + (row.can_view ? 'checked' : '') + '></td>' +
                    '<td class="text-center"><input type="checkbox" class="form-check-input perm-create" ' + (row.can_create ? 'checked' : '') + '></td>' +
                    '<td class="text-center"><input type="checkbox" class="form-check-input perm-update" ' + (row.can_update ? 'checked' : '') + '></td>' +
                    '<td class="text-center"><input type="checkbox" class="form-check-input perm-delete" ' + (row.can_delete ? 'checked' : '') + '></td>' +
                '</tr>'
            );
        });
    }

    function loadMatrix(roleId) {
        currentRoleId = roleId;
        $('#permisosBody').html('<tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>');

        $.get(APLICATIVO_API.PERMISOS.GET.MATRIX, { role_id: roleId })
            .done(function (res) {
                if (!res.success) {
                    ConductorLedger.showAlert('No se pudo cargar la matriz de permisos.', 'danger');
                    return;
                }
                renderMatrix(res.data);
            })
            .fail(function () {
                ConductorLedger.showAlert('Error al cargar permisos.', 'danger');
            });
    }

    function collectPermissions() {
        var permissions = [];
        $('#permisosBody tr[data-option-id]').each(function () {
            var $row = $(this);
            permissions.push({
                app_option_id: parseInt($row.data('option-id'), 10),
                can_view: $row.find('.perm-view').is(':checked'),
                can_create: $row.find('.perm-create').is(':checked'),
                can_update: $row.find('.perm-update').is(':checked'),
                can_delete: $row.find('.perm-delete').is(':checked')
            });
        });
        return permissions;
    }

    $('#selectRole').on('change', function () {
        loadMatrix($(this).val());
    });

    $('#btnSavePermisos').on('click', function () {
        if (!currentRoleId) {
            ConductorLedger.showAlert('Seleccione un rol.', 'warning');
            return;
        }

        $.ajax({
            url: APLICATIVO_API.PERMISOS.PUT.UPDATE,
            method: 'PUT',
            data: {
                role_id: currentRoleId,
                permissions: collectPermissions()
            }
        })
            .done(function (res) {
                ConductorLedger.showAlert(res.message, 'success');
            })
            .fail(function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error al guardar.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                ConductorLedger.showAlert(msg, 'danger');
            });
    });

    if ($('#selectRole').val()) {
        loadMatrix($('#selectRole').val());
    }
});
