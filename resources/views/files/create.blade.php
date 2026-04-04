@extends('layouts.app')
@section('title', 'Upload File')
@section('page-title', 'Upload File')

@section('content')
<div class="card">
    <div class="card-body" style="padding: 28px;">
        <form method="POST" action="{{ route('files.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Client</label>
                    <select name="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
                        <option value="">Select Client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                        @endforeach
                    </select>
                    @error('client_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-control" value="{{ old('category') }}" placeholder="e.g. Tax Returns, Notices, Agreements">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">File</label>
                <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" required>
                <small style="color: #9ca3af;">Max 20MB. PDF, Word, Excel, Images supported.</small>
                @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes about this file...">{{ old('notes') }}</textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-accent"><i class="bi bi-upload me-1"></i> Upload</button>
                <a href="{{ route('files.index') }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
