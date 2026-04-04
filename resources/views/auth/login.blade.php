@extends('layouts.app')
@section('title', 'Login - FTI Pak')

@section('styles')
<style>
    .guest-wrapper {
        background: var(--primary);
        position: relative;
        overflow: hidden;
    }
    .guest-wrapper::before {
        content: '';
        position: absolute; top: -30%; right: -10%;
        width: 800px; height: 800px;
        background: radial-gradient(circle, rgba(215,223,39,0.07) 0%, transparent 60%);
    }
    .guest-wrapper::after {
        content: '';
        position: absolute; bottom: -20%; left: -5%;
        width: 500px; height: 500px;
        background: radial-gradient(circle, rgba(215,223,39,0.04) 0%, transparent 60%);
    }
    .login-box {
        width: 100%; max-width: 440px;
        background: #fff; border-radius: 20px;
        box-shadow: 0 25px 60px rgba(0,0,0,0.25);
        overflow: hidden; position: relative; z-index: 1;
        margin: 40px;
    }
    .login-header {
        background: var(--primary-dark);
        padding: 36px 40px 32px;
        text-align: center;
        border-bottom: 3px solid var(--accent);
    }
    .login-body { padding: 36px 40px 40px; }
</style>
@endsection

@section('content')
<div class="login-box">
    <div class="login-header">
        <div style="width: 56px; height: 56px; background: var(--accent); border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem; color: var(--primary); margin-bottom: 16px;">FT</div>
        <h2 style="color: #fff; font-weight: 800; font-size: 1.5rem; margin: 0;">FTI Pak</h2>
        <p style="color: rgba(255,255,255,0.4); font-size: 0.8rem; margin: 4px 0 0; letter-spacing: 1px; text-transform: uppercase; font-weight: 600;">Tax Management System</p>
    </div>
    <div class="login-body">
        <h5 style="font-weight: 700; color: var(--primary); margin: 0 0 4px;">Welcome back</h5>
        <p style="color: #9ca3af; font-size: 0.85rem; margin: 0 0 28px;">Sign in to your account</p>

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
            <div class="form-check mb-4">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label small" for="remember" style="color: #6b7280;">Remember me</label>
            </div>
            <button type="submit" class="btn btn-accent w-100" style="padding: 12px; font-size: 0.95rem;">
                Sign In <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>
        <p style="text-align: center; margin: 28px 0 0; color: #d1d5db; font-size: 0.72rem;">&copy; {{ date('Y') }} FTI Pak. All rights reserved.</p>
    </div>
</div>
@endsection
