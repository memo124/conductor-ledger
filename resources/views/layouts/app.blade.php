<!DOCTYPE html>
<html lang="{{ auth()->check() ? (auth()->user()->locale_preference ?? 'es') : config('app.locale', 'es') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title', ui('app.brand')) — {{ ui('app.financial_control') }}</title>

    <script>
        window.CL_I18N = @json($clUiTranslations ?? []);
    </script>

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

    <link href="{{ asset('vendor/bootstrap/5.3.3/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/datatables/1.13.8/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/select2/4.1.0-rc.0/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome/6.5.1/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app-themes.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="cl-body" @auth data-user-theme="{{ auth()->user()->theme_preference ?? 'auto' }}" data-user-locale="{{ auth()->user()->locale_preference ?? 'es' }}" data-user-currency="{{ auth()->user()->currency_preference ?? 'USD' }}" @endauth>
    @auth
    <div class="cl-sidebar-overlay" id="clSidebarOverlay" aria-hidden="true"></div>

    <header class="cl-mobile-header">
        <button type="button" class="cl-sidebar-toggle" id="btnSidebarToggle" aria-label="{{ ui('sidebar.open_menu') }}" aria-expanded="false" aria-controls="clSidebar">
            <i class="fa-solid fa-bars"></i>
        </button>
        <span class="cl-mobile-brand">
            <i class="fa-solid fa-chart-line"></i> {{ ui('app.brand') }}
        </span>
    </header>

    <nav class="cl-sidebar" id="clSidebar" aria-hidden="true">
        <div class="cl-brand">
            <i class="fa-solid fa-chart-line cl-brand-icon"></i>
            <span>{{ ui('app.brand') }}</span>
            <button type="button" class="cl-sidebar-close" id="btnSidebarClose" aria-label="{{ ui('sidebar.close_menu') }}">
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
                <label for="selectLocalePreference" class="form-label mb-1">{{ ui('sidebar.language') }}</label>
                <select id="selectLocalePreference" class="form-select form-select-sm">
                    @foreach($clLocales ?? [] as $code => $meta)
                        <option value="{{ $code }}" @selected((auth()->user()->locale_preference ?? 'es') === $code)>{{ $meta['label'] ?? strtoupper($code) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cl-theme-picker mt-2">
                <label for="selectThemePreference" class="form-label mb-1">{{ ui('sidebar.theme') }}</label>
                <select id="selectThemePreference" class="form-select form-select-sm">
                    <option value="light">{{ ui('theme.light') }}</option>
                    <option value="dark">{{ ui('theme.dark') }}</option>
                    <option value="auto">{{ ui('theme.auto') }}</option>
                </select>
            </div>
            <button type="button" id="btnLogout" class="btn btn-sm btn-outline-danger w-100 mt-2">
                <i class="fa-solid fa-right-from-bracket"></i> {{ ui('sidebar.logout') }}
            </button>
        </div>
    </nav>
    @endauth

    <main class="@auth cl-main @else cl-main-auth @endauth">
        @yield('content')
    </main>

    <script src="{{ asset('vendor/jquery/3.7.1/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/1.13.8/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/1.13.8/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/common/i18n.js') }}"></script>
    <script src="{{ asset('js/common/datatables-config.js') }}"></script>
    <script src="{{ asset('vendor/select2/4.1.0-rc.0/js/select2.min.js') }}"></script>
    <script src="{{ asset('js/config/api_endpoints.js') }}"></script>
    <script src="{{ asset('js/common/alerts.js') }}"></script>
    <script src="{{ asset('js/common/loader.js') }}"></script>
    <script src="{{ asset('js/common/theme.js') }}"></script>
    @auth
    <script>
        window.CL_MONEY_CONFIG = @json($clMoneyConfig);
    </script>
    <script src="{{ asset('js/common/money.js') }}"></script>
    <script src="{{ asset('js/common/sidebar.js') }}"></script>
    <script src="{{ asset('js/common/logout.js') }}"></script>
    @endauth
    @stack('scripts')
</body>
</html>
