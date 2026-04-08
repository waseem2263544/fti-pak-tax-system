@extends('layouts.app')
@section('title', 'New Receipt Voucher')
@section('page-title', 'Receipt Vouchers')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('accounting.receipt-vouchers.index') }}" style="color: #9ca3af; text-decoration: none; font-size: 0.85rem;">
        <i class="bi bi-chevron-left"></i> Back to Receipt Vouchers
    </a>
</div>

<form action="{{ route('accounting.receipt-vouchers.store') }}" method="POST" id="receipt-form">
    @csrf

    <div class="row">
        <div class="col-md-8">
            <!-- Receipt Details -->
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
                    New Receipt Voucher
                </div>
                <div class="card-body" style="padding: 24px;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Received From (Client) <span class="text-danger">*</span></label>
                            <select class="form-select searchable @error('client_id') is-invalid @enderror" name="client_id" id="client-select" required>
                                <option value="">Select Client</option>
                                @foreach($clients ?? [] as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id', request('client_id')) == $client->id ? 'selected' : '' }}>{{ $client->name }}</option>
                                @endforeach
                            </select>
                            @error('client_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('payment_date') is-invalid @enderror" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                            @error('payment_date') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('amount') is-invalid @enderror" name="amount" value="{{ old('amount') }}" step="0.01" min="0.01" placeholder="0.00" required>
                            @error('amount') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="mt-4">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="method-cash" value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'checked' : '' }} onchange="toggleChequeField()">
                                <label class="form-check-label" for="method-cash" style="font-size: 0.88rem;">Cash</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="method-bank" value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'checked' : '' }} onchange="toggleChequeField()">
                                <label class="form-check-label" for="method-bank" style="font-size: 0.88rem;">Bank Transfer</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="method-cheque" value="cheque" {{ old('payment_method') === 'cheque' ? 'checked' : '' }} onchange="toggleChequeField()">
                                <label class="form-check-label" for="method-cheque" style="font-size: 0.88rem;">Cheque</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="method-online" value="online" {{ old('payment_method') === 'online' ? 'checked' : '' }} onchange="toggleChequeField()">
                                <label class="form-check-label" for="method-online" style="font-size: 0.88rem;">Online</label>
                            </div>
                        </div>
                        @error('payment_method') <span class="text-danger" style="font-size: 0.82rem;">{{ $message }}</span> @enderror
                    </div>

                    <!-- Cheque Number (conditional) -->
                    <div class="mt-3" id="cheque-field" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Cheque Number</label>
                                <input type="text" class="form-control @error('cheque_number') is-invalid @enderror" name="cheque_number" value="{{ old('cheque_number') }}" placeholder="Enter cheque number...">
                                @error('cheque_number') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Receiving Account <span class="text-danger">*</span></label>
                            <select class="form-select searchable @error('account_id') is-invalid @enderror" name="account_id" required>
                                <option value="">Select Bank/Cash Account</option>
                                @foreach($bankAccounts ?? [] as $acc)
                                    <option value="{{ $acc->id }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->code }} - {{ $acc->name }}</option>
                                @endforeach
                            </select>
                            @error('account_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Link to Invoice</label>
                            <select class="form-select searchable @error('invoice_id') is-invalid @enderror" name="invoice_id" id="invoice-select">
                                <option value="">No invoice link</option>
                                @foreach($unpaidInvoices ?? [] as $inv)
                                    <option value="{{ $inv->id }}" {{ old('invoice_id', request('invoice_id')) == $inv->id ? 'selected' : '' }}>
                                        {{ $inv->invoice_number }} - {{ $inv->client->name ?? '' }} (PKR {{ number_format($inv->total - $inv->paid_amount, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('invoice_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Narration</label>
                        <textarea class="form-control @error('narration') is-invalid @enderror" name="narration" rows="3" placeholder="Payment description or remarks...">{{ old('narration') }}</textarea>
                        @error('narration') <span class="invalid-feedback">{{ $message }}</span> @enderror
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
                <a href="{{ route('accounting.receipt-vouchers.index') }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </div>

        <!-- Summary Sidebar -->
        <div class="col-md-4">
            <div class="card" style="position: sticky; top: 24px;">
                <div class="card-header d-flex align-items-center gap-2">
                    <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
                    Summary
                </div>
                <div class="card-body" style="padding: 20px;">
                    <div style="text-align: center; padding: 20px 0;">
                        <div style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 8px;">Receipt Amount</div>
                        <div style="font-weight: 800; font-size: 2rem; color: #10b981;" id="summary-amount">PKR 0.00</div>
                    </div>
                    <div style="border-top: 1px solid #f0f2f5; padding-top: 16px;">
                        <div class="d-flex justify-content-between mb-2">
                            <span style="font-size: 0.82rem; color: #9ca3af;">Type</span>
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--primary);">Receipt</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span style="font-size: 0.82rem; color: #9ca3af;">Method</span>
                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--primary);" id="summary-method">Cash</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function toggleChequeField() {
        const chequeRadio = document.getElementById('method-cheque');
        const chequeField = document.getElementById('cheque-field');
        chequeField.style.display = chequeRadio.checked ? 'block' : 'none';

        // Update summary method
        const methods = document.querySelectorAll('input[name="payment_method"]');
        methods.forEach(function(m) {
            if (m.checked) {
                const labels = { cash: 'Cash', bank_transfer: 'Bank Transfer', cheque: 'Cheque', online: 'Online' };
                document.getElementById('summary-method').textContent = labels[m.value] || m.value;
            }
        });
    }

    window.toggleChequeField = toggleChequeField;
    toggleChequeField();

    // Update summary amount
    const amountInput = document.querySelector('input[name="amount"]');
    if (amountInput) {
        amountInput.addEventListener('input', function() {
            const val = parseFloat(this.value) || 0;
            document.getElementById('summary-amount').textContent = 'PKR ' + val.toFixed(2);
        });
        // Trigger initial
        const initVal = parseFloat(amountInput.value) || 0;
        if (initVal > 0) {
            document.getElementById('summary-amount').textContent = 'PKR ' + initVal.toFixed(2);
        }
    }
});
</script>
@endsection
