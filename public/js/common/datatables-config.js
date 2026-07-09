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
        processing: '<div class="cl-dt-processing"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Procesando...</div>',
        emptyTable: 'No hay datos disponibles en la tabla',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
        infoFiltered: '(filtrado de _MAX_ registros totales)',
        lengthMenu: 'Mostrar _MENU_ registros',
        loadingRecords: 'Cargando...',
        search: 'Buscar:',
        zeroRecords: 'No se encontraron registros coincidentes',
        paginate: {
            first: 'Primero',
            last: 'Último',
            next: 'Siguiente',
            previous: 'Anterior'
        },
        aria: {
            sortAscending: ': activar para ordenar la columna ascendente',
            sortDescending: ': activar para ordenar la columna descendente'
        }
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
