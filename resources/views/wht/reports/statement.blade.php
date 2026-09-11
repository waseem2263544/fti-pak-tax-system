@extends('layouts.app')
@section('title', 'Monthly Withholding Statement')
@section('page-title', 'Monthly Withholding Statement')

@section('content')
@include('wht.partials.agent-bar')

<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" class="d-flex align-items-end gap-2 flex-wrap">
            <div>
                <label class="form-label mb-1">Tax Period</label>
                <input type="month" name="month" class="form-control" value="{{ $month }}">
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Show</button>
            <a href="{{ route('wht.reports.statement-filing', ['month' => $month]) }}" class="btn btn-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> FBR Statement (.xlsm)
            </a>
            <a href="{{ route('wht.reports.statement-excel', ['month' => $month]) }}" class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-excel me-1"></i> Working Copy
            </a>
            <div class="ms-auto text-muted" style="font-size: 0.82rem; max-width: 460px;">
                Grouped by section for the statement under s.165. Entries are matched on their
                <strong>tax period</strong>, so a payment deposited later still lands in the month it belongs to.
            </div>
        </form>
    </div>
</div>

@if($grouped->isEmpty())
    <div class="card"><div class="card-body text-center py-5 text-muted">
        Nothing recorded for {{ $monthStart->format('F Y') }}.
    </div></div>
@else
<div class="card mb-4">
    <div class="card-header"><strong>Summary — {{ $monthStart->format('F Y') }}</strong></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Nature of Payment</th>
                    <th class="text-center">Payees</th>
                    <th class="text-end">Gross Amount</th>
                    <th class="text-end">Tax Withheld</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grouped as $g)
                <tr>
                    <td class="fw-semibold">{{ $g['section'] }}</td>
                    <td>{{ $g['nature'] }}</td>
                    <td class="text-center">{{ $g['payees'] }}</td>
                    <td class="text-end">{{ number_format($g['gross'], 0) }}</td>
                    <td class="text-end fw-semibold">{{ number_format($g['tax'], 0) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="3" class="text-end">Total</td>
                    <td class="text-end">{{ number_format($grouped->sum('gross'), 0) }}</td>
                    <td class="text-end">{{ number_format($grouped->sum('tax'), 0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@foreach($grouped as $g)
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>{{ $g['section'] }} — {{ $g['nature'] }}</strong>
        <span class="text-muted" style="font-size: 0.82rem;">{{ $g['items']->count() }} entries</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Payee</th>
                    <th>CNIC / NTN</th>
                    <th>Status</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Tax</th>
                    <th>Payment Date</th>
                    <th>CPR</th>
                </tr>
            </thead>
            <tbody>
                @foreach($g['items'] as $i)
                @php $party = $g['kind'] === 'salary' ? $i->employee : $i->party; @endphp
                <tr>
                    <td>{{ $party?->name ?? '—' }}</td>
                    <td>{{ $party?->cnic_ntn ?: '—' }}</td>
                    <td>{{ $party?->atl_status === 'non-filer' ? 'Non-filer' : 'Filer' }}</td>
                    <td class="text-end">
                        {{ number_format($g['kind'] === 'salary' ? $i->total_salary : $i->gross_amount, 0) }}
                    </td>
                    <td class="text-end">
                        @if($g['kind'] === 'salary')
                            —
                        @else
                            {{ rtrim(rtrim(number_format($i->tax_rate, 2), '0'), '.') }}%
                        @endif
                    </td>
                    <td class="text-end fw-semibold">
                        {{ number_format($g['kind'] === 'salary' ? $i->tax_deducted : $i->tax_withheld, 0) }}
                    </td>
                    <td>{{ $i->payment_date?->format('d M Y') }}</td>
                    <td>{{ $i->cpr_no ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach
@endif
@endsection
