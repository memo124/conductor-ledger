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
    <nav class="cl-sidebar">
        <div class="cl-brand">
            <i class="fa-solid fa-chart-line cl-brand-icon"></i>
            <span>ConductorLedger</span>
        </div>
        <ul class="cl-nav">
            <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <li><a href="{{ route('viajes.index') }}" class="{{ request()->routeIs('viajes.*') ? 'active' : '' }}"><i class="fa-solid fa-road"></i> Viajes</a></li>
            <li><a href="{{ route('gastos.index') }}" class="{{ request()->routeIs('gastos.*') ? 'active' : '' }}"><i class="fa-solid fa-wallet"></i> Gastos</a></li>
            <li><a href="{{ route('vehiculos.index') }}" class="{{ request()->routeIs('vehiculos.*') ? 'active' : '' }}"><i class="fa-solid fa-car"></i> Vehículos</a></li>
            <li><a href="{{ route('graficos.index') }}" class="{{ request()->routeIs('graficos.*') ? 'active' : '' }}"><i class="fa-solid fa-chart-pie"></i> Gráficos</a></li>
            <li class="cl-nav-divider">Maestros</li>
            <li><a href="{{ route('tipos-propiedad.index') }}" class="{{ request()->routeIs('tipos-propiedad.*') ? 'active' : '' }}"><i class="fa-solid fa-key"></i> Tipos Propiedad</a></li>
            <li><a href="{{ route('categorias-gasto.index') }}" class="{{ request()->routeIs('categorias-gasto.*') ? 'active' : '' }}"><i class="fa-solid fa-tags"></i> Categorías Gasto</a></li>
            <li class="cl-nav-divider">Cuenta</li>
            <li><a href="{{ route('perfil.index') }}" class="{{ request()->routeIs('perfil.*') ? 'active' : '' }}"><i class="fa-solid fa-user"></i> Mi Perfil</a></li>
            @if(auth()->user()->isAdmin())
            <li><a href="{{ route('usuarios.index') }}" class="{{ request()->routeIs('usuarios.*') ? 'active' : '' }}"><i class="fa-solid fa-users-gear"></i> Usuarios</a></li>
            @endif
        </ul>
        <div class="cl-sidebar-footer">
            <span class="cl-user-name">{{ auth()->user()->name }}</span>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('js/config/api_endpoints.js') }}"></script>
    <script src="{{ asset('js/common/alerts.js') }}"></script>
    <script src="{{ asset('js/common/theme.js') }}"></script>
    @auth
    <script src="{{ asset('js/common/logout.js') }}"></script>
    @endauth
    @stack('scripts')
</body>
</html>
