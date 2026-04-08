<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccFiscalYear;
use App\Models\AccJournalEntry;
use App\Models\AccSalesInvoice;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    /**
     * Display all sales invoices.
     */
    public function index(Request $request)
    {
        $query = AccSalesInvoice::with(['client', 'creator'])->latest('date');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }

        $invoices = $query->paginate(25)->withQueryString();
        $clients = Client::orderBy('name')->get();

        return view('accounting.sales-invoices.index', compact('invoices', 'clients'));
    }

    /**
     * Show the form for creating a new sales invoice.
     */
    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $revenueAccounts = AccAccount::active()->ofType('revenue')->orderBy('code')->get();
        $nextNumber = AccSalesInvoice::nextNumber();

        return view('accounting.sales-invoices.create', compact('clients', 'revenueAccounts', 'nextNumber'));
    }

    /**
     * Store a newly created sales invoice.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'              => 'required|exists:clients,id',
            'date'                   => 'required|date',
            'due_date'               => 'required|date|after_or_equal:date',
            'reference'              => 'nullable|string|max:255',
            'discount_amount'        => 'nullable|numeric|min:0',
            'notes'                  => 'nullable|string',
            'terms'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.account_id'     => 'required|exists:acc_accounts,id',
            'items.*.description'    => 'required|string|max:255',
            'items.*.quantity'       => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.tax_rate'       => 'nullable|numeric|min:0|max:100',
            'items.*.discount'       => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Calculate item amounts
            $items = collect($validated['items'])->map(function ($item) {
                $amount = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $taxAmount = $amount * (($item['tax_rate'] ?? 0) / 100);
                return array_merge($item, [
                    'amount'     => round($amount, 2),
                    'tax_amount' => round($taxAmount, 2),
                ]);
            });

            $subtotal = $items->sum('amount');
            $taxAmount = $items->sum('tax_amount');
            $discountAmount = $validated['discount_amount'] ?? 0;
            $total = $subtotal + $taxAmount - $discountAmount;

            $invoice = AccSalesInvoice::create([
                'invoice_number' => AccSalesInvoice::nextNumber(),
                'client_id'      => $validated['client_id'],
                'date'           => $validated['date'],
                'due_date'       => $validated['due_date'],
                'reference'      => $validated['reference'],
                'subtotal'       => $subtotal,
                'tax_amount'     => $taxAmount,
                'discount_amount'=> $discountAmount,
                'total'          => $total,
                'amount_paid'    => 0,
                'balance_due'    => $total,
                'status'         => 'draft',
                'notes'          => $validated['notes'],
                'terms'          => $validated['terms'],
                'created_by'     => auth()->id(),
            ]);

            foreach ($items as $item) {
                $invoice->items()->create([
                    'account_id'  => $item['account_id'],
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'tax_rate'    => $item['tax_rate'] ?? 0,
                    'tax_amount'  => $item['tax_amount'],
                    'discount'    => $item['discount'] ?? 0,
                    'amount'      => $item['amount'],
                ]);
            }

            // Generate journal entry
            $fy = AccFiscalYear::getForDate($validated['date']);

            if (!$fy) {
                throw new \Exception('No fiscal year found for the invoice date.');
            }

            $arAccountId = AccAccount::setting('accounts_receivable_id');
            $taxAccountId = AccAccount::setting('sales_tax_id');

            if (!$arAccountId) {
                throw new \Exception('Accounts Receivable default account is not configured.');
            }

            $je = AccJournalEntry::create([
                'entry_number'  => AccJournalEntry::nextNumber(),
                'date'          => $validated['date'],
                'fiscal_year_id'=> $fy->id,
                'reference'     => $invoice->invoice_number,
                'narration'     => 'Sales Invoice: ' . $invoice->invoice_number,
                'source_type'   => 'sales_invoice',
                'source_id'     => $invoice->id,
                'total_amount'  => $total,
                'is_posted'     => false,
                'created_by'    => auth()->id(),
            ]);

            $jeLines = [];

            // DR: Accounts Receivable for total
            $jeLines[] = [
                'account_id'  => $arAccountId,
                'debit'       => $total,
                'credit'      => 0,
                'description' => 'Accounts Receivable - ' . $invoice->invoice_number,
            ];

            // CR: Revenue accounts for each item (grouped by account)
            $revenueByAccount = $items->groupBy('account_id');
            foreach ($revenueByAccount as $accountId => $accountItems) {
                $jeLines[] = [
                    'account_id'  => $accountId,
                    'debit'       => 0,
                    'credit'      => $accountItems->sum('amount'),
                    'description' => 'Revenue - ' . $invoice->invoice_number,
                ];
            }

            // CR: Sales Tax if applicable
            if ($taxAmount > 0 && $taxAccountId) {
                $jeLines[] = [
                    'account_id'  => $taxAccountId,
                    'debit'       => 0,
                    'credit'      => $taxAmount,
                    'description' => 'Sales Tax - ' . $invoice->invoice_number,
                ];
            }

            // DR: Discount if applicable
            if ($discountAmount > 0) {
                $discountAccountId = AccAccount::setting('sales_discount_id');
                if ($discountAccountId) {
                    $jeLines[] = [
                        'account_id'  => $discountAccountId,
                        'debit'       => $discountAmount,
                        'credit'      => 0,
                        'description' => 'Sales Discount - ' . $invoice->invoice_number,
                    ];
                }
            }

            $je->lines()->createMany($jeLines);
            $je->post();

            $invoice->update(['journal_entry_id' => $je->id]);

            DB::commit();

            return redirect()->route('accounting.sales-invoices.show', $invoice)
                ->with('success', 'Sales invoice created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create invoice: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified sales invoice.
     */
    public function show(AccSalesInvoice $salesInvoice)
    {
        $salesInvoice->load(['client', 'items.account', 'journalEntry.lines.account', 'creator', 'receipts']);

        return view('accounting.sales-invoices.show', compact('salesInvoice'));
    }

    /**
     * Show the form for editing (only if draft).
     */
    public function edit(AccSalesInvoice $salesInvoice)
    {
        if ($salesInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be edited.');
        }

        $salesInvoice->load('items');
        $clients = Client::orderBy('name')->get();
        $revenueAccounts = AccAccount::active()->ofType('revenue')->orderBy('code')->get();

        return view('accounting.sales-invoices.edit', compact('salesInvoice', 'clients', 'revenueAccounts'));
    }

    /**
     * Update the specified sales invoice.
     */
    public function update(Request $request, AccSalesInvoice $salesInvoice)
    {
        if ($salesInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be updated.');
        }

        $validated = $request->validate([
            'client_id'              => 'required|exists:clients,id',
            'date'                   => 'required|date',
            'due_date'               => 'required|date|after_or_equal:date',
            'reference'              => 'nullable|string|max:255',
            'discount_amount'        => 'nullable|numeric|min:0',
            'notes'                  => 'nullable|string',
            'terms'                  => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.account_id'     => 'required|exists:acc_accounts,id',
            'items.*.description'    => 'required|string|max:255',
            'items.*.quantity'       => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.tax_rate'       => 'nullable|numeric|min:0|max:100',
            'items.*.discount'       => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Delete old items
            $salesInvoice->items()->delete();

            // Recalculate items
            $items = collect($validated['items'])->map(function ($item) {
                $amount = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $taxAmount = $amount * (($item['tax_rate'] ?? 0) / 100);
                return array_merge($item, [
                    'amount'     => round($amount, 2),
                    'tax_amount' => round($taxAmount, 2),
                ]);
            });

            $subtotal = $items->sum('amount');
            $taxAmount = $items->sum('tax_amount');
            $discountAmount = $validated['discount_amount'] ?? 0;
            $total = $subtotal + $taxAmount - $discountAmount;

            $salesInvoice->update([
                'client_id'      => $validated['client_id'],
                'date'           => $validated['date'],
                'due_date'       => $validated['due_date'],
                'reference'      => $validated['reference'],
                'subtotal'       => $subtotal,
                'tax_amount'     => $taxAmount,
                'discount_amount'=> $discountAmount,
                'total'          => $total,
                'balance_due'    => $total - $salesInvoice->amount_paid,
                'notes'          => $validated['notes'],
                'terms'          => $validated['terms'],
            ]);

            foreach ($items as $item) {
                $salesInvoice->items()->create([
                    'account_id'  => $item['account_id'],
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'tax_rate'    => $item['tax_rate'] ?? 0,
                    'tax_amount'  => $item['tax_amount'],
                    'discount'    => $item['discount'] ?? 0,
                    'amount'      => $item['amount'],
                ]);
            }

            // Reverse old JE and create new one
            if ($salesInvoice->journal_entry_id) {
                $oldJE = $salesInvoice->journalEntry;
                if ($oldJE && $oldJE->is_posted) {
                    $oldJE->update(['is_reversed' => true]);
                }
                $oldJE?->lines()->delete();
                $oldJE?->delete();
            }

            $fy = AccFiscalYear::getForDate($validated['date']);
            if (!$fy) {
                throw new \Exception('No fiscal year found for the invoice date.');
            }

            $arAccountId = AccAccount::setting('accounts_receivable_id');
            $taxAccountId = AccAccount::setting('sales_tax_id');

            $je = AccJournalEntry::create([
                'entry_number'  => AccJournalEntry::nextNumber(),
                'date'          => $validated['date'],
                'fiscal_year_id'=> $fy->id,
                'reference'     => $salesInvoice->invoice_number,
                'narration'     => 'Sales Invoice (Updated): ' . $salesInvoice->invoice_number,
                'source_type'   => 'sales_invoice',
                'source_id'     => $salesInvoice->id,
                'total_amount'  => $total,
                'is_posted'     => false,
                'created_by'    => auth()->id(),
            ]);

            $jeLines = [];

            $jeLines[] = [
                'account_id'  => $arAccountId,
                'debit'       => $total,
                'credit'      => 0,
                'description' => 'Accounts Receivable - ' . $salesInvoice->invoice_number,
            ];

            $revenueByAccount = $items->groupBy('account_id');
            foreach ($revenueByAccount as $accountId => $accountItems) {
                $jeLines[] = [
                    'account_id'  => $accountId,
                    'debit'       => 0,
                    'credit'      => $accountItems->sum('amount'),
                    'description' => 'Revenue - ' . $salesInvoice->invoice_number,
                ];
            }

            if ($taxAmount > 0 && $taxAccountId) {
                $jeLines[] = [
                    'account_id'  => $taxAccountId,
                    'debit'       => 0,
                    'credit'      => $taxAmount,
                    'description' => 'Sales Tax - ' . $salesInvoice->invoice_number,
                ];
            }

            if ($discountAmount > 0) {
                $discountAccountId = AccAccount::setting('sales_discount_id');
                if ($discountAccountId) {
                    $jeLines[] = [
                        'account_id'  => $discountAccountId,
                        'debit'       => $discountAmount,
                        'credit'      => 0,
                        'description' => 'Sales Discount - ' . $salesInvoice->invoice_number,
                    ];
                }
            }

            $je->lines()->createMany($jeLines);
            $je->post();

            $salesInvoice->update(['journal_entry_id' => $je->id]);

            DB::commit();

            return redirect()->route('accounting.sales-invoices.show', $salesInvoice)
                ->with('success', 'Sales invoice updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update invoice: ' . $e->getMessage());
        }
    }

    /**
     * Mark the invoice as sent.
     */
    public function markSent(AccSalesInvoice $salesInvoice)
    {
        if ($salesInvoice->status === 'draft') {
            $salesInvoice->update(['status' => 'sent']);
            return back()->with('success', 'Invoice marked as sent.');
        }

        return back()->with('error', 'Only draft invoices can be marked as sent.');
    }

    /**
     * Remove the specified invoice (only if draft and no payments).
     */
    public function destroy(AccSalesInvoice $salesInvoice)
    {
        if ($salesInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be deleted.');
        }

        if ($salesInvoice->amount_paid > 0) {
            return back()->with('error', 'Cannot delete an invoice with payments applied.');
        }

        DB::beginTransaction();

        try {
            // Remove associated journal entry
            if ($salesInvoice->journal_entry_id) {
                $je = $salesInvoice->journalEntry;
                if ($je) {
                    $je->lines()->delete();
                    $je->delete();
                }
            }

            $salesInvoice->items()->delete();
            $salesInvoice->delete();

            DB::commit();

            return redirect()->route('accounting.sales-invoices.index')
                ->with('success', 'Sales invoice deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete invoice: ' . $e->getMessage());
        }
    }
}
