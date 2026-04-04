@extends('layouts.app')
@section('title', 'Employees')
@section('page-title', 'Employees')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h5>Team Members</h5>
    <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus"></i> Add Employee
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $employee)
                <tr>
                    <td><a href="{{ route('employees.show', $employee) }}">{{ $employee->name }}</a></td>
                    <td>{{ $employee->email }}</td>
                    <td>
                        @foreach($employee->roles as $role)
                            <span class="badge bg-primary">{{ $role->display_name ?? $role->name }}</span>
                        @endforeach
                    </td>
                    <td>
                        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this employee?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No employees found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $employees->links() }}</div>
@endsection
