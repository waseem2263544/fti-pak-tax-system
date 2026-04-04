<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FTI Pak Tax Management')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #303a50;
            --primary-dark: #242d3f;
            --primary-light: #3d4a63;
            --accent: #D7DF27;
            --accent-hover: #c5cc20;
            --sidebar-width: 260px;
            --text-light: #e8e9ec;
            --text-muted-light: #9ba3b5;
        }

        * { font-family: 'Inter', sans-serif; }

        body {
            background-color: #f4f5f7;
            color: #333;
        }

        /* Sidebar */
        .sidebar {
            min-height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--text-light);
            padding: 0;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-brand {
            padding: 24px 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-brand h5 {
            font-weight: 700;
            font-size: 1.2rem;
            margin: 0;
            color: var(--accent);
            letter-spacing: 0.5px;
        }

        .sidebar-brand small {
            color: var(--text-muted-light);
            font-size: 0.75rem;
        }

        .sidebar nav {
            padding: 12px 0;
        }

        .sidebar a {
            color: var(--text-muted-light);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 11px 24px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .sidebar a i {
            font-size: 1.1rem;
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .sidebar a:hover {
            color: #fff;
            background-color: rgba(255,255,255,0.06);
            border-left-color: rgba(215,223,39,0.3);
        }

        .sidebar a.active {
            color: #fff;
            background-color: rgba(215,223,39,0.1);
            border-left-color: var(--accent);
        }

        .sidebar a.active i {
            color: var(--accent);
        }

        .sidebar hr {
            border-color: rgba(255,255,255,0.08);
            margin: 8px 20px;
        }

        /* Main Content */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1;
        }

        /* Top Navbar */
        .top-nav {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .top-nav .page-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
            margin: 0;
        }

        .top-nav-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        /* Notification Bell */
        .notification-bell {
            position: relative;
            cursor: pointer;
            padding: 6px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .notification-bell:hover {
            background: #f0f1f3;
        }

        .notification-bell i {
            font-size: 1.2rem;
            color: var(--primary);
        }

        .notification-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #ef4444;
            color: #fff;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
        }

        .btn-logout {
            background: none;
            border: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 0.85rem;
            padding: 6px 14px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background: #f9fafb;
            color: #ef4444;
            border-color: #fecaca;
        }

        /* Content */
        .main-content {
            padding: 24px 28px;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        }

        .card-header {
            background: none;
            border-bottom: 1px solid #f0f1f3;
            font-weight: 600;
            padding: 16px 20px;
        }

        /* Tables */
        .table thead th {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            border-bottom: 2px solid #f0f1f3;
            padding: 12px 16px;
        }

        .table td {
            padding: 12px 16px;
            vertical-align: middle;
            font-size: 0.875rem;
            border-bottom: 1px solid #f8f9fa;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(215,223,39,0.04);
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            padding: 8px 18px;
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
            border-color: var(--primary-light);
        }

        .btn-accent {
            background-color: var(--accent);
            border-color: var(--accent);
            color: var(--primary);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 8px 18px;
        }

        .btn-accent:hover {
            background-color: var(--accent-hover);
            border-color: var(--accent-hover);
            color: var(--primary);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
            border-radius: 8px;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        /* Badges */
        .badge {
            font-weight: 500;
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 6px;
        }

        .badge-accent {
            background-color: var(--accent);
            color: var(--primary);
        }

        /* Alerts */
        .alert {
            border-radius: 10px;
            border: none;
            font-size: 0.875rem;
        }

        /* Form Controls */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            font-size: 0.875rem;
            padding: 9px 14px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(215,223,39,0.15);
        }

        .form-label {
            font-weight: 500;
            font-size: 0.85rem;
            color: #374151;
        }

        /* Dashboard Stats */
        .stat-card {
            border-radius: 12px;
            padding: 20px;
            border: none;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-card .stat-label {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        /* Escalated border */
        .escalated {
            border-left: 4px solid #ef4444;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }

        /* Guest layout */
        .guest-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            overflow: hidden;
        }

        /* Pagination */
        .pagination { gap: 4px; }
        .page-link {
            border-radius: 8px !important;
            border: none;
            color: var(--primary);
            font-size: 0.85rem;
            padding: 6px 12px;
        }
        .page-item.active .page-link {
            background-color: var(--primary);
            color: #fff;
        }
    </style>
    @yield('styles')
</head>
<body>
    @auth
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-brand">
                <h5>FTI PAK</h5>
                <small>Tax Management System</small>
            </div>

            <nav>
                <a href="{{ route('dashboard') }}" class="@if(Route::currentRouteName() == 'dashboard') active @endif">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>

                <a href="{{ route('clients.index') }}" class="@if(str_starts_with(Route::currentRouteName(), 'clients.')) active @endif">
                    <i class="bi bi-people-fill"></i> Clients
                </a>

                <a href="{{ route('tasks.index') }}" class="@if(str_starts_with(Route::currentRouteName(), 'tasks.')) active @endif">
                    <i class="bi bi-check2-square"></i> Tasks
                </a>

                <a href="{{ route('fbr-notices.index') }}" class="@if(str_starts_with(Route::currentRouteName(), 'fbr-notices.')) active @endif">
                    <i class="bi bi-envelope-paper-fill"></i> FBR Notices
                </a>

                @if(Auth::user()->hasRole('admin'))
                <a href="{{ route('employees.index') }}" class="@if(str_starts_with(Route::currentRouteName(), 'employees.')) active @endif">
                    <i class="bi bi-person-badge-fill"></i> Employees
                </a>
                @endif

                <a href="{{ route('mini-apps.index') }}" class="@if(Route::currentRouteName() == 'mini-apps.index') active @endif">
                    <i class="bi bi-puzzle-fill"></i> Mini Apps
                </a>

                <hr>

                <a href="{{ route('notifications.index') }}" class="@if(Route::currentRouteName() == 'notifications.index') active @endif">
                    <i class="bi bi-bell-fill"></i> Notifications
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-wrapper">
            <!-- Top Nav -->
            <div class="top-nav">
                <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                <div class="top-nav-actions">
                    <div class="notification-bell" onclick="loadNotifications()">
                        <i class="bi bi-bell-fill"></i>
                        <div class="notification-badge" id="notif-count" style="display: none;">0</div>
                    </div>
                    <span class="text-muted small">{{ Auth::user()->name }}</span>
                    <button class="btn-logout" onclick="document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </div>

            <!-- Content -->
            <div class="main-content">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-1"></i> {{ session('error') }}
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

    @auth
    <script>
        function loadNotifications() {
            fetch('{{ route("notifications.latest") }}')
                .then(r => r.json())
                .then(data => {
                    const count = data.filter(n => !n.is_read).length;
                    if (count > 0) {
                        document.getElementById('notif-count').textContent = count;
                        document.getElementById('notif-count').style.display = 'flex';
                    } else {
                        document.getElementById('notif-count').style.display = 'none';
                    }
                });
        }
        setInterval(loadNotifications, 60000);
        loadNotifications();
    </script>
    @endauth

    @yield('scripts')
</body>
</html>
