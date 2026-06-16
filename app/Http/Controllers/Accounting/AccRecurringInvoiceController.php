<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccRecurringInvoice;
use App\Models\Client;
use App\Services\Accounting\SalesInvoicePoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccRecurringInvoiceController extends Controller
{
    public function index()
    {
        $templates = AccRecurringInvoice::with('client')->orderBy('next_date')->get();
        $dueCount = $templates->filter->isDue()->count();
        return view('accounting.recurring-invoices.index', compact('templates', 'dueCount'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $revenueAccounts = AccAccount::active()->ofType('revenue')->orderBy('code')->get();
        $template = new AccRecurringInvoice(['frequency' => 'monthly', 'due_days' => 30, 'next_date' => now()->toDateString(), 'items' => []]);
        return view('accounting.recurring-invoices.create', compact('clients', 'revenueAccounts', 'template'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['created_by'] = auth()->id();
        AccRecurringInvoice::create($data);
        return redirect()->route('accounting.recurring-invoices.index')->with('success', 'Recurring invoice template created.');
    }

    public function edit(AccRecurringInvoice $recurringInvoice)
    {
        $clients = Client::orderBy('name')->get();
        $revenueAccounts = AccAccount::active()->ofType('revenue')->orderBy('code')->get();
        $template = $recurringInvoice;
        return view('accounting.recurring-invoices.edit', compact('clients', 'revenueAccounts', 'template'));
    }

    public function update(Request $request, AccRecurringInvoice $recurringInvoice)
    {
        $recurringInvoice->update($this->validateData($request));
        return redirect()->route('accounting.recurring-invoices.index')->with('success', 'Recurring invoice template updated.');
    }

    public function destroy(AccRecurringInvoice $recurringInvoice)
    {
        $recurringInvoice->delete();
        return redirect()->route('accounting.recurring-invoices.index')->with('success', 'Recurring invoice template deleted.');
    }

    /** Generate a real sales invoice from a template (and advance its schedule). */
    public function generate(AccRecurringInvoice $recurringInvoice)
    {
        if (empty($recurringInvoice->items)) {
            return back()->with('error', 'This template has no line items.');
        }

        DB::beginTransaction();
        try {
            $invoice = SalesInvoicePoster::create([
                'client_id'       => $recurringInvoice->client_id,
                'date'            => now()->toDateString(),
                'due_date'        => now()->addDays((int) ($recurringInvoice->due_days ?: 30))->toDateString(),
                'reference'       => $recurringInvoice->reference,
                'discount_amount' => $recurringInvoice->discount_amount ?? 0,
                'notes'           => $recurringInvoice->notes,
                'terms'           => $recurringInvoice->terms,
                'items'           => $recurringInvoice->items,
                'created_by'      => auth()->id(),
            ]);
            $recurringInvoice->advanceSchedule();
            DB::commit();
            return redirect()->route('accounting.sales-invoices.show', $invoice)->with('success', 'Invoice ' . $invoice->invoice_number . ' generated from recurring template.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to generate invoice: ' . $e->getMessage());
        }
    }

    /** Generate invoices for every template that is due. */
    public function generateDue()
    {
        $due = AccRecurringInvoice::with('client')->where('is_active', true)
            ->whereDate('next_date', '<=', now()->toDateString())->get()
            ->filter(fn($t) => !empty($t->items));

        $count = 0;
        foreach ($due as $template) {
            DB::beginTransaction();
            try {
                SalesInvoicePoster::create([
                    'client_id'       => $template->client_id,
                    'date'            => now()->toDateString(),
                    'due_date'        => now()->addDays((int) ($template->due_days ?: 30))->toDateString(),
                    'reference'       => $template->reference,
                    'discount_amount' => $template->discount_amount ?? 0,
                    'notes'           => $template->notes,
                    'terms'           => $template->terms,
                    'items'           => $template->items,
                    'created_by'      => auth()->id(),
                ]);
                $template->advanceSchedule();
                DB::commit();
                $count++;
            } catch (\Exception $e) {
                DB::rollBack();
            }
        }

        return redirect()->route('accounting.recurring-invoices.index')
            ->with('success', $count > 0 ? "Generated {$count} invoice(s) from due templates." : 'No due templates were generated.');
    }

    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'client_id'           => 'required|exists:clients,id',
            'frequency'           => 'required|in:weekly,monthly,quarterly,yearly',
            'next_date'           => 'required|date',
            'due_days'            => 'required|integer|min:0|max:365',
            'reference'           => 'nullable|string|max:255',
            'notes'               => 'nullable|string',
            'terms'               => 'nullable|string',
            'discount_amount'     => 'nullable|numeric|min:0',
            'is_active'           => 'nullable|boolean',
            'items'               => 'required|array|min:1',
            'items.*.account_id'  => 'required|exists:acc_accounts,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.tax_rate'    => 'nullable|numeric|min:0|max:100',
            'items.*.discount'    => 'nullable|numeric|min:0',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['discount_amount'] = $validated['discount_amount'] ?? 0;
        return $validated;
    }
}
