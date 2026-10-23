@extends('layouts.app')
@section('title', 'Firm Registration')
@section('page-title', 'Firm Registration')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 gap-2 flex-wrap">
    <form method="GET" class="d-flex gap-2" style="max-width: 360px; flex: 1;">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search firm name">
        <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
    <a href="{{ route('firm-registrations.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Firm
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Firm</th>
                    <th>Partners</th>
                    <th>Deed date</th>
                    <th>Client</th>
                    <th class="text-end">Documents</th>
                </tr>
            </thead>
            <tbody>
            @forelse($firms as $firm)
                <tr>
                    <td><a href="{{ route('firm-registrations.show', $firm) }}" style="font-weight: 600;">{{ $firm->firm_name }}</a></td>
                    <td style="font-size: 0.85rem;">{{ collect($firm->partners)->pluck('name')->join(', ') }}</td>
                    <td>{{ $firm->deed_date?->format('d M Y') }}</td>
                    <td>{{ $firm->client?->name ?? '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('firm-registrations.download', [$firm, 'all']) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark-zip me-1"></i> Download all
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-5" style="color: var(--n-400);">
                    No firms yet. <a href="{{ route('firm-registrations.create') }}">Add the first one</a>.
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $firms->links() }}</div>
@endsection
