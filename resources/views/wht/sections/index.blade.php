@extends('layouts.app')
@section('title', 'FBR Sections')
@section('page-title', 'FBR Payment Sections')

@section('content')
<div class="alert alert-info d-flex align-items-start" style="font-size: 0.85rem;">
    <i class="bi bi-info-circle me-2 mt-1"></i>
    <div>Global list of withholding heads and their PSID payment codes, shared by every withholding agent.</div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" class="d-flex gap-2 align-items-end">
        <div>
            <label class="form-label mb-1">Search</label>
            <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Section, nature or code…">
        </div>
        <div>
            <label class="form-label mb-1">Applies To</label>
            <select class="form-select" name="applies_to">
                <option value="">All</option>
                @foreach(['purchase' => 'Payments', 'salary' => 'Salaries', 'both' => 'Both'] as $v => $l)
                    <option value="{{ $v }}" {{ request('applies_to') == $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i></button>
        <a href="{{ route('wht.sections.index') }}" class="btn btn-outline-primary"><i class="bi bi-x-lg"></i></a>
    </form>
    <button class="btn btn-accent" onclick="openSection()"><i class="bi bi-plus-lg me-1"></i> Add Section</button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Section</th>
                    <th>Payment Nature</th>
                    <th>Payment Section</th>
                    <th>Applies To</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sections as $s)
                <tr @if(!$s->is_active) class="text-muted" @endif>
                    <td><code>{{ $s->code }}</code></td>
                    <td class="fw-semibold">{{ $s->section }}</td>
                    <td>{{ $s->payment_nature }}</td>
                    <td>{{ $s->payment_section }}</td>
                    <td>{{ ['purchase' => 'Payments', 'salary' => 'Salaries', 'both' => 'Both'][$s->applies_to] }}</td>
                    <td>
                        @if($s->regime === 'final')
                            <span class="badge bg-warning text-dark">Final</span>
                        @else
                            <span class="badge bg-secondary">Adjustable</span>
                        @endif
                    </td>
                    <td>
                        @if($s->is_active)
                            <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary" onclick='openSection(@json($s))'><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="{{ route('wht.sections.destroy', $s) }}" class="d-inline" onsubmit="return confirm('Delete section {{ $s->code }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $sections->links() }}</div>

<div class="modal fade" id="sectionModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" id="sectionForm">
            @csrf
            <input type="hidden" name="_method" id="sectionMethod" value="POST">
            <div class="modal-header">
                <h5 class="modal-title" id="sectionTitle">Add Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Section <span class="text-danger">*</span></label>
                        <input type="text" name="section" id="x_section" class="form-control" placeholder="153(1)(b)" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">PSID Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="x_code" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Payment Nature <span class="text-danger">*</span></label>
                        <input type="text" name="payment_nature" id="x_nature" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Payment Section <span class="text-danger">*</span></label>
                        <input type="text" name="payment_section" id="x_payment" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Applies To <span class="text-danger">*</span></label>
                        <select name="applies_to" id="x_applies" class="form-select" required>
                            <option value="purchase">Payments</option>
                            <option value="salary">Salaries</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Challan tab <span class="text-danger">*</span></label>
                        <select name="regime" id="x_regime" class="form-select" required>
                            @foreach(\App\Models\WhtSection::REGIMES as $k => $label)
                                <option value="{{ $k }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Which tab FBR's payment page opens for this section.</div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="x_active" checked>
                            <label class="form-check-label" for="x_active">Active</label>
                        </div>
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
@endsection

@section('scripts')
<script>
const sectionModal = new bootstrap.Modal(document.getElementById('sectionModal'));

function openSection(s) {
    const form = document.getElementById('sectionForm');
    const set = (id, v) => document.getElementById(id).value = v ?? '';

    if (s) {
        document.getElementById('sectionTitle').textContent = 'Edit Section';
        document.getElementById('sectionMethod').value = 'PUT';
        form.action = '{{ url('wht/sections') }}/' + s.id;
        set('x_section', s.section);
        set('x_code', s.code);
        set('x_nature', s.payment_nature);
        set('x_payment', s.payment_section);
        set('x_applies', s.applies_to);
        set('x_regime', s.regime || 'adjustable');
        document.getElementById('x_active').checked = !!s.is_active;
    } else {
        document.getElementById('sectionTitle').textContent = 'Add Section';
        document.getElementById('sectionMethod').value = 'POST';
        form.action = '{{ route('wht.sections.store') }}';
        form.reset();
        document.getElementById('x_active').checked = true;
    }

    sectionModal.show();
}
</script>
@endsection
