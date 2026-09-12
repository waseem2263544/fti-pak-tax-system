@extends('layouts.app')
@section('title', 'WHT Salaries')
@section('page-title', 'Salaries')

@section('content')
@include('wht.partials.agent-switch', ['company' => $company])

<div class="d-flex justify-content-between align-items-center mb-3">
    <p style="color: var(--n-400); font-size: 0.85rem; margin: 0;">Salary payments and tax deducted under section 149.</p>
    <a href="{{ route('wht.salaries.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i> Record Salary</a>
</div>

<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Employee, PSID, CPR…">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Employee</label>
                    <select class="form-select" name="employee_id">
                        <option value="">All</option>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}" {{ request('employee_id') == $e->id ? 'selected' : '' }}>{{ $e->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="month" class="form-control" name="from" value="{{ request('from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="month" class="form-control" name="to" value="{{ request('to') }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i></button>
                    <a href="{{ route('wht.salaries.index') }}" class="btn btn-outline-primary"><i class="bi bi-x-lg"></i></a>
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
                    <th>Month</th>
                    <th>Employee</th>
                    <th class="text-end">Taxable</th>
                    <th class="text-end">Exempt</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Tax</th>
                    <th class="text-end">Net Paid</th>
                    <th>Challan</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salaries as $s)
                <tr>
                    <td>{{ $s->salary_month?->format('M Y') }}</td>
                    <td>
                        {{ $s->employee?->name ?? '—' }}
                        <div class="text-muted" style="font-size: 0.72rem;">{{ $s->employee?->cnic_ntn }}</div>
                    </td>
                    <td class="text-end">{{ number_format($s->taxable_salary, 0) }}</td>
                    <td class="text-end">{{ number_format($s->exempt_amount, 0) }}</td>
                    <td class="text-end">{{ number_format($s->total_salary, 0) }}</td>
                    <td class="text-end fw-semibold">{{ number_format($s->tax_deducted, 0) }}</td>
                    <td class="text-end">{{ number_format($s->final_net_payment, 0) }}</td>
                    <td>
                        @if($s->cpr_no)
                            <span class="badge bg-success bg-opacity-10 text-success">{{ $s->cpr_no }}</span>
                        @elseif($s->psid_no)
                            <span class="badge bg-warning bg-opacity-10 text-warning">PSID only</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('wht.reports.certificate', ['type' => 'salary', 'id' => $s->id]) }}"
                           target="_blank" class="btn btn-sm btn-outline-primary" title="Certificate"><i class="bi bi-file-earmark-pdf"></i></a>
                        <a href="{{ route('wht.salaries.edit', $s) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('wht.salaries.destroy', $s) }}" class="d-inline"
                              onsubmit="return confirm('Delete this salary record?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-5">No salary records match these filters.</td></tr>
                @endforelse
            </tbody>
            @if($salaries->isNotEmpty())
            <tfoot>
                <tr class="fw-semibold">
                    <td colspan="4" class="text-end">Totals (filtered)</td>
                    <td class="text-end">{{ number_format($totals->g, 0) }}</td>
                    <td class="text-end">{{ number_format($totals->t, 0) }}</td>
                    <td class="text-end">{{ number_format($totals->n, 0) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="mt-3">{{ $salaries->links() }}</div>
@endsection
