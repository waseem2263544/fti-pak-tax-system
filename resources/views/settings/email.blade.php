@extends('layouts.app')
@section('title', 'Email Settings')
@section('page-title', 'Email Settings')

@section('content')
<div class="row g-3">
    <div class="col-md-8">
        <!-- Microsoft Email Connection -->
        <div class="card section-card mb-4">
            <div class="card-header" style="background: rgba(48,58,80,0.02);">
                <i class="bi bi-microsoft" style="color: #0078d4;"></i>
                <span style="font-weight: 700;">Microsoft Email Integration</span>
            </div>
            <div class="card-body" style="padding: 24px;">
                @if($settings)
                    <!-- Connected -->
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div style="width: 48px; height: 48px; border-radius: 12px; background: #d1fae5; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-check-circle-fill" style="color: #065f46; font-size: 1.4rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: var(--primary); font-size: 1rem;">Connected</div>
                            <div style="font-size: 0.85rem; color: #6b7280;">{{ $settings->email_address }}</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.8px;">FBR Sender Email</div>
                            <div style="font-size: 0.88rem; color: var(--primary); font-family: monospace;">{{ $settings->fbr_sender_email }}</div>
                        </div>
                        <div class="col-md-4">
                            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.8px;">Token Expires</div>
                            <div style="font-size: 0.88rem; color: var(--primary);">
                                @if($settings->token_expires_at)
                                    {{ $settings->token_expires_at->format('M d, Y H:i') }}
                                    @if($settings->token_expires_at->isPast())
                                        <span class="badge" style="background: #fef2f2; color: #dc2626;">Expired</span>
                                    @else
                                        <span class="badge" style="background: #d1fae5; color: #065f46;">Active</span>
                                    @endif
                                @else
                                    Unknown
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.8px;">Last Synced</div>
                            <div style="font-size: 0.88rem; color: var(--primary);">{{ $settings->last_synced_at ? $settings->last_synced_at->diffForHumans() : 'Never' }}</div>
                        </div>
                    </div>

                    <!-- Update FBR sender email -->
                    <form method="POST" action="{{ route('settings.email.update-sender') }}" class="mb-3">
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <label class="form-label">FBR Sender Email (to filter)</label>
                                <input type="email" name="fbr_sender_email" class="form-control" value="{{ $settings->fbr_sender_email }}">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary btn-sm">Update</button>
                            </div>
                        </div>
                    </form>

                    <div class="d-flex gap-2">
                        <a href="{{ route('auth.microsoft.test') }}" class="btn btn-accent btn-sm"><i class="bi bi-lightning-charge me-1"></i>Test Connection</a>
                        <a href="{{ route('auth.microsoft.redirect') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-repeat me-1"></i>Reconnect</a>
                        <form method="POST" action="{{ route('auth.microsoft.disconnect') }}" onsubmit="return confirm('Disconnect Microsoft email?')">
                            @csrf
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle me-1"></i>Disconnect</button>
                        </form>
                    </div>
                @else
                    <!-- Not Connected -->
                    <div class="text-center py-4">
                        <div style="width: 64px; height: 64px; border-radius: 16px; background: rgba(0,120,212,0.08); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                            <i class="bi bi-microsoft" style="color: #0078d4; font-size: 1.8rem;"></i>
                        </div>
                        <h5 style="font-weight: 700; color: var(--primary);">Connect Microsoft Email</h5>
                        <p style="color: #6b7280; font-size: 0.85rem; max-width: 400px; margin: 0 auto 20px;">
                            Connect your Outlook/Office 365 email to automatically fetch FBR notices. The system will scan incoming emails from FBR every hour.
                        </p>
                        <a href="{{ route('auth.microsoft.redirect') }}" class="btn btn-accent">
                            <i class="bi bi-microsoft me-2"></i>Connect with Microsoft
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- How it works -->
        <div class="card section-card">
            <div class="card-header">
                <i class="bi bi-info-circle" style="color: var(--accent);"></i>
                <span style="font-weight: 700;">How it works</span>
            </div>
            <div class="card-body" style="padding: 20px;">
                <div class="d-flex gap-3 mb-3">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.75rem; color: var(--primary); flex-shrink: 0;">1</div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.85rem; color: var(--primary);">Connect Email</div>
                        <div style="font-size: 0.78rem; color: #9ca3af;">Sign in with your Microsoft account that receives FBR emails</div>
                    </div>
                </div>
                <div class="d-flex gap-3 mb-3">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.75rem; color: var(--primary); flex-shrink: 0;">2</div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.85rem; color: var(--primary);">Auto-Scan</div>
                        <div style="font-size: 0.78rem; color: #9ca3af;">System checks for FBR emails every hour automatically</div>
                    </div>
                </div>
                <div class="d-flex gap-3 mb-3">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.75rem; color: var(--primary); flex-shrink: 0;">3</div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.85rem; color: var(--primary);">Categorize</div>
                        <div style="font-size: 0.78rem; color: #9ca3af;">Notices are auto-categorized by section and tax year</div>
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.75rem; color: var(--primary); flex-shrink: 0;">4</div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.85rem; color: var(--primary);">Get Notified</div>
                        <div style="font-size: 0.78rem; color: #9ca3af;">All admins receive in-app notifications for new notices</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
