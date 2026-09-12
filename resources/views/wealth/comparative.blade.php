@extends('layouts.app')
@section('title', $client->name . ' — Comparative Wealth Statement')
@section('page-title', $client->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 style="font-size: 1rem; font-weight: 700; margin: 0;">Comparative wealth statement</h2>
        <p class="text-muted mb-0" style="font-size: 0.82rem;">As at 30 June of each year. Blank means the line was not declared that year.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
        <a href="{{ route('wealth.show', $client) }}" class="btn btn-sm btn-outline-primary">Back to working</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th style="min-width: 300px;">Description</th>
                    @foreach($years as $y)
                        <th class="num">{{ $y }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @foreach(['asset', 'liability'] as $kind)
                @foreach(\App\Models\WealthLine::SECTIONS[$kind] as $key => $label)
                    @php $group = $lines->where('kind', $kind)->where('section', $key); @endphp
                    @continue($group->isEmpty())
                    <tr>
                        <td colspan="{{ $years->count() + 1 }}"
                            style="background: var(--surface-sunk); font-weight: 600; font-size: 0.78rem; color: var(--text-soft);">
                            {{ $label }}
                        </td>
                    </tr>
                    @foreach($group as $line)
                        <tr>
                            <td style="font-size: 0.82rem;">{{ $line->description }}</td>
                            @foreach($years as $y)
                                @php $a = $line->amountFor((int) $y); @endphp
                                <td class="num" style="font-size: 0.82rem; {{ $a === null ? 'color: var(--text-faint);' : '' }}">
                                    {{ $a === null ? '—' : number_format((float) $a, 0) }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr>
                        <td class="text-muted" style="font-size: 0.78rem; padding-left: 24px;">Subtotal</td>
                        @foreach($years as $y)
                            <td class="num text-muted" style="font-size: 0.78rem;">
                                {{ number_format($group->sum(fn($l) => (float) ($l->amountFor((int) $y) ?? 0)), 0) }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
            </tbody>
            <tfoot>
                <tr style="border-top: 2px solid var(--border-strong);">
                    <td style="font-weight: 600;">Total assets</td>
                    @foreach($years as $y)<td class="num" style="font-weight: 600;">{{ number_format($totals[$y]['assets'], 0) }}</td>@endforeach
                </tr>
                <tr>
                    <td style="font-weight: 600;">Less: liabilities</td>
                    @foreach($years as $y)<td class="num" style="font-weight: 600;">{{ number_format($totals[$y]['liabilities'], 0) }}</td>@endforeach
                </tr>
                <tr>
                    <td style="font-weight: 700;">Net wealth</td>
                    @foreach($years as $y)<td class="num" style="font-weight: 700;">{{ number_format($totals[$y]['net'], 0) }}</td>@endforeach
                </tr>
                <tr>
                    <td class="text-muted" style="font-size: 0.8rem;">Movement on prior year</td>
                    @foreach($years as $i => $y)
                        <td class="num text-muted" style="font-size: 0.8rem;">
                            @if($i === 0)
                                —
                            @else
                                {{ number_format($totals[$y]['net'] - $totals[$years[$i - 1]]['net'], 0) }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

@section('styles')
<style>
@media print {
    .sidebar, .top-nav, .btn { display: none !important; }
    .main-wrapper { margin-left: 0 !important; }
    .main-content { padding: 0 !important; }
    .card { border: none !important; }
}
</style>
@endsection
