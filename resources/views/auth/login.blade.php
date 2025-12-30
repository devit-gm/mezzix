@extends('layouts.app')

@section('content')
<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center py-3">
    <div class="row justify-content-center w-100 mx-2">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">

            <div class="card shadow-lg border-0" style="border-radius: 20px; overflow: hidden;">

                <div class="card-body p-3 p-md-4" style="max-height: 90vh; overflow-y: auto;">
                    <!-- Logo y Título -->
                    <div class="text-center mb-3">
                        <img src="{{ siteLogo() }}" style="width:120px; height:auto" class="mb-2" alt="Logo">
                        <h2 class="color-rojo fw-bold mb-1">{{ siteName() }}</h2>
                        <p class="text-muted small mb-0">{{ __('Accede a tu cuenta') }}</p>
                    </div>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">
                                <i class="bi bi-envelope me-1"></i> {{ __('Email Address') }}
                            </label>
                            <input id="email" type="email" 
                                class="form-control form-control-lg @error('email') is-invalid @enderror" 
                                name="email" 
                                value="{{ old('email') }}" 
                                placeholder="tu@email.com"
                                required 
                                autocomplete="email" 
                                autofocus
                                style="border-radius: 10px;">

                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">
                                <i class="bi bi-lock me-1"></i> {{ __('Password') }}
                            </label>
                            <div class="password-container position-relative">
                                <input id="password" type="password" 
                                    class="form-control form-control-lg @error('password') is-invalid @enderror" 
                                    name="password" 
                                    placeholder="••••••••"
                                    required 
                                    autocomplete="current-password"
                                    style="border-radius: 10px; padding-right: 45px;">
                                <i class="bi bi-eye toggle-password position-absolute" 
                                   id="togglePassword"
                                   style="right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 1.2rem;"></i>
                                
                                @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">
                                    {{ __('Remember Me') }}
                                </label>
                            </div>
                        </div>

                        <!-- Botón de Login -->
                        <div class="d-grid gap-2 mb-2">
                            <button type="submit" class="btn btn-light btn-lg fw-semibold" style="border-radius: 10px;">
                                <i class="bi bi-box-arrow-in-right me-2"></i>{{ __('Access') }}
                            </button>
                        </div>

                        <!-- Forgot Password -->
                        @if (Route::has('password.request'))
                        <div class="text-center mb-2">
                            <a class="btn btn-link color-rojo text-decoration-none small" href="{{ route('password.request') }}">
                                <i class="bi bi-question-circle me-1"></i>{{ __('Forgot Your Password?') }}
                            </a>
                        </div>
                        @endif

                        <!-- Footer con año -->
                        <div class="text-center mt-2">
                            <p class="text-muted small mb-0">© {{ date('Y') }} {{ siteName() }}</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection