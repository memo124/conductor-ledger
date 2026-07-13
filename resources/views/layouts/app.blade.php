<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', 'ConductorLedger') — Control Financiero</title>

    <script>
        (function () {
            var pref = @json(auth()->check() ? (auth()->user()->theme_preference ?? 'auto') : null);
            if (!pref) {
                try { pref = localStorage.getItem('cl_theme_preference') || 'auto'; } catch (e) { pref = 'auto'; }
            }
            var hour = new Date().getHours();
            var theme = pref === 'dark' ? 'dark' : pref === 'light' ? 'light' : ((hour >= 19 || hour < 6) ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.style.colorScheme = theme;
            document.documentElement.style.backgroundColor = theme === 'dark' ? '#0d0f12' : '#f0f2f5';
        })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/app-themes.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="cl-body" @auth data-user-theme="{{ auth()->user()->theme_preference ?? 'auto' }}" @endauth>
    @auth
    <div class="cl-sidebar-overlay" id="clSidebarOverlay" aria-hidden="true"></div>

    <header class="cl-mobile-header">
        <button type="button" class="cl-sidebar-toggle" id="btnSidebarToggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="clSidebar">
            <i class="fa-solid fa-bars"></i>
        </button>
        <span class="cl-mobile-brand">
            <i class="fa-solid fa-chart-line"></i> ConductorLedger
        </span>
    </header>

    <nav class="cl-sidebar" id="clSidebar" aria-hidden="true">
        <div class="cl-brand">
            <i class="fa-solid fa-chart-line cl-brand-icon"></i>
            <span>ConductorLedger</span>
            <button type="button" class="cl-sidebar-close" id="btnSidebarClose" aria-label="Cerrar menú">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <ul class="cl-nav">
            @foreach($clMenu ?? [] as $section)
                @if($section->is_divider && !empty($section->children))
                    <li class="cl-nav-divider">{{ $section->label }}</li>
                @endif
                @foreach($section->children as $item)
                    @if($item->route_name)
                    <li>
                        <a href="{{ route($item->route_name) }}" class="{{ request()->routeIs(str_replace('.index', '.*', $item->route_name)) ? 'active' : '' }}">
                            <i class="{{ $item->icon }}"></i> {{ $item->label }}
                        </a>
                    </li>
                    @endif
                @endforeach
            @endforeach
        </ul>
        <div class="cl-sidebar-footer">
            <span class="cl-user-name">{{ auth()->user()->name }}</span>
            <small class="text-muted d-block mt-1">v{{ config('conductor-ledger.version') }}</small>
            <div class="cl-theme-picker mt-2">
                <label for="selectThemePreference" class="form-label mb-1">Tema</label>
                <select id="selectThemePreference" class="form-select form-select-sm">
                    <option value="light">Claro</option>
                    <option value="dark">Oscuro</option>
                    <option value="auto">Automático (por hora)</option>
                </select>
            </div>
            <button type="button" id="btnLogout" class="btn btn-sm btn-outline-danger w-100 mt-2">
                <i class="fa-solid fa-right-from-bracket"></i> Salir
            </button>
        </div>
    </nav>
    @endauth

    <main class="@auth cl-main @else cl-main-auth @endauth">
        @yield('content')
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('js/common/datatables-config.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('js/config/api_endpoints.js') }}"></script>
    <script src="{{ asset('js/common/alerts.js') }}"></script>
    <script src="{{ asset('js/common/loader.js') }}"></script>
    <script src="{{ asset('js/common/theme.js') }}"></script>
    @auth
    <script src="{{ asset('js/common/sidebar.js') }}"></script>
    <script src="{{ asset('js/common/logout.js') }}"></script>
    @endauth
    @stack('scripts')
</body>
</html>
