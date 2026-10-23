@extends('layouts.app')
@section('title', $firm->firm_name)
@section('page-title', 'Firm Registration')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4" style="font-size: 0.85rem;">
    <a href="{{ route('firm-registrations.index') }}" style="color: var(--n-400); text-decoration: none;"><i class="bi bi-house me-1"></i>Firm Registration</a>
    <span style="color: var(--n-300);"><i class="bi bi-chevron-right"></i></span>
    <span style="color: var(--primary); font-weight: 600;">{{ $firm->firm_name }}</span>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-file-earmark-word me-1"></i> <strong>Documents</strong>
            <small style="color: var(--n-400);">Legal size · stamp-paper pages start {{ rtrim(rtrim(number_format($firm->stamp_top_cm, 2), '0'), '.') }} cm down</small></span>
        <a href="{{ route('firm-registrations.download', [$firm, 'all']) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-file-earmark-zip me-1"></i> Download all three (ZIP)
        </a>
    </div>
    <div class="card-body d-flex gap-2 flex-wrap" style="padding: 20px 24px;">
        @foreach(\App\Services\FirmRegistration\FirmDocuments::DOCS as $key => $label)
            <a href="{{ route('firm-registrations.download', [$firm, $key]) }}" class="btn btn-outline-primary">
                <i class="bi bi-download me-1"></i> {{ $label }}
                <small class="d-block" style="font-size: 0.7rem; opacity: .7;">{{ $key === 'form-a' ? 'plain paper' : 'stamp paper' }}</small>
            </a>
        @endforeach
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>{{ $firm->firm_name }}</strong>
        <div class="d-flex gap-2">
            <a href="{{ route('firm-registrations.create', ['from' => $firm->id]) }}" class="btn btn-sm btn-outline-secondary" title="New firm with the same partners and witnesses"><i class="bi bi-copy me-1"></i> Copy to new firm</a>
            <a href="{{ route('firm-registrations.edit', $firm) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i> Edit</a>
            <form method="POST" action="{{ route('firm-registrations.destroy', $firm) }}" onsubmit="return confirm('Delete this firm registration?');">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
        </div>
    </div>
    <div class="card-body" style="padding: 24px;">
        <dl class="row mb-0" style="font-size: 0.9rem;">
            <dt class="col-sm-3">Head office</dt><dd class="col-sm-9">{{ $firm->principal_place }}</dd>
            <dt class="col-sm-3">Other places</dt><dd class="col-sm-9">{{ $firm->other_places ?: 'NIL' }}</dd>
            <dt class="col-sm-3">Business</dt><dd class="col-sm-9">{{ $firm->business_description }}</dd>
            <dt class="col-sm-3">Deed date</dt><dd class="col-sm-9">{{ $firm->deed_date->format('d M Y') }}</dd>
            <dt class="col-sm-3">Commenced</dt><dd class="col-sm-9">{{ $firm->commencement_date->format('d M Y') }} · {{ $firm->duration }}</dd>
            <dt class="col-sm-3">Managing partner</dt><dd class="col-sm-9">{{ $firm->managingPartner()['name'] ?? 'None' }}</dd>
            @if($firm->client)<dt class="col-sm-3">Client</dt><dd class="col-sm-9">{{ $firm->client->name }}</dd>@endif
            @if($firm->notes)<dt class="col-sm-3">Notes</dt><dd class="col-sm-9">{{ $firm->notes }}</dd>@endif
        </dl>
    </div>
</div>

<div class="row g-4 mb-5">
    @foreach(['partners' => 'Partners', 'witnesses' => 'Witnesses'] as $key => $title)
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><strong>{{ $title }}</strong></div>
            <div class="card-body p-0">
                <table class="table mb-0" style="font-size: 0.85rem;">
                    @foreach($firm->$key as $i => $p)
                    <tr>
                        <td style="width: 32px; color: var(--n-400);">{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $p['name'] }}</strong> {{ $p['relation'] ?? 'S/O' }} {{ $p['father_name'] }}<br>
                            <span style="color: var(--n-500);">{{ $p['cnic'] ?? '' }}{{ !empty($p['cell']) ? ' · ' . $p['cell'] : '' }}</span><br>
                            <span style="color: var(--n-500);">{{ $p['address'] ?? '' }}</span>
                        </td>
                        @if($key === 'partners')
                        <td class="text-end" style="white-space: nowrap;">{{ $p['capital_share'] }}% capital<br>{{ $p['profit_share'] }}% P&amp;L</td>
                        @endif
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
