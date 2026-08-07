<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="#" class="logo">
                    <i class="fas fa-cube"></i>
                    <span>CRM System</span>
                </a>
            </div>
            <div class="header-right">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search...">
                </div>
                <div class="header-icons">
                    <i class="fas fa-bell"></i>
                    <i class="fas fa-envelope"></i>
                    <div class="user-avatar">
                        <img src="https://ui-avatars.com/api/?name=Admin&background=4F46E5&color=fff" alt="User">
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="main-wrapper">
            <!-- Sidebar -->
            <aside class="sidebar" id="sidebar">
                <nav class="sidebar-nav">
                    <!-- Dashboard (No Dropdown - Always Visible) -->
                    <div class="nav-item dashboard-link active">
                        <a href="{{ route('dashboard') }}">
                            <i class="fas fa-chart-pie"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                    <div class="nav-item dashboard-link">
                        <a href="{{ route('staff.create') }}" class="active">
                            <i class="fas fa-users"></i>
                            <span><b>Staff</b></span>
                        </a>
                    </div>
                    <!-- ==================== CONTACTS ==================== -->
                    <div class="nav-section">
                        <div class="nav-header" onclick="toggleDropdown(this)">
                            <div class="nav-header-left">
                                <i class="fas fa-address-book"></i>
                                <span>CONTACTS</span>
                            </div>
                            <i class="fas fa-chevron-right dropdown-icon"></i>
                        </div>
                        <div class="nav-children">
                            <div class="nav-item">
                                <a href="{{ route('contacts.all') }}">
                                    <i class="fas fa-list"></i>
                                    <span>All Contacts</span>
                                    <span class="badge">245</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('contacts.add') }}">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Add New Contact</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('contacts.import') }}">
                                    <i class="fas fa-file-import"></i>
                                    <span>Import Contacts</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('contacts.groups') }}">
                                    <i class="fas fa-users"></i>
                                    <span>Contact Groups</span>
                                    <span class="badge">12</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== COMPANIES ==================== -->
                    <div class="nav-section">
                        <div class="nav-header" onclick="toggleDropdown(this)">
                            <div class="nav-header-left">
                                <i class="fas fa-building"></i>
                                <span>COMPANIES</span>
                            </div>
                            <i class="fas fa-chevron-right dropdown-icon"></i>
                        </div>
                        <div class="nav-children">
                            <div class="nav-item">
                                <a href="{{ route('companies.all') }}">
                                    <i class="fas fa-building"></i>
                                    <span>All Companies</span>
                                    <span class="badge">89</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('companies.add') }}">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Add New Company</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== DEALS ==================== -->
                    <div class="nav-section">
                        <div class="nav-header" onclick="toggleDropdown(this)">
                            <div class="nav-header-left">
                                <i class="fas fa-chart-line"></i>
                                <span>DEALS</span>
                            </div>
                            <i class="fas fa-chevron-right dropdown-icon"></i>
                        </div>
                        <div class="nav-children">
                            <div class="nav-item">
                                <a href="{{ route('deals.pipeline') }}">
                                    <i class="fas fa-tasks"></i>
                                    <span>Pipeline</span>
                                    <span class="badge">34</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('deals.all') }}">
                                    <i class="fas fa-list"></i>
                                    <span>All Deals</span>
                                    <span class="badge">156</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('deals.add') }}">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Add New Deal</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('deals.lost') }}">
                                    <i class="fas fa-times-circle"></i>
                                    <span>Lost Deals</span>
                                    <span class="badge">23</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== TASKS ==================== -->
                    <div class="nav-section">
                        <div class="nav-header" onclick="toggleDropdown(this)">
                            <div class="nav-header-left">
                                <i class="fas fa-check-circle"></i>
                                <span>TASKS</span>
                            </div>
                            <i class="fas fa-chevron-right dropdown-icon"></i>
                        </div>
                        <div class="nav-children">
                            <div class="nav-item">
                                <a href="{{ route('tasks.my') }}">
                                    <i class="fas fa-user-check"></i>
                                    <span>My Tasks</span>
                                    <span class="badge">12</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('tasks.all') }}">
                                    <i class="fas fa-list"></i>
                                    <span>All Tasks</span>
                                    <span class="badge">67</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('tasks.create') }}">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Create Task</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('tasks.completed') }}">
                                    <i class="fas fa-check-double"></i>
                                    <span>Completed Tasks</span>
                                    <span class="badge">45</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- ==================== CALENDAR ==================== -->
                    <div class="nav-section">
                        <div class="nav-header" onclick="toggleDropdown(this)">
                            <div class="nav-header-left">
                                <i class="fas fa-calendar-alt"></i>
                                <span>CALENDAR</span>
                            </div>
                            <i class="fas fa-chevron-right dropdown-icon"></i>
                        </div>
                        <div class="nav-children">
                            <div class="nav-item">
                                <a href="{{ route('calendar.schedule') }}">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Schedule</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('calendar.events') }}">
                                    <i class="fas fa-calendar-plus"></i>
                                    <span>Events</span>
                                    <span class="badge">8</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== COMMUNICATIONS ==================== -->
                    <div class="nav-section">
                        <div class="nav-header" onclick="toggleDropdown(this)">
                            <div class="nav-header-left">
                                <i class="fas fa-comments"></i>
                                <span>COMMUNICATIONS</span>
                            </div>
                            <i class="fas fa-chevron-right dropdown-icon"></i>
                        </div>
                        <div class="nav-children">
                            <div class="nav-item">
                                <a href="{{ route('communications.emails') }}">
                                    <i class="fas fa-envelope"></i>
                                    <span>Emails</span>
                                    <span class="badge">28</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('communications.calls') }}">
                                    <i class="fas fa-phone"></i>
                                    <span>Calls</span>
                                    <span class="badge">15</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('communications.meetings') }}">
                                    <i class="fas fa-handshake"></i>
                                    <span>Meetings</span>
                                    <span class="badge">6</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('communications.activity-log') }}">
                                    <i class="fas fa-history"></i>
                                    <span>Activity Log</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== REPORTS ==================== -->
                    <div class="nav-section">
                        <div class="nav-header" onclick="toggleDropdown(this)">
                            <div class="nav-header-left">
                                <i class="fas fa-file-alt"></i>
                                <span>REPORTS</span>
                            </div>
                            <i class="fas fa-chevron-right dropdown-icon"></i>
                        </div>
                        <div class="nav-children">
                            <div class="nav-item">
                                <a href="{{ route('reports.sales') }}">
                                    <i class="fas fa-dollar-sign"></i>
                                    <span>Sales Report</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('reports.activity') }}">
                                    <i class="fas fa-chart-bar"></i>
                                    <span>Activity Report</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('reports.performance') }}">
                                    <i class="fas fa-trophy"></i>
                                    <span>Performance Report</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== SETTINGS ==================== -->
                    <div class="nav-section">
                        <div class="nav-header" onclick="toggleDropdown(this)">
                            <div class="nav-header-left">
                                <i class="fas fa-cog"></i>
                                <span>SETTINGS</span>
                            </div>
                            <i class="fas fa-chevron-right dropdown-icon"></i>
                        </div>
                        <div class="nav-children">
                            <div class="nav-item">
                                <a href="{{ route('settings.general') }}">
                                    <i class="fas fa-sliders-h"></i>
                                    <span>General Settings</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('settings.user-management') }}">
                                    <i class="fas fa-users-cog"></i>
                                    <span>User Management</span>
                                </a>
                            </div>
                            <div class="nav-item">
                                <a href="{{ route('settings.roles-permissions') }}">
                                    <i class="fas fa-user-shield"></i>
                                    <span>Roles & Permissions</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Logout -->
                    <div class="nav-item dashboard-link">
                        <a href="#" class="text-danger">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </nav>
            </aside>

            <!-- Content Area -->
            <main class="content">
                {{ $slot }}
            </main>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <span>&copy; {{ date('Y') }} CRM System. All rights reserved.</span>
                <span>Version 1.0.0</span>
            </div>
        </footer>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle dropdown for sidebar sections
        function toggleDropdown(headerElement) {
            const section = headerElement.closest('.nav-section');
            const children = section.querySelector('.nav-children');
            const icon = headerElement.querySelector('.dropdown-icon');
            
            if (children.classList.contains('open')) {
                children.classList.remove('open');
                icon.classList.remove('rotated');
            } else {
                children.classList.add('open');
                icon.classList.add('rotated');
            }
        }

        // Toggle sidebar visibility on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.sidebar-toggle');
            const isClickInside = sidebar.contains(event.target) || toggleBtn.contains(event.target);
            
            if (!isClickInside && window.innerWidth <= 768) {
                sidebar.classList.remove('show');
            }
        });

        // All sections start closed
        document.addEventListener('DOMContentLoaded', function() {
            // All sections remain closed by default
            // No open classes added
        });
    </script>

    @livewireScripts
</body>
</html>