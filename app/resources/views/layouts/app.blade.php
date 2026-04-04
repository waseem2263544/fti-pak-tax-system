<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FTI Pak Tax Management')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #2c3e50;
            color: white;
            padding: 20px 0;
        }
        .sidebar a {
            color: #ecf0f1;
            text-decoration: none;
            display: block;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        .sidebar a:hover {
            background-color: #34495e;
            padding-left: 30px;
        }
        .sidebar a.active {
            background-color: #3498db;
            border-left: 4px solid #2980b9;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .notification-bell {
            position: relative;
            cursor: pointer;
        }
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .escalated {
            border-left: 4px solid #e74c3c;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="d-flex">
        @auth
        <!-- Sidebar -->
        <div class="sidebar" style="width: 250px;">
            <div class="ps-3 mb-3">
                <h5 class="mb-0">FTI Pak Tax</h5>
                <small class="text-muted">Management System</small>
            </div>

            <nav>
                <a href="{{ route('dashboard') }}" class="@if(Route::currentRouteName() == 'dashboard') active @endif">
                    <i class="bi bi-house"></i> Dashboard
                </a>
                
                <a href="{{ route('clients.index') }}" class="@if(Route::currentRouteName() == 'clients.index') active @endif">
                    <i class="bi bi-people"></i> Clients
                </a>

                <a href="{{ route('tasks.index') }}" class="@if(Route::currentRouteName() == 'tasks.index') active @endif">
                    <i class="bi bi-check-square"></i> Tasks
                </a>

                <a href="{{ route('fbr-notices.index') }}" class="@if(Route::currentRouteName() == 'fbr-notices.index') active @endif">
                    <i class="bi bi-envelope"></i> FBR Notices
                </a>

                @if(Auth::user()->hasRole('admin'))
                <a href="{{ route('employees.index') }}" class="@if(Route::currentRouteName() == 'employees.index') active @endif">
                    <i class="bi bi-person-badge"></i> Employees
                </a>
                @endif

                <a href="{{ route('mini-apps.index') }}" class="@if(Route::currentRouteName() == 'mini-apps.index') active @endif">
                    <i class="bi bi-puzzle"></i> Mini Apps
                </a>

                <hr class="border-secondary">

                <a href="{{ route('notifications.index') }}">
                    <i class="bi bi-bell"></i> Notifications
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div style="flex: 1;">
            <!-- Top Nav -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <span class="navbar-brand">@yield('page-title', 'Dashboard')</span>
                    <div class="ms-auto">
                        <div class="notification-bell" onclick="loadNotifications()">
                            <i class="bi bi-bell-fill" style="font-size: 20px;"></i>
                            <div class="notification-badge" id="notif-count" style="display: none;">0</div>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="document.getElementById('logout-form').submit();">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </nav>

            <!-- Content -->
            <div class="main-content">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @yield('content')
            </div>
        </div>
        @endauth

        @guest
        <div class="container-fluid">
            @yield('content')
        </div>
        @endguest
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function loadNotifications() {
            fetch('{{ route("notifications.latest") }}')
                .then(r => r.json())
                .then(data => {
                    console.log(data);
                    const count = data.filter(n => !n.is_read).length;
                    if (count > 0) {
                        document.getElementById('notif-count').textContent = count;
                        document.getElementById('notif-count').style.display = 'flex';
                    }
                });
        }

        // Check notifications every minute
        setInterval(loadNotifications, 60000);
        loadNotifications();
    </script>

    @yield('scripts')
</body>
</html>
