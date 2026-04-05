@extends('layouts.app')
@section('title', 'Clients')
@section('page-title', 'Clients')

@section('content')
<!-- Search & Filters -->
<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="{{ route('clients.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <div style="position: relative;">
                        <i class="bi bi-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone, or FBR username..." value="{{ request('search') }}" style="padding-left: 40px;">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Types</option>
                        <option value="Individual" {{ request('status') == 'Individual' ? 'selected' : '' }}>Individual</option>
                        <option value="AOP" {{ request('status') == 'AOP' ? 'selected' : '' }}>AOP</option>
                        <option value="Company" {{ request('status') == 'Company' ? 'selected' : '' }}>Company</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="service" class="form-select">
                        <option value="">All Services</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" {{ request('service') == $service->id ? 'selected' : '' }}>{{ $service->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i> Search</button>
                    @if(request()->hasAny(['search', 'status', 'service']))
                        <a href="{{ route('clients.index') }}" class="btn btn-outline-primary" title="Clear"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div style="font-size: 0.85rem; color: #6b7280;">
        Showing <strong>{{ $clients->total() }}</strong> client{{ $clients->total() !== 1 ? 's' : '' }}
        @if(request('search')) for "<strong>{{ request('search') }}</strong>" @endif
        @if(request('status')) &middot; Type: <strong>{{ request('status') }}</strong> @endif
    </div>
    <a href="{{ route('clients.create') }}" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg me-1"></i> Add Client</a>
</div>

<!-- Clients Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Type</th>
                    <th>FBR</th>
                    <th>Services</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 36px; height: 36px; background: rgba(48,58,80,0.06); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.7rem; color: var(--primary); flex-shrink: 0;">{{ strtoupper(substr($client->name, 0, 2)) }}</div>
                            <div>
                                <a href="{{ route('clients.show', $client) }}" style="color: var(--primary); font-weight: 600; text-decoration: none; font-size: 0.88rem;">{{ $client->name }}</a>
                                @if($client->email)<div style="font-size: 0.75rem; color: #9ca3af;">{{ $client->email }}</div>@endif
                            </div>
                        </div>
                    </td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $client->contact_no ?: '-' }}</td>
                    <td>
                        @if($client->status == 'Individual')
                            <span class="badge" style="background: rgba(48,58,80,0.06); color: var(--primary);">Individual</span>
                        @elseif($client->status == 'AOP')
                            <span class="badge" style="background: var(--accent-glow); color: #5c6300;">AOP</span>
                        @else
                            <span class="badge" style="background: #dbeafe; color: #1e40af;">Company</span>
                        @endif
                    </td>
                    <td>
                        @if($client->fbr_username)
                            <span style="font-family: monospace; font-size: 0.78rem; color: #6b7280;">{{ $client->fbr_username }}</span>
                        @else
                            <span style="color: #d1d5db;">-</span>
                        @endif
                    </td>
                    <td>
                        @php $svcCount = $client->activeServices()->count(); @endphp
                        @if($svcCount > 0)
                            <span class="badge" style="background: #d1fae5; color: #065f46;">{{ $svcCount }} active</span>
                        @else
                            <span style="color: #d1d5db; font-size: 0.82rem;">None</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('clients.destroy', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this client?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5" style="color: #9ca3af;">
                        @if(request()->hasAny(['search', 'status', 'service']))
                            <i class="bi bi-search" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                            No clients match your search. <a href="{{ route('clients.index') }}" style="color: var(--primary); font-weight: 600;">Clear filters</a>
                        @else
                            <i class="bi bi-people" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                            No clients yet. <a href="{{ route('clients.create') }}" style="color: var(--primary); font-weight: 600;">Add your first client</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $clients->links() }}</div>
@endsection
