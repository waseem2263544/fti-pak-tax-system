<?php

namespace App\Services\Accounting;

use App\Models\AccAccount;
use App\Models\AccFiscalYear;
use App\Models\AccJournalEntry;
use App\Models\AccSalesInvoice;

/**
 * Creates a sales invoice with its items and a posted, balanced journal entry.
 * Shared by SalesInvoiceController@store and recurring-invoice generation so the
 * double-entry logic lives in exactly one place. Caller is responsible for the
 * surrounding DB transaction.
 *
 * $data keys: client_id, date, due_date, reference, discount_amount, notes, terms,
 *             created_by, items[] (account_id, description, quantity, unit_price, tax_rate, discount)
 */
class SalesInvoicePoster
{
    public static function create(array $data): AccSalesInvoice
    {
        $items = collect($data['items'])->map(function ($item) {
            $amount = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
            $taxAmount = $amount * (($item['tax_rate'] ?? 0) / 100);
            return array_merge($item, [
                'amount'     => round($amount, 2),
                'tax_amount' => round($taxAmount, 2),
            ]);
        });

        $subtotal = $items->sum('amount');
        $taxAmount = $items->sum('tax_amount');
        $discountAmount = $data['discount_amount'] ?? 0;
        $total = $subtotal + $taxAmount - $discountAmount;

        $invoice = AccSalesInvoice::create([
            'invoice_number'  => AccSalesInvoice::nextNumber(),
            'client_id'       => $data['client_id'],
            'date'            => $data['date'],
            'due_date'        => $data['due_date'],
            'reference'       => $data['reference'] ?? null,
            'subtotal'        => $subtotal,
            'tax_amount'      => $taxAmount,
            'discount_amount' => $discountAmount,
            'total'           => $total,
            'amount_paid'     => 0,
            'balance_due'     => $total,
            'status'          => 'draft',
            'notes'           => $data['notes'] ?? null,
            'terms'           => $data['terms'] ?? null,
            'created_by'      => $data['created_by'] ?? auth()->id(),
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

        $fy = AccFiscalYear::getForDate($data['date']);
        if (!$fy) {
            throw new \Exception('No fiscal year found for the invoice date.');
        }

        $arAccountId = AccAccount::resolveId('accounts_receivable');
        $taxAccountId = AccAccount::resolveId('sales_tax');
        if (!$arAccountId) {
            throw new \Exception('Accounts Receivable default account is not configured.');
        }

        $je = AccJournalEntry::create([
            'entry_number'   => AccJournalEntry::nextNumber(),
            'date'           => $data['date'],
            'fiscal_year_id' => $fy->id,
            'reference'      => $invoice->invoice_number,
            'narration'      => 'Sales Invoice: ' . $invoice->invoice_number,
            'source_type'    => 'sales_invoice',
            'source_id'      => $invoice->id,
            'total_amount'   => $total,
            'is_posted'      => false,
            'created_by'     => $data['created_by'] ?? auth()->id(),
        ]);

        $jeLines = [];
        $jeLines[] = ['account_id' => $arAccountId, 'debit' => $total, 'credit' => 0, 'description' => 'Accounts Receivable - ' . $invoice->invoice_number];

        foreach ($items->groupBy('account_id') as $accountId => $accountItems) {
            $jeLines[] = ['account_id' => $accountId, 'debit' => 0, 'credit' => $accountItems->sum('amount'), 'description' => 'Revenue - ' . $invoice->invoice_number];
        }

        if ($taxAmount > 0 && $taxAccountId) {
            $jeLines[] = ['account_id' => $taxAccountId, 'debit' => 0, 'credit' => $taxAmount, 'description' => 'Sales Tax - ' . $invoice->invoice_number];
        }

        if ($discountAmount > 0) {
            $discountAccountId = AccAccount::resolveId('sales_discount');
            if ($discountAccountId) {
                $jeLines[] = ['account_id' => $discountAccountId, 'debit' => $discountAmount, 'credit' => 0, 'description' => 'Sales Discount - ' . $invoice->invoice_number];
            }
        }

        $je->lines()->createMany($jeLines);
        $je->post();

        $invoice->update(['journal_entry_id' => $je->id]);

        return $invoice;
    }
}
