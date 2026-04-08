@extends('layouts.app')
@section('title', 'New Journal Entry')
@section('page-title', 'Journal Entries')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('accounting.journal-entries.index') }}" style="color: #9ca3af; text-decoration: none; font-size: 0.85rem;">
        <i class="bi bi-chevron-left"></i> Back to Journal Entries
    </a>
</div>

<form action="{{ route('accounting.journal-entries.store') }}" method="POST" id="je-form">
    @csrf

    <!-- Header Fields -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            New Journal Entry
        </div>
        <div class="card-body" style="padding: 24px;">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('entry_date') is-invalid @enderror" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" required>
                    @error('entry_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Reference</label>
                    <input type="text" class="form-control @error('reference') is-invalid @enderror" name="reference" value="{{ old('reference') }}" placeholder="Optional reference">
                    @error('reference') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Narration <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('narration') is-invalid @enderror" name="narration" value="{{ old('narration') }}" placeholder="Describe this journal entry..." required>
                    @error('narration') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Journal Lines -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
                Entry Lines
            </div>
            <button type="button" class="btn btn-sm btn-accent" id="add-line-btn"><i class="bi bi-plus-lg me-1"></i> Add Line</button>
        </div>
        <div class="table-responsive">
            <table class="table mb-0" id="lines-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 35%;">Account</th>
                        <th style="width: 25%;">Description</th>
                        <th style="width: 15%;" class="text-end">Debit</th>
                        <th style="width: 15%;" class="text-end">Credit</th>
                        <th style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody id="lines-body">
                    <!-- Lines will be added via JS -->
                </tbody>
                <tfoot>
                    <tr style="background: linear-gradient(180deg, #fafbfc 0%, #f6f7f9 100%);">
                        <td colspan="3" style="font-weight: 700; color: var(--primary); font-size: 0.88rem; padding: 16px 20px;">Totals</td>
                        <td class="text-end" style="padding: 16px 20px;">
                            <span id="total-debit" style="font-weight: 800; font-size: 1rem; color: var(--primary);">0.00</span>
                        </td>
                        <td class="text-end" style="padding: 16px 20px;">
                            <span id="total-credit" style="font-weight: 800; font-size: 1rem; color: var(--primary);">0.00</span>
                        </td>
                        <td></td>
                    </tr>
                    <tr id="difference-row" style="display: none;">
                        <td colspan="3" style="font-weight: 700; color: #ef4444; font-size: 0.85rem; padding: 8px 20px;">Difference (must be zero)</td>
                        <td colspan="2" class="text-end" style="padding: 8px 20px;">
                            <span id="difference" style="font-weight: 800; font-size: 1rem; color: #ef4444;">0.00</span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger mb-4">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="d-flex gap-2">
        <button type="submit" name="action" value="draft" class="btn btn-primary"><i class="bi bi-file-earmark me-1"></i> Save as Draft</button>
        <button type="submit" name="action" value="post" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i> Save &amp; Post</button>
        <a href="{{ route('accounting.journal-entries.index') }}" class="btn btn-outline-primary">Cancel</a>
    </div>
</form>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let lineIndex = 0;
    const linesBody = document.getElementById('lines-body');
    const addBtn = document.getElementById('add-line-btn');

    const accountOptions = `
        <option value="">Select Account</option>
        @foreach($accounts ?? [] as $acc)
        <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
        @endforeach
    `;

    function addLine(accountId, description, debit, credit) {
        lineIndex++;
        const row = document.createElement('tr');
        row.setAttribute('data-line', lineIndex);
        row.innerHTML = `
            <td style="vertical-align: middle; font-size: 0.82rem; color: #9ca3af; font-weight: 600;">${lineIndex}</td>
            <td>
                <select class="form-select form-select-sm je-account" name="lines[${lineIndex}][account_id]" required>
                    ${accountOptions}
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="lines[${lineIndex}][description]" placeholder="Line description..." value="${description || ''}">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-end je-debit" name="lines[${lineIndex}][debit]" value="${debit || '0.00'}" step="0.01" min="0" onfocus="this.select()">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-end je-credit" name="lines[${lineIndex}][credit]" value="${credit || '0.00'}" step="0.01" min="0" onfocus="this.select()">
            </td>
            <td class="text-center" style="vertical-align: middle;">
                <button type="button" class="btn btn-sm text-danger remove-line" title="Remove" style="padding: 2px 6px;"><i class="bi bi-x-lg"></i></button>
            </td>
        `;
        linesBody.appendChild(row);

        // Initialize TomSelect on the new account dropdown
        const sel = row.querySelector('.je-account');
        if (typeof TomSelect !== 'undefined') {
            new TomSelect(sel, {
                allowEmptyOption: true,
                placeholder: 'Select Account',
                controlInput: '<input>',
                render: {
                    no_results: function() { return '<div class="no-results" style="padding:10px;color:#9ca3af;font-size:0.85rem;">No match found</div>'; }
                }
            });
        }

        // Set account if provided
        if (accountId && sel.tomselect) {
            sel.tomselect.setValue(accountId);
        }

        // Auto-clear opposing field
        const debitInput = row.querySelector('.je-debit');
        const creditInput = row.querySelector('.je-credit');
        debitInput.addEventListener('input', function() {
            if (parseFloat(this.value) > 0) creditInput.value = '0.00';
            updateTotals();
        });
        creditInput.addEventListener('input', function() {
            if (parseFloat(this.value) > 0) debitInput.value = '0.00';
            updateTotals();
        });

        // Remove line
        row.querySelector('.remove-line').addEventListener('click', function() {
            row.remove();
            renumberLines();
            updateTotals();
        });

        updateTotals();
    }

    function renumberLines() {
        const rows = linesBody.querySelectorAll('tr');
        rows.forEach(function(row, i) {
            row.querySelector('td:first-child').textContent = i + 1;
        });
        lineIndex = rows.length;
    }

    function updateTotals() {
        let totalDebit = 0, totalCredit = 0;
        document.querySelectorAll('.je-debit').forEach(function(el) {
            totalDebit += parseFloat(el.value) || 0;
        });
        document.querySelectorAll('.je-credit').forEach(function(el) {
            totalCredit += parseFloat(el.value) || 0;
        });

        document.getElementById('total-debit').textContent = totalDebit.toFixed(2);
        document.getElementById('total-credit').textContent = totalCredit.toFixed(2);

        const diff = Math.abs(totalDebit - totalCredit);
        const diffRow = document.getElementById('difference-row');
        const diffSpan = document.getElementById('difference');
        if (diff > 0.001) {
            diffRow.style.display = '';
            diffSpan.textContent = diff.toFixed(2);
        } else {
            diffRow.style.display = 'none';
        }
    }

    addBtn.addEventListener('click', function() { addLine(); });

    // Start with 2 empty lines
    addLine();
    addLine();
});
</script>
@endsection
