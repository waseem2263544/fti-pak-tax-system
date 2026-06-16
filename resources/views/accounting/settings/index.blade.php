@extends('layouts.app')
@section('title', 'Accounting Settings')
@section('page-title', 'Accounting Settings')

@section('content')
@php
    $acctOptions = function ($selected) use ($accounts) {
        $out = '<option value="">— Not set —</option>';
        foreach ($accounts as $a) {
            $sel = (string) $selected === (string) $a->id ? ' selected' : '';
            $out .= '<option value="' . $a->id . '"' . $sel . '>' . e($a->code . ' · ' . $a->name) . '</option>';
        }
        return $out;
    };
    $g = fn($k, $d = '') => $settings[$k] ?? $d;
@endphp

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('accounting.settings.update') }}">
    @csrf @method('PUT')

    <!-- Company Info -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            Company Information
            <small class="text-muted ms-2" style="font-weight: 400;">Shown on invoices, vouchers &amp; reports</small>
        </div>
        <div class="card-body" style="padding: 24px;">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" class="form-control" value="{{ $g('company_name') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" name="company_address" class="form-control" value="{{ $g('company_address') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">NTN</label>
                    <input type="text" name="company_ntn" class="form-control" value="{{ $g('company_ntn') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">STRN</label>
                    <input type="text" name="company_strn" class="form-control" value="{{ $g('company_strn') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="company_phone" class="form-control" value="{{ $g('company_phone') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Email</label>
                    <input type="text" name="company_email" class="form-control" value="{{ $g('company_email') }}">
                </div>
            </div>
        </div>
    </div>

    <!-- Document Prefixes -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            Document Numbering
        </div>
        <div class="card-body" style="padding: 24px;">
            <div class="row">
                @foreach(['invoice_prefix' => 'Sales Invoice', 'bill_prefix' => 'Purchase Bill', 'receipt_prefix' => 'Receipt Voucher', 'payment_prefix' => 'Payment Voucher', 'journal_prefix' => 'Journal Entry'] as $key => $label)
                <div class="col-md-2 mb-3">
                    <label class="form-label">{{ $label }}</label>
                    <input type="text" name="{{ $key }}" class="form-control" value="{{ $g($key) }}" placeholder="e.g. INV">
                </div>
                @endforeach
            </div>
            <small class="text-muted">Numbers are generated as PREFIX-0001, PREFIX-0002, …</small>
        </div>
    </div>

    <!-- Default Control Accounts -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            Default Control Accounts
            <small class="text-muted ms-2" style="font-weight: 400;">Used when auto-posting invoices &amp; vouchers</small>
        </div>
        <div class="card-body" style="padding: 24px;">
            <div class="row">
                @foreach([
                    'default_receivable_account' => 'Accounts Receivable',
                    'default_payable_account' => 'Accounts Payable',
                    'default_cash_account' => 'Cash',
                    'default_bank_account' => 'Bank',
                    'default_sales_account' => 'Default Sales / Revenue',
                    'default_purchase_account' => 'Default Purchase / Expense',
                    'default_sales_tax_account' => 'Output Sales Tax (Payable)',
                    'default_purchase_tax_account' => 'Input Sales Tax (Adjustable)',
                    'default_sales_discount_account' => 'Sales Discounts',
                ] as $key => $label)
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ $label }}</label>
                    <select name="{{ $key }}" class="form-select acct-select">{!! $acctOptions($g($key)) !!}</select>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Invoice Defaults -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <div style="width: 8px; height: 8px; background: var(--accent); border-radius: 50%;"></div>
            Invoice Defaults
        </div>
        <div class="card-body" style="padding: 24px;">
            <div class="mb-3">
                <label class="form-label">Default Payment Terms</label>
                <input type="text" name="invoice_terms" class="form-control" value="{{ $g('invoice_terms') }}" placeholder="e.g. Payment due within 30 days">
            </div>
            <div class="mb-3">
                <label class="form-label">Invoice Footer</label>
                <input type="text" name="invoice_footer" class="form-control" value="{{ $g('invoice_footer') }}" placeholder="e.g. Thank you for your business!">
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-accent"><i class="bi bi-check-circle me-1"></i>Save Settings</button>
        <a href="{{ route('accounting.dashboard') }}" class="btn btn-outline-primary">Cancel</a>
    </div>
</form>
@endsection

@section('scripts')
<script>
    if (window.TomSelect) {
        document.querySelectorAll('.acct-select').forEach(function (el) {
            new TomSelect(el, { create: false });
        });
    }
</script>
@endsection
