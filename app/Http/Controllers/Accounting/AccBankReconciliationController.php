<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccJournalEntryLine;
use Illuminate\Http\Request;

class AccBankReconciliationController extends Controller
{
    /** List bank & cash accounts to reconcile. */
    public function index()
    {
        $accounts = AccAccount::active()
            ->whereRaw('LOWER(sub_type) IN (?, ?)', ['bank', 'cash'])
            ->orderBy('code')->get()
            ->map(function ($a) {
                $a->computed_balance = $a->balance;
                $a->uncleared_count = AccJournalEntryLine::where('account_id', $a->id)
                    ->where('cleared', false)
                    ->whereHas('journalEntry', fn($q) => $q->where('is_posted', true))->count();
                return $a;
            });

        return view('accounting.reconciliation.index', compact('accounts'));
    }

    /** Reconciliation worksheet for one account. */
    public function show(Request $request, AccAccount $account)
    {
        $asOf = $request->input('as_of_date', now()->toDateString());
        $statementBalance = $request->filled('statement_balance') ? (float) $request->input('statement_balance') : null;

        $lines = $this->linesFor($account, $asOf)
            ->sortBy(fn($l) => optional($l->journalEntry)->date . str_pad($l->id, 10, '0', STR_PAD_LEFT))
            ->values();

        $openingBalance = $this->openingBalance($account);
        $clearedMovement = $lines->where('cleared', true)->sum(fn($l) => (float) $l->debit - (float) $l->credit);
        $clearedBalance = $openingBalance + $clearedMovement;
        $difference = $statementBalance !== null ? $statementBalance - $clearedBalance : null;

        return view('accounting.reconciliation.show', compact(
            'account', 'lines', 'asOf', 'statementBalance', 'openingBalance', 'clearedBalance', 'difference'
        ));
    }

    /** Persist cleared flags for the lines shown on the worksheet. */
    public function save(Request $request, AccAccount $account)
    {
        $asOf = $request->input('as_of_date', now()->toDateString());
        $checked = array_map('intval', (array) $request->input('cleared_lines', []));

        foreach ($this->linesFor($account, $asOf) as $line) {
            $isChecked = in_array($line->id, $checked, true);
            if ($isChecked && !$line->cleared) {
                $line->forceFill(['cleared' => true, 'cleared_at' => now()])->save();
            } elseif (!$isChecked && $line->cleared) {
                $line->forceFill(['cleared' => false, 'cleared_at' => null])->save();
            }
        }

        return redirect()->route('accounting.reconciliation.show', [
            'account' => $account->id,
            'as_of_date' => $asOf,
            'statement_balance' => $request->input('statement_balance'),
        ])->with('success', 'Reconciliation saved.');
    }

    private function linesFor(AccAccount $account, string $asOf)
    {
        return AccJournalEntryLine::with('journalEntry')
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', fn($q) => $q->where('is_posted', true)->whereDate('date', '<=', $asOf))
            ->get();
    }

    private function openingBalance(AccAccount $account): float
    {
        if ($account->opening_balance_type === 'debit') return (float) $account->opening_balance;
        if ($account->opening_balance_type === 'credit') return -(float) $account->opening_balance;
        return 0.0;
    }
}
