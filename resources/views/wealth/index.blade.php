@extends('layouts.app')
@section('title', 'Wealth Statement')
@section('page-title', 'Wealth Statement')

@section('content')
<p class="text-muted mb-3" style="font-size: 0.85rem;">
    Working papers for the wealth statement and the income tax return. Figures are recorded as you
    enter them &mdash; tax chargeable is taken from IRIS, never calculated here.
</p>

<form method="GET" action="{{ route('wealth.index') }}" class="d-flex gap-2 mb-3">
    <input type="search" name="q" value="{{ $search }}" class="form-control form-control-sm"
           placeholder="Search clients…" style="max-width: 380px;">
    <button class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
    @if($search !== '')
        <a href="{{ route('wealth.index') }}" class="btn btn-outline-primary btn-sm" title="Clear"><i class="bi bi-x-lg"></i></a>
    @endif
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Type</th>
                    <th class="num">Lines on file</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($clients as $client)
                <tr>
                    <td style="font-weight: 600;">{{ $client->name }}</td>
                    <td><span class="badge bg-secondary">{{ $client->status ?: '—' }}</span></td>
                    <td class="num">{{ $client->wealth_lines_count ?: '—' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('wealth.show', $client) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil-square me-1"></i> Working
                        </a>
                        @if($client->wealth_lines_count)
                            <a href="{{ route('wealth.comparative', $client) }}" class="btn btn-sm btn-outline-primary" title="Comparative statement">
                                <i class="bi bi-table"></i>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center py-5 text-muted">
                    @if($search !== '') Nothing matches “{{ $search }}”. @else No clients yet. @endif
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $clients->links() }}</div>
@endsection
