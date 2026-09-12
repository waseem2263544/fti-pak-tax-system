@extends('layouts.app')
@section('title', 'Challans')
@section('page-title', 'Challans (PSID / CPR)')

@section('content')
@include('wht.partials.agent-switch', ['company' => $company])

<div class="d-flex justify-content-between align-items-center mb-3">
    <p style="color: var(--n-400); font-size: 0.85rem; margin: 0;">
        Every PSID referenced by a payment or salary record, with the period it covers and its tax total.
    </p>
    <a href="{{ route('wht.psid.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-upload me-1"></i> Prepare PSID
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>PSID No.</th>
                    <th>CPR No.</th>
                    <th>Tax Period</th>
                    <th class="text-center">Entries</th>
                    <th class="text-end">Total Tax</th>
                    <th class="text-end">Schedule</th>
                </tr>
            </thead>
            <tbody>
                @forelse($challans as $c)
                @php
                    $from = $c->period_from ? \Illuminate\Support\Carbon::parse($c->period_from) : null;
                    $to   = $c->period_to ? \Illuminate\Support\Carbon::parse($c->period_to) : null;
                @endphp
                <tr>
                    <td class="fw-semibold">{{ $c->psid_no }}</td>
                    <td>
                        @if($c->cpr_no)
                            <span class="badge bg-success bg-opacity-10 text-success">{{ $c->cpr_no }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($from && $to)
                            {{ $from->format('M Y') }}@if($from->format('Y-m') !== $to->format('Y-m')) &ndash; {{ $to->format('M Y') }}@endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $c->entries }}</td>
                    <td class="text-end fw-semibold">{{ number_format($c->total_tax, 0) }}</td>
                    <td class="text-end">
                        <a href="{{ route('wht.challans.pdf', ['psid' => $c->psid_no]) }}" target="_blank"
                           class="btn btn-sm btn-outline-primary" title="Schedule of entries behind this challan">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Schedule
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-5">
                    No PSIDs recorded yet. Assign one on the
                    <a href="{{ route('wht.psid.index') }}">Prepare PSID</a> page and it will appear here.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
