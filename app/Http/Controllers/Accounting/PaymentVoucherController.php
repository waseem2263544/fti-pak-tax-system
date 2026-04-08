<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccContact;
use App\Models\AccFiscalYear;
use App\Models\AccJournalEntry;
use App\Models\AccPurchaseInvoice;
use App\Models\AccVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentVoucherController extends Controller
{
    /**
     * Display all payment vouchers.
     */
    public function index(Request $request)
    {
        $query = AccVoucher::payments()->with(['contact', 'paymentAccount', 'creator'])->latest('date');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%")
                  ->orWhere('party_name', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhereHas('contact', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vouchers = $query->paginate(25)->withQueryString();

        return view('accounting.payment-vouchers.index', compact('vouchers'));
    }

    /**
     * Show the form for creating a new payment voucher.
     */
    public function create()
    {
        $vendors = AccContact::where('type', 'vendor')->where('is_active', true)->orderBy('name')->get();
        $expenseAccounts = AccAccount::active()->ofType('expense')->orderBy('code')->get();
        $bankCashAccounts = AccAccount::active()
            ->where(function ($q) {
                $q->where('sub_type', 'Bank')
                  ->orWhere('sub_type', 'Cash');
            })->orderBy('code')->get();
        $apAccount = AccAccount::find(AccAccount::setting('accounts_payable_id'));
        $unpaidBills = AccPurchaseInvoice::where('balance_due', '>', 0)->with('contact')->latest('date')->get();
        $nextNumber = AccVoucher::nextPaymentNumber();

        return view('accounting.payment-vouchers.create', compact(
            'vendors', 'expenseAccounts', 'bankCashAccounts', 'apAccount', 'unpaidBills', 'nextNumber'
        ));
    }

    /**
     * Store a newly created payment voucher.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'               => 'required|date',
            'contact_id'         => 'nullable|exists:acc_contacts,id',
            'party_name'         => 'nullable|string|max:255',
            'payment_account_id' => 'required|exists:acc_accounts,id',
            'amount'             => 'required|numeric|min:0.01',
            'payment_method'     => 'nullable|string|in:cash,cheque,bank_transfer,online',
            'cheque_number'      => 'nullable|string|max:50',
            'reference'          => 'nullable|string|max:255',
            'narration'          => 'nullable|string',
            'invoice_id'         => 'nullable|exists:acc_purchase_invoices,id',
            'items'              => 'required|array|min:1',
            'items.*.account_id' => 'required|exists:acc_accounts,id',
            'items.*.description'=> 'nullable|string|max:255',
            'items.*.amount'     => 'required|numeric|min:0.01',
        ]);

        // Validate total items amount matches voucher amount
        $itemsTotal = collect($validated['items'])->sum('amount');
        if (round($itemsTotal, 2) !== round($validated['amount'], 2)) {
            return back()->withInput()->withErrors(['amount' => 'Voucher amount must equal the sum of line items. Items total: ' . number_format($itemsTotal, 2)]);
        }

        DB::beginTransaction();

        try {
            $voucher = AccVoucher::create([
                'voucher_number'    => AccVoucher::nextPaymentNumber(),
                'type'              => 'payment',
                'date'              => $validated['date'],
                'contact_id'        => $validated['contact_id'],
                'party_name'        => $validated['party_name'],
                'payment_account_id'=> $validated['payment_account_id'],
                'amount'            => $validated['amount'],
                'payment_method'    => $validated['payment_method'],
                'cheque_number'     => $validated['cheque_number'],
                'reference'         => $validated['reference'],
                'narration'         => $validated['narration'],
                'status'            => 'approved',
                'invoice_id'        => $validated['invoice_id'],
                'invoice_type'      => $validated['invoice_id'] ? 'purchase' : null,
                'created_by'        => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $voucher->items()->create([
                    'account_id'  => $item['account_id'],
                    'description' => $item['description'] ?? null,
                    'amount'      => $item['amount'],
                ]);
            }

            // Generate journal entry: DR AP/Expense accounts, CR Bank/Cash
            $fy = AccFiscalYear::getForDate($validated['date']);

            if (!$fy) {
                throw new \Exception('No fiscal year found for the voucher date.');
            }

            $je = AccJournalEntry::create([
                'entry_number'  => AccJournalEntry::nextNumber(),
                'date'          => $validated['date'],
                'fiscal_year_id'=> $fy->id,
                'reference'     => $voucher->voucher_number,
                'narration'     => 'Payment Voucher: ' . $voucher->voucher_number . ($voucher->party_name ? ' - ' . $voucher->party_name : ''),
                'source_type'   => 'payment_voucher',
                'source_id'     => $voucher->id,
                'total_amount'  => $validated['amount'],
                'is_posted'     => false,
                'created_by'    => auth()->id(),
            ]);

            $jeLines = [];

            // DR: Each expense/AP account from items
            foreach ($validated['items'] as $item) {
                $jeLines[] = [
                    'account_id'  => $item['account_id'],
                    'debit'       => $item['amount'],
                    'credit'      => 0,
                    'description' => $item['description'] ?? 'Payment - ' . $voucher->voucher_number,
                ];
            }

            // CR: Bank/Cash account for total
            $jeLines[] = [
                'account_id'  => $validated['payment_account_id'],
                'debit'       => 0,
                'credit'      => $validated['amount'],
                'description' => 'Payment from ' . (AccAccount::find($validated['payment_account_id'])->name ?? 'Bank/Cash'),
            ];

            $je->lines()->createMany($jeLines);
            $je->post();

            $voucher->update(['journal_entry_id' => $je->id]);

            // Update linked purchase invoice amount_paid if applicable
            if ($validated['invoice_id']) {
                $purchaseInvoice = AccPurchaseInvoice::find($validated['invoice_id']);
                if ($purchaseInvoice) {
                    $purchaseInvoice->amount_paid += $validated['amount'];
                    $purchaseInvoice->balance_due = $purchaseInvoice->total - $purchaseInvoice->amount_paid;

                    if ($purchaseInvoice->balance_due <= 0) {
                        $purchaseInvoice->balance_due = 0;
                        $purchaseInvoice->status = 'paid';
                    } else {
                        $purchaseInvoice->status = 'partial';
                    }

                    $purchaseInvoice->save();
                }
            }

            DB::commit();

            return redirect()->route('accounting.payment-vouchers.show', $voucher)
                ->with('success', 'Payment voucher created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create payment voucher: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified payment voucher.
     */
    public function show(AccVoucher $paymentVoucher)
    {
        $paymentVoucher->load(['contact', 'paymentAccount', 'items.account', 'journalEntry.lines.account', 'creator']);

        return view('accounting.payment-vouchers.show', compact('paymentVoucher'));
    }

    /**
     * Show the form for editing a payment voucher.
     */
    public function edit(AccVoucher $paymentVoucher)
    {
        if ($paymentVoucher->status === 'cancelled') {
            return back()->with('error', 'Cancelled vouchers cannot be edited.');
        }

        $paymentVoucher->load('items');
        $vendors = AccContact::where('type', 'vendor')->where('is_active', true)->orderBy('name')->get();
        $expenseAccounts = AccAccount::active()->ofType('expense')->orderBy('code')->get();
        $bankCashAccounts = AccAccount::active()
            ->where(function ($q) {
                $q->where('sub_type', 'Bank')
                  ->orWhere('sub_type', 'Cash');
            })->orderBy('code')->get();

        return view('accounting.payment-vouchers.edit', compact('paymentVoucher', 'vendors', 'expenseAccounts', 'bankCashAccounts'));
    }

    /**
     * Update the specified payment voucher.
     */
    public function update(Request $request, AccVoucher $paymentVoucher)
    {
        if ($paymentVoucher->status === 'cancelled') {
            return back()->with('error', 'Cancelled vouchers cannot be updated.');
        }

        $validated = $request->validate([
            'date'               => 'required|date',
            'contact_id'         => 'nullable|exists:acc_contacts,id',
            'party_name'         => 'nullable|string|max:255',
            'payment_account_id' => 'required|exists:acc_accounts,id',
            'amount'             => 'required|numeric|min:0.01',
            'payment_method'     => 'nullable|string|in:cash,cheque,bank_transfer,online',
            'cheque_number'      => 'nullable|string|max:50',
            'reference'          => 'nullable|string|max:255',
            'narration'          => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.account_id' => 'required|exists:acc_accounts,id',
            'items.*.description'=> 'nullable|string|max:255',
            'items.*.amount'     => 'required|numeric|min:0.01',
        ]);

        $itemsTotal = collect($validated['items'])->sum('amount');
        if (round($itemsTotal, 2) !== round($validated['amount'], 2)) {
            return back()->withInput()->withErrors(['amount' => 'Voucher amount must equal the sum of line items.']);
        }

        DB::beginTransaction();

        try {
            $oldAmount = $paymentVoucher->amount;
            $oldInvoiceId = $paymentVoucher->invoice_id;

            $paymentVoucher->update([
                'date'              => $validated['date'],
                'contact_id'        => $validated['contact_id'],
                'party_name'        => $validated['party_name'],
                'payment_account_id'=> $validated['payment_account_id'],
                'amount'            => $validated['amount'],
                'payment_method'    => $validated['payment_method'],
                'cheque_number'     => $validated['cheque_number'],
                'reference'         => $validated['reference'],
                'narration'         => $validated['narration'],
            ]);

            // Recreate items
            $paymentVoucher->items()->delete();
            foreach ($validated['items'] as $item) {
                $paymentVoucher->items()->create([
                    'account_id'  => $item['account_id'],
                    'description' => $item['description'] ?? null,
                    'amount'      => $item['amount'],
                ]);
            }

            // Recreate journal entry
            if ($paymentVoucher->journal_entry_id) {
                $oldJE = $paymentVoucher->journalEntry;
                if ($oldJE) {
                    $oldJE->lines()->delete();
                    $oldJE->delete();
                }
            }

            $fy = AccFiscalYear::getForDate($validated['date']);
            if (!$fy) {
                throw new \Exception('No fiscal year found for the voucher date.');
            }

            $je = AccJournalEntry::create([
                'entry_number'  => AccJournalEntry::nextNumber(),
                'date'          => $validated['date'],
                'fiscal_year_id'=> $fy->id,
                'reference'     => $paymentVoucher->voucher_number,
                'narration'     => 'Payment Voucher (Updated): ' . $paymentVoucher->voucher_number,
                'source_type'   => 'payment_voucher',
                'source_id'     => $paymentVoucher->id,
                'total_amount'  => $validated['amount'],
                'is_posted'     => false,
                'created_by'    => auth()->id(),
            ]);

            $jeLines = [];
            foreach ($validated['items'] as $item) {
                $jeLines[] = [
                    'account_id'  => $item['account_id'],
                    'debit'       => $item['amount'],
                    'credit'      => 0,
                    'description' => $item['description'] ?? 'Payment - ' . $paymentVoucher->voucher_number,
                ];
            }
            $jeLines[] = [
                'account_id'  => $validated['payment_account_id'],
                'debit'       => 0,
                'credit'      => $validated['amount'],
                'description' => 'Payment from ' . (AccAccount::find($validated['payment_account_id'])->name ?? 'Bank/Cash'),
            ];

            $je->lines()->createMany($jeLines);
            $je->post();
            $paymentVoucher->update(['journal_entry_id' => $je->id]);

            // Update linked purchase invoice if amount changed
            if ($oldInvoiceId) {
                $purchaseInvoice = AccPurchaseInvoice::find($oldInvoiceId);
                if ($purchaseInvoice) {
                    $purchaseInvoice->amount_paid = $purchaseInvoice->amount_paid - $oldAmount + $validated['amount'];
                    $purchaseInvoice->balance_due = $purchaseInvoice->total - $purchaseInvoice->amount_paid;
                    if ($purchaseInvoice->balance_due <= 0) {
                        $purchaseInvoice->balance_due = 0;
                        $purchaseInvoice->status = 'paid';
                    } else {
                        $purchaseInvoice->status = 'partial';
                    }
                    $purchaseInvoice->save();
                }
            }

            DB::commit();

            return redirect()->route('accounting.payment-vouchers.show', $paymentVoucher)
                ->with('success', 'Payment voucher updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update payment voucher: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified payment voucher.
     */
    public function destroy(AccVoucher $paymentVoucher)
    {
        DB::beginTransaction();

        try {
            // Reverse the amount on linked purchase invoice
            if ($paymentVoucher->invoice_id && $paymentVoucher->invoice_type === 'purchase') {
                $purchaseInvoice = AccPurchaseInvoice::find($paymentVoucher->invoice_id);
                if ($purchaseInvoice) {
                    $purchaseInvoice->amount_paid -= $paymentVoucher->amount;
                    $purchaseInvoice->balance_due = $purchaseInvoice->total - $purchaseInvoice->amount_paid;
                    if ($purchaseInvoice->amount_paid <= 0) {
                        $purchaseInvoice->amount_paid = 0;
                        $purchaseInvoice->status = 'approved';
                    } else {
                        $purchaseInvoice->status = 'partial';
                    }
                    $purchaseInvoice->save();
                }
            }

            // Remove journal entry
            if ($paymentVoucher->journal_entry_id) {
                $je = $paymentVoucher->journalEntry;
                if ($je) {
                    $je->lines()->delete();
                    $je->delete();
                }
            }

            $paymentVoucher->items()->delete();
            $paymentVoucher->delete();

            DB::commit();

            return redirect()->route('accounting.payment-vouchers.index')
                ->with('success', 'Payment voucher deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete payment voucher: ' . $e->getMessage());
        }
    }
}
