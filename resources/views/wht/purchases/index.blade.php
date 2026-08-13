@extends('layouts.app')
@section('title', 'WHT Payments')
@section('page-title', 'Payments')

@section('content')
@include('wht.partials.agent-bar')

<div class="d-flex justify-content-between align-items-center mb-3">
    <p style="color: #9ca3af; font-size: 0.85rem; margin: 0;">Payments to vendors, contractors and service providers.</p>
    <a href="{{ route('wht.purchases.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i> Record Payment</a>
</div>

<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Vendor, PSID, CPR…">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Vendor</label>
                    <select class="form-select" name="party_id">
                        <option value="">All</option>
                        @foreach($parties as $p)
                            <option value="{{ $p->id }}" {{ request('party_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Section</label>
                    <select class="form-select" name="section">
                        <option value="">All</option>
                        @foreach($sections->unique('section') as $s)
                            <option value="{{ $s->section }}" {{ request('section') == $s->section ? 'selected' : '' }}>{{ $s->section }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">From</label>
                    <input type="month" class="form-control" name="from" value="{{ request('from') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">To</label>
                    <input type="month" class="form-control" name="to" value="{{ request('to') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Deposited</label>
                    <select class="form-select" name="deposited">
                        <option value="">Any</option>
                        <option value="yes" {{ request('deposited') == 'yes' ? 'selected' : '' }}>Yes</option>
                        <option value="no" {{ request('deposited') == 'no' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i></button>
                    <a href="{{ route('wht.purchases.index') }}" class="btn btn-outline-primary"><i class="bi bi-x-lg"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Vendor</th>
                    <th>Section</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Tax</th>
                    <th class="text-end">Net</th>
                    <th>Paid</th>
                    <th>Challan</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $p)
                <tr>
                    <td>{{ $p->period_month?->format('M Y') }}</td>
                    <td>
                        {{ $p->party?->name ?? '—' }}
                        <div class="text-muted" style="font-size: 0.72rem;">
                            {{ $p->party?->cnic_ntn }}
                            @if($p->party?->atl_status === 'non-filer')
                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.6rem;">Non-filer</span>
                            @endif
                        </div>
                    </td>
                    <td>{{ $p->section ?: '—' }}</td>
                    <td class="text-end">{{ number_format($p->gross_amount, 0) }}</td>
                    <td class="text-end">
                        {{ rtrim(rtrim(number_format($p->tax_rate, 2), '0'), '.') }}%
                        @if($p->rate_source === 'manual')
                            <i class="bi bi-pencil-fill text-muted" style="font-size: 0.6rem;" title="Manually overridden"></i>
                        @endif
                    </td>
                    <td class="text-end fw-semibold">{{ number_format($p->tax_withheld, 0) }}</td>
                    <td class="text-end">{{ number_format($p->net_payment, 0) }}</td>
                    <td>{{ $p->payment_date?->format('d M Y') }}</td>
                    <td>
                        @if($p->cpr_no)
                            <span class="badge bg-success bg-opacity-10 text-success">{{ $p->cpr_no }}</span>
                        @elseif($p->psid_no)
                            <span class="badge bg-warning bg-opacity-10 text-warning">PSID only</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('wht.reports.certificate', ['type' => 'purchase', 'id' => $p->id]) }}"
                           target="_blank" class="btn btn-sm btn-outline-primary" title="Certificate"><i class="bi bi-file-earmark-pdf"></i></a>
                        <a href="{{ route('wht.purchases.edit', $p) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('wht.purchases.destroy', $p) }}" class="d-inline"
                              onsubmit="return confirm('Delete this payment?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-muted py-5">No payments match these filters.</td></tr>
                @endforelse
            </tbody>
            @if($purchases->isNotEmpty())
            <tfoot>
                <tr class="fw-semibold">
                    <td colspan="3" class="text-end">Totals (filtered)</td>
                    <td class="text-end">{{ number_format($totals->g, 0) }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($totals->t, 0) }}</td>
                    <td class="text-end">{{ number_format($totals->n, 0) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="mt-3">{{ $purchases->links() }}</div>
@endsection
