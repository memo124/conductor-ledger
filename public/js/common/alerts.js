window.ConductorLedger = window.ConductorLedger || {};

ConductorLedger.getCsrfToken = function () {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
};

ConductorLedger.setupAjaxCsrf = function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': ConductorLedger.getCsrfToken()
        }
    });
};

ConductorLedger.showAlert = function (message, type) {
    type = type || 'success';
    var container = document.querySelector('.cl-alert-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'cl-alert-container';
        document.body.appendChild(container);
    }
    var alert = document.createElement('div');
    alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
    alert.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    container.appendChild(alert);
    setTimeout(function () {
        alert.remove();
    }, 5000);
};
