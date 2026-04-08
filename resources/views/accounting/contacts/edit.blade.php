@extends('layouts.app')
@section('title', 'Edit Contact')
@section('page-title', 'Vendors / Contacts')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('accounting.contacts.index') }}" style="color: #9ca3af; text-decoration: none; font-size: 0.85rem;">
        <i class="bi bi-chevron-left"></i> Back to Contacts
    </a>
</div>

<form action="{{ route('accounting.contacts.update', $contact) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card mb-4" style="max-width: 800px;">
        <div class="card-header d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            Edit Contact: {{ $contact->name }}
        </div>
        <div class="card-body" style="padding: 24px;">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $contact->name) }}" placeholder="Vendor / contact name" required>
                    @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                        <option value="vendor" {{ old('type', $contact->type) === 'vendor' ? 'selected' : '' }}>Vendor</option>
                        <option value="other" {{ old('type', $contact->type) === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('type') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $contact->email) }}" placeholder="vendor@example.com">
                    @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $contact->phone) }}" placeholder="+92 300 1234567">
                    @error('phone') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-12">
                    <label class="form-label">Address</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="2" placeholder="Full address...">{{ old('address', $contact->address) }}</textarea>
                    @error('address') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">NTN</label>
                    <input type="text" class="form-control @error('ntn') is-invalid @enderror" name="ntn" value="{{ old('ntn', $contact->ntn) }}" placeholder="National Tax Number">
                    @error('ntn') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">STRN</label>
                    <input type="text" class="form-control @error('strn') is-invalid @enderror" name="strn" value="{{ old('strn', $contact->strn) }}" placeholder="Sales Tax Registration">
                    @error('strn') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Opening Balance</label>
                    <input type="number" class="form-control @error('opening_balance') is-invalid @enderror" name="opening_balance" value="{{ old('opening_balance', $contact->opening_balance ?? 0) }}" step="0.01" min="0" placeholder="0.00">
                    @error('opening_balance') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger mb-4" style="max-width: 800px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i> Update Contact</button>
        <a href="{{ route('accounting.contacts.index') }}" class="btn btn-outline-primary">Cancel</a>
    </div>
</form>
@endsection
