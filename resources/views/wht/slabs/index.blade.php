@extends('layouts.app')
@section('title', 'Salary Tax Slabs')
@section('page-title', 'Salary Tax Slabs')

@section('content')
<div class="alert alert-info d-flex align-items-start" style="font-size: 0.85rem;">
    <i class="bi bi-info-circle me-2 mt-1"></i>
    <div>
        Global slabs, one set per tax year. Pakistan's tax year runs July–June and is named for the year it ends in,
        so July 2025 – June 2026 is <strong>TY2026</strong>. A salary record picks its slabs from its salary month.
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="GET" class="d-flex align-items-end gap-2">
        <div>
            <label class="form-label mb-1">Tax Year</label>
            <select name="tax_year" class="form-select" onchange="this.form.submit()">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $taxYear == $y ? 'selected' : '' }}>TY{{ $y }}</option>
                @endforeach
                @if(!$years->contains($taxYear))
                    <option value="{{ $taxYear }}" selected>TY{{ $taxYear }} (empty)</option>
                @endif
            </select>
        </div>
    </form>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#copyModal">
            <i class="bi bi-files me-1"></i> Copy Year
        </button>
        <button class="btn btn-accent" onclick="openSlab()"><i class="bi bi-plus-lg me-1"></i> Add Slab</button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="text-end">Annual Income Over</th>
                    <th class="text-end">Up To</th>
                    <th class="text-end">Fixed Tax</th>
                    <th class="text-end">Rate on Excess</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($slabs as $s)
                <tr>
                    <td class="text-end">{{ number_format($s->min_salary, 0) }}</td>
                    <td class="text-end">{{ $s->max_salary ? number_format($s->max_salary, 0) : 'and above' }}</td>
                    <td class="text-end">{{ number_format($s->fixed_tax, 0) }}</td>
                    <td class="text-end fw-semibold">{{ rtrim(rtrim(number_format($s->tax_rate, 2), '0'), '.') }}%</td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary" onclick='openSlab(@json($s))'><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="{{ route('wht.slabs.destroy', $s) }}" class="d-inline" onsubmit="return confirm('Delete this slab?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-5">
                    No slabs for TY{{ $taxYear }}. Add them, or copy from another year.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="slabModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" id="slabForm">
            @csrf
            <input type="hidden" name="_method" id="slabMethod" value="POST">
            <input type="hidden" name="tax_year" id="s_year" value="{{ $taxYear }}">
            <div class="modal-header">
                <h5 class="modal-title" id="slabTitle">Add Slab</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Annual Income Over <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="min_salary" id="s_min" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Up To</label>
                        <input type="number" step="0.01" name="max_salary" id="s_max" class="form-control" placeholder="blank = top slab">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Fixed Tax <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="fixed_tax" id="s_fixed" class="form-control" value="0" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Rate on Excess (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" max="100" name="tax_rate" id="s_rate" class="form-control" required>
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

<div class="modal fade" id="copyModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('wht.slabs.copy-year') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Copy Slabs to Another Year</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">From Tax Year</label>
                        <input type="number" name="from_year" class="form-control" value="{{ $taxYear }}" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">To Tax Year</label>
                        <input type="number" name="to_year" class="form-control" value="{{ $taxYear + 1 }}" required>
                    </div>
                </div>
                <p class="text-muted mt-3 mb-0" style="font-size: 0.82rem;">
                    The target year must be empty. Copy first, then adjust the numbers the Finance Act changed.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Copy</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const slabModal = new bootstrap.Modal(document.getElementById('slabModal'));

function openSlab(s) {
    const form = document.getElementById('slabForm');
    const set = (id, v) => document.getElementById(id).value = v ?? '';

    if (s) {
        document.getElementById('slabTitle').textContent = 'Edit Slab';
        document.getElementById('slabMethod').value = 'PUT';
        form.action = '{{ url('wht/slabs') }}/' + s.id;
        set('s_year', s.tax_year);
        set('s_min', s.min_salary);
        set('s_max', s.max_salary);
        set('s_fixed', s.fixed_tax);
        set('s_rate', s.tax_rate);
    } else {
        document.getElementById('slabTitle').textContent = 'Add Slab';
        document.getElementById('slabMethod').value = 'POST';
        form.action = '{{ route('wht.slabs.store') }}';
        form.reset();
        set('s_year', '{{ $taxYear }}');
        set('s_fixed', 0);
    }

    slabModal.show();
}
</script>
@endsection
