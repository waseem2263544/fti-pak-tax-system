@extends('layouts.app')

@section('title', 'Login - FTI Pak Tax Management')

@section('content')
<div class="login-card" style="width: 420px;">
    <div style="background: var(--primary); padding: 32px; text-align: center;">
        <h3 style="color: var(--accent); font-weight: 700; margin: 0; font-size: 1.6rem; letter-spacing: 1px;">FTI PAK</h3>
        <p style="color: var(--text-muted-light); margin: 4px 0 0; font-size: 0.85rem;">Tax Management System</p>
    </div>
    <div style="padding: 36px;">
        <h5 style="font-weight: 600; color: var(--primary); margin-bottom: 24px;">Sign in to your account</h5>

        @if($errors->any())
        <div class="alert alert-danger py-2" style="font-size: 0.85rem;">
            @foreach($errors->all() as $error)
                <div><i class="bi bi-exclamation-circle me-1"></i>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label small" for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn btn-accent w-100" style="padding: 10px; font-size: 0.95rem;">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
            </button>
        </form>
    </div>
</div>
@endsection
