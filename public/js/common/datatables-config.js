window.ConductorLedger = window.ConductorLedger || {};

ConductorLedger.mobileCardsMq = window.matchMedia('(max-width: 767.98px)');

ConductorLedger.initMobileCards = function (table) {
    function applyMobileCards() {
        var isMobile = ConductorLedger.mobileCardsMq.matches;
        var $table = $(table.table().node());

        $table.toggleClass('cl-dt-mobile-cards', isMobile);

        if (!isMobile) {
            $table.find('tbody td').removeAttr('data-label');
            return;
        }

        table.columns().every(function (colIdx) {
            var header = $(table.column(colIdx).header()).text().trim();
            table.column(colIdx).nodes().to$().attr('data-label', header);
        });
    }

    table.on('draw.dt', applyMobileCards);
    ConductorLedger.mobileCardsMq.addEventListener('change', applyMobileCards);
    applyMobileCards();
};

ConductorLedger.chainInitComplete = function (options) {
    var userInit = options.initComplete;
    options.initComplete = function () {
        ConductorLedger.initMobileCards(this.api());
        if (typeof userInit === 'function') {
            userInit.call(this);
        }
    };
    return options;
};

ConductorLedger.defaultDataTableOptions = ConductorLedger.chainInitComplete({
    processing: true,
    serverSide: true,
    autoWidth: false,
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
    }
});

ConductorLedger.simpleDataTableOptions = ConductorLedger.chainInitComplete({
    paging: false,
    searching: false,
    info: false,
    ordering: false,
    autoWidth: false,
    language: {
        emptyTable: 'Sin datos',
        zeroRecords: 'Sin datos'
    }
});
