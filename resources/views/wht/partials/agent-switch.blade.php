{{-- Agent switcher for the page header. Replaces the old full-width bar. --}}
@php
    $whtAgents = \App\Models\WhtCompany::accessibleTo(Auth::user());
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-building me-1"></i>
                <strong>{{ $company->name }}</strong>
                @if($company->ntn_cnic)
                    <span class="text-muted ms-1" style="font-size: 0.8rem;">NTN {{ $company->ntn_cnic }}</span>
                @endif
            </button>
            <ul class="dropdown-menu" style="max-height: 320px; overflow-y: auto;">
                @foreach($whtAgents as $a)
                    <li>
                        <a class="dropdown-item {{ $a->id === $company->id ? 'active' : '' }}"
                           href="{{ route('wht.companies.select', $a) }}">
                            {{ $a->name }}
                            @if($a->ntn_cnic)
                                <span class="{{ $a->id === $company->id ? '' : 'text-muted' }}" style="font-size: 0.78rem;">
                                    · {{ $a->ntn_cnic }}
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
                @if(Auth::user()->hasRole('admin'))
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('wht.companies.edit', $company) }}">
                        <i class="bi bi-pencil me-1"></i> Edit {{ $company->name }}
                        @if(blank($company->office_reference))
                            <span class="badge bg-warning text-dark ms-1">office ref missing</span>
                        @endif
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('wht.companies.create') }}">
                        <i class="bi bi-plus-lg me-1"></i> Add withholding agent
                    </a></li>
                @endif
            </ul>
        </div>

        @isset($subtitle)
            <span class="text-muted" style="font-size: 0.85rem;">{{ $subtitle }}</span>
        @endisset
    </div>

    @isset($actions)
        <div class="d-flex gap-2">{!! $actions !!}</div>
    @endisset
</div>
