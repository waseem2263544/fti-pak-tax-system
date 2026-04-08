<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccJournalEntryLine;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Display all accounts grouped by type with balance.
     */
    public function index(Request $request)
    {
        $query = AccAccount::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $accounts = $query->orderBy('code')->get();

        // Group accounts by type
        $grouped = $accounts->groupBy('type');

        // Compute balance for each account
        $accounts->each(function ($account) {
            $account->computed_balance = $account->balance;
        });

        $types = ['asset', 'liability', 'equity', 'revenue', 'expense'];

        return view('accounting.accounts.index', compact('grouped', 'types'));
    }

    /**
     * Show the form for creating a new account.
     */
    public function create()
    {
        $types = ['asset', 'liability', 'equity', 'revenue', 'expense'];

        $subTypes = [
            'asset'     => ['Current Asset', 'Fixed Asset', 'Bank', 'Cash', 'Receivable', 'Other Asset'],
            'liability' => ['Current Liability', 'Long-term Liability', 'Payable', 'Other Liability'],
            'equity'    => ['Owner Equity', 'Retained Earnings', 'Other Equity'],
            'revenue'   => ['Operating Revenue', 'Other Revenue'],
            'expense'   => ['Operating Expense', 'Cost of Goods Sold', 'Administrative', 'Other Expense'],
        ];

        $parentAccounts = AccAccount::whereNull('parent_id')->orderBy('code')->get();

        return view('accounting.accounts.create', compact('types', 'subTypes', 'parentAccounts'));
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'                => 'required|string|max:20|unique:acc_accounts,code',
            'name'                => 'required|string|max:255',
            'type'                => 'required|in:asset,liability,equity,revenue,expense',
            'sub_type'            => 'nullable|string|max:100',
            'parent_id'           => 'nullable|exists:acc_accounts,id',
            'description'         => 'nullable|string',
            'is_active'           => 'boolean',
            'opening_balance'     => 'nullable|numeric|min:0',
            'opening_balance_type'=> 'nullable|in:debit,credit',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        AccAccount::create($validated);

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account created successfully.');
    }

    /**
     * Display account ledger with all journal lines.
     */
    public function show(Request $request, AccAccount $account)
    {
        $query = $account->journalLines()
            ->with(['journalEntry', 'journalEntry.fiscalYear'])
            ->whereHas('journalEntry', function ($q) {
                $q->where('is_posted', true);
            });

        if ($request->filled('from_date')) {
            $query->whereHas('journalEntry', fn($q) => $q->where('date', '>=', $request->from_date));
        }

        if ($request->filled('to_date')) {
            $query->whereHas('journalEntry', fn($q) => $q->where('date', '<=', $request->to_date));
        }

        $lines = $query->orderBy('id')->paginate(50)->withQueryString();

        // Calculate running balance
        $runningBalance = 0;
        if ($account->opening_balance) {
            $runningBalance = $account->isDebitNature()
                ? ($account->opening_balance_type === 'debit' ? $account->opening_balance : -$account->opening_balance)
                : ($account->opening_balance_type === 'credit' ? $account->opening_balance : -$account->opening_balance);
        }

        return view('accounting.accounts.show', compact('account', 'lines', 'runningBalance'));
    }

    /**
     * Show the form for editing the specified account.
     */
    public function edit(AccAccount $account)
    {
        $types = ['asset', 'liability', 'equity', 'revenue', 'expense'];

        $subTypes = [
            'asset'     => ['Current Asset', 'Fixed Asset', 'Bank', 'Cash', 'Receivable', 'Other Asset'],
            'liability' => ['Current Liability', 'Long-term Liability', 'Payable', 'Other Liability'],
            'equity'    => ['Owner Equity', 'Retained Earnings', 'Other Equity'],
            'revenue'   => ['Operating Revenue', 'Other Revenue'],
            'expense'   => ['Operating Expense', 'Cost of Goods Sold', 'Administrative', 'Other Expense'],
        ];

        $parentAccounts = AccAccount::where('id', '!=', $account->id)
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get();

        return view('accounting.accounts.edit', compact('account', 'types', 'subTypes', 'parentAccounts'));
    }

    /**
     * Update the specified account.
     */
    public function update(Request $request, AccAccount $account)
    {
        $validated = $request->validate([
            'code'                => 'required|string|max:20|unique:acc_accounts,code,' . $account->id,
            'name'                => 'required|string|max:255',
            'type'                => 'required|in:asset,liability,equity,revenue,expense',
            'sub_type'            => 'nullable|string|max:100',
            'parent_id'           => 'nullable|exists:acc_accounts,id',
            'description'         => 'nullable|string',
            'is_active'           => 'boolean',
            'opening_balance'     => 'nullable|numeric|min:0',
            'opening_balance_type'=> 'nullable|in:debit,credit',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $account->update($validated);

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    /**
     * Remove the specified account (only if no transactions exist).
     */
    public function destroy(AccAccount $account)
    {
        if ($account->is_system) {
            return back()->with('error', 'System accounts cannot be deleted.');
        }

        if ($account->journalLines()->exists()) {
            return back()->with('error', 'Cannot delete account with existing transactions.');
        }

        if ($account->children()->exists()) {
            return back()->with('error', 'Cannot delete account with child accounts.');
        }

        $account->delete();

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account deleted successfully.');
    }
}
