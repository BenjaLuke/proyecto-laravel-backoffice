<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Backoffice')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script>
        (function () {
            const savedTheme = localStorage.getItem('backoffice-theme');
            const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const theme = (savedTheme === 'dark' || savedTheme === 'light') ? savedTheme : preferredTheme;

            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-body: #eef3f8;
            --bg-body-2: rgba(13, 110, 253, 0.10);
            --bg-body-3: rgba(13, 202, 240, 0.10);
            --bg-card: rgba(255, 255, 255, 0.88);
            --bg-subtle: #f8fafc;
            --bg-input: rgba(255, 255, 255, 0.92);
            --bg-table-stripe: rgba(15, 23, 42, 0.025);
            --text-main: #1e293b;
            --text-soft: #64748b;
            --border-color: rgba(148, 163, 184, 0.28);
            --input-border: #cbd5e1;
            --shadow-soft: 0 12px 35px rgba(15, 23, 42, 0.10);
            --shadow-hover: 0 16px 40px rgba(15, 23, 42, 0.14);
            --navbar-bg: rgba(15, 23, 42, 0.90);
            --navbar-link: rgba(255, 255, 255, 0.82);
            --navbar-link-hover-bg: rgba(255, 255, 255, 0.12);
            --logo-opacity: 0.5;
            --theme-btn-bg: rgba(255, 255, 255, 0.10);
            --theme-btn-border: rgba(255, 255, 255, 0.18);
            --theme-btn-color: #ffffff;
        }

        html[data-theme="dark"] {
            --bg-body: #0b1220;
            --bg-body-2: rgba(59, 130, 246, 0.16);
            --bg-body-3: rgba(34, 211, 238, 0.10);
            --bg-card: rgba(15, 23, 42, 0.82);
            --bg-subtle: #111c2f;
            --bg-input: rgba(15, 23, 42, 0.92);
            --bg-table-stripe: rgba(255, 255, 255, 0.03);
            --text-main: #e5edf8;
            --text-soft: #9fb1c7;
            --border-color: rgba(148, 163, 184, 0.18);
            --input-border: rgba(148, 163, 184, 0.28);
            --shadow-soft: 0 12px 35px rgba(0, 0, 0, 0.34);
            --shadow-hover: 0 16px 40px rgba(0, 0, 0, 0.42);
            --navbar-bg: rgba(2, 6, 23, 0.92);
            --navbar-link: rgba(255, 255, 255, 0.82);
            --navbar-link-hover-bg: rgba(255, 255, 255, 0.10);
            --logo-opacity: 0.07;
            --theme-btn-bg: rgba(255, 255, 255, 0.08);
            --theme-btn-border: rgba(255, 255, 255, 0.14);
            --theme-btn-color: #f8fafc;
        }

        html, body {
            min-height: 100%;
        }

        body {
            position: relative;
            color: var(--text-main);
            background:
                radial-gradient(circle at top left, var(--bg-body-2), transparent 30%),
                radial-gradient(circle at bottom right, var(--bg-body-3), transparent 25%),
                var(--bg-body);
            transition: background-color 0.25s ease, color 0.25s ease;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image: url("{{ asset('images/logo.jpg') }}");
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 100% auto;
            opacity: var(--logo-opacity);
            filter: grayscale(1);
            z-index: -1;
            pointer-events: none;
        }

        .custom-navbar {
            background: var(--navbar-bg) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.20);
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .navbar .nav-link {
            color: var(--navbar-link) !important;
            padding: 0.45rem 0.90rem;
            border-radius: 999px;
            transition: all 0.18s ease;
        }

        .navbar .nav-link:hover,
        .navbar .nav-link.active {
            color: #fff !important;
            background: var(--navbar-link-hover-bg);
        }

        .theme-toggle {
            border: 1px solid var(--theme-btn-border);
            background: var(--theme-btn-bg);
            color: var(--theme-btn-color);
            border-radius: 999px;
            padding: 0.45rem 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.18s ease;
        }

        .theme-toggle:hover {
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.16);
        }

        .page-shell {
            position: relative;
            z-index: 1;
        }
        .hero-panel,
        .card-lift {
            border: 1px solid var(--border-color);
            border-radius: 22px;
            background: var(--bg-card);
            backdrop-filter: blur(8px);
            box-shadow: var(--shadow-soft);
            color: var(--text-main);
        }

        .card-lift {
            transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.25s ease;
        }

        .card-lift:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .metric-link-card {
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .metric-link-card::after {
            content: "\F138";
            font-family: "bootstrap-icons";
            position: absolute;
            right: 1rem;
            bottom: 0.85rem;
            color: var(--text-soft);
            font-size: 0.95rem;
            opacity: 0.68;
            transition: transform 0.18s ease, opacity 0.18s ease, color 0.18s ease;
        }

        .metric-link-card:hover::after {
            transform: translateX(3px);
            opacity: 1;
            color: #0d6efd;
        }

        .metric-link-card .metric-label {
            text-decoration: underline;
            text-decoration-style: dotted;
            text-underline-offset: 0.22rem;
        }

        .metric-label {
            color: var(--text-soft);
            font-size: 0.90rem;
            margin-bottom: 0.30rem;
        }

        .metric-value {
            font-size: 1.85rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .metric-subvalue {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .metric-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .icon-primary { background: rgba(13, 110, 253, 0.12); color: #0d6efd; }
        .icon-warning { background: rgba(245, 158, 11, 0.16); color: #d97706; }
        .icon-danger  { background: rgba(220, 53, 69, 0.14); color: #dc3545; }
        .icon-info    { background: rgba(13, 202, 240, 0.16); color: #0891b2; }
        .icon-success { background: rgba(25, 135, 84, 0.14); color: #198754; }
        .icon-secondary { background: rgba(100, 116, 139, 0.14); color: #475569; }

        .section-title {
            font-size: 0.88rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-soft);
            margin-bottom: 1rem;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .status-pill.ok {
            background: rgba(25, 135, 84, 0.14);
            color: #198754;
        }

        .status-pill.warn {
            background: rgba(245, 158, 11, 0.16);
            color: #9a6700;
        }

        .status-pill.alert {
            background: rgba(220, 53, 69, 0.14);
            color: #b42318;
        }

        .pagination {
            gap: 0.35rem;
            align-items: center;
        }

        .page-link {
            font-size: 0.95rem !important;
            line-height: 1.2 !important;
            padding: 0.5rem 0.8rem !important;
            min-width: 40px;
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .page-link svg {
            width: 1rem !important;
            height: 1rem !important;
        }

        .page-link span[aria-hidden="true"] {
            font-size: 1rem !important;
            line-height: 1 !important;
        }

        .signal-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.55rem;
        }

        .signal-dot.ok { background: #198754; }
        .signal-dot.warn { background: #f59e0b; }
        .signal-dot.danger { background: #dc3545; }
        .signal-dot.info { background: #0dcaf0; }

        .quick-link {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.75rem 1rem;
            border-radius: 14px;
            border: 1px solid var(--border-color);
            background: var(--bg-input);
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.18s ease;
        }

        .quick-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            color: #0d6efd;
        }

        .table-clean {
            --bs-table-bg: transparent;
            margin-bottom: 0;
        }

        .table-clean thead th {
            color: var(--text-soft);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom-width: 1px;
        }

        .table-clean tbody td {
            vertical-align: middle;
        }

        .count-badge {
            display: inline-block;
            min-width: 34px;
            padding: 0.28rem 0.60rem;
            border-radius: 999px;
            text-align: center;
            font-size: 0.80rem;
            font-weight: 700;
        }

        .count-badge.zero {
            background: rgba(100, 116, 139, 0.14);
            color: #475569;
        }

        .count-badge.good {
            background: rgba(25, 135, 84, 0.14);
            color: #146c43;
        }

        .count-badge.warn {
            background: rgba(245, 158, 11, 0.16);
            color: #9a6700;
        }

        .count-badge.danger {
            background: rgba(220, 53, 69, 0.14);
            color: #b42318;
        }

        .muted-note {
            color: var(--text-soft);
            font-size: 0.92rem;
        }
        .card {
            border: 1px solid var(--border-color);
            border-radius: 22px;
            background: var(--bg-card);
            backdrop-filter: blur(8px);
            box-shadow: var(--shadow-soft);
            color: var(--text-main);
            transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.25s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-hover);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
        }

        .table-responsive {
            border-radius: 16px;
        }

        .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--text-main);
            --bs-table-striped-color: var(--text-main);
            --bs-table-border-color: var(--border-color);
            --bs-table-striped-bg: var(--bg-table-stripe);
            margin-bottom: 0;
        }

        .table thead th {
            color: var(--text-soft);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom-width: 1px;
        }

        .table tbody td {
            vertical-align: middle;
        }

        .form-label,
        .col-form-label {
            font-weight: 600;
            color: var(--text-main);
        }

        .form-control,
        .form-select,
        textarea.form-control {
            background: var(--bg-input);
            color: var(--text-main);
            border: 1px solid var(--input-border);
            border-radius: 14px;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.25s ease, color 0.25s ease;
        }

        .form-control::placeholder {
            color: var(--text-soft);
        }

        .form-control:focus,
        .form-select:focus,
        textarea.form-control:focus {
            background: var(--bg-input);
            color: var(--text-main);
            border-color: rgba(13, 110, 253, 0.45);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.14);
        }

        .form-check-input {
            border-color: var(--input-border);
            background-color: var(--bg-input);
        }

        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .form-text,
        .text-muted {
            color: var(--text-soft) !important;
        }

        .btn {
            border-radius: 12px;
            font-weight: 600;
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-sm {
            min-height: 36px;
        }

        .btn-warning {
            color: #231700;
        }

        .btn-outline-secondary {
            border-color: var(--input-border);
            color: var(--text-main);
        }

        .btn-outline-secondary:hover {
            color: #fff;
        }

    .btn-purple {
        background-color: #8b5cf6;
        border-color: #8b5cf6;
        color: #fff;
    }

    .btn-purple:hover {
        background-color: #7c3aed;
        border-color: #7c3aed;
        color: #fff;
    }

    .btn-purple:focus,
    .btn-purple:active {
        background-color: #6d28d9 !important;
        border-color: #6d28d9 !important;
        color: #fff !important;
        box-shadow: 0 0 0 0.25rem rgba(139, 92, 246, 0.25) !important;
    }

        .badge {
            border-radius: 999px;
            padding: 0.55em 0.8em;
            font-weight: 700;
        }

        .alert {
            border: 1px solid var(--border-color);
            border-radius: 18px;
            box-shadow: var(--shadow-soft);
        }

        .pagination {
            gap: 0.3rem;
        }

        .page-link {
            border-radius: 12px !important;
            border-color: var(--border-color);
            background: var(--bg-card);
            color: var(--text-main);
        }

        .page-link:hover {
            color: var(--text-main);
            background: var(--bg-subtle);
        }

        .page-item.active .page-link {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }

        .list-group-item {
            background: transparent;
            color: var(--text-main);
            border-color: var(--border-color);
        }

        .border,
        .border-top,
        .border-bottom,
        .border-start,
        .border-end {
            border-color: var(--border-color) !important;
        }

        .bg-light {
            background: var(--bg-subtle) !important;
            color: var(--text-main) !important;
        }

        pre {
            background: var(--bg-subtle);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 0.75rem;
        }

        hr {
            border-color: var(--border-color);
            opacity: 1;
        }

        details summary {
            font-weight: 600;
        }

        img {
            border-color: var(--border-color);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark custom-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand" href="{{ route('backoffice.dashboard') }}">Backoffice</a>

        <div class="collapse navbar-collapse show">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('backoffice.dashboard') ? 'active' : '' }}"
                    href="{{ route('backoffice.dashboard') }}">
                        Dashboard
                    </a>
                </li>

                @if(auth()->user()->hasPermission('categories_view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"
                        href="{{ route('categories.index') }}">
                            Categorías
                        </a>
                    </li>
                @endif

                @if(auth()->user()->hasPermission('products_view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}"
                        href="{{ route('products.index') }}">
                            Productos
                        </a>
                    </li>
                @endif

                @if(auth()->user()->hasPermission('products_view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('stock-entries.*') ? 'active' : '' }}"
                        href="{{ route('stock-entries.index') }}">
                            Stock
                        </a>
                    </li>
                @endif

                @if(auth()->user()->hasPermission('calendar_view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('calendar.*') && !request()->routeIs('calendar.create') ? 'active' : '' }}"
                        href="{{ route('calendar.index') }}">
                            Calendario
                        </a>
                    </li>
                @endif

                @if(auth()->user()->hasPermission('calendar_manage'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('calendar.create') ? 'active' : '' }}"
                        href="{{ route('calendar.create') }}">
                            Nuevo pedido
                        </a>        
                    </li>
                @endif

                @if(Route::has('activity.index') && auth()->user()->hasPermission('activity_view'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('activity.*') ? 'active' : '' }}"
                        href="{{ route('activity.index') }}">
                            Actividad
                        </a>
                    </li>
                @endif
                @if(auth()->user()->hasPermission('users_manage'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                        href="{{ route('users.index') }}">
                            Usuarios
                        </a>
                    </li>
                @endif
            </ul>

            <div class="d-flex align-items-center gap-2">
                <button type="button" id="theme-toggle" class="theme-toggle">
                    <i id="theme-toggle-icon" class="bi bi-moon-stars-fill"></i>
                    <span id="theme-toggle-label">Modo oscuro</span>
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Salir</button>
                </form>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4 page-shell">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const root = document.documentElement;
        const toggleButton = document.getElementById('theme-toggle');
        const toggleIcon = document.getElementById('theme-toggle-icon');
        const toggleLabel = document.getElementById('theme-toggle-label');

        function updateToggleUi(theme) {
            if (theme === 'dark') {
                toggleIcon.className = 'bi bi-sun-fill';
                toggleLabel.textContent = 'Modo claro';
                toggleButton.setAttribute('aria-label', 'Cambiar a modo claro');
            } else {
                toggleIcon.className = 'bi bi-moon-stars-fill';
                toggleLabel.textContent = 'Modo oscuro';
                toggleButton.setAttribute('aria-label', 'Cambiar a modo oscuro');
            }
        }

        function setTheme(theme) {
            root.setAttribute('data-theme', theme);
            localStorage.setItem('backoffice-theme', theme);
            updateToggleUi(theme);
        }

        updateToggleUi(root.getAttribute('data-theme') || 'light');

        toggleButton.addEventListener('click', function () {
            const currentTheme = root.getAttribute('data-theme') || 'light';
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';

            setTheme(nextTheme);
        });
    });
</script>
</body>
</html>
