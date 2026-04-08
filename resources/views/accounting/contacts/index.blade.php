@extends('layouts.app')
@section('title', 'Vendors / Contacts')
@section('page-title', 'Vendors / Contacts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p style="color: #9ca3af; font-size: 0.85rem; margin: 0;">Manage vendors and supplier contacts.</p>
    </div>
    <a href="{{ route('accounting.contacts.create') }}" class="btn btn-accent"><i class="bi bi-plus-lg me-1"></i> New Vendor</a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="{{ route('accounting.contacts.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Name, email, phone or NTN...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <option value="vendor" {{ request('type') == 'vendor' ? 'selected' : '' }}>Vendor</option>
                        <option value="other" {{ request('type') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                </div>
                @if(request()->hasAny(['search', 'type']))
                <div class="col-md-1">
                    <a href="{{ route('accounting.contacts.index') }}" class="btn btn-outline-primary w-100" title="Clear"><i class="bi bi-x-lg"></i></a>
                </div>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Contacts Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>NTN</th>
                    <th class="text-end">Outstanding Balance</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts ?? [] as $contact)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 32px; height: 32px; background: rgba(48,58,80,0.06); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.65rem; color: var(--primary);">{{ strtoupper(substr($contact->name, 0, 2)) }}</div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.88rem; color: var(--primary);">{{ $contact->name }}</div>
                                <div style="font-size: 0.75rem; color: #9ca3af;">{{ ucfirst($contact->type ?? 'vendor') }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $contact->email ?? '-' }}</td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $contact->phone ?? '-' }}</td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $contact->ntn ?? '-' }}</td>
                    <td class="text-end" style="font-weight: 600; font-size: 0.85rem; {{ ($contact->outstanding_balance ?? 0) > 0 ? 'color: #ef4444;' : 'color: #6b7280;' }}">
                        PKR {{ number_format($contact->outstanding_balance ?? 0, 2) }}
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="{{ route('accounting.contacts.edit', $contact) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('accounting.contacts.destroy', $contact) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this contact?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5" style="color: #9ca3af;">
                        <i class="bi bi-people" style="font-size: 2rem; display: block; margin-bottom: 8px; opacity: 0.3;"></i>
                        No contacts found. <a href="{{ route('accounting.contacts.create') }}" style="color: var(--primary); font-weight: 600;">Add one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($contacts ?? collect(), 'links'))
<div class="mt-3">{{ $contacts->links() }}</div>
@endif
@endsection
