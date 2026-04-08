@extends('layouts.app')
@section('title', 'New Purchase Invoice')
@section('page-title', 'Purchase Invoices')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('accounting.purchase-invoices.index') }}" style="color: #9ca3af; text-decoration: none; font-size: 0.85rem;">
        <i class="bi bi-chevron-left"></i> Back to Purchase Invoices
    </a>
</div>

<form action="{{ route('accounting.purchase-invoices.store') }}" method="POST" id="bill-form">
    @csrf

    <!-- Header -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            New Purchase Invoice
        </div>
        <div class="card-body" style="padding: 24px;">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Vendor <span class="text-danger">*</span></label>
                    <select class="form-select searchable @error('contact_id') is-invalid @enderror" name="contact_id" id="vendor-select">
                        <option value="">Select Vendor</option>
                        @foreach($contacts ?? [] as $contact)
                            <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>{{ $contact->name }}</option>
                        @endforeach
                    </select>
                    @error('contact_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    <div class="mt-2">
                        <input type="text" class="form-control form-control-sm @error('vendor_name') is-invalid @enderror" name="vendor_name" value="{{ old('vendor_name') }}" placeholder="Or enter vendor name manually..." style="font-size: 0.82rem;">
                        @error('vendor_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Vendor Invoice # <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('vendor_invoice_number') is-invalid @enderror" name="vendor_invoice_number" value="{{ old('vendor_invoice_number') }}" placeholder="Vendor's bill #">
                    @error('vendor_invoice_number') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bill Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('bill_date') is-invalid @enderror" name="bill_date" value="{{ old('bill_date', date('Y-m-d')) }}" required>
                    @error('bill_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Due Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('due_date') is-invalid @enderror" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" required>
                    @error('due_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Reference</label>
                    <input type="text" class="form-control @error('reference') is-invalid @enderror" name="reference" value="{{ old('reference') }}" placeholder="PO number...">
                    @error('reference') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
                Line Items
            </div>
            <button type="button" class="btn btn-sm btn-accent" id="add-line-btn"><i class="bi bi-plus-lg me-1"></i> Add Line</button>
        </div>
        <div class="table-responsive">
            <table class="table mb-0" id="lines-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 22%;">Account</th>
                        <th style="width: 23%;">Description</th>
                        <th style="width: 10%;" class="text-end">Qty</th>
                        <th style="width: 13%;" class="text-end">Unit Price</th>
                        <th style="width: 10%;" class="text-end">Tax %</th>
                        <th style="width: 13%;" class="text-end">Amount</th>
                        <th style="width: 4%;"></th>
                    </tr>
                </thead>
                <tbody id="lines-body">
                    <!-- Lines added via JS -->
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <!-- Notes -->
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-body" style="padding: 20px;">
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Internal notes about this bill...">{{ old('notes') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">Terms &amp; Conditions</label>
                        <textarea class="form-control" name="terms" rows="3" placeholder="Payment terms...">{{ old('terms') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-body" style="padding: 20px;">
                    <div class="d-flex justify-content-between align-items-center mb-3" style="padding-bottom: 12px; border-bottom: 1px solid #f0f2f5;">
                        <span style="font-size: 0.85rem; color: #6b7280;">Subtotal</span>
                        <span id="subtotal" style="font-weight: 600; color: var(--primary);">PKR 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3" style="padding-bottom: 12px; border-bottom: 1px solid #f0f2f5;">
                        <span style="font-size: 0.85rem; color: #6b7280;">Tax</span>
                        <span id="tax-total" style="font-weight: 600; color: var(--primary);">PKR 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3" style="padding-bottom: 12px; border-bottom: 1px solid #f0f2f5;">
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size: 0.85rem; color: #6b7280;">Discount</span>
                            <input type="number" class="form-control form-control-sm" name="discount" id="discount-input" value="{{ old('discount', '0') }}" step="0.01" min="0" style="width: 100px;" oninput="calculateTotals()">
                        </div>
                        <span id="discount-display" style="font-weight: 600; color: #ef4444;">- PKR 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center" style="padding: 12px 0;">
                        <span style="font-weight: 700; font-size: 1rem; color: var(--primary);">Total</span>
                        <span id="grand-total" style="font-weight: 800; font-size: 1.35rem; color: var(--primary);">PKR 0.00</span>
                    </div>
                    <input type="hidden" name="subtotal" id="input-subtotal">
                    <input type="hidden" name="tax_amount" id="input-tax">
                    <input type="hidden" name="total" id="input-total">
                </div>
            </div>
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
        <a href="{{ route('accounting.purchase-invoices.index') }}" class="btn btn-outline-primary">Cancel</a>
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
        @foreach($expenseAccounts ?? [] as $acc)
        <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
        @endforeach
    `;

    function addLine() {
        lineIndex++;
        const row = document.createElement('tr');
        row.setAttribute('data-line', lineIndex);
        row.innerHTML = `
            <td style="vertical-align: middle; font-size: 0.82rem; color: #9ca3af; font-weight: 600;">${lineIndex}</td>
            <td>
                <select class="form-select form-select-sm inv-account" name="items[${lineIndex}][account_id]" required>
                    ${accountOptions}
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="items[${lineIndex}][description]" placeholder="Item description...">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-end inv-qty" name="items[${lineIndex}][quantity]" value="1" step="0.01" min="0.01" oninput="calculateRow(this)" onfocus="this.select()">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-end inv-price" name="items[${lineIndex}][unit_price]" value="0.00" step="0.01" min="0" oninput="calculateRow(this)" onfocus="this.select()">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-end inv-tax" name="items[${lineIndex}][tax_rate]" value="0" step="0.01" min="0" max="100" oninput="calculateRow(this)" onfocus="this.select()">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm text-end inv-amount" readonly style="background: #fafbfc; font-weight: 600;" value="0.00">
                <input type="hidden" name="items[${lineIndex}][amount]" class="inv-amount-hidden">
            </td>
            <td class="text-center" style="vertical-align: middle;">
                <button type="button" class="btn btn-sm text-danger remove-line" title="Remove" style="padding: 2px 6px;"><i class="bi bi-x-lg"></i></button>
            </td>
        `;
        linesBody.appendChild(row);

        const sel = row.querySelector('.inv-account');
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

        row.querySelector('.remove-line').addEventListener('click', function() {
            row.remove();
            renumberLines();
            calculateTotals();
        });

        calculateTotals();
    }

    function renumberLines() {
        const rows = linesBody.querySelectorAll('tr');
        rows.forEach(function(row, i) {
            row.querySelector('td:first-child').textContent = i + 1;
        });
        lineIndex = rows.length;
    }

    window.calculateRow = function(el) {
        const row = el.closest('tr');
        const qty = parseFloat(row.querySelector('.inv-qty').value) || 0;
        const price = parseFloat(row.querySelector('.inv-price').value) || 0;
        const taxRate = parseFloat(row.querySelector('.inv-tax').value) || 0;
        const lineTotal = qty * price;
        const lineTax = lineTotal * (taxRate / 100);
        const amount = lineTotal + lineTax;
        row.querySelector('.inv-amount').value = amount.toFixed(2);
        row.querySelector('.inv-amount-hidden').value = amount.toFixed(2);
        calculateTotals();
    };

    window.calculateTotals = function() {
        let subtotal = 0, taxTotal = 0;
        document.querySelectorAll('#lines-body tr').forEach(function(row) {
            const qty = parseFloat(row.querySelector('.inv-qty')?.value) || 0;
            const price = parseFloat(row.querySelector('.inv-price')?.value) || 0;
            const taxRate = parseFloat(row.querySelector('.inv-tax')?.value) || 0;
            const lineTotal = qty * price;
            subtotal += lineTotal;
            taxTotal += lineTotal * (taxRate / 100);
        });

        const discount = parseFloat(document.getElementById('discount-input').value) || 0;
        const grandTotal = subtotal + taxTotal - discount;

        document.getElementById('subtotal').textContent = 'PKR ' + subtotal.toFixed(2);
        document.getElementById('tax-total').textContent = 'PKR ' + taxTotal.toFixed(2);
        document.getElementById('discount-display').textContent = '- PKR ' + discount.toFixed(2);
        document.getElementById('grand-total').textContent = 'PKR ' + grandTotal.toFixed(2);

        document.getElementById('input-subtotal').value = subtotal.toFixed(2);
        document.getElementById('input-tax').value = taxTotal.toFixed(2);
        document.getElementById('input-total').value = grandTotal.toFixed(2);
    };

    addBtn.addEventListener('click', addLine);

    // Start with 1 line
    addLine();
});
</script>
@endsection
