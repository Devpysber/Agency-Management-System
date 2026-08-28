@php
    $appName = config('app.name', 'Portal');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in · {{ $appName }}</title>

    <script>
        (function () {
            try {
                document.documentElement.setAttribute('data-theme', localStorage.getItem('cp-theme') || 'light');
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;750&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @vite(['resources/css/client-portal.css'])

    <style>
        body.cp-body { min-height: 100vh; display: flex; }
        .lg-wrap { flex: 1; display: grid; grid-template-columns: 1.05fr 1fr; min-height: 100vh; }

        /* Left brand panel */
        .lg-brand {
            position: relative;
            padding: 56px 60px;
            display: flex; flex-direction: column; justify-content: space-between;
            color: #fff;
            background:
                radial-gradient(130% 100% at 0% 0%, rgba(255,255,255,0.16), transparent 55%),
                linear-gradient(150deg, #4338ca 0%, #4f46e5 40%, #7c3aed 100%);
            overflow: hidden;
        }
        .lg-brand::after {
            content: ""; position: absolute; right: -140px; bottom: -160px;
            width: 460px; height: 460px; border-radius: 50%;
            background: rgba(255,255,255,0.08);
        }
        .lg-brand::before {
            content: ""; position: absolute; right: 60px; top: -120px;
            width: 260px; height: 260px; border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }
        .lg-logo { display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 17px; position: relative; z-index: 1; }
        .lg-logo .mark {
            width: 40px; height: 40px; border-radius: 12px;
            background: rgba(255,255,255,0.18); backdrop-filter: blur(6px);
            display: flex; align-items: center; justify-content: center; font-size: 17px;
        }
        .lg-pitch { position: relative; z-index: 1; }
        .lg-pitch h1 { font-size: 30px; font-weight: 750; line-height: 1.2; letter-spacing: -0.02em; color: #fff; }
        .lg-pitch p { margin-top: 12px; font-size: 14px; color: rgba(255,255,255,0.82); max-width: 420px; line-height: 1.6; }
        .lg-features { margin-top: 30px; display: flex; flex-direction: column; gap: 14px; position: relative; z-index: 1; }
        .lg-feature { display: flex; align-items: center; gap: 12px; font-size: 13.5px; color: rgba(255,255,255,0.92); }
        .lg-feature i {
            width: 30px; height: 30px; border-radius: 9px; flex-shrink: 0;
            background: rgba(255,255,255,0.16);
            display: flex; align-items: center; justify-content: center; font-size: 13px;
        }
        .lg-foot { position: relative; z-index: 1; font-size: 12px; color: rgba(255,255,255,0.6); }

        /* Right form panel */
        .lg-panel {
            display: flex; align-items: center; justify-content: center;
            padding: 48px 32px;
            background: var(--cp-bg);
        }
        .lg-card { width: 100%; max-width: 400px; }
        .lg-card h2 { font-size: 22px; font-weight: 750; letter-spacing: -0.02em; }
        .lg-card .sub { font-size: 13px; color: var(--cp-text-soft); margin-top: 4px; margin-bottom: 26px; }

        .lg-field { margin-bottom: 16px; }
        .lg-field label { display: block; font-size: 12px; font-weight: 600; color: var(--cp-text-soft); margin-bottom: 6px; }
        .lg-input-wrap { position: relative; }
        .lg-input-wrap > i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--cp-text-faint); font-size: 13px; }
        .lg-input {
            width: 100%;
            background: var(--cp-surface);
            border: 1px solid var(--cp-border);
            border-radius: 11px;
            padding: 11px 13px 11px 38px;
            font-size: 13.5px; font-family: inherit; color: var(--cp-text);
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .lg-input:focus { outline: 0; border-color: var(--cp-primary); box-shadow: 0 0 0 3px var(--cp-primary-soft); }
        .lg-input.is-invalid { border-color: var(--cp-danger); }
        .lg-pw-toggle {
            position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
            border: 0; background: 0; color: var(--cp-text-faint); cursor: pointer;
            width: 30px; height: 30px; border-radius: 8px; font-size: 13px;
        }
        .lg-pw-toggle:hover { color: var(--cp-text); background: var(--cp-surface-3); }
        .lg-error { display: block; font-size: 11.5px; color: var(--cp-danger); margin-top: 5px; }

        .lg-row { display: flex; align-items: center; justify-content: space-between; margin: 4px 0 20px; }
        .lg-check { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--cp-text-soft); cursor: pointer; }
        .lg-check input { width: 15px; height: 15px; accent-color: var(--cp-primary); }
        .lg-link { font-size: 12.5px; font-weight: 600; color: var(--cp-primary); }

        .lg-submit {
            width: 100%;
            background: var(--cp-primary); color: #fff;
            border: 0; border-radius: 11px;
            padding: 12px; font-size: 14px; font-weight: 650; font-family: inherit;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(79,70,229,0.28);
            transition: background 0.15s, transform 0.1s;
        }
        .lg-submit:hover { background: var(--cp-primary-strong); }
        .lg-submit:active { transform: translateY(1px); }

        .lg-alert {
            display: flex; align-items: center; gap: 9px;
            background: var(--cp-danger-soft); color: var(--cp-danger);
            border: 1px solid color-mix(in srgb, var(--cp-danger) 30%, transparent);
            border-radius: 11px; padding: 11px 14px; font-size: 12.5px; margin-bottom: 20px;
        }
        .lg-theme {
            position: absolute; top: 20px; right: 24px;
            width: 36px; height: 36px; border-radius: 10px;
            border: 1px solid var(--cp-border); background: var(--cp-surface);
            color: var(--cp-text-soft); cursor: pointer; font-size: 14px;
            display: flex; align-items: center; justify-content: center; z-index: 5;
        }
        .lg-mobile-brand { display: none; }

        @media (max-width: 900px) {
            .lg-wrap { grid-template-columns: 1fr; }
            .lg-brand { display: none; }
            .lg-mobile-brand {
                display: flex; align-items: center; gap: 10px; justify-content: center;
                font-weight: 700; font-size: 16px; color: var(--cp-text); margin-bottom: 24px;
            }
            .lg-mobile-brand .mark {
                width: 34px; height: 34px; border-radius: 10px; color: #fff;
                background: linear-gradient(135deg, var(--cp-primary), #8b5cf6);
                display: flex; align-items: center; justify-content: center;
            }
        }
    </style>
</head>
<body class="cp-body">
    <button class="lg-theme" onclick="(function(){try{var t=localStorage.getItem('cp-theme')==='dark'?'light':'dark';localStorage.setItem('cp-theme',t);document.documentElement.setAttribute('data-theme',t);document.getElementById('lg-ti').className=t==='dark'?'fas fa-sun':'fas fa-moon';}catch(e){}})()">
        <i id="lg-ti" class="fas fa-moon"></i>
    </button>

    <div class="lg-wrap">
        <div class="lg-brand">
            <div class="lg-logo">
                <span class="mark"><i class="fas fa-cube"></i></span>
                {{ $appName }}
            </div>

            <div class="lg-pitch">
                <h1>Run the agency. Deliver for every client.</h1>
                <p>One login for your team and your clients — projects, deals, tasks, estimates, and payments, all scoped to what each person is meant to see.</p>

                <div class="lg-features">
                    <div class="lg-feature"><i class="fas fa-diagram-project"></i> Live project &amp; milestone tracking</div>
                    <div class="lg-feature"><i class="fas fa-user-shield"></i> Role-based access, per designation</div>
                    <div class="lg-feature"><i class="fas fa-file-invoice"></i> Estimates, quotations &amp; payments in one place</div>
                    <div class="lg-feature"><i class="fas fa-lock"></i> Secure, company-scoped access</div>
                </div>
            </div>

            <div class="lg-foot">&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</div>
        </div>

        <div class="lg-panel">
            <div class="lg-card">
                <div class="lg-mobile-brand">
                    <span class="mark"><i class="fas fa-cube"></i></span>
                    {{ $appName }}
                </div>

                <h2>Sign in</h2>
                <p class="sub">Enter your credentials to access the portal.</p>

                @if ($errors->any())
                    <div class="lg-alert">
                        <i class="fas fa-circle-exclamation"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="lg-field">
                        <label for="email">Email address</label>
                        <div class="lg-input-wrap">
                            <i class="fas fa-envelope"></i>
                            <input id="email" name="email" type="email" autocomplete="email" autofocus required
                                   value="{{ old('email') }}"
                                   class="lg-input @error('email') is-invalid @enderror"
                                   placeholder="you@company.com">
                        </div>
                        @error('email') <span class="lg-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="lg-field">
                        <label for="password">Password</label>
                        <div class="lg-input-wrap">
                            <i class="fas fa-lock"></i>
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                   class="lg-input @error('password') is-invalid @enderror"
                                   placeholder="••••••••">
                            <button type="button" class="lg-pw-toggle" aria-label="Show password"
                                    onclick="(function(b){var i=document.getElementById('password');var s=i.type==='password';i.type=s?'text':'password';b.firstElementChild.className=s?'fas fa-eye-slash':'fas fa-eye';})(this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('password') <span class="lg-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="lg-row">
                        <label class="lg-check">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            Remember me
                        </label>
                        @if (Route::has('password.request'))
                            <a class="lg-link" href="{{ route('password.request') }}">Forgot password?</a>
                        @endif
                    </div>

                    <button type="submit" class="lg-submit">Sign in</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            try {
                if ((localStorage.getItem('cp-theme') || 'light') === 'dark') {
                    document.getElementById('lg-ti').className = 'fas fa-sun';
                }
            } catch (e) {}
        })();
        // If this page is restored from the back/forward cache after the user has
        // logged in, force a real request so the "guest" redirect kicks them to
        // their dashboard instead of showing a stale login form.
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) window.location.reload();
        });
    </script>
</body>
</html>
