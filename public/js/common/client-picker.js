window.ConductorLedger = window.ConductorLedger || {};

ConductorLedger.CLIENT_TAG_PREFIX = 'new:';

ConductorLedger.initClientPicker = function ($clientSelect, options) {
    options = options || {};

    ConductorLedger.initSelect2Paginated($clientSelect, {
        url: APLICATIVO_API.CLIENTES.GET.SELECT2,
        placeholder: options.placeholder || ConductorLedger.I18n.t('pages.clientes.select_client'),
        dropdownParent: options.dropdownParent || null,
        extra: {
            tags: true,
            allowClear: options.required !== true,
            createTag: function (params) {
                var term = $.trim(params.term || '');
                if (term === '') {
                    return null;
                }

                return {
                    id: ConductorLedger.CLIENT_TAG_PREFIX + term,
                    text: term
                };
            }
        }
    });

    return $clientSelect;
};

ConductorLedger.initDependentPicker = function ($dependentSelect, $clientSelect, options) {
    options = options || {};

    $dependentSelect.select2({
        theme: 'default',
        width: '100%',
        allowClear: true,
        placeholder: options.placeholder || ConductorLedger.I18n.t('pages.clientes.select_dependent'),
        dropdownParent: options.dropdownParent || null
    });

    $clientSelect.on('change', function () {
        ConductorLedger.reloadDependentPicker($dependentSelect, $clientSelect);
    });
};

ConductorLedger.reloadDependentPicker = function ($dependentSelect, $clientSelect) {
    var clientValue = $clientSelect.val();
    $dependentSelect.empty().append(new Option('', '', false, false));

    if (!clientValue || String(clientValue).indexOf(ConductorLedger.CLIENT_TAG_PREFIX) === 0 || !/^\d+$/.test(String(clientValue))) {
        $dependentSelect.prop('disabled', true).val(null).trigger('change');
        return;
    }

    $.get(APLICATIVO_API.CLIENTES.GET.SELECT2_DEPENDENTS, { client_id: clientValue }).done(function (res) {
        (res.results || []).forEach(function (item) {
            $dependentSelect.append(new Option(item.text, item.id, false, false));
        });
        $dependentSelect.prop('disabled', false).val(null).trigger('change');
    });
};

ConductorLedger.readClientPicker = function ($clientSelect, $dependentSelect) {
    var value = $clientSelect.val();
    var dependentId = $dependentSelect && !$dependentSelect.prop('disabled') ? $dependentSelect.val() : null;
    var payload = {
        client_id: null,
        client_dependent_id: dependentId || null,
        client_display_name: null,
        display_name: null
    };

    if (!value) {
        return payload;
    }

    if (String(value).indexOf(ConductorLedger.CLIENT_TAG_PREFIX) === 0) {
        var name = String(value).substring(ConductorLedger.CLIENT_TAG_PREFIX.length);
        payload.client_display_name = name;
        payload.display_name = name;
        payload.client_dependent_id = null;
        return payload;
    }

    if (/^\d+$/.test(String(value))) {
        payload.client_id = parseInt(value, 10);
        return payload;
    }

    payload.client_display_name = String(value);
    payload.display_name = String(value);
    payload.client_dependent_id = null;

    return payload;
};

ConductorLedger.setClientPicker = function ($clientSelect, $dependentSelect, data) {
    data = data || {};
    $clientSelect.find('option').remove();

    if (data.client_id) {
        var clientLabel = data.client_label || data.client_name || ('Cliente #' + data.client_id);
        $clientSelect.append(new Option(clientLabel, String(data.client_id), true, true)).trigger('change');
        if ($dependentSelect) {
            ConductorLedger.reloadDependentPicker($dependentSelect, $clientSelect);
            if (data.client_dependent_id) {
                setTimeout(function () {
                    $dependentSelect.val(String(data.client_dependent_id)).trigger('change');
                }, 250);
            }
        }
        return;
    }

    if (data.client_display_name || data.display_name) {
        var name = data.client_display_name || data.display_name;
        var tagId = ConductorLedger.CLIENT_TAG_PREFIX + name;
        $clientSelect.append(new Option(name, tagId, true, true)).trigger('change');
        if ($dependentSelect) {
            $dependentSelect.prop('disabled', true).val(null).trigger('change');
        }
        return;
    }

    $clientSelect.val(null).trigger('change');
    if ($dependentSelect) {
        $dependentSelect.prop('disabled', true).val(null).trigger('change');
    }
};

ConductorLedger.resetClientPicker = function ($clientSelect, $dependentSelect) {
    ConductorLedger.setClientPicker($clientSelect, $dependentSelect, {});
};

ConductorLedger.syncClientHiddenFields = function ($clientSelect, $dependentSelect, $hiddenMap) {
    var data = ConductorLedger.readClientPicker($clientSelect, $dependentSelect);
    $hiddenMap.client_id.val(data.client_id || '');
    $hiddenMap.client_dependent_id.val(data.client_dependent_id || '');
    $hiddenMap.client_display_name.val(data.client_display_name || '');
};
