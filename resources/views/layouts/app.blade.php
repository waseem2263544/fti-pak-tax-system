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
        /*
         * FTI Pak design system.
         *
         * Two tiers of colour token. The neutral ramp (--n-*) is ordered by
         * lightness, so a value can be swapped for its neighbour anywhere
         * without changing what the element means. Semantic tokens carry
         * meaning instead, and always come as a tint/ink pair.
         *
         * Views should reach for tokens, never raw hex. Restyling the app is
         * then a matter of editing this block alone.
         */
        :root {
            /* Neutral ramp */
            --n-0:   #ffffff;
            --n-25:  #fafbfc;
            --n-50:  #f5f7f9;
            --n-100: #eef1f4;
            --n-150: #e8ebef;
            --n-200: #dfe3e8;
            --n-300: #c7ced7;
            --n-400: #a2abb8;
            --n-500: #77828f;
            --n-600: #55606f;
            --n-700: #3b4553;
            --n-800: #252d3a;
            --n-900: #161d2b;

            /* Surfaces and text */
            --bg:          var(--n-50);
            --surface:     var(--n-0);
            --surface-sunk:var(--n-25);
            --border:      var(--n-150);
            --border-strong: var(--n-200);
            --text:        var(--n-900);
            --text-soft:   var(--n-600);
            --text-muted:  var(--n-500);
            --text-faint:  var(--n-400);

            /* Brand */
            --primary:       #303a50;
            --primary-dark:  #232b3c;
            --primary-light: #44506b;
            --accent:        #D7DF27;
            --accent-dark:   #bcc41f;
            --accent-ink:    #5c6108;
            --accent-glow:   rgba(215,223,39,0.14);

            /* Semantic pairs: tint background, ink foreground */
            --ok:        #10b981;  --ok-tint:     #e7f8f1;  --ok-ink:     #0a6b4d;
            --danger:    #ef4444;  --danger-tint: #fdecec;  --danger-ink: #9b1c1c;
            --warn:      #f59e0b;  --warn-tint:   #fef4e2;  --warn-ink:   #8a5200;
            --info:      #3b82f6;  --info-tint:   #e8f1fe;  --info-ink:   #1a4fa8;

            --sidebar-w: 262px;
            --radius-sm: 6px;
            --radius:    9px;
            --radius-lg: 12px;

            /* Shadows are for things that float. Flat surfaces use a border. */
            --shadow-sm:      0 1px 2px rgba(22,29,43,0.04);
            --shadow-md:      0 4px 12px rgba(22,29,43,0.07);
            --shadow-lg:      0 8px 30px rgba(22,29,43,0.10);
            --shadow-overlay: 0 12px 32px rgba(22,29,43,0.12);
        }

        *, *::before, *::after { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }

        body {
            background: var(--bg);
            color: var(--text);
            margin: 0;
            font-size: 0.875rem;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1, h2, h3, h4, h5, h6 { letter-spacing: -0.015em; color: var(--text); }

        a { text-decoration: none; }

        /* ── SIDEBAR ────────────────────────────────────────────────── */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--primary-dark);
            display: flex; flex-direction: column;
            z-index: 200;
            overflow-y: auto; overflow-x: hidden;
            border-right: 1px solid rgba(255,255,255,0.06);
        }

        .sidebar-brand { padding: 26px 22px 20px; }
        .sidebar-brand .logo { display: flex; align-items: center; gap: 12px; }

        .sidebar-brand .logo-icon {
            width: 38px; height: 38px;
            background: var(--accent);
            border-radius: var(--radius);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.95rem; color: var(--primary-dark);
            letter-spacing: -0.5px;
        }

        .sidebar-brand .logo-text h5 {
            margin: 0; font-size: 1.05rem; font-weight: 700;
            color: #fff; letter-spacing: 0;
        }

        .sidebar-brand .logo-text span {
            font-size: 0.68rem; color: rgba(255,255,255,0.4);
            font-weight: 500; letter-spacing: 0.4px; text-transform: uppercase;
        }

        .sidebar-section { padding: 4px 14px 8px; }

        .sidebar-section-label,
        .sidebar-collapse-toggle {
            font-size: 0.66rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.9px;
            color: rgba(255,255,255,0.32);
            padding: 18px 10px 8px;
        }

        .sidebar-collapse-toggle {
            cursor: pointer; display: flex;
            justify-content: space-between; align-items: center;
            transition: color 0.15s;
            user-select: none;
        }
        .sidebar-collapse-toggle:hover { color: rgba(255,255,255,0.6); }
        .sidebar-collapse-toggle .chevron { transition: transform 0.2s ease; font-size: 0.55rem; opacity: 0.7; }
        .sidebar-collapse-toggle.open .chevron { transform: rotate(90deg); }
        .sidebar-collapsible { max-height: 0; overflow: hidden; transition: max-height 0.25s ease; }
        .sidebar-collapsible.open { max-height: 700px; }

        .sidebar a {
            display: flex; align-items: center; gap: 11px;
            padding: 8px 10px; margin: 1px 0;
            border-radius: var(--radius-sm);
            color: rgba(255,255,255,0.62);
            font-size: 0.83rem; font-weight: 500;
            transition: color 0.15s, background 0.15s;
            position: relative;
        }

        .sidebar a i {
            font-size: 0.98rem; width: 18px; text-align: center;
            opacity: 0.75; transition: opacity 0.15s, color 0.15s;
        }

        .sidebar a:hover { color: #fff; background: rgba(255,255,255,0.05); }
        .sidebar a:hover i { opacity: 1; }

        .sidebar a.active {
            color: #fff;
            background: rgba(255,255,255,0.08);
            font-weight: 600;
        }
        .sidebar a.active i { color: var(--accent); opacity: 1; }

        /* A single quiet rule marks the current page. */
        .sidebar a.active::before {
            content: '';
            position: absolute; left: -14px; top: 50%; transform: translateY(-50%);
            width: 2px; height: 18px;
            background: var(--accent);
            border-radius: 0 2px 2px 0;
        }

        .sidebar-user {
            margin-top: auto;
            padding: 14px 18px;
            border-top: 1px solid rgba(255,255,255,0.07);
            display: flex; align-items: center; gap: 11px;
        }

        .sidebar-user-avatar {
            width: 34px; height: 34px;
            background: var(--accent);
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.78rem; color: var(--primary-dark);
            flex-shrink: 0;
        }

        .sidebar-user-info { flex: 1; min-width: 0; }
        .sidebar-user-info .name {
            font-size: 0.81rem; font-weight: 600; color: #fff;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-user-info .role { font-size: 0.68rem; color: rgba(255,255,255,0.38); }

        /* ── SHELL ──────────────────────────────────────────────────── */
        .main-wrapper { margin-left: var(--sidebar-w); min-height: 100vh; }

        .top-nav {
            background: var(--surface);
            padding: 0 28px;
            height: 60px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100;
        }

        .top-nav .page-title {
            font-size: 1.05rem; font-weight: 700; color: var(--text);
            margin: 0; letter-spacing: -0.02em;
        }

        .top-nav-actions { display: flex; align-items: center; gap: 8px; }

        /* Global search. Styled here rather than through inline JS so the
           focus state is one CSS rule instead of six string assignments. */
        .global-search {
            width: 280px;
            padding: 8px 12px 8px 34px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.82rem;
            background: var(--surface-sunk);
            outline: none;
            transition: width 0.2s ease, border-color 0.15s, background 0.15s, box-shadow 0.15s;
        }
        .global-search::placeholder { color: var(--text-faint); }
        .global-search:focus {
            width: 340px;
            background: var(--surface);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        .global-search-icon {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: var(--text-faint); font-size: 0.82rem; pointer-events: none;
        }
        .global-search-dropdown {
            display: none; position: absolute; top: 100%; left: 0; right: 0;
            margin-top: 6px; background: var(--surface);
            border-radius: var(--radius); border: 1px solid var(--border);
            box-shadow: var(--shadow-overlay);
            z-index: 1000; max-height: 320px; overflow-y: auto;
        }

        .nav-icon-btn {
            width: 36px; height: 36px;
            border-radius: var(--radius); border: 1px solid var(--border);
            background: var(--surface);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; position: relative;
            transition: background 0.15s, border-color 0.15s;
        }
        .nav-icon-btn:hover { background: var(--n-50); border-color: var(--border-strong); }
        .nav-icon-btn i { font-size: 1rem; color: var(--text-soft); }

        .notification-badge {
            position: absolute; top: 3px; right: 3px;
            background: var(--danger); color: #fff;
            width: 15px; height: 15px; border-radius: 50%;
            font-size: 9px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid var(--surface);
        }

        .btn-logout-nav {
            padding: 7px 14px; border-radius: var(--radius);
            border: 1px solid var(--border); background: var(--surface);
            font-size: 0.8rem; font-weight: 500; color: var(--text-soft);
            cursor: pointer; transition: background 0.15s, color 0.15s, border-color 0.15s;
            display: flex; align-items: center; gap: 6px;
        }
        .btn-logout-nav:hover { background: var(--danger-tint); color: var(--danger-ink); border-color: var(--danger-tint); }

        .main-content { padding: 26px 28px 40px; min-height: calc(100vh - 60px); }

        /* ── CARDS ──────────────────────────────────────────────────── */
        .card {
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            background: var(--surface);
            box-shadow: none;
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border);
            padding: 15px 20px;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text);
        }

        .card-footer { background: transparent; border-top: 1px solid var(--border); padding: 14px 20px; }

        .stat-card {
            padding: 20px;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            background: var(--surface);
            box-shadow: none;
            transition: border-color 0.15s;
        }
        .stat-card:hover { border-color: var(--border-strong); }

        .stat-card .stat-icon {
            width: 42px; height: 42px;
            border-radius: var(--radius);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem;
        }

        .stat-card .stat-value {
            font-size: 1.65rem; font-weight: 700;
            color: var(--text); line-height: 1.1;
            margin-bottom: 3px; letter-spacing: -0.02em;
            font-variant-numeric: tabular-nums;
        }

        .stat-card .stat-label {
            font-size: 0.72rem; font-weight: 500;
            color: var(--text-muted); text-transform: none;
            letter-spacing: 0;
        }

        /* ── TABLES ─────────────────────────────────────────────────── */
        .table { margin-bottom: 0; color: var(--text); }

        .table thead th {
            font-size: 0.72rem; font-weight: 600;
            text-transform: none; letter-spacing: 0;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            padding: 11px 18px;
            background: var(--surface-sunk);
            white-space: nowrap;
        }

        .table td {
            padding: 12px 18px;
            font-size: 0.84rem;
            border-bottom: 1px solid var(--n-100);
            vertical-align: middle;
            color: var(--text);
        }

        .table tbody tr:last-child td { border-bottom: none; }
        .table-hover tbody tr { transition: background 0.12s; }
        .table-hover tbody tr:hover { background: var(--n-25); }

        /* Money and counts line up when the digits are tabular. */
        .table td.num, .table th.num, .num { font-variant-numeric: tabular-nums; }

        /* ── BUTTONS ────────────────────────────────────────────────── */
        .btn {
            border-radius: var(--radius);
            font-weight: 500;
            font-size: 0.83rem;
            padding: 8px 16px;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }
        .btn:focus, .btn:focus-visible { box-shadow: 0 0 0 3px var(--accent-glow); }
        .btn-sm { padding: 5px 11px; font-size: 0.78rem; border-radius: var(--radius-sm); }

        .btn-primary {
            background: var(--primary); border: 1px solid var(--primary);
            color: #fff; font-weight: 600; box-shadow: none;
        }
        .btn-primary:hover, .btn-primary:focus {
            background: var(--primary-light); border-color: var(--primary-light); color: #fff;
        }

        .btn-accent {
            background: var(--accent); border: 1px solid var(--accent);
            color: var(--primary-dark); font-weight: 600; box-shadow: none;
        }
        .btn-accent:hover, .btn-accent:focus {
            background: var(--accent-dark); border-color: var(--accent-dark); color: var(--primary-dark);
        }

        .btn-outline-primary {
            color: var(--text); background: var(--surface);
            border: 1px solid var(--border-strong); font-weight: 500;
        }
        .btn-outline-primary:hover, .btn-outline-primary:focus {
            background: var(--n-50); border-color: var(--n-300); color: var(--text);
        }

        .btn-outline-secondary {
            color: var(--text-soft); background: var(--surface);
            border: 1px solid var(--border-strong);
        }
        .btn-outline-secondary:hover { background: var(--n-50); color: var(--text); border-color: var(--n-300); }

        .btn-outline-danger { color: var(--danger-ink); border: 1px solid var(--border-strong); background: var(--surface); }
        .btn-outline-danger:hover { background: var(--danger-tint); border-color: var(--danger-tint); color: var(--danger-ink); }

        .btn-danger { background: var(--danger); border: 1px solid var(--danger); color: #fff; }
        .btn-danger:hover { background: var(--danger-ink); border-color: var(--danger-ink); color: #fff; }

        .btn-light, .btn-secondary { border-radius: var(--radius); }

        /* ── BADGES ─────────────────────────────────────────────────── */
        .badge {
            font-weight: 600; font-size: 0.7rem;
            padding: 4px 9px; border-radius: var(--radius-sm);
            letter-spacing: 0;
        }
        .badge.bg-primary   { background: var(--primary) !important; }
        .badge.bg-success   { background: var(--ok-tint) !important;     color: var(--ok-ink) !important; }
        .badge.bg-danger    { background: var(--danger-tint) !important; color: var(--danger-ink) !important; }
        .badge.bg-warning   { background: var(--warn-tint) !important;   color: var(--warn-ink) !important; }
        .badge.bg-info      { background: var(--info-tint) !important;   color: var(--info-ink) !important; }
        .badge.bg-secondary { background: var(--n-100) !important;       color: var(--text-soft) !important; }
        .badge.bg-light     { background: var(--n-50) !important;        color: var(--text-soft) !important; }

        /* ── FORMS ──────────────────────────────────────────────────── */
        .form-control, .form-select {
            border-radius: var(--radius); border: 1px solid var(--border-strong);
            font-size: 0.84rem; padding: 8px 12px;
            transition: border-color 0.15s, box-shadow 0.15s;
            background: var(--surface);
            color: var(--text);
        }
        .form-control::placeholder { color: var(--text-faint); }
        .form-control:hover, .form-select:hover { border-color: var(--n-300); }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
            background: var(--surface);
        }
        .form-control-sm, .form-select-sm { padding: 5px 10px; font-size: 0.8rem; border-radius: var(--radius-sm); }
        .form-label { font-weight: 500; font-size: 0.8rem; color: var(--text-soft); margin-bottom: 5px; }
        .form-text { font-size: 0.75rem; color: var(--text-muted); }
        .form-check-input:checked { background-color: var(--primary); border-color: var(--primary); }
        .form-check-input:focus { box-shadow: 0 0 0 3px var(--accent-glow); border-color: var(--accent); }
        .input-group-text { background: var(--surface-sunk); border: 1px solid var(--border-strong); color: var(--text-muted); font-size: 0.82rem; border-radius: var(--radius); }
        .invalid-feedback { font-size: 0.75rem; }
        .is-invalid { border-color: var(--danger) !important; }

        /* ── ALERTS ─────────────────────────────────────────────────── */
        .alert {
            border-radius: var(--radius);
            border: 1px solid transparent;
            font-size: 0.84rem; font-weight: 500;
            box-shadow: none;
            padding: 12px 16px;
        }
        .alert-success { background: var(--ok-tint);     color: var(--ok-ink);     border-color: rgba(16,185,129,0.18); }
        .alert-danger  { background: var(--danger-tint); color: var(--danger-ink); border-color: rgba(239,68,68,0.18); }
        .alert-warning { background: var(--warn-tint);   color: var(--warn-ink);   border-color: rgba(245,158,11,0.2); }
        .alert-info    { background: var(--info-tint);   color: var(--info-ink);   border-color: rgba(59,130,246,0.18); }

        /* ── TABS ───────────────────────────────────────────────────── */
        .nav-tabs { border-bottom: 1px solid var(--border); gap: 2px; }
        .nav-tabs .nav-link {
            border: none; border-bottom: 2px solid transparent;
            color: var(--text-muted); font-size: 0.84rem; font-weight: 500;
            padding: 9px 14px; border-radius: 0;
        }
        .nav-tabs .nav-link:hover { color: var(--text); border-bottom-color: var(--border-strong); }
        .nav-tabs .nav-link.active { color: var(--text); background: transparent; border-bottom-color: var(--accent); font-weight: 600; }

        /* ── DROPDOWNS / MODALS ─────────────────────────────────────── */
        .dropdown-menu {
            border: 1px solid var(--border); border-radius: var(--radius);
            box-shadow: var(--shadow-overlay); font-size: 0.84rem; padding: 5px;
        }
        .dropdown-item { border-radius: var(--radius-sm); padding: 7px 11px; color: var(--text); }
        .dropdown-item:hover { background: var(--n-50); }

        .modal-content { border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-overlay); }
        .modal-header { border-bottom: 1px solid var(--border); padding: 16px 20px; }
        .modal-title { font-size: 1rem; font-weight: 600; }
        .modal-footer { border-top: 1px solid var(--border); padding: 14px 20px; }

        /* ── PAGINATION ─────────────────────────────────────────────── */
        .pagination { gap: 3px; margin-bottom: 0; }
        .page-link {
            border-radius: var(--radius-sm) !important; border: 1px solid var(--border);
            color: var(--text-soft); font-size: 0.81rem; font-weight: 500; padding: 6px 11px;
        }
        .page-link:hover { background: var(--n-50); color: var(--text); border-color: var(--border-strong); }
        .page-item.active .page-link { background: var(--primary); border-color: var(--primary); color: #fff; }
        .page-link svg { width: 13px; height: 13px; }
        .page-item.disabled .page-link { color: var(--text-faint); background: transparent; }

        /* ── MISC ───────────────────────────────────────────────────── */
        .text-muted { color: var(--text-muted) !important; }
        hr { border-color: var(--border); opacity: 1; }
        .escalated { border-left: 2px solid var(--danger); }

        ::-webkit-scrollbar { width: 9px; height: 9px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--n-200); border-radius: 10px; border: 2px solid var(--bg); }
        ::-webkit-scrollbar-thumb:hover { background: var(--n-300); }

        .guest-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; }

        /* ── TOM SELECT ─────────────────────────────────────────────── */
        .ts-wrapper { font-family: 'Plus Jakarta Sans', sans-serif; }
        .ts-wrapper .ts-control {
            border: 1px solid var(--border-strong) !important;
            border-radius: var(--radius) !important;
            padding: 6px 11px !important;
            font-size: 0.84rem !important;
            min-height: 38px !important;
            background: var(--surface) !important;
            box-shadow: none !important;
        }
        .ts-wrapper.focus .ts-control { border-color: var(--accent) !important; box-shadow: 0 0 0 3px var(--accent-glow) !important; }
        .ts-wrapper .ts-dropdown {
            border-radius: var(--radius) !important; border: 1px solid var(--border) !important;
            box-shadow: var(--shadow-overlay) !important; margin-top: 4px !important;
        }
        .ts-wrapper .ts-dropdown .option { font-size: 0.84rem !important; padding: 7px 12px !important; border-radius: var(--radius-sm); }
        .ts-wrapper .ts-dropdown .option.active { background: var(--n-50) !important; color: var(--text) !important; }
        .ts-wrapper .ts-dropdown .option:hover { background: var(--n-50) !important; }
        .ts-wrapper .ts-control > input { font-size: 0.84rem !important; }
        .ts-wrapper.form-select-sm .ts-control { min-height: 32px !important; padding: 3px 9px !important; font-size: 0.8rem !important; }
        .ts-wrapper .item { font-size: 0.84rem; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-wrapper { margin-left: 0; }
            .main-content { padding: 18px 16px 32px; }
            .top-nav { padding: 0 16px; }
            .global-search, .global-search:focus { width: 150px; }
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
                    <i class="bi bi-people-fill"></i> Client Credentials
                </a>
                <a href="{{ route('income-tax-returns.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'income-tax-returns')) active @endif">
                    <i class="bi bi-file-earmark-text"></i> Income Tax Returns
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
                <a href="{{ route('documents.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'documents.')) active @endif">
                    <i class="bi bi-folder2-open"></i> Documents
                </a>
                <a href="{{ route('files.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'files.')) active @endif">
                    <i class="bi bi-folder-fill"></i> File Management
                </a>

                <div class="sidebar-collapse-toggle @if(str_starts_with(Route::currentRouteName() ?? '', 'wht.')) open @endif" onclick="toggleSection('wht')">
                    <span><i class="bi bi-percent me-1"></i> Withholding Tax</span>
                    <i class="bi bi-chevron-right chevron"></i>
                </div>
                <div class="sidebar-collapsible @if(str_starts_with(Route::currentRouteName() ?? '', 'wht.')) open @endif" id="section-wht">
                    <a href="{{ route('wht.dashboard') }}" class="@if(Route::currentRouteName() == 'wht.dashboard') active @endif">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="{{ route('wht.transactions.index') }}" class="@if(in_array(Route::currentRouteName(), ['wht.transactions.index']) || str_starts_with(Route::currentRouteName() ?? '', 'wht.purchases') || str_starts_with(Route::currentRouteName() ?? '', 'wht.salaries') || str_starts_with(Route::currentRouteName() ?? '', 'wht.imports')) active @endif">
                        <i class="bi bi-list-ul"></i> Transactions
                    </a>
                    <a href="{{ route('wht.deposit.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'wht.deposit') || str_starts_with(Route::currentRouteName() ?? '', 'wht.challans')) active @endif">
                        <i class="bi bi-bank"></i> Deposit
                    </a>
                    <a href="{{ route('wht.reports.statement') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'wht.reports')) active @endif">
                        <i class="bi bi-journal-text"></i> Filing
                    </a>
                    <a href="{{ route('wht.setup.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'wht.setup') || str_starts_with(Route::currentRouteName() ?? '', 'wht.parties') || str_starts_with(Route::currentRouteName() ?? '', 'wht.companies') || str_starts_with(Route::currentRouteName() ?? '', 'wht.rates') || str_starts_with(Route::currentRouteName() ?? '', 'wht.slabs') || str_starts_with(Route::currentRouteName() ?? '', 'wht.sections')) active @endif">
                        <i class="bi bi-sliders"></i> Setup
                    </a>
                </div>

                <div class="sidebar-collapse-toggle @if(str_starts_with(Route::currentRouteName() ?? '', 'processes.')) open @endif" onclick="toggleSection('operations')">
                    <span><i class="bi bi-gear me-1"></i> Operations</span>
                    <i class="bi bi-chevron-right chevron"></i>
                </div>
                <div class="sidebar-collapsible @if(str_starts_with(Route::currentRouteName() ?? '', 'processes.')) open @endif" id="section-operations">
                    <a href="{{ route('processes.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'processes.')) active @endif">
                        <i class="bi bi-arrow-repeat"></i> Processes
                    </a>
                </div>

                @if(Auth::user()->hasRole('admin'))
                <div class="sidebar-collapse-toggle @if(in_array(Route::currentRouteName(), ['extension.download', 'settings.email']) || str_starts_with(Route::currentRouteName() ?? '', 'employees.')) open @endif" onclick="toggleSection('admin')">
                    <span><i class="bi bi-shield-lock me-1"></i> Administration</span>
                    <i class="bi bi-chevron-right chevron"></i>
                </div>
                <div class="sidebar-collapsible @if(in_array(Route::currentRouteName(), ['extension.download', 'settings.email']) || str_starts_with(Route::currentRouteName() ?? '', 'employees.')) open @endif" id="section-admin">
                    <a href="{{ route('extension.download') }}" class="@if(Route::currentRouteName() == 'extension.download') active @endif">
                        <i class="bi bi-puzzle-fill"></i> Chrome Extension
                    </a>
                    <a href="{{ route('employees.index') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'employees.')) active @endif">
                        <i class="bi bi-person-badge-fill"></i> Employees
                    </a>
                    <a href="{{ route('settings.email') }}" class="@if(str_starts_with(Route::currentRouteName() ?? '', 'settings.')) active @endif">
                        <i class="bi bi-envelope-at"></i> Email Integration
                    </a>
                </div>
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
                                <i class="bi bi-search global-search-icon"></i>
                                <input type="text" name="q" id="global-search" class="global-search"
                                    placeholder="Search clients, tasks, proceedings..." autocomplete="off"
                                    onblur="setTimeout(function(){ document.getElementById('search-dropdown').style.display='none'; }, 200)"
                                    oninput="searchSuggest(this.value)">
                            </div>
                        </form>
                        <div id="search-dropdown" class="global-search-dropdown"></div>
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
        function toggleSection(name) {
            var toggle = document.querySelector('[onclick="toggleSection(\'' + name + '\')"]');
            var section = document.getElementById('section-' + name);
            toggle.classList.toggle('open');
            section.classList.toggle('open');
        }

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

    @endauth
    @yield('scripts')
</body>
</html>
