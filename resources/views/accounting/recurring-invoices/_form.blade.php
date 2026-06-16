@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ $action }}">
    @csrf
    @if(($method ?? 'POST') === 'PUT') @method('PUT') @endif

    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-arrow-repeat me-1"></i>Schedule</div>
        <div class="card-body row g-3" style="padding:24px;">
            <div class="col-md-4">
                <label class="form-label">Client <span class="text-danger">*</span></label>
                <select name="client_id" class="form-select tom" required>
                    <option value="">Select client…</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ (string)old('client_id', $template->client_id) === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Frequency</label>
                <select name="frequency" class="form-select">
                    @foreach(['weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'] as $k => $v)
                        <option value="{{ $k }}" {{ old('frequency', $template->frequency) === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Next Date</label>
                <input type="date" name="next_date" class="form-control" value="{{ old('next_date', optional($template->next_date)->format('Y-m-d') ?? $template->next_date) }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Payment Terms (days)</label>
                <input type="number" name="due_days" class="form-control" value="{{ old('due_days', $template->due_days ?? 30) }}" min="0">
            </div>
            <div class="col-md-2 d-flex align-items-center">
                <div class="form-check mt-4">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Reference</label>
                <input type="text" name="reference" class="form-control" value="{{ old('reference', $template->reference) }}" placeholder="e.g. Monthly retainer">
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-ul me-1"></i>Line Items</span>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addRow()"><i class="bi bi-plus-lg me-1"></i>Add Line</button>
        </div>
        <div class="table-responsive">
            <table class="table mb-0" id="itemsTable">
                <thead><tr><th style="width:28%;">Account</th><th>Description</th><th style="width:90px;">Qty</th><th style="width:120px;">Unit Price</th><th style="width:90px;">Tax %</th><th style="width:110px;">Discount</th><th style="width:40px;"></th></tr></thead>
                <tbody id="itemsBody"></tbody>
            </table>
        </div>
        <div class="card-body row g-3" style="padding:20px 24px;">
            <div class="col-md-3 offset-md-9">
                <label class="form-label">Header Discount</label>
                <input type="number" step="0.01" name="discount_amount" class="form-control" value="{{ old('discount_amount', $template->discount_amount ?? 0) }}">
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body row g-3" style="padding:24px;">
            <div class="col-md-6"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $template->notes) }}</textarea></div>
            <div class="col-md-6"><label class="form-label">Terms</label><textarea name="terms" class="form-control" rows="2">{{ old('terms', $template->terms) }}</textarea></div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Save Template</button>
        <a href="{{ route('accounting.recurring-invoices.index') }}" class="btn btn-outline-primary">Cancel</a>
    </div>
</form>

@section('scripts')
<script>
    var ACCOUNTS = @json($revenueAccounts->map(fn($a) => ['id' => $a->id, 'label' => $a->code . ' · ' . $a->name]));
    var EXISTING = @json(old('items', $template->items ?: []));
    var idx = 0;

    function addRow(item) {
        item = item || {};
        var opts = '<option value="">Account…</option>';
        ACCOUNTS.forEach(function (a) { opts += '<option value="' + a.id + '"' + (String(item.account_id) === String(a.id) ? ' selected' : '') + '>' + a.label + '</option>'; });
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><select name="items[' + idx + '][account_id]" class="form-select form-select-sm" required>' + opts + '</select></td>' +
            '<td><input name="items[' + idx + '][description]" class="form-control form-control-sm" value="' + (item.description ? String(item.description).replace(/"/g, '&quot;') : '') + '" required></td>' +
            '<td><input name="items[' + idx + '][quantity]" type="number" step="0.01" class="form-control form-control-sm" value="' + (item.quantity || 1) + '" required></td>' +
            '<td><input name="items[' + idx + '][unit_price]" type="number" step="0.01" class="form-control form-control-sm" value="' + (item.unit_price || 0) + '" required></td>' +
            '<td><input name="items[' + idx + '][tax_rate]" type="number" step="0.01" class="form-control form-control-sm" value="' + (item.tax_rate || 0) + '"></td>' +
            '<td><input name="items[' + idx + '][discount]" type="number" step="0.01" class="form-control form-control-sm" value="' + (item.discount || 0) + '"></td>' +
            '<td><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest(\'tr\').remove()"><i class="bi bi-x"></i></button></td>';
        document.getElementById('itemsBody').appendChild(tr);
        idx++;
    }

    if (EXISTING && EXISTING.length) { EXISTING.forEach(addRow); } else { addRow(); }
    if (window.TomSelect) { document.querySelectorAll('.tom').forEach(function (el) { new TomSelect(el, { create: false }); }); }
</script>
@endsection
