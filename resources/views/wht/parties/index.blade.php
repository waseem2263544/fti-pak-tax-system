@extends('layouts.app')
@section('title', 'Vendors & Employees')
@section('page-title', 'Vendors & Employees')

@section('content')
@include('wht.partials.agent-switch', ['company' => $company])

<div class="d-flex justify-content-between align-items-center mb-3">
    <p style="color: #9ca3af; font-size: 0.85rem; margin: 0;">Parties this agent withholds tax from.</p>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-upload me-1"></i> Import CSV
        </button>
        <button class="btn btn-accent" onclick="openParty()"><i class="bi bi-plus-lg me-1"></i> Add Party</button>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Name or CNIC/NTN…">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="type">
                        <option value="">All</option>
                        @foreach(['vendor' => 'Vendor', 'employee' => 'Employee', 'both' => 'Both'] as $v => $l)
                            <option value="{{ $v }}" {{ request('type') == $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">ATL Status</label>
                    <select class="form-select" name="atl_status">
                        <option value="">All</option>
                        <option value="filer" {{ request('atl_status') == 'filer' ? 'selected' : '' }}>Filer</option>
                        <option value="non-filer" {{ request('atl_status') == 'non-filer' ? 'selected' : '' }}>Non-filer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="show_inactive" value="1" id="showInactive"
                               {{ request()->boolean('show_inactive') ? 'checked' : '' }}>
                        <label class="form-check-label" for="showInactive">Show inactive</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i></button>
                    <a href="{{ route('wht.parties.index') }}" class="btn btn-outline-primary"><i class="bi bi-x-lg"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>CNIC / NTN</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>ATL</th>
                    <th>Default Section</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($parties as $p)
                <tr @if(!$p->is_active) class="text-muted" @endif>
                    <td>
                        {{ $p->name }}
                        @unless($p->is_active)<span class="badge bg-secondary ms-1" style="font-size: 0.6rem;">Inactive</span>@endunless
                        @if($p->city)<div class="text-muted" style="font-size: 0.72rem;">{{ $p->city }}</div>@endif
                    </td>
                    <td>{{ $p->cnic_ntn ?: '—' }}</td>
                    <td>{{ ucfirst($p->type) }}</td>
                    <td>{{ $p->category === 'aop' ? 'AOP' : ucfirst($p->category) }}</td>
                    <td>
                        @if($p->atl_status === 'filer')
                            <span class="badge bg-success bg-opacity-10 text-success">Filer</span>
                        @else
                            <span class="badge bg-warning bg-opacity-10 text-warning">Non-filer</span>
                        @endif
                    </td>
                    <td>{{ $p->default_section ?: '—' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('wht.reports.annual-certificate', $p->id) }}" target="_blank"
                           class="btn btn-sm btn-outline-primary" title="Annual certificate"><i class="bi bi-file-earmark-pdf"></i></a>
                        <button class="btn btn-sm btn-outline-primary" onclick='openParty(@json($p))'><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="{{ route('wht.parties.destroy', $p) }}" class="d-inline"
                              onsubmit="return confirm('Delete {{ $p->name }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-5">No parties yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $parties->links() }}</div>

{{-- Add / edit --}}
<div class="modal fade" id="partyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" id="partyForm">
            @csrf
            <input type="hidden" name="_method" id="partyMethod" value="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="partyTitle">Add Party</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="f_name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">CNIC / NTN</label>
                        <input type="text" name="cnic_ntn" id="f_cnic" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" id="f_city" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" id="f_type" class="form-select" required>
                            <option value="vendor">Vendor</option>
                            <option value="employee">Employee</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" id="f_category" class="form-select" required>
                            <option value="individual">Individual</option>
                            <option value="company">Company</option>
                            <option value="aop">AOP</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ATL Status <span class="text-danger">*</span></label>
                        <select name="atl_status" id="f_atl" class="form-select" required>
                            <option value="filer">Filer</option>
                            <option value="non-filer">Non-filer</option>
                        </select>
                        <div class="form-text">Drives which rate applies.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="f_active" checked>
                            <label class="form-check-label" for="f_active">Active</label>
                        </div>
                    </div>

                    <div class="col-12"><hr class="my-1"></div>
                    <div class="col-12"><small class="text-muted text-uppercase" style="letter-spacing:.04em;">Defaults for new entries</small></div>

                    <div class="col-md-4">
                        <label class="form-label">Default Section</label>
                        <select name="default_section" id="f_section" class="form-select">
                            <option value="">None</option>
                            @foreach($sections as $s)
                                <option value="{{ $s->section }}">{{ $s->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Calc Mode</label>
                        <select name="default_calc_mode" id="f_mode" class="form-select">
                            <option value="gross">Gross</option>
                            <option value="net">Net</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Default Salary</label>
                        <input type="number" step="0.01" name="default_salary_amount" id="f_salary" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Exempt %</label>
                        <input type="number" step="0.01" name="exempt_rate" id="f_exempt" class="form-control" placeholder="default">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="f_address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- CSV import --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('wht.parties.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Import Parties</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="font-size: 0.85rem;">
                    CSV with a header row, columns in this order:
                </p>
                <code style="font-size: 0.78rem;">name, cnic_ntn, type, category, address, atl_status, default_section</code>
                <p class="text-muted mt-2 mb-3" style="font-size: 0.8rem;">
                    <code>type</code>: vendor / employee / both · <code>category</code>: individual / company / aop ·
                    <code>atl_status</code>: filer / non-filer. Rows whose CNIC/NTN already exists are skipped.
                </p>
                <input type="file" name="file" class="form-control" accept=".csv" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const partyModal = new bootstrap.Modal(document.getElementById('partyModal'));

function openParty(p) {
    const form = document.getElementById('partyForm');
    const set = (id, val) => document.getElementById(id).value = val ?? '';

    if (p) {
        document.getElementById('partyTitle').textContent = 'Edit Party';
        document.getElementById('partyMethod').value = 'PUT';
        form.action = '{{ url('wht/parties') }}/' + p.id;
        set('f_name', p.name);
        set('f_cnic', p.cnic_ntn);
        set('f_city', p.city);
        set('f_type', p.type);
        set('f_category', p.category);
        set('f_atl', p.atl_status);
        set('f_section', p.default_section);
        set('f_mode', p.default_calc_mode);
        set('f_salary', p.default_salary_amount);
        set('f_exempt', p.exempt_rate);
        set('f_address', p.address);
        document.getElementById('f_active').checked = !!p.is_active;
    } else {
        document.getElementById('partyTitle').textContent = 'Add Party';
        document.getElementById('partyMethod').value = 'POST';
        form.action = '{{ route('wht.parties.store') }}';
        form.reset();
        document.getElementById('f_active').checked = true;
    }

    partyModal.show();
}
</script>
@endsection
