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

        body { background: #f0f2f5; color: #1f2937; margin: 0; }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--primary);
            display: flex; flex-direction: column;
            z-index: 200;
            overflow: hidden;
        }

        .sidebar::before {
            content: '';
            position: absolute; top: -60%; right: -40%;
            width: 100%; height: 100%;
            background: radial-gradient(circle, rgba(215,223,39,0.06) 0%, transparent 70%);
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
            background: rgba(215,223,39,0.12);
        }

        .sidebar a.active i { color: var(--accent); }

        .sidebar a.active::before {
            content: '';
            position: absolute; left: -16px; top: 50%; transform: translateY(-50%);
            width: 4px; height: 24px;
            background: var(--accent);
            border-radius: 0 4px 4px 0;
        }

        .sidebar-user {
            margin-top: auto;
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.06);
            display: flex; align-items: center; gap: 12px;
            position: relative;
        }

        .sidebar-user-avatar {
            width: 36px; height: 36px;
            background: var(--primary-light);
            border: 2px solid rgba(215,223,39,0.3);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.8rem; color: var(--accent);
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
            background: #fff;
            padding: 0 32px;
            height: 64px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid #e8eaed;
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
        .main-content { padding: 28px 32px; }

        /* ── CARDS ── */
        .card {
            border: 1px solid #e8eaed;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            background: #fff;
        }

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
            border-radius: var(--radius);
            transition: all 0.25s cubic-bezier(.4,0,.2,1);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
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
            font-size: 0.75rem; font-weight: 600;
            color: #9ca3af; text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        /* ── TABLES ── */
        .table thead th {
            font-size: 0.7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: #9ca3af;
            border-bottom: 1px solid #f0f2f5;
            padding: 14px 20px;
            background: #fafbfc;
        }

        .table td {
            padding: 14px 20px;
            font-size: 0.85rem;
            border-bottom: 1px solid #f8f9fb;
            vertical-align: middle;
        }

        .table-hover tbody tr:hover { background: #fafbfc; }

        /* ── BUTTONS ── */
        .btn-primary {
            background: var(--primary); border-color: var(--primary);
            border-radius: 10px; font-weight: 600; font-size: 0.85rem;
            padding: 9px 20px; transition: all 0.2s;
        }
        .btn-primary:hover { background: var(--primary-light); border-color: var(--primary-light); transform: translateY(-1px); }

        .btn-accent {
            background: var(--accent); border: 2px solid var(--accent);
            color: var(--primary); border-radius: 10px;
            font-weight: 700; font-size: 0.85rem;
            padding: 9px 20px; transition: all 0.2s;
        }
        .btn-accent:hover { background: var(--accent-dark); border-color: var(--accent-dark); color: var(--primary); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(215,223,39,0.3); }

        .btn-outline-primary {
            color: var(--primary); border-color: #e0e3e8; border-radius: 10px;
            font-weight: 500; font-size: 0.82rem;
        }
        .btn-outline-primary:hover { background: var(--primary); border-color: var(--primary); color: #fff; }

        /* ── BADGES ── */
        .badge {
            font-weight: 600; font-size: 0.7rem;
            padding: 5px 10px; border-radius: 6px;
            letter-spacing: 0.3px;
        }

        /* ── FORMS ── */
        .form-control, .form-select {
            border-radius: 10px; border: 1.5px solid #e5e7eb;
            font-size: 0.85rem; padding: 10px 14px;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(215,223,39,0.12);
        }
        .form-label { font-weight: 600; font-size: 0.82rem; color: #374151; margin-bottom: 6px; }

        /* ── ALERTS ── */
        .alert { border-radius: var(--radius); border: none; font-size: 0.85rem; font-weight: 500; }
        .alert-success { background: #ecfdf5; color: #065f46; }
        .alert-danger { background: #fef2f2; color: #991b1b; }

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
                <a href="{{ route('fbr-notices.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'fbr-notices.')) active @endif">
                    <i class="bi bi-envelope-paper-fill"></i> FBR Notifications
                </a>

                <a href="{{ route('files.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'files.')) active @endif">
                    <i class="bi bi-folder-fill"></i> File Management
                </a>

                <div class="sidebar-section-label">Operations</div>
                <a href="{{ route('processes.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'processes.')) active @endif">
                    <i class="bi bi-arrow-repeat"></i> Processes
                </a>
                <a href="{{ route('proceedings.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'proceedings.')) active @endif">
                    <i class="bi bi-bank2"></i> Proceedings
                </a>

                @if(Auth::user()->hasRole('admin'))
                <div class="sidebar-section-label">Administration</div>
                <a href="{{ route('employees.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'employees.')) active @endif">
                    <i class="bi bi-person-badge-fill"></i> Employees
                </a>
                @endif

                <div class="sidebar-section-label">Workspace</div>
                <a href="{{ route('mini-apps.index') }}" class="@if(Route::currentRouteName() == 'mini-apps.index') active @endif">
                    <i class="bi bi-puzzle-fill"></i> Mini Apps
                </a>
                <div class="sidebar-section-label">Settings</div>
                <a href="{{ route('settings.email') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'settings.')) active @endif">
                    <i class="bi bi-envelope-at"></i> Email Integration
                </a>
                <a href="{{ route('scheduled-tasks.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'scheduled-tasks.')) active @endif">
                    <i class="bi bi-clock-history"></i> Scheduled Tasks
                </a>
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
    @yield('scripts')
</body>
</html>
