<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccFiscalYear;
use App\Models\AccJournalEntry;
use App\Models\AccSalesInvoice;
use App\Models\AccVoucher;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptVoucherController extends Controller
{
    /**
     * Display all receipt vouchers.
     */
    public function index(Request $request)
    {
        $query = AccVoucher::receipts()->with(['client', 'paymentAccount', 'creator'])->latest('date');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('voucher_number', 'like', "%{$search}%")
                  ->orWhere('party_name', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($q) => $q->where('name', 'like', "%{$search}%"));
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

        return view('accounting.receipt-vouchers.index', compact('vouchers'));
    }

    /**
     * Show the form for creating a new receipt voucher.
     */
    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $revenueAccounts = AccAccount::active()->ofType('revenue')->orderBy('code')->get();
        $bankCashAccounts = AccAccount::active()
            ->where(function ($q) {
                $q->where('sub_type', 'Bank')
                  ->orWhere('sub_type', 'Cash');
            })->orderBy('code')->get();
        $arAccount = AccAccount::find(AccAccount::setting('accounts_receivable_id'));
        $unpaidInvoices = AccSalesInvoice::where('balance_due', '>', 0)->with('client')->latest('date')->get();
        $nextNumber = AccVoucher::nextReceiptNumber();

        return view('accounting.receipt-vouchers.create', compact(
            'clients', 'revenueAccounts', 'bankCashAccounts', 'arAccount', 'unpaidInvoices', 'nextNumber'
        ));
    }

    /**
     * Store a newly created receipt voucher.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'               => 'required|date',
            'client_id'          => 'nullable|exists:clients,id',
            'party_name'         => 'nullable|string|max:255',
            'payment_account_id' => 'required|exists:acc_accounts,id',
            'amount'             => 'required|numeric|min:0.01',
            'payment_method'     => 'nullable|string|in:cash,cheque,bank_transfer,online',
            'cheque_number'      => 'nullable|string|max:50',
            'reference'          => 'nullable|string|max:255',
            'narration'          => 'nullable|string',
            'invoice_id'         => 'nullable|exists:acc_sales_invoices,id',
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
                'voucher_number'    => AccVoucher::nextReceiptNumber(),
                'type'              => 'receipt',
                'date'              => $validated['date'],
                'client_id'         => $validated['client_id'],
                'party_name'        => $validated['party_name'],
                'payment_account_id'=> $validated['payment_account_id'],
                'amount'            => $validated['amount'],
                'payment_method'    => $validated['payment_method'],
                'cheque_number'     => $validated['cheque_number'],
                'reference'         => $validated['reference'],
                'narration'         => $validated['narration'],
                'status'            => 'approved',
                'invoice_id'        => $validated['invoice_id'],
                'invoice_type'      => $validated['invoice_id'] ? 'sales' : null,
                'created_by'        => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $voucher->items()->create([
                    'account_id'  => $item['account_id'],
                    'description' => $item['description'] ?? null,
                    'amount'      => $item['amount'],
                ]);
            }

            // Generate journal entry: DR Bank/Cash, CR AR/Revenue
            $fy = AccFiscalYear::getForDate($validated['date']);

            if (!$fy) {
                throw new \Exception('No fiscal year found for the voucher date.');
            }

            $je = AccJournalEntry::create([
                'entry_number'  => AccJournalEntry::nextNumber(),
                'date'          => $validated['date'],
                'fiscal_year_id'=> $fy->id,
                'reference'     => $voucher->voucher_number,
                'narration'     => 'Receipt Voucher: ' . $voucher->voucher_number . ($voucher->party_name ? ' - ' . $voucher->party_name : ''),
                'source_type'   => 'receipt_voucher',
                'source_id'     => $voucher->id,
                'total_amount'  => $validated['amount'],
                'is_posted'     => false,
                'created_by'    => auth()->id(),
            ]);

            $jeLines = [];

            // DR: Bank/Cash account for total
            $jeLines[] = [
                'account_id'  => $validated['payment_account_id'],
                'debit'       => $validated['amount'],
                'credit'      => 0,
                'description' => 'Receipt into ' . (AccAccount::find($validated['payment_account_id'])->name ?? 'Bank/Cash'),
            ];

            // CR: Each AR/Revenue account from items
            foreach ($validated['items'] as $item) {
                $jeLines[] = [
                    'account_id'  => $item['account_id'],
                    'debit'       => 0,
                    'credit'      => $item['amount'],
                    'description' => $item['description'] ?? 'Receipt - ' . $voucher->voucher_number,
                ];
            }

            $je->lines()->createMany($jeLines);
            $je->post();

            $voucher->update(['journal_entry_id' => $je->id]);

            // Update linked sales invoice amount_paid if applicable
            if ($validated['invoice_id']) {
                $salesInvoice = AccSalesInvoice::find($validated['invoice_id']);
                if ($salesInvoice) {
                    $salesInvoice->amount_paid += $validated['amount'];
                    $salesInvoice->balance_due = $salesInvoice->total - $salesInvoice->amount_paid;

                    if ($salesInvoice->balance_due <= 0) {
                        $salesInvoice->balance_due = 0;
                        $salesInvoice->status = 'paid';
                    } else {
                        $salesInvoice->status = 'partial';
                    }

                    $salesInvoice->save();
                }
            }

            DB::commit();

            return redirect()->route('accounting.receipt-vouchers.show', $voucher)
                ->with('success', 'Receipt voucher created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create receipt voucher: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified receipt voucher.
     */
    public function show(AccVoucher $receiptVoucher)
    {
        $receiptVoucher->load(['client', 'paymentAccount', 'items.account', 'journalEntry.lines.account', 'creator']);

        return view('accounting.receipt-vouchers.show', compact('receiptVoucher'));
    }

    /**
     * Show the form for editing a receipt voucher.
     */
    public function edit(AccVoucher $receiptVoucher)
    {
        if ($receiptVoucher->status === 'cancelled') {
            return back()->with('error', 'Cancelled vouchers cannot be edited.');
        }

        $receiptVoucher->load('items');
        $clients = Client::orderBy('name')->get();
        $revenueAccounts = AccAccount::active()->ofType('revenue')->orderBy('code')->get();
        $bankCashAccounts = AccAccount::active()
            ->where(function ($q) {
                $q->where('sub_type', 'Bank')
                  ->orWhere('sub_type', 'Cash');
            })->orderBy('code')->get();

        return view('accounting.receipt-vouchers.edit', compact('receiptVoucher', 'clients', 'revenueAccounts', 'bankCashAccounts'));
    }

    /**
     * Update the specified receipt voucher.
     */
    public function update(Request $request, AccVoucher $receiptVoucher)
    {
        if ($receiptVoucher->status === 'cancelled') {
            return back()->with('error', 'Cancelled vouchers cannot be updated.');
        }

        $validated = $request->validate([
            'date'               => 'required|date',
            'client_id'          => 'nullable|exists:clients,id',
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
            $oldAmount = $receiptVoucher->amount;
            $oldInvoiceId = $receiptVoucher->invoice_id;

            $receiptVoucher->update([
                'date'              => $validated['date'],
                'client_id'         => $validated['client_id'],
                'party_name'        => $validated['party_name'],
                'payment_account_id'=> $validated['payment_account_id'],
                'amount'            => $validated['amount'],
                'payment_method'    => $validated['payment_method'],
                'cheque_number'     => $validated['cheque_number'],
                'reference'         => $validated['reference'],
                'narration'         => $validated['narration'],
            ]);

            // Recreate items
            $receiptVoucher->items()->delete();
            foreach ($validated['items'] as $item) {
                $receiptVoucher->items()->create([
                    'account_id'  => $item['account_id'],
                    'description' => $item['description'] ?? null,
                    'amount'      => $item['amount'],
                ]);
            }

            // Recreate journal entry
            if ($receiptVoucher->journal_entry_id) {
                $oldJE = $receiptVoucher->journalEntry;
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
                'reference'     => $receiptVoucher->voucher_number,
                'narration'     => 'Receipt Voucher (Updated): ' . $receiptVoucher->voucher_number,
                'source_type'   => 'receipt_voucher',
                'source_id'     => $receiptVoucher->id,
                'total_amount'  => $validated['amount'],
                'is_posted'     => false,
                'created_by'    => auth()->id(),
            ]);

            $jeLines = [];

            $jeLines[] = [
                'account_id'  => $validated['payment_account_id'],
                'debit'       => $validated['amount'],
                'credit'      => 0,
                'description' => 'Receipt into ' . (AccAccount::find($validated['payment_account_id'])->name ?? 'Bank/Cash'),
            ];

            foreach ($validated['items'] as $item) {
                $jeLines[] = [
                    'account_id'  => $item['account_id'],
                    'debit'       => 0,
                    'credit'      => $item['amount'],
                    'description' => $item['description'] ?? 'Receipt - ' . $receiptVoucher->voucher_number,
                ];
            }

            $je->lines()->createMany($jeLines);
            $je->post();
            $receiptVoucher->update(['journal_entry_id' => $je->id]);

            // Update linked sales invoice if amount changed
            if ($oldInvoiceId) {
                $salesInvoice = AccSalesInvoice::find($oldInvoiceId);
                if ($salesInvoice) {
                    $salesInvoice->amount_paid = $salesInvoice->amount_paid - $oldAmount + $validated['amount'];
                    $salesInvoice->balance_due = $salesInvoice->total - $salesInvoice->amount_paid;
                    if ($salesInvoice->balance_due <= 0) {
                        $salesInvoice->balance_due = 0;
                        $salesInvoice->status = 'paid';
                    } else {
                        $salesInvoice->status = 'partial';
                    }
                    $salesInvoice->save();
                }
            }

            DB::commit();

            return redirect()->route('accounting.receipt-vouchers.show', $receiptVoucher)
                ->with('success', 'Receipt voucher updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update receipt voucher: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified receipt voucher.
     */
    public function destroy(AccVoucher $receiptVoucher)
    {
        DB::beginTransaction();

        try {
            // Reverse the amount on linked sales invoice
            if ($receiptVoucher->invoice_id && $receiptVoucher->invoice_type === 'sales') {
                $salesInvoice = AccSalesInvoice::find($receiptVoucher->invoice_id);
                if ($salesInvoice) {
                    $salesInvoice->amount_paid -= $receiptVoucher->amount;
                    $salesInvoice->balance_due = $salesInvoice->total - $salesInvoice->amount_paid;
                    if ($salesInvoice->amount_paid <= 0) {
                        $salesInvoice->amount_paid = 0;
                        $salesInvoice->status = 'sent';
                    } else {
                        $salesInvoice->status = 'partial';
                    }
                    $salesInvoice->save();
                }
            }

            // Remove journal entry
            if ($receiptVoucher->journal_entry_id) {
                $je = $receiptVoucher->journalEntry;
                if ($je) {
                    $je->lines()->delete();
                    $je->delete();
                }
            }

            $receiptVoucher->items()->delete();
            $receiptVoucher->delete();

            DB::commit();

            return redirect()->route('accounting.receipt-vouchers.index')
                ->with('success', 'Receipt voucher deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete receipt voucher: ' . $e->getMessage());
        }
    }
}
