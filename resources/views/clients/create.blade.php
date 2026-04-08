@extends('layouts.app')

@section('page-title', 'Create Client')

@section('content')
<div class="card">
    <div class="card-body" style="padding: 28px;">
        <form action="{{ route('clients.store') }}" method="POST">
            @csrf

                    <!-- Basic Info -->
                    <div class="mb-3">
                        <label class="form-label">Client Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" required>
                        @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" required>
                            @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact No *</label>
                            <input type="text" class="form-control @error('contact_no') is-invalid @enderror" name="contact_no" required>
                            @error('contact_no') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                            <option value="">Select Status</option>
                            <option value="Individual">Individual</option>
                            <option value="AOP">AOP (Association of Persons)</option>
                            <option value="Company">Company</option>
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
                                    <input class="form-check-input" type="checkbox" name="services[]" value="{{ $service->id }}" id="service{{ $service->id }}">
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
                        <div class="card-header bg-light">
                            <h6 class="mb-0">FBR Credentials (Optional)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">FBR Username</label>
                                    <input type="text" class="form-control" name="fbr_username">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">FBR Password</label>
                                    <input type="password" class="form-control" name="fbr_password">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IT Pin Code</label>
                                <input type="text" class="form-control" name="it_pin_code">
                            </div>
                        </div>
                    </div>

                    <!-- KPRA Credentials -->
                    <div class="card mt-3 mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">KPRA Credentials (Optional)</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">KPRA Username</label>
                                    <input type="text" class="form-control" name="kpra_username">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">KPRA Password</label>
                                    <input type="password" class="form-control" name="kpra_password">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">KPRA Pin</label>
                                <input type="text" class="form-control" name="kpra_pin">
                            </div>
                        </div>
                    </div>

                    <!-- SECP Directors -->
                    <div class="card mt-3 mb-3">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">SECP Directors (Optional)</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addDirector()" style="font-size: 0.75rem;"><i class="bi bi-plus me-1"></i>Add Director</button>
                        </div>
                        <div class="card-body" id="directors-container">
                            <p class="text-muted small" id="no-directors-msg">No directors added yet. Click "Add Director" to add one.</p>
                        </div>
                    </div>

                    <!-- Folder Link -->
                    <div class="mb-3">
                        <label class="form-label">Document Folder Link (Optional)</label>
                        <input type="url" class="form-control" name="folder_link" placeholder="https://drive.google.com/...">
                    </div>

                    <!-- Shareholders -->
                    <div class="mb-3">
                        <label class="form-label">Shareholders (Optional)</label>
                        <div id="shareholders-container">
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
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addShareholder()">+ Add Shareholder</button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-accent">Create Client</button>
                        <a href="{{ route('clients.index') }}" class="btn btn-outline-primary">Cancel</a>
                    </div>
                </form>
    </div>
</div>

<script>
function addShareholder() {
    const container = document.getElementById('shareholders-container');
    const html = `
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
    `;
    container.insertAdjacentHTML('beforeend', html);
}

var directorIndex = 0;
function addDirector() {
    var msg = document.getElementById('no-directors-msg');
    if (msg) msg.remove();
    var container = document.getElementById('directors-container');
    directorIndex++;
    var html = '<div class="director-row mb-3 pb-3" style="border-bottom: 1px solid #f0f2f5;"><div class="row">'
        + '<div class="col-md-3 mb-2"><label class="form-label">Director Name</label><input type="text" class="form-control" name="directors[' + directorIndex + '][director_name]" required></div>'
        + '<div class="col-md-3 mb-2"><label class="form-label">CNIC</label><input type="text" class="form-control" name="directors[' + directorIndex + '][cnic]"></div>'
        + '<div class="col-md-2 mb-2"><label class="form-label">Password</label><input type="text" class="form-control" name="directors[' + directorIndex + '][secp_password]"></div>'
        + '<div class="col-md-2 mb-2"><label class="form-label">PIN</label><input type="text" class="form-control" name="directors[' + directorIndex + '][secp_pin]"></div>'
        + '<div class="col-md-2 mb-2 d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest(\'.director-row\').remove()"><i class="bi bi-trash"></i> Remove</button></div>'
        + '</div></div>';
    container.insertAdjacentHTML('beforeend', html);
}
</script>
@endsection
