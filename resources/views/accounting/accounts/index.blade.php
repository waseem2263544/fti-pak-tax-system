@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('page-title', 'Chart of Accounts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p style="color: #9ca3af; font-size: 0.85rem; margin: 0;">Manage your chart of accounts organized by type.</p>
    </div>
    <a href="{{ route('accounting.accounts.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i> Add Account</a>
</div>

@php
    $types = [
        'asset' => ['label' => 'Assets', 'icon' => 'bi-safe', 'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.08)'],
        'liability' => ['label' => 'Liabilities', 'icon' => 'bi-credit-card', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.07)'],
        'equity' => ['label' => 'Equity', 'icon' => 'bi-pie-chart', 'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.08)'],
        'revenue' => ['label' => 'Revenue', 'icon' => 'bi-graph-up', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.08)'],
        'expense' => ['label' => 'Expenses', 'icon' => 'bi-graph-down', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.08)'],
    ];
@endphp

<div class="accordion" id="accountsAccordion">
    @foreach($types as $typeKey => $typeMeta)
    @php
        $typeAccounts = ($accounts[$typeKey] ?? collect());
        $parentAccounts = $typeAccounts->whereNull('parent_id');
        $totalBalance = $typeAccounts->sum('balance');
    @endphp
    <div class="card mb-3" style="overflow: hidden;">
        <div class="card-header p-0" id="heading-{{ $typeKey }}">
            <button class="btn w-100 d-flex justify-content-between align-items-center collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $typeKey }}" aria-expanded="true"
                    style="padding: 18px 24px; border: none; background: linear-gradient(135deg, {{ $typeMeta['bg'] }} 0%, #fff 100%); border-radius: 0;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 42px; height: 42px; border-radius: 12px; background: {{ $typeMeta['bg'] }}; display: flex; align-items: center; justify-content: center;">
                        <i class="bi {{ $typeMeta['icon'] }}" style="color: {{ $typeMeta['color'] }}; font-size: 1.1rem;"></i>
                    </div>
                    <div class="text-start">
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--primary);">{{ $typeMeta['label'] }}</div>
                        <div style="font-size: 0.75rem; color: #9ca3af;">{{ $typeAccounts->count() }} account{{ $typeAccounts->count() !== 1 ? 's' : '' }}</div>
                    </div>
                </div>
                <div class="text-end">
                    <div style="font-weight: 700; font-size: 1.05rem; color: var(--primary);">PKR {{ number_format(abs($totalBalance), 2) }}</div>
                    <i class="bi bi-chevron-down" style="font-size: 0.75rem; color: #9ca3af; transition: transform 0.3s;"></i>
                </div>
            </button>
        </div>
        <div id="collapse-{{ $typeKey }}" class="collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#accountsAccordion">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Account Name</th>
                            <th>Sub-Type</th>
                            <th class="text-end">Balance</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($parentAccounts as $parent)
                        <tr>
                            <td style="font-family: monospace; font-size: 0.82rem; font-weight: 600; color: var(--primary);">{{ $parent->code }}</td>
                            <td>
                                <span style="font-weight: 700; color: var(--primary); font-size: 0.88rem;">{{ $parent->name }}</span>
                            </td>
                            <td>
                                @if($parent->sub_type)
                                    <span class="badge" style="background: {{ $typeMeta['bg'] }}; color: {{ $typeMeta['color'] }};">{{ $parent->sub_type }}</span>
                                @else
                                    <span style="color: #d1d5db;">-</span>
                                @endif
                            </td>
                            <td class="text-end" style="font-weight: 700; font-size: 0.88rem;">PKR {{ number_format($parent->balance, 2) }}</td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('accounting.accounts.show', $parent) }}" class="btn btn-sm btn-outline-primary" title="Ledger"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('accounting.accounts.edit', $parent) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                </div>
                            </td>
                        </tr>
                        @php $children = $typeAccounts->where('parent_id', $parent->id); @endphp
                        @foreach($children as $child)
                        <tr>
                            <td style="font-family: monospace; font-size: 0.82rem; color: #6b7280; padding-left: 48px;">{{ $child->code }}</td>
                            <td style="padding-left: 48px;">
                                <span style="color: #374151; font-size: 0.85rem;">{{ $child->name }}</span>
                            </td>
                            <td>
                                @if($child->sub_type)
                                    <span class="badge" style="background: #f3f4f6; color: #6b7280;">{{ $child->sub_type }}</span>
                                @else
                                    <span style="color: #d1d5db;">-</span>
                                @endif
                            </td>
                            <td class="text-end" style="font-size: 0.85rem; color: #374151;">PKR {{ number_format($child->balance, 2) }}</td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('accounting.accounts.show', $child) }}" class="btn btn-sm btn-outline-primary" title="Ledger"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('accounting.accounts.edit', $child) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4" style="color: #9ca3af; font-size: 0.85rem;">
                                No {{ strtolower($typeMeta['label']) }} accounts yet.
                                <a href="{{ route('accounting.accounts.create') }}?type={{ $typeKey }}" style="color: var(--primary); font-weight: 600;">Add one</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
