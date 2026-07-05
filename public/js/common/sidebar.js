(function () {
    var sidebar = document.querySelector('.cl-sidebar');
    var overlay = document.getElementById('clSidebarOverlay');
    var toggleBtn = document.getElementById('btnSidebarToggle');
    var closeBtn = document.getElementById('btnSidebarClose');

    if (!sidebar) return;

    function openSidebar() {
        document.body.classList.add('cl-sidebar-open');
        sidebar.setAttribute('aria-hidden', 'false');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
    }

    function closeSidebar() {
        document.body.classList.remove('cl-sidebar-open');
        sidebar.setAttribute('aria-hidden', 'true');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
    }

    function toggleSidebar() {
        if (document.body.classList.contains('cl-sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeSidebar);
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    sidebar.querySelectorAll('.cl-nav a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.matchMedia('(max-width: 991.98px)').matches) {
                closeSidebar();
            }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSidebar();
    });

    window.addEventListener('resize', function () {
        if (window.matchMedia('(min-width: 992px)').matches) {
            closeSidebar();
        }
    });
})();
