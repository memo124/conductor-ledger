window.ConductorLedger = window.ConductorLedger || {};

ConductorLedger.initSelect2Paginated = function ($element, options) {
    options = options || {};

    $element.select2($.extend(true, {
        theme: 'default',
        width: '100%',
        placeholder: options.placeholder || 'Seleccione...',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            url: options.url,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || '',
                    page: params.page || 1
                };
            },
            processResults: function (data) {
                return {
                    results: data.results || [],
                    pagination: data.pagination || { more: false }
                };
            },
            cache: true
        },
        dropdownParent: options.dropdownParent || null
    }, options.extra || {}));
};

ConductorLedger.validateMoneyForm = function ($form) {
    var valid = true;
    $form.find('input[type="number"][min="0"]').each(function () {
        var value = parseFloat($(this).val());
        if (isNaN(value) || value < 0) {
            valid = false;
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    return valid;
};
