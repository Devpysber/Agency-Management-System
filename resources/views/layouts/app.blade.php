@include('layouts.partials.head')
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
                <div id="adminClock" style="display:flex;align-items:center;gap:8px;padding:6px 12px;border:1px solid var(--border);border-radius:8px;font-size:12px;color:var(--text-soft);">
                    <i class="fas fa-clock"></i>
                    <span style="display:flex;flex-direction:column;line-height:1.15;">
                        <b class="ac-time" style="font-weight:700;color:var(--text);font-variant-numeric:tabular-nums;">--:--:--</b>
                        <span class="ac-date" style="font-size:10.5px;color:var(--text-faint);"></span>
                    </span>
                </div>
                <button type="button" class="theme-toggle" onclick="toggleAppTheme()" aria-label="Toggle dark mode" title="Toggle theme">
                    <i data-theme-icon class="fas fa-moon"></i>
                </button>
                @auth
                    <livewire:admin-header-menu />
                @endauth
            </div>
        </header>

        <!-- Main Content -->
        <div class="main-wrapper">
            <!-- Sidebar -->
            <aside class="sidebar" id="sidebar">
                <nav class="sidebar-nav" wire:navigate>
                    <!-- Dashboard (No Dropdown - Always Visible) -->
                    <div class="nav-item dashboard-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}">
                            <i class="fas fa-chart-pie"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                    @php
                        // Admin and CEO are one combined identity/panel — the
                        // technical super-admin account IS the CEO's login.
                        $myDesignation = auth()->user()->role === 'admin' ? null
                            : optional(\App\Models\staff::where('user_id', auth()->id())->first())->designation;
                        $isAdmin = auth()->user()->role === 'admin' || $myDesignation === 'CEO';
                        $can = fn ($m) => $isAdmin || auth()->user()->hasPermission($m, 'View');
                    @endphp
                    <div class="nav-item dashboard-link {{ request()->routeIs('messages.index') ? 'active' : '' }}">
                        <a href="{{ route('messages.index') }}">
                            <i class="fas fa-comment-dots"></i>
                            <span>Messages</span>
                        </a>
                    </div>
                    @if($isAdmin)
                    <div class="nav-item dashboard-link {{ request()->routeIs('assistant') ? 'active' : '' }}">
                        <a href="{{ route('assistant') }}">
                            <i class="fas fa-robot"></i>
                            <span>Admin Assistant</span>
                        </a>
                    </div>
                    @endif
                    @php
                        $navGroups = [
                            ['CLIENT MANAGEMENT', 'fa-handshake-angle', [
                                ['Contacts', 'contacts.all', 'fa-address-book', $can('Contacts')],
                                ['Companies', 'companies.all', 'fa-building', $can('Companies')],
                                ['Deals', 'deals.pipeline', 'fa-chart-line', $can('Deals')],
                                ['Communications', 'communications.emails', 'fa-comments', $can('Communications')],
                            ]],
                            ['PROJECT MANAGEMENT', 'fa-diagram-project', [
                                ['Projects', 'projects.all', 'fa-diagram-project', $can('Projects')],
                                ['Tasks', 'tasks.all', 'fa-list-check', $can('Tasks')],
                                ['Bugs', 'bugs.all', 'fa-bug', $can('Bugs')],
                                ['Calendar', 'calendar.schedule', 'fa-calendar-days', true],
                            ]],
                            ['SALES', 'fa-file-invoice-dollar', [
                                ['Estimates', 'estimates.all', 'fa-file-invoice', $can('Estimates')],
                                ['Quotations', 'quotations.all', 'fa-file-signature', $can('Quotations')],
                            ]],
                            ['CATALOG', 'fa-boxes-stacked', [
                                ['Services', 'services.all', 'fa-gears', $can('Services')],
                                ['Products', 'products.all', 'fa-box', $can('Products')],
                                ['Pricing', 'pricing.all', 'fa-tags', $can('Pricing')],
                            ]],
                            ['MARKETING', 'fa-bullhorn', [
                                ['Portfolio', 'portfolio.all', 'fa-briefcase', $can('Portfolio')],
                                ['Testimonials', 'testimonials.all', 'fa-quote-left', $can('Testimonials')],
                                ['Blog', 'blog.all', 'fa-newspaper', $can('Blog')],
                            ]],
                            ['TEAM', 'fa-users', [
                                ['Staff', 'staff.all', 'fa-id-badge', $can('Staff')],
                                ['Attendance', 'attendance.index', 'fa-fingerprint', $can('Attendance')],
                            ]],
                            ['REPORTS', 'fa-chart-pie', [
                                ['Sales Report', 'reports.sales', 'fa-dollar-sign', $can('Reports')],
                                ['Activity Report', 'reports.activity', 'fa-chart-bar', $can('Reports')],
                                ['Performance', 'reports.performance', 'fa-trophy', $can('Reports')],
                                ['Client Portal', 'reports.client-attendance', 'fa-user-clock', $can('Reports')],
                            ]],
                            ['SETTINGS', 'fa-gear', [
                                ['General', 'settings.general', 'fa-sliders', $can('Settings')],
                                ['User Management', 'settings.user-management', 'fa-users-gear', $isAdmin],
                                ['Roles & Permissions', 'settings.roles-permissions', 'fa-user-shield', $isAdmin],
                                ['Payment Gateways', 'settings.payment-gateways', 'fa-credit-card', $can('Settings')],
                            ]],
                        ];
                    @endphp

                    @foreach ($navGroups as [$label, $groupIcon, $links])
                        @php($visible = collect($links)->filter(fn ($l) => $l[3] === true)->values())
                        @if ($visible->isNotEmpty())
                            <div class="nav-section">
                                <div class="nav-header" onclick="toggleDropdown(this)">
                                    <div class="nav-header-left">
                                        <i class="fas {{ $groupIcon }}"></i>
                                        <span>{{ $label }}</span>
                                    </div>
                                    <i class="fas fa-chevron-right dropdown-icon"></i>
                                </div>
                                <div class="nav-children">
                                    @foreach ($visible as [$title, $routeName, $icon, $ok])
                                        <div class="nav-item {{ request()->routeIs(explode('.', $routeName)[0] . '.*') ? 'active' : '' }}">
                                            <a href="{{ route($routeName) }}">
                                                <i class="fas {{ $icon }}"></i>
                                                <span>{{ $title }}</span>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
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

    <script>
        // Toggle dropdown for sidebar sections
        // Which sections are open is remembered across page loads by section label.
        function sidebarSectionKey(section) {
            var lbl = section.querySelector('.nav-header-left span');
            return lbl ? lbl.textContent.trim() : '';
        }
        function loadOpenSections() {
            try { return JSON.parse(localStorage.getItem('crm_open_sections') || '[]'); } catch (e) { return []; }
        }
        function saveOpenSections(list) {
            try { localStorage.setItem('crm_open_sections', JSON.stringify(list)); } catch (e) {}
        }
        function setSectionOpen(section, open) {
            var children = section.querySelector('.nav-children');
            var icon = section.querySelector('.dropdown-icon');
            if (!children) return;
            children.classList.toggle('open', open);
            if (icon) icon.classList.toggle('rotated', open);
        }

        function toggleDropdown(headerElement) {
            var section = headerElement.closest('.nav-section');
            var children = section.querySelector('.nav-children');
            var nowOpen = !children.classList.contains('open');
            setSectionOpen(section, nowOpen);

            var key = sidebarSectionKey(section);
            var list = loadOpenSections().filter(function (k) { return k !== key; });
            if (nowOpen) list.push(key);
            saveOpenSections(list);
        }

        // Restore open sections + auto-open the one holding the current page.
        function restoreSidebar() {
            var open = loadOpenSections();
            var path = window.location.pathname;
            document.querySelectorAll('.sidebar .nav-section').forEach(function (section) {
                var key = sidebarSectionKey(section);
                var hasActive = Array.prototype.some.call(section.querySelectorAll('a[href]'), function (a) {
                    try {
                        var u = new URL(a.href, window.location.origin);
                        return u.pathname !== '/' && (path === u.pathname || path.indexOf(u.pathname + '/') === 0);
                    } catch (e) { return false; }
                });
                setSectionOpen(section, open.indexOf(key) !== -1 || hasActive);
            });
        }
        if (document.readyState !== 'loading') restoreSidebar();
        document.addEventListener('DOMContentLoaded', restoreSidebar);
        document.addEventListener('livewire:navigated', restoreSidebar);

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

        // ---- Admin motion: entrance animations, scroll-reveal, stat count-up ----
        (function () {
            var css = `
                @keyframes a-fade-up { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:none; } }
                @keyframes a-pulse { 0%,100% { opacity:1; } 50% { opacity:.25; } }
                .a-reveal { opacity:0; transform:translateY(16px); transition:opacity .5s ease, transform .5s ease; }
                .a-reveal.is-in { opacity:1; transform:none; }
                .dashboard .stat-card, .dashboard .card { animation: a-fade-up .4s ease both; }
                .a-stagger > [class*="col-"]:nth-child(1) .stat-card { animation-delay:.02s; }
                .a-stagger > [class*="col-"]:nth-child(2) .stat-card { animation-delay:.06s; }
                .a-stagger > [class*="col-"]:nth-child(3) .stat-card { animation-delay:.10s; }
                .a-stagger > [class*="col-"]:nth-child(4) .stat-card { animation-delay:.14s; }
                .a-stagger > [class*="col-"]:nth-child(5) .stat-card { animation-delay:.18s; }
                .a-stagger > [class*="col-"]:nth-child(6) .stat-card { animation-delay:.22s; }
                .table tbody tr { animation: a-fade-up .3s ease both; }
                .table tbody tr:nth-child(1){animation-delay:.02s}.table tbody tr:nth-child(2){animation-delay:.04s}
                .table tbody tr:nth-child(3){animation-delay:.06s}.table tbody tr:nth-child(4){animation-delay:.08s}
                .table tbody tr:nth-child(5){animation-delay:.10s}.table tbody tr:nth-child(6){animation-delay:.12s}
                .table tbody tr:nth-child(7){animation-delay:.14s}.table tbody tr:nth-child(n+8){animation-delay:.16s}
                @media (prefers-reduced-motion: reduce) {
                    .dashboard .stat-card, .dashboard .card, .table tbody tr { animation:none !important; }
                    .a-reveal { opacity:1; transform:none; transition:none; }
                }
                /* When this browser tab is not the active one, stop all motion. */
                body.tab-hidden *, body.tab-hidden *::before, body.tab-hidden *::after { animation-play-state: paused !important; }
                body.tab-hidden .dashboard .stat-card, body.tab-hidden .dashboard .card, body.tab-hidden .table tbody tr { animation: none !important; }
            `;
            var s = document.createElement('style'); s.textContent = css; document.head.appendChild(s);

            function motion() {
                // scroll reveal
                var items = document.querySelectorAll('.a-reveal:not(.is-in)');
                if ('IntersectionObserver' in window) {
                    var io = new IntersectionObserver(function (es) {
                        es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
                    }, { rootMargin: '0px 0px -6% 0px' });
                    items.forEach(function (el) { io.observe(el); });
                } else {
                    items.forEach(function (el) { el.classList.add('is-in'); });
                }
            }
            function countUp() {
                document.querySelectorAll('.stat-number:not([data-cu])').forEach(function (el) {
                    el.setAttribute('data-cu', '1');
                    var final = (el.textContent || '').trim();
                    var m = final.replace(/,/g, '').match(/-?\d+(\.\d+)?/);
                    if (!m) return;
                    var target = parseFloat(m[0]); if (!isFinite(target) || target === 0) return;
                    var i = final.replace(/,/g, '').indexOf(m[0]);
                    var pre = final.replace(/,/g, '').slice(0, i), suf = final.replace(/,/g, '').slice(i + m[0].length);
                    var dec = (m[0].split('.')[1] || '').length, t0 = performance.now();
                    (function f(now) {
                        var p = Math.min(1, (now - t0) / 700), v = target * (1 - Math.pow(1 - p, 3));
                        el.textContent = pre + v.toLocaleString(undefined, { minimumFractionDigits: dec, maximumFractionDigits: dec }) + suf;
                        if (p < 1) requestAnimationFrame(f); else el.textContent = final;
                    })(t0);
                });
            }
            function run() { motion(); countUp(); }
            if (document.readyState !== 'loading') run();
            document.addEventListener('DOMContentLoaded', run);
            document.addEventListener('livewire:navigated', function () { run(); });
            document.addEventListener('livewire:init', function () {
                if (window.Livewire && window.Livewire.hook) window.Livewire.hook('morphed', motion);
            });
        })();

        // ---- Tab-active presence heartbeat + animation gating ----
        (function () {
            var token = document.querySelector('meta[name="csrf-token"]');
            token = token ? token.getAttribute('content') : '';
            var timer = null;

            function ping() {
                if (document.visibilityState !== 'visible') return;
                fetch('{{ route('heartbeat') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                    keepalive: true,
                }).catch(function () {});
            }
            function start() { stop(); ping(); timer = setInterval(ping, 45000); }
            function stop() { if (timer) { clearInterval(timer); timer = null; } }

            function sync() {
                if (document.visibilityState === 'visible') {
                    document.body.classList.remove('tab-hidden');
                    start();
                } else {
                    document.body.classList.add('tab-hidden');
                    stop();
                }
            }
            document.addEventListener('visibilitychange', sync);
            window.addEventListener('focus', sync);
            window.addEventListener('pagehide', stop);
            sync();
        })();

        // Live navbar clock
        (function () {
            var el = document.getElementById('adminClock');
            if (!el) return;
            var pad = function (n) { return String(n).padStart(2, '0'); };
            function tick() {
                var d = new Date();
                var t = el.querySelector('.ac-time'), dt = el.querySelector('.ac-date');
                if (t) t.textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
                if (dt) dt.textContent = d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
            }
            tick();
            setInterval(tick, 1000);
        })();
    </script>

    @auth
        <livewire:event-reminders />
        <livewire:absence-popup />
    @endauth

    {{-- Global toast popups (replace the old inline .alert-flash banners) --}}
    <div id="toast-stack"></div>
    @foreach (['success' => 'success', 'ok' => 'success', 'status' => 'info', 'error' => 'error', 'warning' => 'info'] as $key => $type)
        @if (session($key))
            <div class="toast-seed" data-type="{{ $type }}" data-msg="{{ session($key) }}"></div>
        @endif
    @endforeach
    <style>
        [x-cloak] { display: none !important; }
        .alert-flash { display: none !important; }
        #toast-stack { position: fixed; right: 22px; bottom: 22px; z-index: 3000; display: flex; flex-direction: column; gap: 10px; }
        .toast-pop {
            display: flex; align-items: center; gap: 10px; min-width: 280px; max-width: 400px;
            background: #fff; border: 1px solid #e5e7eb; border-left: 4px solid #6b7280;
            border-radius: 12px; box-shadow: 0 16px 44px rgba(16,24,40,.2); padding: 12px 14px;
            font-size: 13px; color: #1f2937;
            transform: translateX(32px); opacity: 0;
            transition: transform .3s cubic-bezier(.34,1.56,.64,1), opacity .3s;
        }
        .toast-pop.in { transform: none; opacity: 1; }
        .toast-pop.out { transform: translateX(32px); opacity: 0; }
        .toast-pop i:first-child { font-size: 15px; }
        .toast-pop.success { border-left-color: #10b981; } .toast-pop.success > i:first-child { color: #10b981; }
        .toast-pop.error { border-left-color: #ef4444; } .toast-pop.error > i:first-child { color: #ef4444; }
        .toast-pop.info { border-left-color: #4f46e5; } .toast-pop.info > i:first-child { color: #4f46e5; }
        .toast-pop span { flex: 1; }
        .toast-pop button { border: 0; background: 0; color: #9ca3af; font-size: 17px; line-height: 1; cursor: pointer; padding: 0 2px; }
        .toast-pop button:hover { color: #4b5563; }
    </style>
    <script>
        (function () {
            window.showToast = function (msg, type) {
                if (!msg) return;
                type = type || 'info';
                var host = document.getElementById('toast-stack');
                if (!host) return;
                var icon = type === 'success' ? 'circle-check' : type === 'error' ? 'circle-exclamation' : 'circle-info';
                var el = document.createElement('div');
                el.className = 'toast-pop ' + type;
                el.innerHTML = '<i class="fas fa-' + icon + '"></i><span></span><button aria-label="Close">&times;</button>';
                el.querySelector('span').textContent = msg;
                var timer = setTimeout(function () { close(); }, 4500);
                function close() { clearTimeout(timer); el.classList.remove('in'); el.classList.add('out'); setTimeout(function () { el.remove(); }, 300); }
                el.querySelector('button').addEventListener('click', close);
                host.appendChild(el);
                requestAnimationFrame(function () { el.classList.add('in'); });
            };

            function flushSeeds() {
                document.querySelectorAll('.toast-seed').forEach(function (s) {
                    window.showToast(s.getAttribute('data-msg'), s.getAttribute('data-type'));
                    s.remove();
                });
            }
            if (document.readyState !== 'loading') flushSeeds();
            document.addEventListener('DOMContentLoaded', flushSeeds);
            document.addEventListener('livewire:navigated', flushSeeds);

            document.addEventListener('livewire:init', function () {
                if (!window.Livewire) return;
                ['toast', 'cp-toast', 'notify'].forEach(function (evt) {
                    window.Livewire.on(evt, function (p) {
                        p = Array.isArray(p) ? p[0] : p;
                        window.showToast((p && (p.message || p.text)) || p, (p && p.type) || 'info');
                    });
                });
            });
        })();
    </script>

    @livewireScripts
</body>
</html>