<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccContact;
use App\Models\AccFiscalYear;
use App\Models\AccJournalEntry;
use App\Models\AccPurchaseInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    /**
     * Display all purchase invoices.
     */
    public function index(Request $request)
    {
        $query = AccPurchaseInvoice::with(['contact', 'creator'])->latest('date');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                  ->orWhere('vendor_name', 'like', "%{$search}%")
                  ->orWhere('vendor_invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('contact', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->contact_id);
        }

        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }

        $invoices = $query->paginate(25)->withQueryString();
        $contacts = AccContact::where('type', 'vendor')->orderBy('name')->get();

        return view('accounting.purchase-invoices.index', compact('invoices', 'contacts'));
    }

    /**
     * Show the form for creating a new purchase invoice.
     */
    public function create()
    {
        $contacts = AccContact::where('type', 'vendor')->where('is_active', true)->orderBy('name')->get();
        $expenseAccounts = AccAccount::active()->ofType('expense')->orderBy('code')->get();
        $nextNumber = AccPurchaseInvoice::nextNumber();

        return view('accounting.purchase-invoices.create', compact('contacts', 'expenseAccounts', 'nextNumber'));
    }

    /**
     * Store a newly created purchase invoice.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_id'             => 'required|exists:acc_contacts,id',
            'vendor_invoice_no'      => 'nullable|string|max:255',
            'date'                   => 'required|date',
            'due_date'               => 'required|date|after_or_equal:date',
            'notes'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.account_id'     => 'required|exists:acc_accounts,id',
            'items.*.description'    => 'required|string|max:255',
            'items.*.quantity'       => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.tax_rate'       => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {
            $contact = AccContact::findOrFail($validated['contact_id']);

            // Calculate item amounts
            $items = collect($validated['items'])->map(function ($item) {
                $amount = $item['quantity'] * $item['unit_price'];
                $taxAmount = $amount * (($item['tax_rate'] ?? 0) / 100);
                return array_merge($item, [
                    'amount'     => round($amount, 2),
                    'tax_amount' => round($taxAmount, 2),
                ]);
            });

            $subtotal = $items->sum('amount');
            $taxAmount = $items->sum('tax_amount');
            $total = $subtotal + $taxAmount;

            $invoice = AccPurchaseInvoice::create([
                'bill_number'     => AccPurchaseInvoice::nextNumber(),
                'contact_id'      => $validated['contact_id'],
                'vendor_name'     => $contact->name,
                'vendor_invoice_no'=> $validated['vendor_invoice_no'],
                'date'            => $validated['date'],
                'due_date'        => $validated['due_date'],
                'subtotal'        => $subtotal,
                'tax_amount'      => $taxAmount,
                'total'           => $total,
                'amount_paid'     => 0,
                'balance_due'     => $total,
                'status'          => 'draft',
                'notes'           => $validated['notes'],
                'created_by'      => auth()->id(),
            ]);

            foreach ($items as $item) {
                $invoice->items()->create([
                    'account_id'  => $item['account_id'],
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'tax_rate'    => $item['tax_rate'] ?? 0,
                    'tax_amount'  => $item['tax_amount'],
                    'amount'      => $item['amount'],
                ]);
            }

            // Generate journal entry: DR Expense accounts, CR Accounts Payable
            $fy = AccFiscalYear::getForDate($validated['date']);

            if (!$fy) {
                throw new \Exception('No fiscal year found for the invoice date.');
            }

            $apAccountId = AccAccount::setting('accounts_payable_id');
            $inputTaxAccountId = AccAccount::setting('purchase_tax_id');

            if (!$apAccountId) {
                throw new \Exception('Accounts Payable default account is not configured.');
            }

            $je = AccJournalEntry::create([
                'entry_number'  => AccJournalEntry::nextNumber(),
                'date'          => $validated['date'],
                'fiscal_year_id'=> $fy->id,
                'reference'     => $invoice->bill_number,
                'narration'     => 'Purchase Invoice: ' . $invoice->bill_number . ' (' . $contact->name . ')',
                'source_type'   => 'purchase_invoice',
                'source_id'     => $invoice->id,
                'total_amount'  => $total,
                'is_posted'     => false,
                'created_by'    => auth()->id(),
            ]);

            $jeLines = [];

            // DR: Expense accounts for each item (grouped by account)
            $expenseByAccount = $items->groupBy('account_id');
            foreach ($expenseByAccount as $accountId => $accountItems) {
                $jeLines[] = [
                    'account_id'  => $accountId,
                    'debit'       => $accountItems->sum('amount'),
                    'credit'      => 0,
                    'description' => 'Expense - ' . $invoice->bill_number,
                ];
            }

            // DR: Input Tax if applicable
            if ($taxAmount > 0 && $inputTaxAccountId) {
                $jeLines[] = [
                    'account_id'  => $inputTaxAccountId,
                    'debit'       => $taxAmount,
                    'credit'      => 0,
                    'description' => 'Input Tax - ' . $invoice->bill_number,
                ];
            }

            // CR: Accounts Payable for total
            $jeLines[] = [
                'account_id'  => $apAccountId,
                'debit'       => 0,
                'credit'      => $total,
                'description' => 'Accounts Payable - ' . $invoice->bill_number,
            ];

            $je->lines()->createMany($jeLines);
            $je->post();

            $invoice->update(['journal_entry_id' => $je->id]);

            DB::commit();

            return redirect()->route('accounting.purchase-invoices.show', $invoice)
                ->with('success', 'Purchase invoice created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create purchase invoice: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified purchase invoice.
     */
    public function show(AccPurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load(['contact', 'items.account', 'journalEntry.lines.account', 'creator', 'payments']);

        return view('accounting.purchase-invoices.show', compact('purchaseInvoice'));
    }

    /**
     * Show the form for editing (only if draft).
     */
    public function edit(AccPurchaseInvoice $purchaseInvoice)
    {
        if ($purchaseInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft purchase invoices can be edited.');
        }

        $purchaseInvoice->load('items');
        $contacts = AccContact::where('type', 'vendor')->where('is_active', true)->orderBy('name')->get();
        $expenseAccounts = AccAccount::active()->ofType('expense')->orderBy('code')->get();

        return view('accounting.purchase-invoices.edit', compact('purchaseInvoice', 'contacts', 'expenseAccounts'));
    }

    /**
     * Update the specified purchase invoice.
     */
    public function update(Request $request, AccPurchaseInvoice $purchaseInvoice)
    {
        if ($purchaseInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft purchase invoices can be updated.');
        }

        $validated = $request->validate([
            'contact_id'             => 'required|exists:acc_contacts,id',
            'vendor_invoice_no'      => 'nullable|string|max:255',
            'date'                   => 'required|date',
            'due_date'               => 'required|date|after_or_equal:date',
            'notes'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.account_id'     => 'required|exists:acc_accounts,id',
            'items.*.description'    => 'required|string|max:255',
            'items.*.quantity'       => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.tax_rate'       => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {
            $contact = AccContact::findOrFail($validated['contact_id']);

            // Delete old items
            $purchaseInvoice->items()->delete();

            $items = collect($validated['items'])->map(function ($item) {
                $amount = $item['quantity'] * $item['unit_price'];
                $taxAmount = $amount * (($item['tax_rate'] ?? 0) / 100);
                return array_merge($item, [
                    'amount'     => round($amount, 2),
                    'tax_amount' => round($taxAmount, 2),
                ]);
            });

            $subtotal = $items->sum('amount');
            $taxAmount = $items->sum('tax_amount');
            $total = $subtotal + $taxAmount;

            $purchaseInvoice->update([
                'contact_id'       => $validated['contact_id'],
                'vendor_name'      => $contact->name,
                'vendor_invoice_no'=> $validated['vendor_invoice_no'],
                'date'             => $validated['date'],
                'due_date'         => $validated['due_date'],
                'subtotal'         => $subtotal,
                'tax_amount'       => $taxAmount,
                'total'            => $total,
                'balance_due'      => $total - $purchaseInvoice->amount_paid,
                'notes'            => $validated['notes'],
            ]);

            foreach ($items as $item) {
                $purchaseInvoice->items()->create([
                    'account_id'  => $item['account_id'],
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'tax_rate'    => $item['tax_rate'] ?? 0,
                    'tax_amount'  => $item['tax_amount'],
                    'amount'      => $item['amount'],
                ]);
            }

            // Reverse old JE and create new one
            if ($purchaseInvoice->journal_entry_id) {
                $oldJE = $purchaseInvoice->journalEntry;
                if ($oldJE) {
                    $oldJE->lines()->delete();
                    $oldJE->delete();
                }
            }

            $fy = AccFiscalYear::getForDate($validated['date']);
            if (!$fy) {
                throw new \Exception('No fiscal year found for the invoice date.');
            }

            $apAccountId = AccAccount::setting('accounts_payable_id');
            $inputTaxAccountId = AccAccount::setting('purchase_tax_id');

            $je = AccJournalEntry::create([
                'entry_number'  => AccJournalEntry::nextNumber(),
                'date'          => $validated['date'],
                'fiscal_year_id'=> $fy->id,
                'reference'     => $purchaseInvoice->bill_number,
                'narration'     => 'Purchase Invoice (Updated): ' . $purchaseInvoice->bill_number,
                'source_type'   => 'purchase_invoice',
                'source_id'     => $purchaseInvoice->id,
                'total_amount'  => $total,
                'is_posted'     => false,
                'created_by'    => auth()->id(),
            ]);

            $jeLines = [];

            $expenseByAccount = $items->groupBy('account_id');
            foreach ($expenseByAccount as $accountId => $accountItems) {
                $jeLines[] = [
                    'account_id'  => $accountId,
                    'debit'       => $accountItems->sum('amount'),
                    'credit'      => 0,
                    'description' => 'Expense - ' . $purchaseInvoice->bill_number,
                ];
            }

            if ($taxAmount > 0 && $inputTaxAccountId) {
                $jeLines[] = [
                    'account_id'  => $inputTaxAccountId,
                    'debit'       => $taxAmount,
                    'credit'      => 0,
                    'description' => 'Input Tax - ' . $purchaseInvoice->bill_number,
                ];
            }

            $jeLines[] = [
                'account_id'  => $apAccountId,
                'debit'       => 0,
                'credit'      => $total,
                'description' => 'Accounts Payable - ' . $purchaseInvoice->bill_number,
            ];

            $je->lines()->createMany($jeLines);
            $je->post();

            $purchaseInvoice->update(['journal_entry_id' => $je->id]);

            DB::commit();

            return redirect()->route('accounting.purchase-invoices.show', $purchaseInvoice)
                ->with('success', 'Purchase invoice updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update purchase invoice: ' . $e->getMessage());
        }
    }

    /**
     * Mark the purchase invoice as approved/received.
     */
    public function markApproved(AccPurchaseInvoice $purchaseInvoice)
    {
        if ($purchaseInvoice->status === 'draft') {
            $purchaseInvoice->update(['status' => 'approved']);
            return back()->with('success', 'Purchase invoice marked as approved.');
        }

        return back()->with('error', 'Only draft purchase invoices can be approved.');
    }

    /**
     * Remove the specified purchase invoice (only if draft and no payments).
     */
    public function destroy(AccPurchaseInvoice $purchaseInvoice)
    {
        if ($purchaseInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft purchase invoices can be deleted.');
        }

        if ($purchaseInvoice->amount_paid > 0) {
            return back()->with('error', 'Cannot delete a purchase invoice with payments applied.');
        }

        DB::beginTransaction();

        try {
            if ($purchaseInvoice->journal_entry_id) {
                $je = $purchaseInvoice->journalEntry;
                if ($je) {
                    $je->lines()->delete();
                    $je->delete();
                }
            }

            $purchaseInvoice->items()->delete();
            $purchaseInvoice->delete();

            DB::commit();

            return redirect()->route('accounting.purchase-invoices.index')
                ->with('success', 'Purchase invoice deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete purchase invoice: ' . $e->getMessage());
        }
    }
}
