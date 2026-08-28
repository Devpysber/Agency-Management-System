@php
    $appName = config('app.name', 'Portal');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot password · {{ $appName }}</title>

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
        .lg-foot { position: relative; z-index: 1; font-size: 12px; color: rgba(255,255,255,0.6); }

        .lg-panel {
            display: flex; align-items: center; justify-content: center;
            padding: 48px 32px;
            background: var(--cp-bg);
        }
        .lg-card { width: 100%; max-width: 400px; }
        .lg-card h2 { font-size: 22px; font-weight: 750; letter-spacing: -0.02em; }
        .lg-card .sub { font-size: 13px; color: var(--cp-text-soft); margin-top: 4px; margin-bottom: 26px; line-height: 1.5; }

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
        .lg-error { display: block; font-size: 11.5px; color: var(--cp-danger); margin-top: 5px; }

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
        .lg-status {
            display: flex; align-items: center; gap: 9px;
            background: var(--cp-success-soft, #ecfdf5); color: var(--cp-success, #059669);
            border: 1px solid color-mix(in srgb, var(--cp-success, #059669) 30%, transparent);
            border-radius: 11px; padding: 11px 14px; font-size: 12.5px; margin-bottom: 20px;
        }
        .lg-back { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: var(--cp-text-soft); text-decoration: none; margin-top: 18px; }
        .lg-back:hover { color: var(--cp-primary); }
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
                <h1>Forgot your password?</h1>
                <p>Enter the email on your account and we'll send you a link to set a new one.</p>
            </div>

            <div class="lg-foot">&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</div>
        </div>

        <div class="lg-panel">
            <div class="lg-card">
                <div class="lg-mobile-brand">
                    <span class="mark"><i class="fas fa-cube"></i></span>
                    {{ $appName }}
                </div>

                <h2>Reset your password</h2>
                <p class="sub">We'll email you a link to get back in.</p>

                @if (session('status'))
                    <div class="lg-status">
                        <i class="fas fa-circle-check"></i>
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="lg-alert">
                        <i class="fas fa-circle-exclamation"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
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

                    <button type="submit" class="lg-submit">Send reset link</button>
                </form>

                <a class="lg-back" href="{{ route('login') }}"><i class="fas fa-arrow-left"></i> Back to sign in</a>
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
    </script>
</body>
</html>
