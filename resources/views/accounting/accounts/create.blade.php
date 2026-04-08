@extends('layouts.app')
@section('title', 'Create Account')
@section('page-title', 'Chart of Accounts')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('accounting.accounts.index') }}" style="color: #9ca3af; text-decoration: none; font-size: 0.85rem;">
        <i class="bi bi-chevron-left"></i> Back to Chart of Accounts
    </a>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
        Add New Account
    </div>
    <div class="card-body" style="padding: 28px;">
        <form action="{{ route('accounting.accounts.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Account Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code', $suggestedCode ?? '') }}" placeholder="e.g. 1001" required>
                    @error('code') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-9">
                    <label class="form-label">Account Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" placeholder="e.g. Cash in Hand" required>
                    @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <label class="form-label">Account Type <span class="text-danger">*</span></label>
                    <select class="form-select @error('type') is-invalid @enderror" name="type" id="account-type" required>
                        <option value="">Select Type</option>
                        <option value="asset" {{ old('type', request('type')) == 'asset' ? 'selected' : '' }}>Asset</option>
                        <option value="liability" {{ old('type', request('type')) == 'liability' ? 'selected' : '' }}>Liability</option>
                        <option value="equity" {{ old('type', request('type')) == 'equity' ? 'selected' : '' }}>Equity</option>
                        <option value="revenue" {{ old('type', request('type')) == 'revenue' ? 'selected' : '' }}>Revenue</option>
                        <option value="expense" {{ old('type', request('type')) == 'expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                    @error('type') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sub-Type</label>
                    <select class="form-select @error('sub_type') is-invalid @enderror" name="sub_type" id="account-sub-type">
                        <option value="">Select Sub-Type</option>
                    </select>
                    @error('sub_type') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Parent Account</label>
                    <select class="form-select searchable @error('parent_id') is-invalid @enderror" name="parent_id" id="parent-account">
                        <option value="">None (Top-Level)</option>
                        @foreach($parentAccounts ?? [] as $pa)
                            <option value="{{ $pa->id }}" data-type="{{ $pa->type }}" {{ old('parent_id') == $pa->id ? 'selected' : '' }}>
                                {{ $pa->code }} - {{ $pa->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="Optional description for this account...">{{ old('description') }}</textarea>
                @error('description') <span class="invalid-feedback">{{ $message }}</span> @enderror
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label">Opening Balance</label>
                    <div class="input-group">
                        <span class="input-group-text" style="border-radius: 10px 0 0 10px; border: 1.5px solid #e2e5ea; border-right: none; background: #f8f9fb; font-size: 0.82rem; font-weight: 600; color: #6b7280;">PKR</span>
                        <input type="number" class="form-control @error('opening_balance') is-invalid @enderror" name="opening_balance" value="{{ old('opening_balance', '0.00') }}" step="0.01" min="0">
                        @error('opening_balance') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Balance Type</label>
                    <div class="d-flex gap-3 mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="opening_balance_type" id="balance-debit" value="debit" {{ old('opening_balance_type', 'debit') == 'debit' ? 'checked' : '' }}>
                            <label class="form-check-label" for="balance-debit" style="font-size: 0.85rem;">Debit</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="opening_balance_type" id="balance-credit" value="credit" {{ old('opening_balance_type') == 'credit' ? 'checked' : '' }}>
                            <label class="form-check-label" for="balance-credit" style="font-size: 0.85rem;">Credit</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4 pt-3" style="border-top: 1px solid #f0f2f5;">
                <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg me-1"></i> Create Account</button>
                <a href="{{ route('accounting.accounts.index') }}" class="btn btn-outline-primary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const subTypes = {
        asset: ['Current Asset', 'Fixed Asset', 'Bank', 'Cash', 'Receivable', 'Inventory', 'Prepaid Expense', 'Other Asset'],
        liability: ['Current Liability', 'Long-Term Liability', 'Payable', 'Tax Payable', 'Other Liability'],
        equity: ['Owner Equity', 'Retained Earnings', 'Share Capital', 'Drawing'],
        revenue: ['Operating Revenue', 'Service Revenue', 'Other Income', 'Interest Income'],
        expense: ['Operating Expense', 'Administrative Expense', 'Cost of Sales', 'Depreciation', 'Tax Expense', 'Other Expense']
    };

    const typeSelect = document.getElementById('account-type');
    const subTypeSelect = document.getElementById('account-sub-type');
    const parentSelect = document.getElementById('parent-account');
    const oldSubType = '{{ old('sub_type') }}';

    function updateSubTypes() {
        const type = typeSelect.value;
        subTypeSelect.innerHTML = '<option value="">Select Sub-Type</option>';
        if (type && subTypes[type]) {
            subTypes[type].forEach(function(st) {
                const opt = document.createElement('option');
                opt.value = st;
                opt.textContent = st;
                if (st === oldSubType) opt.selected = true;
                subTypeSelect.appendChild(opt);
            });
        }
    }

    function filterParentAccounts() {
        const type = typeSelect.value;
        if (parentSelect.tomselect) {
            // TomSelect is active, filter options
            const allOptions = parentSelect.tomselect.options;
            Object.keys(allOptions).forEach(function(key) {
                const opt = allOptions[key];
                if (key === '') return;
                if (!type || opt.type === type) {
                    parentSelect.tomselect.updateOption(key, opt);
                }
            });
            parentSelect.tomselect.refreshOptions(false);
        } else {
            // Plain select fallback
            Array.from(parentSelect.options).forEach(function(opt) {
                if (opt.value === '') return;
                opt.style.display = (!type || opt.dataset.type === type) ? '' : 'none';
            });
        }
    }

    typeSelect.addEventListener('change', function() {
        updateSubTypes();
        filterParentAccounts();
    });

    // Initialize on load
    if (typeSelect.value) {
        updateSubTypes();
        filterParentAccounts();
    }
});
</script>
@endsection
