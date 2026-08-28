@php
    $cpUser = auth()->user();
    $cpContact = $cpUser?->contact;
    $cpCompany = $cpContact?->company;
    $cpCompanyId = $cpCompany?->id;

    $cpName = $cpUser?->name ?: 'Client';
    $cpInitials = collect(explode(' ', trim($cpName)))
        ->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');

    $cpGroups = [
        ['label' => 'Overview', 'items' => [
            ['route' => 'client.dashboard', 'match' => ['client.dashboard'],           'icon' => 'fa-chart-pie',    'label' => 'Dashboard'],
            ['route' => 'client.updates',   'match' => ['client.updates'],             'icon' => 'fa-bell',         'label' => 'Updates', 'badge' => true],
        ]],
        ['label' => 'Delivery', 'items' => [
            ['route' => 'client.projects',  'match' => ['client.projects', 'client.project-show'], 'icon' => 'fa-diagram-project', 'label' => 'Projects'],
            ['route' => 'client.insights',  'match' => ['client.insights'],            'icon' => 'fa-wand-magic-sparkles', 'label' => 'AI Insights'],
        ]],
        ['label' => 'Documents', 'items' => [
            ['route' => 'client.estimates', 'match' => ['client.estimates', 'client.estimate-show'],  'icon' => 'fa-file-invoice',   'label' => 'Estimates'],
            ['route' => 'client.quotations','match' => ['client.quotations', 'client.quotation-show'], 'icon' => 'fa-file-signature', 'label' => 'Quotations'],
        ]],
        ['label' => 'Finance', 'items' => [
            ['route' => 'client.payments',  'match' => ['client.payments'],            'icon' => 'fa-credit-card',  'label' => 'Payments'],
        ]],
        ['label' => 'Account', 'items' => [
            ['route' => 'client.profile',   'match' => ['client.profile'],             'icon' => 'fa-user-gear',    'label' => 'My Profile'],
        ]],
    ];

    $cpNav = collect($cpGroups)->pluck('items')->flatten(1);
    $cpCurrent = $cpNav->first(fn ($i) => request()->routeIs(...$i['match']));
    $cpTitle = $cpCurrent['label'] ?? 'Client Portal';

    $cpUnread = \Illuminate\Support\Facades\Schema::hasTable('user_alerts')
        ? \App\Models\UserAlert::where('user_id', $cpUser?->id)->whereNull('read_at')->count()
        : 0;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Client Portal' }} · {{ $cpCompany->company_name ?? config('app.name') }}</title>

    {{-- Pre-paint theme: avoids a light→dark flash on load --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('cp-theme') || 'light';
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;750&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    @vite(['resources/css/client-portal.css', 'resources/js/client-portal.js'])
    <style>
        .cp-nav-badge { margin-left: auto; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 999px;
            background: #ef4444; color: #fff; font-size: 11px; font-weight: 800;
            display: inline-flex; align-items: center; justify-content: center; }
        .cp-nav-link.is-active .cp-nav-badge { background: #fff; color: #4f46e5; }
    </style>
    @livewireStyles
</head>
<body class="cp-body">
    <div class="cp-loading-bar"></div>

    <div class="cp-shell">
        {{-- ==================== Sidebar ==================== --}}
        <aside class="cp-sidebar">
            <a href="{{ route('client.dashboard') }}" wire:navigate class="cp-brand">
                <span class="cp-brand-mark"><i class="fas fa-layer-group"></i></span>
                <span class="cp-brand-text">
                    Client Portal
                    <small>{{ Str::limit($cpCompany->company_name ?? 'Workspace', 22) }}</small>
                </span>
            </a>

            <nav class="cp-nav">
                @foreach ($cpGroups as $group)
                    <span class="cp-nav-label">{{ $group['label'] }}</span>
                    @foreach ($group['items'] as $item)
                        <a href="{{ route($item['route']) }}" wire:navigate
                           class="cp-nav-link {{ request()->routeIs(...$item['match']) ? 'is-active' : '' }}">
                            <i class="fas {{ $item['icon'] }}"></i>
                            <span>{{ $item['label'] }}</span>
                            @if (($item['badge'] ?? false) && $cpUnread > 0)
                                <span class="cp-nav-badge">{{ $cpUnread > 99 ? '99+' : $cpUnread }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <div class="cp-nav-footer">
                <a href="{{ route('logout') }}" class="cp-nav-link is-danger"
                   onclick="event.preventDefault(); document.getElementById('cp-logout').submit();">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                    <span>Sign out</span>
                </a>
                <form id="cp-logout" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            </div>
        </aside>

        {{-- ==================== Main ==================== --}}
        <div class="cp-main">
            <header class="cp-header">
                <button class="cp-icon-btn cp-sidebar-toggle" onclick="clientPortal.toggleSidebar()" aria-label="Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="cp-header-title">
                    <strong>{{ $cpTitle }}</strong>
                    <span>{{ $cpCompany->company_name ?? 'No company linked' }}</span>
                </div>

                <div class="cp-header-spacer"></div>

                <div id="cp-clock" class="cp-clock" title="Local time">
                    <i class="fas fa-clock"></i>
                    <div class="cp-clock-stack">
                        <span class="cp-clock-time">--:--:--</span>
                        <span class="cp-clock-date"></span>
                    </div>
                </div>

                @auth
                    <livewire:alert-bell variant="client" />
                @endauth

                <button class="cp-icon-btn" onclick="clientPortal.toggleTheme()" aria-label="Toggle theme">
                    <i id="cp-theme-icon" class="fas fa-moon"></i>
                </button>

                <div class="cp-menu">
                    <div class="cp-user cp-menu-trigger" onclick="clientPortal.toggleMenu(event)">
                        <div class="cp-user-meta">
                            <strong>{{ $cpName }}</strong>
                            <span>{{ $cpContact?->job_title ?: 'Client account' }}</span>
                        </div>
                        <div class="cp-avatar">{{ $cpInitials ?: 'C' }}</div>
                        <i class="fas fa-chevron-down" style="font-size:11px;color:var(--cp-text-faint);"></i>
                    </div>
                    <div class="cp-menu-panel">
                        <div class="cp-menu-head">
                            <strong>{{ $cpName }}</strong>
                            <span>{{ $cpUser?->email }}</span>
                        </div>
                        <a href="{{ route('client.profile') }}" wire:navigate class="cp-menu-item">
                            <i class="fas fa-user-gear"></i> My Profile
                        </a>
                        <button type="button" class="cp-menu-item" onclick="clientPortal.toggleTheme()">
                            <i class="fas fa-circle-half-stroke"></i> Toggle theme
                        </button>
                        <div class="cp-menu-sep"></div>
                        <button type="button" class="cp-menu-item is-danger"
                                onclick="document.getElementById('cp-logout').submit()">
                            <i class="fas fa-arrow-right-from-bracket"></i> Sign out
                        </button>
                    </div>
                </div>
            </header>

            <main class="cp-content">
                {{ $slot }}
            </main>

            <footer class="cp-footer">
                <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
                <span>Client Portal · v1.1</span>
            </footer>
        </div>
    </div>

    <div class="cp-scrim" onclick="clientPortal.closeSidebar()"></div>
    <div class="cp-toasts"></div>

    @auth
        <livewire:event-reminders />
    @endauth

    @livewireScripts
</body>
</html>
