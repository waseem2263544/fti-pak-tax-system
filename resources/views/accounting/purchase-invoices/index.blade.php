@extends('layouts.app')
@section('title', 'Purchase Invoices')
@section('page-title', 'Purchase Invoices')

@section('content')
<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(245,158,11,0.08);">
                    <i class="bi bi-exclamation-circle" style="color: #f59e0b;"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size: 1.4rem;">PKR {{ number_format($totalOutstanding ?? 0, 2) }}</div>
                    <div class="stat-label">Outstanding</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(239,68,68,0.07);">
                    <i class="bi bi-clock-history" style="color: #ef4444;"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size: 1.4rem;">PKR {{ number_format($overdue ?? 0, 2) }}</div>
                    <div class="stat-label">Overdue</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(16,185,129,0.08);">
                    <i class="bi bi-check-circle" style="color: #10b981;"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size: 1.4rem;">PKR {{ number_format($paidThisMonth ?? 0, 2) }}</div>
                    <div class="stat-label">Paid This Month</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p style="color: #9ca3af; font-size: 0.85rem; margin: 0;">Manage vendor bills and track payables.</p>
    </div>
    <a href="{{ route('accounting.purchase-invoices.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i> New Bill</a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="{{ route('accounting.purchase-invoices.index') }}">
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
                        <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                        <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partially Paid</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
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
                    <a href="{{ route('accounting.purchase-invoices.index') }}" class="btn btn-outline-primary w-100" title="Clear"><i class="bi bi-x-lg"></i></a>
                </div>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Bills Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Bill #</th>
                    <th>Vendor</th>
                    <th>Date</th>
                    <th>Due Date</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Balance</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices ?? [] as $invoice)
                <tr>
                    <td>
                        <a href="{{ route('accounting.purchase-invoices.show', $invoice) }}" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 0.88rem;">
                            {{ $invoice->bill_number }}
                        </a>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 28px; height: 28px; background: rgba(48,58,80,0.06); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.6rem; color: var(--primary);">{{ strtoupper(substr($invoice->contact->name ?? '', 0, 2)) }}</div>
                            <span style="font-size: 0.85rem;">{{ Str::limit($invoice->contact->name ?? '-', 25) }}</span>
                        </div>
                    </td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $invoice->bill_date->format('M d, Y') }}</td>
                    <td style="font-size: 0.85rem; {{ $invoice->due_date < now() && $invoice->status !== 'paid' ? 'color: #ef4444; font-weight: 600;' : 'color: #6b7280;' }}">
                        {{ $invoice->due_date->format('M d, Y') }}
                    </td>
                    <td class="text-end" style="font-weight: 600; font-size: 0.85rem;">PKR {{ number_format($invoice->total, 2) }}</td>
                    <td class="text-end" style="font-size: 0.85rem; color: #10b981;">PKR {{ number_format($invoice->paid_amount, 2) }}</td>
                    <td class="text-end" style="font-weight: 600; font-size: 0.85rem;">PKR {{ number_format($invoice->total - $invoice->paid_amount, 2) }}</td>
                    <td>
                        @if($invoice->status === 'paid')
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Paid</span>
                        @elseif($invoice->status === 'partial')
                            <span class="badge" style="background: #dbeafe; color: #1e40af;">Partial</span>
                        @elseif($invoice->status === 'received')
                            <span class="badge" style="background: var(--accent-glow); color: #5c6300;">Received</span>
                        @elseif($invoice->status === 'draft')
                            <span class="badge" style="background: #f3f4f6; color: #6b7280;">Draft</span>
                        @elseif($invoice->status === 'overdue')
                            <span class="badge" style="background: #fef2f2; color: #dc2626;">Overdue</span>
                        @elseif($invoice->status === 'cancelled')
                            <span class="badge" style="background: #fef2f2; color: #991b1b;">Cancelled</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('accounting.purchase-invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                            @if($invoice->status === 'draft')
                            <a href="{{ route('accounting.purchase-invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5" style="color: #9ca3af;">
                        <i class="bi bi-receipt" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                        No bills found. <a href="{{ route('accounting.purchase-invoices.create') }}" style="color: var(--primary); font-weight: 600;">Create one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($invoices ?? collect(), 'links'))
<div class="mt-3">{{ $invoices->links() }}</div>
@endif
@endsection
