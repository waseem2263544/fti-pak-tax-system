@extends('layouts.app')
@section('title', 'Login - FTI Pak')

@section('content')
<div class="guest-left">
    <div style="position: relative; z-index: 1; text-align: center;">
        <div style="width: 72px; height: 72px; background: var(--accent); border-radius: 18px; display: flex; align-items: center; justify-content: center; margin: 0 auto 28px; font-weight: 800; font-size: 1.4rem; color: var(--primary);">FT</div>
        <h1 style="color: #fff; font-size: 2.4rem; font-weight: 800; margin: 0; line-height: 1.2;">FTI Pak</h1>
        <p style="color: rgba(255,255,255,0.5); font-size: 1rem; margin: 8px 0 0; font-weight: 400;">Tax Management System</p>
        <div style="margin-top: 48px; display: flex; gap: 32px; justify-content: center;">
            <div style="text-align: center;">
                <div style="color: var(--accent); font-size: 1.6rem; font-weight: 800;">100%</div>
                <div style="color: rgba(255,255,255,0.35); font-size: 0.75rem; font-weight: 500;">Secure</div>
            </div>
            <div style="width: 1px; background: rgba(255,255,255,0.1);"></div>
            <div style="text-align: center;">
                <div style="color: var(--accent); font-size: 1.6rem; font-weight: 800;">24/7</div>
                <div style="color: rgba(255,255,255,0.35); font-size: 0.75rem; font-weight: 500;">Access</div>
            </div>
            <div style="width: 1px; background: rgba(255,255,255,0.1);"></div>
            <div style="text-align: center;">
                <div style="color: var(--accent); font-size: 1.6rem; font-weight: 800;">FBR</div>
                <div style="color: rgba(255,255,255,0.35); font-size: 0.75rem; font-weight: 500;">Integrated</div>
            </div>
        </div>
    </div>
</div>

<div class="guest-right">
    <div>
        <h2 style="font-weight: 800; color: var(--primary); margin: 0 0 6px; font-size: 1.6rem;">Welcome back</h2>
        <p style="color: #9ca3af; font-size: 0.9rem; margin: 0 0 32px;">Sign in to continue to your dashboard</p>

        @if($errors->any())
        <div class="alert alert-danger py-2 mb-3" style="font-size: 0.82rem;">
            @foreach($errors->all() as $error)
                <div><i class="bi bi-exclamation-circle me-1"></i>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <div style="position: relative;">
                    <i class="bi bi-envelope" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@company.com" style="padding-left: 40px;">
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div style="position: relative;">
                    <i class="bi bi-lock" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password" style="padding-left: 40px;">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label small" for="remember" style="color: #6b7280;">Remember me</label>
                </div>
            </div>

            <button type="submit" class="btn btn-accent w-100" style="padding: 12px; font-size: 0.95rem;">
                Sign In <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>

        <p style="text-align: center; margin-top: 32px; color: #d1d5db; font-size: 0.75rem;">&copy; {{ date('Y') }} FTI Pak Tax Management</p>
    </div>
</div>
@endsection
