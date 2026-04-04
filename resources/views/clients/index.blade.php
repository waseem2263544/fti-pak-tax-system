@extends('layouts.app')

@section('page-title', 'Clients')

@section('content')
<div class="row mb-3">
    <div class="col-md-6">
        <h4>Client Management</h4>
    </div>
    <div class="col-md-6 text-end">
        <a href="{{ route('clients.create') }}" class="btn btn-primary">+ Add Client</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Services</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                    <tr>
                        <td><strong>{{ $client->name }}</strong></td>
                        <td>{{ $client->email }}</td>
                        <td>{{ $client->contact_no }}</td>
                        <td><span class="badge bg-info">{{ $client->status }}</span></td>
                        <td>
                            <span class="badge bg-success">{{ $client->activeServices()->count() }}</span>
                        </td>
                        <td>
                            <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form action="{{ route('clients.destroy', $client) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No clients found. <a href="{{ route('clients.create') }}">Add one now</a></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $clients->links() }}
    </div>
</div>
@endsection
