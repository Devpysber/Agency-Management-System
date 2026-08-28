@include('layouts.partials.head')
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <a href="{{ route('portal.dashboard') }}" class="logo">
                    <i class="fas fa-cube"></i>
                    <span>CRM System</span>
                </a>
            </div>
            <div class="header-right">
                <div class="header-icons">
                    <button type="button" class="theme-toggle" onclick="toggleAppTheme()" aria-label="Toggle dark mode" title="Toggle theme">
                        <i data-theme-icon class="fas fa-moon"></i>
                    </button>
                    <span class="text-muted" style="font-size: 14px;">{{ auth()->user()->name ?? '' }}</span>
                    <div class="user-avatar">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=4F46E5&color=fff" alt="{{ auth()->user()->name ?? 'User' }}">
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="main-wrapper">
            <!-- Sidebar -->
            <aside class="sidebar" id="sidebar">
                <nav class="sidebar-nav">
                    <div class="nav-item dashboard-link active">
                        <a href="{{ route('portal.dashboard') }}">
                            <i class="fas fa-chart-pie"></i>
                            <span>My Dashboard</span>
                        </a>
                    </div>

                    <!-- Logout -->
                    <div class="nav-item dashboard-link">
                        <a href="{{ route('logout') }}" class="text-danger"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
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
    @livewireScripts
</body>
</html>
