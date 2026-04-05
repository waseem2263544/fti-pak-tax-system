@extends('layouts.app')
@section('title', 'Edit Client')
@section('page-title', 'Edit Client')

@section('content')
<div class="card">
    <div class="card-body" style="padding: 28px;">
                <form action="{{ route('clients.update', $client) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Basic Info -->
                    <div class="mb-3">
                        <label class="form-label">Client Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $client->name) }}" required>
                        @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $client->email) }}">
                            @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact No</label>
                            <input type="text" class="form-control @error('contact_no') is-invalid @enderror" name="contact_no" value="{{ old('contact_no', $client->contact_no) }}">
                            @error('contact_no') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                            <option value="Individual" {{ $client->status == 'Individual' ? 'selected' : '' }}>Individual</option>
                            <option value="AOP" {{ $client->status == 'AOP' ? 'selected' : '' }}>AOP (Association of Persons)</option>
                            <option value="Company" {{ $client->status == 'Company' ? 'selected' : '' }}>Company</option>
                        </select>
                        @error('status') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <!-- Active Services -->
                    <div class="mb-3">
                        <label class="form-label">Active Services</label>
                        <div class="row">
                            @foreach($services as $service)
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="services[]" value="{{ $service->id }}" id="service{{ $service->id }}"
                                        {{ $client->activeServices->contains($service->id) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="service{{ $service->id }}">
                                        {{ $service->display_name }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- FBR Credentials -->
                    <div class="card mt-3 mb-3">
                        <div class="card-header"><h6 class="mb-0" style="font-weight: 600;">FBR Credentials</h6></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">FBR Username</label>
                                    <input type="text" class="form-control" name="fbr_username" value="{{ old('fbr_username', $client->fbr_username) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">FBR Password</label>
                                    <input type="text" class="form-control" name="fbr_password" value="{{ old('fbr_password', $client->fbr_password) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">IT Pin Code</label>
                                    <input type="text" class="form-control" name="it_pin_code" value="{{ old('it_pin_code', $client->it_pin_code) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPRA Credentials -->
                    <div class="card mt-3 mb-3">
                        <div class="card-header"><h6 class="mb-0" style="font-weight: 600;">KPRA Credentials</h6></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">KPRA Username</label>
                                    <input type="text" class="form-control" name="kpra_username" value="{{ old('kpra_username', $client->kpra_username) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">KPRA Password</label>
                                    <input type="text" class="form-control" name="kpra_password" value="{{ old('kpra_password', $client->kpra_password) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">KPRA Pin</label>
                                    <input type="text" class="form-control" name="kpra_pin" value="{{ old('kpra_pin', $client->kpra_pin) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECP Credentials -->
                    <div class="card mt-3 mb-3">
                        <div class="card-header"><h6 class="mb-0" style="font-weight: 600;">SECP Credentials</h6></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">SECP Password</label>
                                    <input type="text" class="form-control" name="secp_password" value="{{ old('secp_password', $client->secp_password) }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">SECP Pin</label>
                                    <input type="text" class="form-control" name="secp_pin" value="{{ old('secp_pin', $client->secp_pin) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Folder Link -->
                    <div class="mb-3">
                        <label class="form-label">Document Folder Link</label>
                        <input type="text" class="form-control" name="folder_link" value="{{ old('folder_link', $client->folder_link) }}" placeholder="SharePoint folder path...">
                    </div>

                    <!-- Shareholders -->
                    <div class="mb-3">
                        <label class="form-label">Shareholders</label>
                        <div id="shareholders-container">
                            @forelse($client->shareholders as $sh)
                            <div class="row mb-2">
                                <div class="col-md-8">
                                    <select class="form-select" name="shareholders[]">
                                        <option value="">Select Shareholder</option>
                                        @foreach($potentialShareholders as $s)
                                        <option value="{{ $s->id }}" {{ $sh->id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="share_percentages[]" value="{{ $sh->pivot->share_percentage }}" placeholder="%" step="0.01">
                                </div>
                            </div>
                            @empty
                            <div class="row mb-2">
                                <div class="col-md-8">
                                    <select class="form-select" name="shareholders[]">
                                        <option value="">Select Shareholder</option>
                                        @foreach($potentialShareholders as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="share_percentages[]" placeholder="%" step="0.01">
                                </div>
                            </div>
                            @endforelse
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addShareholder()">+ Add Shareholder</button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3">{{ old('notes', $client->notes) }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-accent">Update Client</button>
                        <a href="{{ route('clients.show', $client) }}" class="btn btn-outline-primary">Cancel</a>
                    </div>
                </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
function addShareholder() {
    var container = document.getElementById('shareholders-container');
    var html = '<div class="row mb-2"><div class="col-md-8"><select class="form-select" name="shareholders[]"><option value="">Select Shareholder</option>@foreach($potentialShareholders as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div><div class="col-md-4"><input type="number" class="form-control" name="share_percentages[]" placeholder="%" step="0.01"></div></div>';
    container.insertAdjacentHTML('beforeend', html);
}
</script>
@endsection
