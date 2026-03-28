@extends('layouts.app')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap');

    :root {
        --login-bg-1: #f6f3ef;
        --login-bg-2: #ece7e1;
        --login-ink: #1e1c1a;
        --login-muted: #69635d;
        --login-border: rgba(34, 29, 24, 0.14);
        --login-card: rgba(255, 255, 255, 0.84);
        --login-accent: #9e2d2d;
        --login-accent-hover: #841f1f;
        --login-focus: rgba(158, 45, 45, 0.16);
    }

    .login-premium-shell {
        min-height: 100vh;
        background:
            radial-gradient(700px 320px at 10% 20%, rgba(158, 45, 45, 0.09), transparent 68%),
            radial-gradient(560px 280px at 86% 88%, rgba(102, 84, 59, 0.09), transparent 72%),
            linear-gradient(145deg, var(--login-bg-1) 0%, var(--login-bg-2) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.2rem;
        font-family: 'Manrope', sans-serif;
    }

    .login-premium-card {
        width: min(100%, 460px);
        border-radius: 22px;
        background: var(--login-card);
        border: 1px solid var(--login-border);
        backdrop-filter: blur(8px);
        box-shadow: 0 16px 40px rgba(41, 32, 25, 0.13);
        padding: 1.7rem 1.4rem;
        animation: login-card-in 300ms ease-out;
    }

    .login-brand {
        text-align: center;
        margin-bottom: 1.35rem;
    }

    .login-brand-logo {
        width: 120px;
        height: auto;
        margin-bottom: 0.5rem;
    }

    .login-brand-title {
        color: var(--login-ink);
        font-weight: 800;
        font-size: clamp(1.45rem, 2.2vw, 1.8rem);
        letter-spacing: -0.015em;
        margin-bottom: 0.2rem;
    }

    .login-brand-subtitle {
        color: var(--login-muted);
        font-size: 0.91rem;
        margin-bottom: 0;
    }

    .login-premium-form .form-label {
        color: var(--login-ink);
        font-weight: 700;
        font-size: 0.9rem;
        margin-bottom: 0.35rem;
    }

    .login-premium-input {
        border-radius: 12px;
        border: 1px solid var(--login-border);
        min-height: 47px;
        font-size: 0.95rem;
        color: var(--login-ink);
        background: rgba(255, 255, 255, 0.88);
        transition: border-color 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
    }

    .login-premium-input:focus {
        border-color: var(--login-accent);
        box-shadow: 0 0 0 4px var(--login-focus);
        background: #fff;
    }

    .login-premium-input.is-invalid {
        border-color: #dc3545;
        box-shadow: none;
    }

    .login-hint {
        color: var(--login-muted);
        font-size: 0.78rem;
        margin-top: 0.45rem;
    }

    .toggle-password {
        color: #7c7670;
        transition: color 150ms ease;
    }

    .toggle-password:hover {
        color: var(--login-ink);
    }

    .login-premium-check {
        border-radius: 4px;
        border-color: rgba(34, 29, 24, 0.28);
    }

    .login-premium-check:checked {
        background-color: var(--login-accent);
        border-color: var(--login-accent);
    }

    .login-premium-submit {
        border-radius: 12px;
        min-height: 48px;
        border: none;
        background: linear-gradient(135deg, var(--login-accent), #7f2121);
        color: #fff;
        font-weight: 700;
        letter-spacing: 0.01em;
        transition: transform 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
        box-shadow: 0 10px 20px rgba(126, 33, 33, 0.24);
    }

    .login-premium-submit:hover {
        color: #fff;
        background: linear-gradient(135deg, var(--login-accent-hover), #6c1b1b);
        transform: translateY(-1px);
        box-shadow: 0 12px 24px rgba(108, 27, 27, 0.28);
    }

    .login-premium-link {
        color: #7a2121;
        font-weight: 600;
        text-decoration: none;
        font-size: 0.86rem;
    }

    .login-premium-link:hover {
        color: #5b1616;
        text-decoration: underline;
    }

    .login-premium-footer {
        margin-top: 0.9rem;
        color: #8a837c;
        font-size: 0.79rem;
    }

    @keyframes login-card-in {
        from {
            opacity: 0;
            transform: translateY(12px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 575.98px) {
        .login-premium-shell {
            padding: 0.8rem;
        }

        .login-premium-card {
            border-radius: 18px;
            padding: 1.25rem 1rem;
        }
    }
</style>

<div class="login-premium-shell">
    <div class="login-premium-card">
        <div class="login-brand">
            <img src="{{ siteLogo() }}" class="login-brand-logo" alt="Logo">
            <h2 class="login-brand-title">{{ siteName() }}</h2>
            <p class="login-brand-subtitle">{{ __('Accede a tu cuenta') }}</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="login-premium-form">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">{{ __('Email Address') }}</label>
                <input id="email" type="email"
                    class="form-control login-premium-input @error('email') is-invalid @enderror"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    autofocus>
                <div class="login-hint">{{ __('Introduce tu correo electrónico de acceso') }}</div>

                @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">{{ __('Password') }}</label>
                <div class="password-container position-relative">
                    <input id="password" type="password"
                        class="form-control login-premium-input @error('password') is-invalid @enderror"
                        name="password"
                        required
                        autocomplete="current-password"
                        style="padding-right: 45px;">
                    <i class="bi bi-eye toggle-password position-absolute"
                        id="togglePassword"
                        style="right: 14px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 1.1rem;"></i>

                    @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                    @enderror
                </div>
                <div class="login-hint">{{ __('Escribe tu contraseña para continuar') }}</div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input login-premium-check" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">
                        {{ __('Remember Me') }}
                    </label>
                </div>
            </div>

            <div class="d-grid mb-2">
                <button type="submit" class="btn login-premium-submit">
                    {{ __('Access') }}
                </button>
            </div>

            @if (Route::has('password.request'))
            <div class="text-center mb-2">
                <a class="login-premium-link" href="{{ route('password.request') }}">
                    {{ __('Forgot Your Password?') }}
                </a>
            </div>
            @endif

            <div class="text-center login-premium-footer">
                <p class="mb-0">© {{ date('Y') }} {{ siteName() }}</p>
            </div>
        </form>
    </div>
</div>
@endsection