<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccSettingController extends Controller
{
    /** Settings keys this screen manages. */
    private array $keys = [
        'company_name', 'company_address', 'company_ntn', 'company_strn', 'company_phone', 'company_email',
        'invoice_prefix', 'bill_prefix', 'payment_prefix', 'receipt_prefix', 'journal_prefix',
        'default_receivable_account', 'default_payable_account', 'default_cash_account', 'default_bank_account',
        'default_sales_account', 'default_purchase_account', 'default_sales_tax_account',
        'default_purchase_tax_account', 'default_sales_discount_account',
        'invoice_terms', 'invoice_footer',
    ];

    public function index()
    {
        $settings = DB::table('acc_settings')->pluck('value', 'key')->toArray();
        $accounts = AccAccount::active()->orderBy('code')->get(['id', 'code', 'name', 'type']);
        return view('accounting.settings.index', compact('settings', 'accounts'));
    }

    public function update(Request $request)
    {
        $now = now();
        foreach ($this->keys as $key) {
            if (!$request->has($key)) continue;
            $value = $request->input($key);
            $value = is_null($value) ? '' : (string) $value;

            if (DB::table('acc_settings')->where('key', $key)->exists()) {
                DB::table('acc_settings')->where('key', $key)->update(['value' => $value, 'updated_at' => $now]);
            } else {
                DB::table('acc_settings')->insert(['key' => $key, 'value' => $value, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        return back()->with('success', 'Accounting settings saved.');
    }

    public function auditLog(Request $request)
    {
        $models = [
            'AccJournalEntry' => 'Journal Entry', 'AccSalesInvoice' => 'Sales Invoice',
            'AccPurchaseInvoice' => 'Purchase Bill', 'AccVoucher' => 'Voucher', 'AccAccount' => 'Account',
        ];
        $logs = AccAuditLog::query()
            ->when($request->input('action'), fn($q, $a) => $q->where('action', $a))
            ->when($request->input('model'), fn($q, $m) => $q->where('model_type', $m))
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('accounting.settings.audit-log', compact('logs', 'models'));
    }
}
