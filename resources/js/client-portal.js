/* ==========================================================================
   Client Portal — runtime helpers
   Loaded once in the <head> of layouts/client.blade.php, so `window.clientPortal`
   survives Livewire wire:navigate page swaps. Handlers are attached via inline
   on* attributes in the layout, or re-bound on `livewire:navigated`.
   ========================================================================== */
(function () {
    'use strict';

    const THEME_KEY = 'cp-theme';

    const clientPortal = {
        /* ---------- Theme ---------- */
        getTheme() {
            try {
                return localStorage.getItem(THEME_KEY) || 'light';
            } catch (e) {
                return 'light';
            }
        },
        applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            const icon = document.getElementById('cp-theme-icon');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
            document.dispatchEvent(new CustomEvent('cp:theme-changed', { detail: { theme } }));
        },
        toggleTheme() {
            const next = this.getTheme() === 'dark' ? 'light' : 'dark';
            try { localStorage.setItem(THEME_KEY, next); } catch (e) { /* private mode */ }
            this.applyTheme(next);
        },

        /* ---------- Sidebar (mobile) ---------- */
        toggleSidebar() {
            document.body.classList.toggle('cp-sidebar-open');
        },
        closeSidebar() {
            document.body.classList.remove('cp-sidebar-open');
        },

        /* ---------- Header dropdown ---------- */
        toggleMenu(ev) {
            if (ev) ev.stopPropagation();
            const menu = document.querySelector('.cp-menu');
            if (menu) menu.classList.toggle('is-open');
        },
        closeMenu() {
            const menu = document.querySelector('.cp-menu');
            if (menu) menu.classList.remove('is-open');
        },

        /* ---------- Toasts ---------- */
        toast(message, type = 'info') {
            let host = document.querySelector('.cp-toasts');
            if (!host) {
                host = document.createElement('div');
                host.className = 'cp-toasts';
                document.body.appendChild(host);
            }
            const el = document.createElement('div');
            el.className = 'cp-toast ' + (type === 'success' ? 'ok' : type === 'error' ? 'err' : '');
            const icon = type === 'success' ? 'fa-circle-check' : type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-info';
            el.innerHTML = '<i class="fas ' + icon + '"></i><span></span>';
            el.querySelector('span').textContent = message;
            host.appendChild(el);
            setTimeout(() => {
                el.style.transition = 'opacity .25s, transform .25s';
                el.style.opacity = '0';
                el.style.transform = 'translateX(20px)';
                setTimeout(() => el.remove(), 260);
            }, 3600);
        },

        /* ---------- Chart.js theme-aware defaults ---------- */
        chartColors() {
            const css = getComputedStyle(document.documentElement);
            const v = (name, fallback) => (css.getPropertyValue(name).trim() || fallback);
            return {
                text: v('--cp-text-soft', '#5b6270'),
                grid: v('--cp-border', '#e7e8ef'),
                surface: v('--cp-surface', '#ffffff'),
            };
        },
        applyChartDefaults() {
            if (typeof Chart === 'undefined') return;
            const c = this.chartColors();
            Chart.defaults.color = c.text;
            Chart.defaults.borderColor = c.grid;
            Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
        },
    };

    /* ---------- Human-readable duration ---------- */
    clientPortal.fmtDuration = function (ms) {
        const overdue = ms < 0;
        let s = Math.floor(Math.abs(ms) / 1000);
        const d = Math.floor(s / 86400); s -= d * 86400;
        const h = Math.floor(s / 3600); s -= h * 3600;
        const m = Math.floor(s / 60); s -= m * 60;
        const parts = [];
        if (d) parts.push(d + 'd');
        if (h || d) parts.push(h + 'h');
        parts.push(m + 'm');
        if (!d) parts.push(s + 's');
        return overdue ? 'Overdue by ' + parts.join(' ') : parts.join(' ') + ' left';
    };

    window.clientPortal = clientPortal;

    /* ---------- Alpine helpers (used via x-data / x-init) ---------- */
    window.cpCountdown = function (iso) {
        return {
            label: '',
            _timer: null,
            start() {
                const target = new Date(iso).getTime();
                const tick = () => { this.label = clientPortal.fmtDuration(target - Date.now()); };
                tick();
                this._timer = setInterval(tick, 1000);
            },
            destroy() { clearInterval(this._timer); },
        };
    };

    window.cpCountUp = function (el, target) {
        if (!el) return;
        const suffix = (String(el.textContent).match(/[^\d.\-]+$/) || [''])[0];
        const dur = 650, t0 = performance.now();
        const frame = (now) => {
            const p = Math.min(1, (now - t0) / dur);
            el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))) + suffix;
            if (p < 1) requestAnimationFrame(frame);
        };
        requestAnimationFrame(frame);
    };

    /* ---------- Scroll reveal ---------- */
    clientPortal.initMotion = function () {
        // Reveal-on-scroll
        const revealables = document.querySelectorAll('.cp-reveal:not(.is-visible)');
        if (revealables.length) {
            if ('IntersectionObserver' in window) {
                const io = new IntersectionObserver((entries) => {
                    entries.forEach((e) => {
                        if (e.isIntersecting) { e.target.classList.add('is-visible'); io.unobserve(e.target); }
                    });
                }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
                revealables.forEach((el) => io.observe(el));
            } else {
                revealables.forEach((el) => el.classList.add('is-visible'));
            }
        }

    };

    /* ---------- Count-up (load / navigation only, not on poll morphs) ---------- */
    clientPortal.initCounters = function () {
        const targets = document.querySelectorAll('.cp-kpi-value:not([data-cu]), [data-countup]:not([data-cu])');
        targets.forEach((el) => {
            el.setAttribute('data-cu', '1');
            const final = (el.getAttribute('data-countup') || el.textContent || '').trim();
            const numStr = final.replace(/,/g, '').match(/-?\d+(\.\d+)?/);
            if (!numStr) return;
            const target = parseFloat(numStr[0]);
            if (!isFinite(target) || target === 0) return;
            const idx = final.replace(/,/g, '').indexOf(numStr[0]);
            const prefix = final.replace(/,/g, '').slice(0, idx);
            const suffix = final.replace(/,/g, '').slice(idx + numStr[0].length);
            const decimals = (numStr[0].split('.')[1] || '').length;
            const t0 = performance.now(), dur = 750;
            const frame = (now) => {
                const p = Math.min(1, (now - t0) / dur);
                const v = target * (1 - Math.pow(1 - p, 3));
                el.textContent = prefix + v.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + suffix;
                if (p < 1) requestAnimationFrame(frame);
                else el.textContent = final;
            };
            requestAnimationFrame(frame);
        });
    };

    /* ---------- Navbar clock ---------- */
    clientPortal.startClock = function () {
        const el = document.getElementById('cp-clock');
        if (!el || el.dataset.running) return;
        el.dataset.running = '1';
        const pad = (n) => String(n).padStart(2, '0');
        const tick = () => {
            const d = new Date();
            const t = el.querySelector('.cp-clock-time');
            const dt = el.querySelector('.cp-clock-date');
            if (t) t.textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
            if (dt) dt.textContent = d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
        };
        tick();
        setInterval(tick, 1000);
    };

    /* ---------- Boot ---------- */
    // Theme is set pre-paint by an inline script in <head>; this keeps the icon in sync.
    clientPortal.applyTheme(clientPortal.getTheme());
    clientPortal.applyChartDefaults();

    // Pause decorative motion whenever this tab is not the active one.
    (function () {
        const sync = () => document.body.classList.toggle('tab-hidden', document.visibilityState !== 'visible');
        document.addEventListener('visibilitychange', sync);
        window.addEventListener('focus', sync);
        sync();
    })();

    clientPortal.startClock();
    clientPortal.initMotion();
    clientPortal.initCounters();

    // Re-sync after every SPA navigation.
    document.addEventListener('livewire:navigated', () => {
        clientPortal.applyTheme(clientPortal.getTheme());
        clientPortal.applyChartDefaults();
        clientPortal.closeSidebar();
        clientPortal.startClock();
        clientPortal.initMotion();
        clientPortal.initCounters();
        window.scrollTo({ top: 0 });
    });

    // Reveal-only after Livewire DOM updates (poll refreshes, filters) — no re-counting.
    document.addEventListener('livewire:init', () => {
        if (window.Livewire && window.Livewire.hook) {
            window.Livewire.hook('morphed', () => clientPortal.initMotion());
        }
    });

    // Navigation progress bar.
    document.addEventListener('livewire:navigate', () => document.body.classList.add('is-navigating'));
    document.addEventListener('livewire:navigated', () => document.body.classList.remove('is-navigating'));

    // Close mobile sidebar / header menu on Escape.
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { clientPortal.closeSidebar(); clientPortal.closeMenu(); }
    });

    // Close header menu on any outside click.
    document.addEventListener('click', (e) => {
        const menu = document.querySelector('.cp-menu.is-open');
        if (menu && !menu.contains(e.target)) menu.classList.remove('is-open');
    });
    document.addEventListener('livewire:navigated', () => clientPortal.closeMenu());

    // Bridge Livewire flash events to toasts (optional: $this->dispatch('cp-toast', ...)).
    document.addEventListener('livewire:init', () => {
        if (window.Livewire) {
            window.Livewire.on('cp-toast', (payload) => {
                const p = Array.isArray(payload) ? payload[0] : payload;
                clientPortal.toast(p.message || p, p.type || 'info');
            });
        }
    });
})();
