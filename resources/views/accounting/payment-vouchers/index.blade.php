@extends('layouts.app')
@section('title', 'Payment Vouchers')
@section('page-title', 'Payment Vouchers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p style="color: #9ca3af; font-size: 0.85rem; margin: 0;">Track payments made to vendors.</p>
    </div>
    <a href="{{ route('accounting.payment-vouchers.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i> New Payment</a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="{{ route('accounting.payment-vouchers.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Vendor</label>
                    <select class="form-select searchable" name="contact_id">
                        <option value="">All Vendors</option>
                        @foreach($contacts ?? [] as $contact)
                            <option value="{{ $contact->id }}" {{ request('contact_id') == $contact->id ? 'selected' : '' }}>{{ $contact->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="posted" {{ request('status') == 'posted' ? 'selected' : '' }}>Posted</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" name="from" value="{{ request('from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" name="to" value="{{ request('to') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                </div>
                @if(request()->hasAny(['contact_id', 'status', 'from', 'to']))
                <div class="col-md-1">
                    <a href="{{ route('accounting.payment-vouchers.index') }}" class="btn btn-outline-primary w-100" title="Clear"><i class="bi bi-x-lg"></i></a>
                </div>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Vouchers Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Payment #</th>
                    <th>Date</th>
                    <th>Paid To</th>
                    <th class="text-end">Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vouchers ?? [] as $voucher)
                <tr>
                    <td>
                        <a href="{{ route('accounting.payment-vouchers.show', $voucher) }}" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 0.88rem;">
                            {{ $voucher->voucher_number }}
                        </a>
                    </td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $voucher->payment_date->format('M d, Y') }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 28px; height: 28px; background: rgba(48,58,80,0.06); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.6rem; color: var(--primary);">{{ strtoupper(substr($voucher->contact->name ?? '', 0, 2)) }}</div>
                            <span style="font-size: 0.85rem;">{{ Str::limit($voucher->contact->name ?? $voucher->party_name ?? '-', 25) }}</span>
                        </div>
                    </td>
                    <td class="text-end" style="font-weight: 600; font-size: 0.85rem; color: #ef4444;">PKR {{ number_format($voucher->amount, 2) }}</td>
                    <td>
                        @if($voucher->payment_method === 'cash')
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Cash</span>
                        @elseif($voucher->payment_method === 'bank_transfer')
                            <span class="badge" style="background: #dbeafe; color: #1e40af;">Bank Transfer</span>
                        @elseif($voucher->payment_method === 'cheque')
                            <span class="badge" style="background: var(--accent-glow); color: #5c6300;">Cheque</span>
                        @elseif($voucher->payment_method === 'online')
                            <span class="badge" style="background: #ede9fe; color: #5b21b6;">Online</span>
                        @endif
                    </td>
                    <td>
                        @if($voucher->status === 'posted')
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Posted</span>
                        @elseif($voucher->status === 'draft')
                            <span class="badge" style="background: #f3f4f6; color: #6b7280;">Draft</span>
                        @elseif($voucher->status === 'cancelled')
                            <span class="badge" style="background: #fef2f2; color: #991b1b;">Cancelled</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('accounting.payment-vouchers.show', $voucher) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5" style="color: #9ca3af;">
                        <i class="bi bi-wallet2" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                        No payment vouchers found. <a href="{{ route('accounting.payment-vouchers.create') }}" style="color: var(--primary); font-weight: 600;">Create one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($vouchers ?? collect(), 'links'))
<div class="mt-3">{{ $vouchers->links() }}</div>
@endif
@endsection
