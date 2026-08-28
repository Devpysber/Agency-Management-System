<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>

    {{-- Theme: applied before paint to avoid a flash. Shared key `cp-theme`
         (same as the login page + client portal). --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('cp-theme') || 'light';
                var r = document.documentElement;
                r.setAttribute('data-theme', t);
                r.setAttribute('data-bs-theme', t === 'dark' ? 'dark' : 'light');
            } catch (e) {}
        })();
        window.toggleAppTheme = function () {
            try {
                var next = (localStorage.getItem('cp-theme') || 'light') === 'dark' ? 'light' : 'dark';
                localStorage.setItem('cp-theme', next);
                var r = document.documentElement;
                r.setAttribute('data-theme', next);
                r.setAttribute('data-bs-theme', next === 'dark' ? 'dark' : 'light');
                syncThemeIcons();
            } catch (e) {}
        };
        function syncThemeIcons() {
            var t = localStorage.getItem('cp-theme') || 'light';
            document.querySelectorAll('[data-theme-icon]').forEach(function (el) {
                el.className = t === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            });
        }
        document.addEventListener('DOMContentLoaded', syncThemeIcons);
        document.addEventListener('livewire:navigated', syncThemeIcons);
    </script>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js for dashboard/report charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @livewireStyles

    <style>
        /* Custom Styles to maintain the same look */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
        }

        /* App Container */
        .app-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: #ffffff;
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #6b7280;
            padding: 0;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 700;
            color: #4F46E5;
            text-decoration: none;
        }

        .logo i {
            font-size: 24px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: #f9fafb;
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .search-box i {
            color: #9ca3af;
            margin-right: 8px;
        }

        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 14px;
            width: 200px;
        }

        .header-icons {
            display: flex;
            align-items: center;
            gap: 16px;
            color: #6b7280;
            font-size: 18px;
        }

        .user-avatar img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Main Wrapper */
        .main-wrapper {
            display: flex;
            flex: 1;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            padding: 20px 0;
            overflow-y: auto;
            flex-shrink: 0;
            height: calc(100vh - 64px);
            position: sticky;
            top: 64px;
        }

        .sidebar-nav {
            padding: 0 12px;
        }

        /* Dashboard Link (no dropdown) */
        .nav-item.dashboard-link {
            margin-bottom: 16px;
        }

        .nav-item.dashboard-link a {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 8px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
            gap: 12px;
        }

        .nav-item.dashboard-link a:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        .nav-item.dashboard-link.active a {
            background: #eef2ff;
            color: #4F46E5;
            font-weight: 500;
        }

        .nav-item.dashboard-link a i {
            width: 20px;
            font-size: 16px;
        }

        /* Dropdown Sections */
        .nav-section {
            margin-bottom: 4px;
        }

        .nav-section .nav-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }

        .nav-section .nav-header:hover {
            background: #f3f4f6;
        }

        .nav-section .nav-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #4b5563;
        }

        .nav-section .nav-header-left i {
            font-size: 16px;
            width: 20px;
            color: #6b7280;
        }

        .nav-section .nav-header .dropdown-icon {
            font-size: 12px;
            color: #9ca3af;
            transition: transform 0.3s ease;
        }

        .nav-section .nav-header .dropdown-icon.rotated {
            transform: rotate(90deg);
        }

        .nav-section .nav-children {
            margin-left: 8px;
            overflow: hidden;
            max-height: 0;
            transition: max-height 0.3s ease-out;
        }

        .nav-section .nav-children.open {
            max-height: 500px;
            transition: max-height 0.4s ease-in;
        }

        .nav-children .nav-item a {
            display: flex;
            align-items: center;
            padding: 8px 16px 8px 44px;
            border-radius: 6px;
            color: #6b7280;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
            gap: 10px;
        }

        .nav-children .nav-item a:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        .nav-children .nav-item.active a {
            background: #eef2ff;
            color: #4F46E5;
            font-weight: 500;
        }

        .nav-children .nav-item a i {
            width: 16px;
            font-size: 13px;
        }

        .nav-children .nav-item .badge {
            margin-left: auto;
            background: #e5e7eb;
            padding: 1px 8px;
            border-radius: 12px;
            font-size: 11px;
            color: #6b7280;
        }

        /* Content */
        .content {
            flex: 1;
            padding: 24px;
            background: #f9fafb;
            min-height: calc(100vh - 128px);
        }

        /* Footer */
        .footer {
            background: #ffffff;
            padding: 16px 24px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Dashboard Styles - Keeping your original styling */
        .dashboard {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
        }

        .page-header p {
            color: #6b7280;
            margin-top: 4px;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #4F46E5;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #4338CA;
        }

        .btn-secondary {
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #f9fafb;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #ffffff;
            flex-shrink: 0;
        }

        .stat-icon.blue { background: #4F46E5; }
        .stat-icon.green { background: #10B981; }
        .stat-icon.purple { background: #8B5CF6; }
        .stat-icon.orange { background: #F59E0B; }
        .stat-icon.red { background: #EF4444; }
        .stat-icon.teal { background: #14B8A6; }

        .stat-info h3 {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin: 4px 0;
        }

        .stat-change {
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .stat-change.positive {
            color: #10B981;
        }

        .stat-change.negative {
            color: #EF4444;
        }

        /* Flash Messages */
        .alert-flash {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            border: 1px solid transparent;
        }

        .alert-flash-success {
            background: #ECFDF5;
            border-color: #A7F3D0;
            color: #065F46;
        }

        .alert-flash-error {
            background: #FEF2F2;
            border-color: #FECACA;
            color: #991B1B;
        }

        .alert-flash-warning {
            background: #FFFBEB;
            border-color: #FDE68A;
            color: #92400E;
        }

        .alert-flash-close {
            margin-left: auto;
            background: none;
            border: none;
            color: inherit;
            opacity: 0.6;
            cursor: pointer;
        }

        .alert-flash-close:hover {
            opacity: 1;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        /* Cards */
        .card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .card.full-width {
            grid-column: 1 / -1;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }

        .view-all {
            color: #4F46E5;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        .card-body {
            padding: 20px;
        }

        .form-select {
            padding: 6px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 13px;
            background: #ffffff;
        }

        /* Chart */
        .chart-placeholder {
            height: 200px;
            display: flex;
            align-items: flex-end;
            padding-top: 20px;
        }

        .chart-bars {
            display: flex;
            justify-content: space-around;
            align-items: flex-end;
            width: 100%;
            height: 100%;
        }

        .bar-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .bar {
            width: 30px;
            background: #4F46E5;
            border-radius: 4px 4px 0 0;
            min-height: 20px;
            transition: height 0.3s;
        }

        .bar-group span {
            font-size: 12px;
            color: #6b7280;
        }

        /* Activity List */
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #ffffff;
            flex-shrink: 0;
        }

        .activity-icon.blue { background: #4F46E5; }
        .activity-icon.green { background: #10B981; }
        .activity-icon.purple { background: #8B5CF6; }
        .activity-icon.orange { background: #F59E0B; }
        .activity-icon.red { background: #EF4444; }

        .activity-info p {
            font-size: 14px;
            color: #1f2937;
        }

        .activity-info p strong {
            font-weight: 600;
        }

        .activity-time {
            font-size: 12px;
            color: #9ca3af;
        }

        /* Pipeline */
        .pipeline-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
        }

        .pipeline-stage h4 {
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
        }

        .stage-count {
            background: #e5e7eb;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }

        .pipeline-items {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .pipeline-card {
            background: #f9fafb;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            font-size: 13px;
            color: #1f2937;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .pipeline-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
                position: fixed;
                top: 64px;
                left: 0;
                width: 280px;
                height: calc(100vh - 64px);
                z-index: 1040;
                box-shadow: 2px 0 8px rgba(0,0,0,0.1);
            }

            .sidebar.show {
                display: block;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .pipeline-grid {
                grid-template-columns: 1fr;
            }

            .search-box input {
                width: 120px;
            }
        }

        /* ============================================================
           THEME TOKENS + DARK MODE  (data-theme on <html>, key cp-theme)
           Light values reproduce the original palette 1:1; dark mirrors
           the client-portal / project-chat dark palette for consistency.
           ============================================================ */
        :root {
            --bg: #f3f4f6;
            --content-bg: #f9fafb;
            --surface: #ffffff;
            --surface-2: #f9fafb;
            --surface-3: #f3f4f6;
            --border: #e5e7eb;
            --text: #1f2937;
            --text-mid: #4b5563;
            --text-soft: #6b7280;
            --text-faint: #9ca3af;
            --primary: #4F46E5;
            --primary-strong: #4338CA;
            --accent-bg: #eef2ff;
            --badge-bg: #e5e7eb;
        }
        :root[data-theme="dark"] {
            --bg: #0f1117;
            --content-bg: #0f1117;
            --surface: #171a22;
            --surface-2: #1f232e;
            --surface-3: #262b38;
            --border: #2a2f3c;
            --text: #e9ebf2;
            --text-mid: #c2c7d2;
            --text-soft: #a3a9b8;
            --text-faint: #7c828f;
            --primary: #7c74ff;
            --primary-strong: #6b62f5;
            --accent-bg: #23263a;
            --badge-bg: #2a2f3c;
        }

        body { background: var(--bg); color: var(--text); }
        .header { background: var(--surface); border-bottom-color: var(--border); }
        .sidebar-toggle { color: var(--text-soft); }
        .logo { color: var(--primary); }
        .search-box { background: var(--surface-2); border-color: var(--border); }
        .search-box input { color: var(--text); }
        .search-box i { color: var(--text-faint); }
        .header-icons { color: var(--text-soft); }
        .sidebar { background: var(--surface); border-right-color: var(--border); }
        .nav-item.dashboard-link a { color: var(--text-soft); }
        .nav-item.dashboard-link a:hover { background: var(--surface-3); color: var(--text); }
        .nav-item.dashboard-link.active a { background: var(--accent-bg); color: var(--primary); }
        .nav-section .nav-header:hover { background: var(--surface-3); }
        .nav-section .nav-header-left { color: var(--text-mid); }
        .nav-section .nav-header-left i { color: var(--text-soft); }
        .nav-section .nav-header .dropdown-icon { color: var(--text-faint); }
        .nav-children .nav-item a { color: var(--text-soft); }
        .nav-children .nav-item a:hover { background: var(--surface-3); color: var(--text); }
        .nav-children .nav-item.active a { background: var(--accent-bg); color: var(--primary); }
        .nav-children .nav-item .badge { background: var(--badge-bg); color: var(--text-soft); }
        .content { background: var(--content-bg); }
        .footer { background: var(--surface); border-top-color: var(--border); color: var(--text-soft); }
        .page-header h1 { color: var(--text); }
        .page-header p { color: var(--text-soft); }
        .btn-secondary { background: var(--surface); color: var(--text); border-color: var(--border); }
        .btn-secondary:hover { background: var(--surface-2); }
        .stat-card { background: var(--surface); border-color: var(--border); }
        .stat-info h3 { color: var(--text-soft); }
        .stat-number { color: var(--text); }
        .card { background: var(--surface); border-color: var(--border); }
        .card-header { border-bottom-color: var(--border); }
        .card-header h3 { color: var(--text); }
        .view-all { color: var(--primary); }
        .form-select { background: var(--surface); border-color: var(--border); color: var(--text); }
        .pipeline-stage h4 { color: var(--text-soft); }
        .stage-count { background: var(--badge-bg); }
        .pipeline-card { background: var(--surface-2); border-color: var(--border); color: var(--text); }
        .activity-info p { color: var(--text); }
        .activity-time { color: var(--text-faint); }
        .bar { background: var(--primary); }

        :root[data-theme="dark"] .alert-flash-success { background: #0d2a20; border-color: #14512f; color: #6ee7b7; }
        :root[data-theme="dark"] .alert-flash-error   { background: #2c1414; border-color: #5b2020; color: #fca5a5; }
        :root[data-theme="dark"] .alert-flash-warning { background: #2c2410; border-color: #5c4a17; color: #fcd34d; }

        /* Site-wide theme toggle button in the header */
        .theme-toggle {
            width: 38px; height: 38px; border-radius: 8px;
            border: 1px solid var(--border); background: var(--surface);
            color: var(--text-soft); cursor: pointer; font-size: 15px;
            display: flex; align-items: center; justify-content: center;
            transition: background .15s, color .15s;
        }
        .theme-toggle:hover { background: var(--surface-3); color: var(--text); }
    </style>
</head>
