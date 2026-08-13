@extends('layouts.app')
@section('title', 'Edit Withholding Agent')
@section('page-title', $company->name)

@section('content')
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><strong>Details</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('wht.companies.update', $company) }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $company->name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">NTN / CNIC</label>
                            <input type="text" name="ntn_cnic" class="form-control" value="{{ old('ntn_cnic', $company->ntn_cnic) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $company->address) }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" {{ $company->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="{{ route('wht.companies.index') }}" class="btn btn-outline-primary">Back</a>
                    </div>
                </form>

                <hr class="my-4">
                <form method="POST" action="{{ route('wht.companies.destroy', $company) }}"
                      onsubmit="return confirm('Delete {{ $company->name }}? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash me-1"></i> Delete Agent
                    </button>
                    <span class="text-muted ms-2" style="font-size: 0.8rem;">Only possible while no transactions exist.</span>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><strong>User Access</strong></div>
            <div class="card-body">
                <p class="text-muted" style="font-size: 0.82rem;">
                    Administrators always have full access. Grant other users what they need here.
                </p>
                <form method="POST" action="{{ route('wht.companies.access', $company) }}">
                    @csrf @method('PUT')
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th class="text-center">View</th>
                                    <th class="text-center">Create</th>
                                    <th class="text-center">Edit</th>
                                    <th class="text-center">Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $u)
                                    @php $p = $company->users->firstWhere('id', $u->id)?->pivot; @endphp
                                    <tr>
                                        <td>{{ $u->name }}<div class="text-muted" style="font-size: 0.75rem;">{{ $u->email }}</div></td>
                                        @foreach(['view', 'create', 'edit', 'delete'] as $ability)
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input"
                                                   name="grants[{{ $u->id }}][can_{{ $ability }}]" value="1"
                                                   {{ $p?->{"can_{$ability}"} ? 'checked' : '' }}>
                                        </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mt-2">Save Access</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
