{{-- Shows which withholding agent the page is scoped to, with a quick switcher. --}}
<div class="card mb-4">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2" style="padding: 12px 20px;">
        <div>
            <span style="color: #9ca3af; font-size: 0.75rem; text-transform: uppercase; letter-spacing: .04em;">Withholding Agent</span>
            <div class="fw-semibold" style="font-size: 1.05rem;">
                {{ $company->name }}
                @if($company->ntn_cnic)
                    <span class="text-muted fw-normal" style="font-size: 0.85rem;">· NTN {{ $company->ntn_cnic }}</span>
                @endif
            </div>
        </div>
        <a href="{{ route('wht.companies.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left-right me-1"></i> Switch
        </a>
    </div>
</div>
