@extends('layouts.app')
@section('title', 'Chrome Extension')
@section('page-title', 'Chrome Extension')

@section('content')
<div class="row g-4">
    <div class="col-md-7">
        <div class="card">
            <div class="card-body" style="padding: 32px;">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div style="width: 56px; height: 56px; background: linear-gradient(135deg, var(--accent) 0%, #a8b01a 100%); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem; color: var(--primary);">FT</div>
                    <div>
                        <h4 style="font-weight: 800; color: var(--primary); margin: 0;">FairTax Credential Manager</h4>
                        <p style="color: #6b7280; font-size: 0.85rem; margin: 0;">Chrome Extension v1.0</p>
                    </div>
                </div>

                <p style="color: #4b5563; font-size: 0.9rem; line-height: 1.7; margin-bottom: 24px;">
                    Auto-fill FBR, KPRA, and SECP login credentials directly from your client database.
                    No more copying and pasting passwords - just click and fill.
                </p>

                <div class="d-flex gap-3 mb-4">
                    <a href="/fairtax-extension.zip" class="btn btn-accent" download>
                        <i class="bi bi-download me-2"></i>Download Extension
                    </a>
                </div>

                <h5 style="font-weight: 700; color: var(--primary); margin-bottom: 16px;">Installation Guide</h5>

                <div class="d-flex gap-3 mb-3">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; color: var(--primary); flex-shrink: 0;">1</div>
                    <div>
                        <div style="font-weight: 600; color: var(--primary);">Download & Extract</div>
                        <div style="font-size: 0.82rem; color: #6b7280;">Click "Download Extension" above. Extract the ZIP file to a folder on your computer.</div>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-3">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; color: var(--primary); flex-shrink: 0;">2</div>
                    <div>
                        <div style="font-weight: 600; color: var(--primary);">Open Chrome Extensions</div>
                        <div style="font-size: 0.82rem; color: #6b7280;">Go to <code style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px;">chrome://extensions</code> in your browser. Enable <strong>"Developer mode"</strong> (top right toggle).</div>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-3">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; color: var(--primary); flex-shrink: 0;">3</div>
                    <div>
                        <div style="font-weight: 600; color: var(--primary);">Load Extension</div>
                        <div style="font-size: 0.82rem; color: #6b7280;">Click <strong>"Load unpacked"</strong>. Select the <code style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px;">chrome-extension</code> folder from the extracted ZIP.</div>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-3">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; color: var(--primary); flex-shrink: 0;">4</div>
                    <div>
                        <div style="font-weight: 600; color: var(--primary);">Pin & Login</div>
                        <div style="font-size: 0.82rem; color: #6b7280;">Pin the extension to your toolbar. Click it and login with your FairTax app email and password.</div>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; color: var(--primary); flex-shrink: 0;">5</div>
                    <div>
                        <div style="font-weight: 600; color: var(--primary);">Use It</div>
                        <div style="font-size: 0.82rem; color: #6b7280;">Visit iris.fbr.gov.pk, kpra.kp.gov.pk, or leap.secp.gov.pk. Click the extension, search for a client, and hit <strong>"Fill"</strong>.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-shield-check me-2" style="color: var(--accent);"></i><span style="font-weight: 700;">Security</span></div>
            <div class="card-body" style="padding: 20px;">
                <div class="mb-2" style="font-size: 0.85rem; color: #4b5563;">
                    <i class="bi bi-check-circle-fill me-2" style="color: #10b981;"></i>Credentials are <strong>never stored</strong> in the browser
                </div>
                <div class="mb-2" style="font-size: 0.85rem; color: #4b5563;">
                    <i class="bi bi-check-circle-fill me-2" style="color: #10b981;"></i>Fetched from server on demand via <strong>encrypted HTTPS</strong>
                </div>
                <div class="mb-2" style="font-size: 0.85rem; color: #4b5563;">
                    <i class="bi bi-check-circle-fill me-2" style="color: #10b981;"></i>Requires <strong>employee login</strong> to access
                </div>
                <div class="mb-2" style="font-size: 0.85rem; color: #4b5563;">
                    <i class="bi bi-check-circle-fill me-2" style="color: #10b981;"></i>Token expires after <strong>12 hours</strong>
                </div>
                <div style="font-size: 0.85rem; color: #4b5563;">
                    <i class="bi bi-check-circle-fill me-2" style="color: #10b981;"></i>Only works on <strong>FBR, KPRA, SECP</strong> portals
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-globe me-2" style="color: var(--accent);"></i><span style="font-weight: 700;">Supported Portals</span></div>
            <div class="card-body p-0">
                <div class="d-flex align-items-center gap-3 px-4 py-3" style="border-bottom: 1px solid #f5f6f8;">
                    <span class="badge" style="background: #dbeafe; color: #1e40af;">FBR</span>
                    <span style="font-size: 0.85rem;">iris.fbr.gov.pk</span>
                </div>
                <div class="d-flex align-items-center gap-3 px-4 py-3" style="border-bottom: 1px solid #f5f6f8;">
                    <span class="badge" style="background: #d1fae5; color: #065f46;">KPRA</span>
                    <span style="font-size: 0.85rem;">kpra.kp.gov.pk</span>
                </div>
                <div class="d-flex align-items-center gap-3 px-4 py-3">
                    <span class="badge" style="background: #fef3c7; color: #92400e;">SECP</span>
                    <span style="font-size: 0.85rem;">leap.secp.gov.pk</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
