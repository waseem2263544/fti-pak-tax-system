<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FTI Pak Tax Management')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #303a50;
            --primary-dark: #1e2536;
            --primary-light: #3f4d68;
            --accent: #D7DF27;
            --accent-dark: #bcc41f;
            --accent-glow: rgba(215,223,39,0.12);
            --sidebar-w: 270px;
            --radius: 12px;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.07);
            --shadow-lg: 0 8px 30px rgba(0,0,0,0.10);
        }

        *, *::before, *::after { font-family: 'Plus Jakarta Sans', sans-serif; }

        body { background: #eef1f6; color: #1f2937; margin: 0; }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0;
            width: var(--sidebar-w);
            background: linear-gradient(180deg, #2a3347 0%, var(--primary-dark) 100%);
            display: flex; flex-direction: column;
            z-index: 200;
            overflow-y: auto; overflow-x: hidden;
            box-shadow: 4px 0 20px rgba(0,0,0,0.08);
        }

        .sidebar::before {
            content: '';
            position: absolute; top: -40%; right: -30%;
            width: 120%; height: 80%;
            background: radial-gradient(circle, rgba(215,223,39,0.05) 0%, transparent 60%);
            pointer-events: none;
        }

        .sidebar-brand {
            padding: 28px 24px 24px;
            position: relative;
        }

        .sidebar-brand .logo {
            display: flex; align-items: center; gap: 12px;
        }

        .sidebar-brand .logo-icon {
            width: 40px; height: 40px;
            background: var(--accent);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 1rem; color: var(--primary);
            letter-spacing: -0.5px;
        }

        .sidebar-brand .logo-text h5 {
            margin: 0; font-size: 1.15rem; font-weight: 800;
            color: #fff; letter-spacing: 0.3px;
        }

        .sidebar-brand .logo-text span {
            font-size: 0.7rem; color: rgba(255,255,255,0.4);
            font-weight: 500; letter-spacing: 0.5px; text-transform: uppercase;
        }

        .sidebar-section { padding: 8px 16px; position: relative; }

        .sidebar-section-label {
            font-size: 0.65rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1.2px;
            color: rgba(255,255,255,0.25);
            padding: 16px 8px 8px;
        }

        .sidebar a {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 12px; margin: 2px 0;
            border-radius: 10px;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            font-size: 0.85rem; font-weight: 500;
            transition: all 0.2s cubic-bezier(.4,0,.2,1);
            position: relative;
        }

        .sidebar a i {
            font-size: 1.05rem; width: 20px; text-align: center;
            transition: color 0.2s;
        }

        .sidebar a:hover {
            color: #fff;
            background: rgba(255,255,255,0.07);
        }

        .sidebar a.active {
            color: #fff;
            background: linear-gradient(135deg, rgba(215,223,39,0.15) 0%, rgba(215,223,39,0.08) 100%);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .sidebar a.active i { color: var(--accent); }

        .sidebar a.active::before {
            content: '';
            position: absolute; left: -16px; top: 50%; transform: translateY(-50%);
            width: 4px; height: 24px;
            background: linear-gradient(180deg, var(--accent) 0%, #a8b01a 100%);
            border-radius: 0 4px 4px 0;
            box-shadow: 0 0 8px rgba(215,223,39,0.4);
        }

        .sidebar-user {
            margin-top: auto;
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.06);
            display: flex; align-items: center; gap: 12px;
            position: relative;
            background: rgba(0,0,0,0.1);
        }

        .sidebar-user-avatar {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--accent) 0%, #a8b01a 100%);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.8rem; color: var(--primary);
            box-shadow: 0 2px 8px rgba(215,223,39,0.3);
        }

        .sidebar-user-info { flex: 1; min-width: 0; }
        .sidebar-user-info .name {
            font-size: 0.82rem; font-weight: 600; color: #fff;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-user-info .role {
            font-size: 0.68rem; color: rgba(255,255,255,0.35);
        }

        /* ── MAIN ── */
        .main-wrapper { margin-left: var(--sidebar-w); min-height: 100vh; }

        /* ── TOP NAV ── */
        .top-nav {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 0 32px;
            height: 64px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid rgba(232,234,237,0.6);
            position: sticky; top: 0; z-index: 100;
        }

        .top-nav .page-title {
            font-size: 1.15rem; font-weight: 700; color: var(--primary); margin: 0;
        }

        .top-nav-actions { display: flex; align-items: center; gap: 8px; }

        .nav-icon-btn {
            width: 40px; height: 40px;
            border-radius: 10px; border: 1px solid #edf0f2;
            background: #fff;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; position: relative;
            transition: all 0.2s;
        }

        .nav-icon-btn:hover { background: #f8f9fb; border-color: #dde1e6; }
        .nav-icon-btn i { font-size: 1.1rem; color: #6b7280; }

        .notification-badge {
            position: absolute; top: 4px; right: 4px;
            background: #ef4444; color: #fff;
            width: 16px; height: 16px; border-radius: 50%;
            font-size: 9px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid #fff;
        }

        .btn-logout-nav {
            padding: 8px 16px; border-radius: 10px;
            border: 1px solid #edf0f2; background: #fff;
            font-size: 0.8rem; font-weight: 500; color: #6b7280;
            cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; gap: 6px;
        }
        .btn-logout-nav:hover { background: #fef2f2; color: #ef4444; border-color: #fecaca; }

        /* ── CONTENT ── */
        .main-content { padding: 28px 32px; min-height: calc(100vh - 64px); }

        /* ── CARDS ── */
        .card {
            border: none;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
            background: #fff;
            transition: box-shadow 0.2s;
        }

        .card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }

        .card-header {
            background: #fff;
            border-bottom: 1px solid #f0f2f5;
            padding: 16px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--primary);
        }

        /* Stat cards */
        .stat-card {
            padding: 24px;
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(.4,0,.2,1);
            position: relative;
            overflow: hidden;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .stat-card::after {
            content: '';
            position: absolute; top: 0; right: 0;
            width: 100px; height: 100px;
            background: radial-gradient(circle at top right, rgba(215,223,39,0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.08);
        }

        .stat-card .stat-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }

        .stat-card .stat-value {
            font-size: 2rem; font-weight: 800;
            color: var(--primary); line-height: 1;
            margin-bottom: 4px;
        }

        .stat-card .stat-label {
            font-size: 0.72rem; font-weight: 600;
            color: #9ca3af; text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        /* ── TABLES ── */
        .table thead th {
            font-size: 0.7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: #9ca3af;
            border-bottom: 2px solid #f0f2f5;
            padding: 14px 20px;
            background: linear-gradient(180deg, #fafbfc 0%, #f6f7f9 100%);
        }

        .table td {
            padding: 14px 20px;
            font-size: 0.85rem;
            border-bottom: 1px solid #f5f6f8;
            vertical-align: middle;
        }

        .table-hover tbody tr { transition: background 0.15s; }
        .table-hover tbody tr:hover { background: rgba(215,223,39,0.03); }

        /* ── BUTTONS ── */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border: none; border-radius: 10px; font-weight: 600; font-size: 0.85rem;
            padding: 9px 20px; transition: all 0.25s; color: #fff;
            box-shadow: 0 2px 6px rgba(48,58,80,0.2);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(48,58,80,0.25); background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%); }

        .btn-accent {
            background: linear-gradient(135deg, var(--accent) 0%, #c8d020 100%);
            border: none; color: var(--primary); border-radius: 10px;
            font-weight: 700; font-size: 0.85rem;
            padding: 10px 22px; transition: all 0.25s;
            box-shadow: 0 2px 8px rgba(215,223,39,0.3);
        }
        .btn-accent:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(215,223,39,0.35); color: var(--primary); }

        .btn-outline-primary {
            color: var(--primary); border: 1.5px solid #dde1e8; border-radius: 10px;
            font-weight: 500; font-size: 0.82rem; transition: all 0.2s;
        }
        .btn-outline-primary:hover { background: var(--primary); border-color: var(--primary); color: #fff; box-shadow: 0 2px 8px rgba(48,58,80,0.15); }

        /* ── BADGES ── */
        .badge {
            font-weight: 600; font-size: 0.7rem;
            padding: 5px 10px; border-radius: 20px;
            letter-spacing: 0.3px;
        }

        /* ── FORMS ── */
        .form-control, .form-select {
            border-radius: 10px; border: 1.5px solid #e2e5ea;
            font-size: 0.85rem; padding: 10px 14px;
            transition: all 0.25s; background: #fff;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(215,223,39,0.12);
            background: #fff;
        }
        .form-control:hover, .form-select:hover { border-color: #c9cdd4; }
        .form-label { font-weight: 600; font-size: 0.82rem; color: #374151; margin-bottom: 6px; }

        /* ── ALERTS ── */
        .alert { border-radius: var(--radius); border: none; font-size: 0.85rem; font-weight: 500; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .alert-success { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); color: #065f46; border-left: 4px solid #10b981; }
        .alert-danger { background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%); color: #991b1b; border-left: 4px solid #ef4444; }

        /* Pagination */
        .pagination { gap: 4px; }
        .page-link { border-radius: 8px !important; border: 1px solid #e8eaed; color: var(--primary); font-size: 0.82rem; font-weight: 500; }
        .page-item.active .page-link { background: var(--primary); border-color: var(--primary); color: #fff; }

        /* ── SCROLLBAR ── */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }

        /* ── ESCALATED ── */
        .escalated { border-left: 3px solid #ef4444; }

        /* ── GUEST ── */
        .guest-wrapper {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }

        /* ── TOM SELECT ── */
        .ts-wrapper { font-family: 'Plus Jakarta Sans', sans-serif; }
        .ts-wrapper .ts-control {
            border: 1.5px solid #e5e7eb !important;
            border-radius: 10px !important;
            padding: 7px 12px !important;
            font-size: 0.85rem !important;
            min-height: 40px !important;
            background: #fff !important;
        }
        .ts-wrapper.focus .ts-control { border-color: var(--accent) !important; box-shadow: 0 0 0 4px rgba(215,223,39,0.12) !important; }
        .ts-wrapper .ts-dropdown { border-radius: 10px !important; border: 1.5px solid #e5e7eb !important; box-shadow: 0 8px 24px rgba(0,0,0,0.08) !important; margin-top: 4px !important; }
        .ts-wrapper .ts-dropdown .option { font-size: 0.85rem !important; padding: 8px 14px !important; }
        .ts-wrapper .ts-dropdown .option.active { background: var(--accent-glow) !important; color: var(--primary) !important; }
        .ts-wrapper .ts-dropdown .option:hover { background: #f8f9fb !important; }
        .ts-wrapper .ts-control > input { font-size: 0.85rem !important; }
        .ts-wrapper.form-select-sm .ts-control { min-height: 32px !important; padding: 4px 10px !important; font-size: 0.82rem !important; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-wrapper { margin-left: 0; }
        }
    </style>
    @yield('styles')
</head>
<body>
    @auth
    <div>
        <div class="sidebar">
            <div class="sidebar-brand">
                <div class="logo">
                    <img src="/images/logo.png" alt="FairTax International" style="max-width: 180px; height: auto; display: block; filter: drop-shadow(0 0 1px rgba(255,255,255,0.8)) brightness(1.5);">
                </div>
            </div>

            <div class="sidebar-section">
                <div class="sidebar-section-label">Main Menu</div>
                <a href="{{ route('dashboard') }}" class="@if(Route::currentRouteName() == 'dashboard') active @endif">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
                <a href="{{ route('clients.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'clients.')) active @endif">
                    <i class="bi bi-people-fill"></i> Clients
                </a>
                <a href="{{ route('tasks.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'tasks.')) active @endif">
                    <i class="bi bi-check2-square"></i> Tasks
                </a>
                <a href="{{ route('proceedings.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'proceedings.')) active @endif">
                    <i class="bi bi-bank2"></i> Proceedings
                </a>
                <a href="{{ route('fbr-notices.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'fbr-notices.')) active @endif">
                    <i class="bi bi-envelope-paper-fill"></i> FBR Notifications
                </a>
                <a href="{{ route('client-documents.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'client-documents.')) active @endif">
                    <i class="bi bi-cloud-fill"></i> Client Documents
                </a>
                <a href="{{ route('files.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'files.')) active @endif">
                    <i class="bi bi-folder-fill"></i> File Management
                </a>
                <a href="{{ route('news.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'news.')) active @endif">
                    <i class="bi bi-newspaper"></i> Tax News
                </a>

                <div class="sidebar-section-label">Operations</div>
                <a href="{{ route('processes.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'processes.')) active @endif">
                    <i class="bi bi-arrow-repeat"></i> Processes
                </a>
                <a href="{{ route('mini-apps.index') }}" class="@if(Route::currentRouteName() == 'mini-apps.index') active @endif">
                    <i class="bi bi-puzzle-fill"></i> Mini Apps
                </a>

                @if(Auth::user()->hasRole('admin'))
                <div class="sidebar-section-label">Administration</div>
                <a href="{{ route('employees.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'employees.')) active @endif">
                    <i class="bi bi-person-badge-fill"></i> Employees
                </a>
                <a href="{{ route('settings.email') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'settings.')) active @endif">
                    <i class="bi bi-envelope-at"></i> Email Integration
                </a>
                @endif
            </div>

            <div class="sidebar-user">
                <div class="sidebar-user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</div>
                <div class="sidebar-user-info">
                    <div class="name">{{ Auth::user()->name }}</div>
                    <div class="role">{{ Auth::user()->roles->first()->display_name ?? 'User' }}</div>
                </div>
            </div>
        </div>

        <div class="main-wrapper">
            <div class="top-nav">
                <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                <div class="top-nav-actions">
                    <!-- Global Search -->
                    <div style="position: relative;" id="search-wrapper">
                        <form action="{{ route('search') }}" method="GET" style="margin: 0;">
                            <div style="position: relative;">
                                <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.85rem;"></i>
                                <input type="text" name="q" id="global-search" placeholder="Search clients, tasks, proceedings..." autocomplete="off"
                                    style="width: 280px; padding: 8px 12px 8px 36px; border: 1.5px solid #edf0f2; border-radius: 10px; font-size: 0.82rem; background: #f8f9fb; transition: all 0.2s; outline: none;"
                                    onfocus="this.style.width='340px'; this.style.borderColor='var(--accent)'; this.style.background='#fff'; this.style.boxShadow='0 0 0 4px rgba(215,223,39,0.12)'"
                                    onblur="setTimeout(function(){document.getElementById('global-search').style.width='280px'; document.getElementById('global-search').style.borderColor='#edf0f2'; document.getElementById('global-search').style.background='#f8f9fb'; document.getElementById('global-search').style.boxShadow='none'; document.getElementById('search-dropdown').style.display='none';}, 200)"
                                    oninput="searchSuggest(this.value)">
                            </div>
                        </form>
                        <div id="search-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; margin-top: 6px; background: #fff; border-radius: 12px; border: 1.5px solid #e8eaed; box-shadow: 0 8px 30px rgba(0,0,0,0.1); z-index: 1000; max-height: 320px; overflow-y: auto;"></div>
                    </div>
                    <a href="{{ route('notifications.index') }}" class="nav-icon-btn" title="Notifications" style="text-decoration: none;">
                        <i class="bi bi-bell"></i>
                        <div class="notification-badge" id="notif-count" style="display: none;">0</div>
                    </a>
                    <button class="btn-logout-nav" onclick="document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
                </div>
            </div>

            <div class="main-content">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif
                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif
                @yield('content')
            </div>
        </div>
    </div>
    @endauth

    @guest
    <div class="guest-wrapper">
        @yield('content')
    </div>
    @endguest

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('select.searchable, select[name="client_id"], select[name="service_id"], select[name="assigned_to"], select[name="assigned_users[]"], select[name="stage"], select[name="status"], select[name="trigger_type"], select[name="service"], select[name="roles[]"], select[name="shareholders[]"], select[name="services[]"]').forEach(function(el) {
                if (el.tomselect) return;
                new TomSelect(el, {
                    allowEmptyOption: true,
                    placeholder: el.options[0] && el.options[0].value === '' ? el.options[0].text : 'Select...',
                    controlInput: '<input>',
                    render: {
                        no_results: function() { return '<div class="no-results" style="padding:10px;color:#9ca3af;font-size:0.85rem;">No match found</div>'; }
                    }
                });
            });
        });
    </script>
    @auth
    <script>
        function loadNotifications() {
            fetch('{{ route("notifications.latest") }}')
                .then(r => r.json())
                .then(data => {
                    const count = data.filter(n => !n.is_read).length;
                    const badge = document.getElementById('notif-count');
                    if (count > 0) { badge.textContent = count; badge.style.display = 'flex'; }
                    else { badge.style.display = 'none'; }
                }).catch(() => {});
        }
        setInterval(loadNotifications, 60000);
        loadNotifications();
    </script>
    @endauth
    @auth
    <script>
    var searchTimer;
    function searchSuggest(q) {
        clearTimeout(searchTimer);
        var dd = document.getElementById('search-dropdown');
        if (q.length < 2) { dd.style.display = 'none'; return; }
        searchTimer = setTimeout(function() {
            fetch('/search/suggest?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    if (data.length === 0) {
                        dd.innerHTML = '<div style="padding: 16px; text-align: center; color: #9ca3af; font-size: 0.82rem;">No results for "'+q+'"</div>';
                    } else {
                        dd.innerHTML = data.map(function(item) {
                            return '<a href="'+item.url+'" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; text-decoration: none; color: var(--primary); border-bottom: 1px solid #f5f6f8; font-size: 0.85rem; transition: background 0.1s;" onmouseover="this.style.background=\'#f8f9fb\'" onmouseout="this.style.background=\'#fff\'">'
                                + '<i class="bi '+item.icon+'" style="color: #9ca3af; font-size: 1rem; width: 20px; text-align: center;"></i>'
                                + '<div><div style="font-weight: 600;">'+item.title+'</div><div style="font-size: 0.72rem; color: #9ca3af;">'+item.type+'</div></div></a>';
                        }).join('');
                        dd.innerHTML += '<a href="/search?q='+encodeURIComponent(q)+'" style="display: block; padding: 10px 16px; text-align: center; font-size: 0.8rem; color: var(--primary); font-weight: 600; text-decoration: none; background: #fafbfc;">View all results <i class="bi bi-arrow-right"></i></a>';
                    }
                    dd.style.display = 'block';
                }).catch(function() { dd.style.display = 'none'; });
        }, 250);
    }
    </script>

    <!-- @Mention System -->
    <style>
        .mention-container { position: relative; }
        .mention-dropdown {
            display: none; position: absolute; bottom: 100%; left: 0; right: 0;
            background: #fff; border-radius: 10px; border: 1.5px solid #e5e7eb;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1); z-index: 1000;
            max-height: 200px; overflow-y: auto; margin-bottom: 4px;
        }
        .mention-dropdown.show { display: block; }
        .mention-item {
            display: flex; align-items: center; gap: 10px; padding: 8px 14px;
            cursor: pointer; font-size: 0.85rem; transition: background 0.1s;
        }
        .mention-item:hover, .mention-item.active { background: rgba(215,223,39,0.08); }
        .mention-item .avatar {
            width: 28px; height: 28px; border-radius: 6px;
            background: linear-gradient(135deg, var(--accent) 0%, #a8b01a 100%);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.6rem; color: var(--primary);
        }
        .mention-tag {
            background: rgba(215,223,39,0.15); color: var(--primary);
            padding: 1px 6px; border-radius: 4px; font-weight: 600;
            font-size: 0.85rem;
        }
    </style>
    <script>
    // @Mention system - init all textareas now and any added later
    var mentionItems = [];
    var mentionActiveIdx = 0;

    function initMentions() {
        var textareas = document.querySelectorAll('textarea[name="body"]');
        console.log('Mention init: found ' + textareas.length + ' textareas');
        textareas.forEach(function(ta) {
            if (ta.dataset.mentionInit) return;
            ta.dataset.mentionInit = '1';
            console.log('Mention: initialized textarea');

            // Wrap textarea in a positioned container
            var wrapper = document.createElement('div');
            wrapper.style.position = 'relative';
            wrapper.style.flex = '1';
            ta.parentNode.insertBefore(wrapper, ta);
            wrapper.appendChild(ta);

            var dd = document.createElement('div');
            dd.className = 'mention-dropdown';
            wrapper.appendChild(dd);

            ta.addEventListener('input', function() {
                var val = ta.value, pos = ta.selectionStart;
                var before = val.substring(0, pos);
                var atIdx = before.lastIndexOf('@');
                if (atIdx >= 0 && (atIdx === 0 || ' \n'.includes(before[atIdx - 1]))) {
                    var q = before.substring(atIdx + 1);
                    if (q.length >= 1 && !q.includes(' ') && !q.includes('\n')) {
                        mentionFetch(q, dd, ta);
                        return;
                    }
                }
                dd.classList.remove('show');
            });

            ta.addEventListener('keydown', function(e) {
                if (!dd.classList.contains('show')) return;
                if (e.key === 'ArrowDown') { e.preventDefault(); mentionActiveIdx = Math.min(mentionActiveIdx + 1, mentionItems.length - 1); mentionHighlight(dd); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); mentionActiveIdx = Math.max(mentionActiveIdx - 1, 0); mentionHighlight(dd); }
                else if (e.key === 'Enter' || e.key === 'Tab') { if (mentionItems.length > 0) { e.preventDefault(); mentionSelect(mentionItems[mentionActiveIdx], ta, dd); } }
                else if (e.key === 'Escape') { dd.classList.remove('show'); }
            });
        });
    }

    function mentionFetch(q, dd, ta) {
        console.log('Mention: fetching users for "' + q + '"');
        fetch('/mentions/users?q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                mentionItems = data;
                mentionActiveIdx = 0;
                if (!data.length) { dd.classList.remove('show'); return; }
                dd.innerHTML = data.map(function(u, i) {
                    return '<div class="mention-item' + (i === 0 ? ' active' : '') + '">'
                        + '<div class="avatar">' + u.initials + '</div>'
                        + '<span style="font-weight:600;color:var(--primary);">' + u.name + '</span></div>';
                }).join('');
                dd.classList.add('show');
                dd.querySelectorAll('.mention-item').forEach(function(el, i) {
                    el.addEventListener('mousedown', function(e) { e.preventDefault(); mentionSelect(data[i], ta, dd); });
                });
            }).catch(function() { dd.classList.remove('show'); });
    }

    function mentionSelect(user, ta, dd) {
        var val = ta.value, pos = ta.selectionStart;
        var before = val.substring(0, pos);
        var atIdx = before.lastIndexOf('@');
        var after = val.substring(pos);
        var insert = '@[user:' + user.id + ']' + user.name + ' ';
        ta.value = val.substring(0, atIdx) + insert + after;
        var np = atIdx + insert.length;
        ta.focus();
        ta.setSelectionRange(np, np);
        dd.classList.remove('show');
    }

    function mentionHighlight(dd) {
        dd.querySelectorAll('.mention-item').forEach(function(el, i) { el.classList.toggle('active', i === mentionActiveIdx); });
    }

    // Run after full page load (including yield scripts content)
    window.addEventListener('load', initMentions);
    </script>
    @endauth
    @yield('scripts')
</body>
</html>
