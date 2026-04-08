<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccContact;
use App\Models\AccFiscalYear;
use App\Models\AccJournalEntry;
use App\Models\AccJournalEntryLine;
use App\Models\AccPurchaseInvoice;
use App\Models\AccSalesInvoice;
use App\Models\AccVoucher;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AccountingReportController extends Controller
{
    /**
     * Dashboard with summary statistics.
     */
    public function dashboard()
    {
        $currentFY = AccFiscalYear::active();

        // Revenue & Expense totals for current fiscal year
        $revenueTotal = 0;
        $expenseTotal = 0;
        $arTotal = 0;
        $apTotal = 0;

        if ($currentFY) {
            $revenueTotal = $this->getTypeBalance('revenue', $currentFY->start_date, $currentFY->end_date);
            $expenseTotal = $this->getTypeBalance('expense', $currentFY->start_date, $currentFY->end_date);
        }

        // Outstanding receivables
        $arTotal = AccSalesInvoice::where('balance_due', '>', 0)->sum('balance_due');
        // Outstanding payables
        $apTotal = AccPurchaseInvoice::where('balance_due', '>', 0)->sum('balance_due');

        // Monthly revenue for the current year
        $monthlyRevenue = AccJournalEntryLine::whereHas('account', fn($q) => $q->where('type', 'revenue'))
            ->whereHas('journalEntry', function ($q) use ($currentFY) {
                $q->where('is_posted', true);
                if ($currentFY) {
                    $q->whereBetween('date', [$currentFY->start_date, $currentFY->end_date]);
                }
            })
            ->join('acc_journal_entries', 'acc_journal_entry_lines.journal_entry_id', '=', 'acc_journal_entries.id')
            ->selectRaw('MONTH(acc_journal_entries.date) as month, SUM(acc_journal_entry_lines.credit) - SUM(acc_journal_entry_lines.debit) as total')
            ->groupByRaw('MONTH(acc_journal_entries.date)')
            ->orderByRaw('MONTH(acc_journal_entries.date)')
            ->pluck('total', 'month')
            ->toArray();

        // Recent transactions
        $recentEntries = AccJournalEntry::with('creator')
            ->where('is_posted', true)
            ->latest('date')
            ->limit(10)
            ->get();

        // Counts
        $stats = [
            'total_invoices'          => AccSalesInvoice::count(),
            'unpaid_invoices'         => AccSalesInvoice::where('balance_due', '>', 0)->count(),
            'total_bills'             => AccPurchaseInvoice::count(),
            'unpaid_bills'            => AccPurchaseInvoice::where('balance_due', '>', 0)->count(),
            'total_journal_entries'   => AccJournalEntry::where('is_posted', true)->count(),
            'draft_journal_entries'   => AccJournalEntry::where('is_posted', false)->count(),
            'revenue_total'           => $revenueTotal,
            'expense_total'           => $expenseTotal,
            'net_income'              => $revenueTotal - $expenseTotal,
            'ar_total'                => $arTotal,
            'ap_total'                => $apTotal,
        ];

        // Bank/Cash balances
        $bankAccounts = AccAccount::active()
            ->where(function ($q) {
                $q->where('sub_type', 'Bank')
                  ->orWhere('sub_type', 'Cash');
            })
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                $account->computed_balance = $account->balance;
                return $account;
            });

        return view('accounting.dashboard', compact('stats', 'monthlyRevenue', 'recentEntries', 'bankAccounts', 'currentFY'));
    }

    /**
     * Trial Balance report.
     */
    public function trialBalance(Request $request)
    {
        $asOfDate = $request->get('as_of_date', now()->toDateString());

        $accounts = AccAccount::orderBy('code')->get()->map(function ($account) use ($asOfDate) {
            $result = $account->journalLines()
                ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                    $q->where('is_posted', true)->where('date', '<=', $asOfDate);
                })
                ->selectRaw('COALESCE(SUM(debit),0) as total_debit, COALESCE(SUM(credit),0) as total_credit')
                ->first();

            $debit = $result->total_debit ?? 0;
            $credit = $result->total_credit ?? 0;

            // Add opening balance
            if ($account->opening_balance_type === 'debit') $debit += ($account->opening_balance ?? 0);
            elseif ($account->opening_balance_type === 'credit') $credit += ($account->opening_balance ?? 0);

            $account->trial_debit = $debit;
            $account->trial_credit = $credit;

            // Net balance for display
            if ($account->isDebitNature()) {
                $net = $debit - $credit;
                $account->balance_debit = $net >= 0 ? $net : 0;
                $account->balance_credit = $net < 0 ? abs($net) : 0;
            } else {
                $net = $credit - $debit;
                $account->balance_credit = $net >= 0 ? $net : 0;
                $account->balance_debit = $net < 0 ? abs($net) : 0;
            }

            return $account;
        })->filter(function ($account) {
            // Only show accounts with activity
            return $account->balance_debit > 0 || $account->balance_credit > 0;
        });

        $totalDebit = $accounts->sum('balance_debit');
        $totalCredit = $accounts->sum('balance_credit');

        return view('accounting.reports.trial-balance', compact('accounts', 'totalDebit', 'totalCredit', 'asOfDate'));
    }

    /**
     * Balance Sheet report.
     */
    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->get('as_of_date', now()->toDateString());

        $assets = $this->getAccountsWithBalance('asset', $asOfDate);
        $liabilities = $this->getAccountsWithBalance('liability', $asOfDate);
        $equity = $this->getAccountsWithBalance('equity', $asOfDate);

        // Calculate retained earnings (net income up to date)
        $retainedEarnings = $this->getTypeBalanceUpTo('revenue', $asOfDate) - $this->getTypeBalanceUpTo('expense', $asOfDate);

        $totalAssets = $assets->sum('computed_balance');
        $totalLiabilities = $liabilities->sum('computed_balance');
        $totalEquity = $equity->sum('computed_balance') + $retainedEarnings;

        return view('accounting.reports.balance-sheet', compact(
            'assets', 'liabilities', 'equity',
            'totalAssets', 'totalLiabilities', 'totalEquity',
            'retainedEarnings', 'asOfDate'
        ));
    }

    /**
     * Income Statement (Profit & Loss) report.
     */
    public function incomeStatement(Request $request)
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        // Default to current fiscal year
        if (!$fromDate || !$toDate) {
            $fy = AccFiscalYear::active();
            if ($fy) {
                $fromDate = $fromDate ?: $fy->start_date->toDateString();
                $toDate = $toDate ?: $fy->end_date->toDateString();
            } else {
                $fromDate = $fromDate ?: now()->startOfYear()->toDateString();
                $toDate = $toDate ?: now()->toDateString();
            }
        }

        $revenueAccounts = AccAccount::ofType('revenue')->orderBy('code')->get()->map(function ($account) use ($fromDate, $toDate) {
            $account->computed_balance = $this->getAccountBalanceForPeriod($account, $fromDate, $toDate);
            return $account;
        })->filter(fn($a) => $a->computed_balance != 0);

        $expenseAccounts = AccAccount::ofType('expense')->orderBy('code')->get()->map(function ($account) use ($fromDate, $toDate) {
            $account->computed_balance = $this->getAccountBalanceForPeriod($account, $fromDate, $toDate);
            return $account;
        })->filter(fn($a) => $a->computed_balance != 0);

        $totalRevenue = $revenueAccounts->sum('computed_balance');
        $totalExpenses = $expenseAccounts->sum('computed_balance');
        $netIncome = $totalRevenue - $totalExpenses;

        return view('accounting.reports.income-statement', compact(
            'revenueAccounts', 'expenseAccounts',
            'totalRevenue', 'totalExpenses', 'netIncome',
            'fromDate', 'toDate'
        ));
    }

    /**
     * General Ledger - all transactions for all accounts.
     */
    public function generalLedger(Request $request)
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = AccJournalEntryLine::with(['account', 'journalEntry'])
            ->whereHas('journalEntry', function ($q) use ($fromDate, $toDate) {
                $q->where('is_posted', true);
                if ($fromDate) $q->where('date', '>=', $fromDate);
                if ($toDate) $q->where('date', '<=', $toDate);
            });

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $lines = $query->orderBy('id')->paginate(50)->withQueryString();

        $accounts = AccAccount::orderBy('code')->get();

        return view('accounting.reports.general-ledger', compact('lines', 'accounts', 'fromDate', 'toDate'));
    }

    /**
     * Account Ledger - single account detail.
     */
    public function accountLedger(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:acc_accounts,id',
        ]);

        $account = AccAccount::findOrFail($request->account_id);

        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = $account->journalLines()
            ->with(['journalEntry'])
            ->whereHas('journalEntry', function ($q) use ($fromDate, $toDate) {
                $q->where('is_posted', true);
                if ($fromDate) $q->where('date', '>=', $fromDate);
                if ($toDate) $q->where('date', '<=', $toDate);
            });

        $lines = $query->orderBy('id')->paginate(50)->withQueryString();

        // Opening balance (all transactions before from_date)
        $openingBalance = 0;
        if ($fromDate) {
            $prior = $account->journalLines()
                ->whereHas('journalEntry', fn($q) => $q->where('is_posted', true)->where('date', '<', $fromDate))
                ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
                ->first();

            if ($account->isDebitNature()) {
                $openingBalance = ($prior->d ?? 0) - ($prior->c ?? 0);
            } else {
                $openingBalance = ($prior->c ?? 0) - ($prior->d ?? 0);
            }

            // Add account opening balance
            if ($account->opening_balance) {
                if ($account->opening_balance_type === 'debit') {
                    $openingBalance += $account->isDebitNature() ? $account->opening_balance : -$account->opening_balance;
                } elseif ($account->opening_balance_type === 'credit') {
                    $openingBalance += $account->isDebitNature() ? -$account->opening_balance : $account->opening_balance;
                }
            }
        }

        $accounts = AccAccount::orderBy('code')->get();

        return view('accounting.reports.account-ledger', compact('account', 'lines', 'openingBalance', 'accounts', 'fromDate', 'toDate'));
    }

    /**
     * Receivable Aging report - by client.
     */
    public function receivableAging()
    {
        $invoices = AccSalesInvoice::where('balance_due', '>', 0)
            ->with('client')
            ->orderBy('due_date')
            ->get();

        $today = Carbon::today();

        $aging = $invoices->groupBy('client_id')->map(function ($clientInvoices) use ($today) {
            $client = $clientInvoices->first()->client;
            $buckets = ['current' => 0, 'days_30' => 0, 'days_60' => 0, 'days_90' => 0, 'over_90' => 0];

            foreach ($clientInvoices as $invoice) {
                $daysOverdue = $today->diffInDays($invoice->due_date, false);
                $balance = $invoice->balance_due;

                if ($daysOverdue <= 0) {
                    $buckets['current'] += $balance;
                } elseif ($daysOverdue <= 30) {
                    $buckets['days_30'] += $balance;
                } elseif ($daysOverdue <= 60) {
                    $buckets['days_60'] += $balance;
                } elseif ($daysOverdue <= 90) {
                    $buckets['days_90'] += $balance;
                } else {
                    $buckets['over_90'] += $balance;
                }
            }

            return [
                'client'   => $client,
                'invoices' => $clientInvoices,
                'buckets'  => $buckets,
                'total'    => array_sum($buckets),
            ];
        });

        $totals = [
            'current'  => $aging->sum('buckets.current'),
            'days_30'  => $aging->sum('buckets.days_30'),
            'days_60'  => $aging->sum('buckets.days_60'),
            'days_90'  => $aging->sum('buckets.days_90'),
            'over_90'  => $aging->sum('buckets.over_90'),
            'total'    => $aging->sum('total'),
        ];

        return view('accounting.reports.receivable-aging', compact('aging', 'totals'));
    }

    /**
     * Payable Aging report - by vendor.
     */
    public function payableAging()
    {
        $invoices = AccPurchaseInvoice::where('balance_due', '>', 0)
            ->with('contact')
            ->orderBy('due_date')
            ->get();

        $today = Carbon::today();

        $aging = $invoices->groupBy('contact_id')->map(function ($contactInvoices) use ($today) {
            $contact = $contactInvoices->first()->contact;
            $buckets = ['current' => 0, 'days_30' => 0, 'days_60' => 0, 'days_90' => 0, 'over_90' => 0];

            foreach ($contactInvoices as $invoice) {
                $daysOverdue = $today->diffInDays($invoice->due_date, false);
                $balance = $invoice->balance_due;

                if ($daysOverdue <= 0) {
                    $buckets['current'] += $balance;
                } elseif ($daysOverdue <= 30) {
                    $buckets['days_30'] += $balance;
                } elseif ($daysOverdue <= 60) {
                    $buckets['days_60'] += $balance;
                } elseif ($daysOverdue <= 90) {
                    $buckets['days_90'] += $balance;
                } else {
                    $buckets['over_90'] += $balance;
                }
            }

            return [
                'contact'  => $contact,
                'invoices' => $contactInvoices,
                'buckets'  => $buckets,
                'total'    => array_sum($buckets),
            ];
        });

        $totals = [
            'current'  => $aging->sum('buckets.current'),
            'days_30'  => $aging->sum('buckets.days_30'),
            'days_60'  => $aging->sum('buckets.days_60'),
            'days_90'  => $aging->sum('buckets.days_90'),
            'over_90'  => $aging->sum('buckets.over_90'),
            'total'    => $aging->sum('total'),
        ];

        return view('accounting.reports.payable-aging', compact('aging', 'totals'));
    }

    public function cashFlow(Request $request)
    {
        $fy = AccFiscalYear::active();
        $fromDate = $request->get('from', $fy ? $fy->start_date->format('Y-m-d') : now()->startOfYear()->format('Y-m-d'));
        $toDate = $request->get('to', now()->format('Y-m-d'));

        // Cash/Bank accounts (sub_type = 'bank')
        $cashAccounts = AccAccount::where('sub_type', 'bank')->where('is_active', true)->orderBy('code')->get();

        $cashFlowData = [];
        $totalOpening = 0;
        $totalReceipts = 0;
        $totalPayments = 0;

        foreach ($cashAccounts as $account) {
            // Opening balance (all transactions before fromDate)
            $opening = $account->journalLines()
                ->whereHas('journalEntry', fn($q) => $q->where('is_posted', true)->where('date', '<', $fromDate))
                ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
                ->first();
            $openingBal = ($opening->d ?? 0) - ($opening->c ?? 0);
            if ($account->opening_balance_type === 'debit') $openingBal += $account->opening_balance;

            // Period receipts (debits to cash = money in)
            $receipts = $account->journalLines()
                ->whereHas('journalEntry', fn($q) => $q->where('is_posted', true)->whereBetween('date', [$fromDate, $toDate]))
                ->sum('debit');

            // Period payments (credits to cash = money out)
            $payments = $account->journalLines()
                ->whereHas('journalEntry', fn($q) => $q->where('is_posted', true)->whereBetween('date', [$fromDate, $toDate]))
                ->sum('credit');

            $closing = $openingBal + $receipts - $payments;

            $cashFlowData[] = [
                'account' => $account,
                'opening' => $openingBal,
                'receipts' => $receipts,
                'payments' => $payments,
                'closing' => $closing,
            ];

            $totalOpening += $openingBal;
            $totalReceipts += $receipts;
            $totalPayments += $payments;
        }

        $totalClosing = $totalOpening + $totalReceipts - $totalPayments;

        // Breakdown by source - receipts from clients vs other
        $receiptsBySource = AccVoucher::receipts()->where('status', 'posted')
            ->whereBetween('date', [$fromDate, $toDate])
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')->pluck('total', 'payment_method')->toArray();

        $paymentsBySource = AccVoucher::payments()->where('status', 'posted')
            ->whereBetween('date', [$fromDate, $toDate])
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')->pluck('total', 'payment_method')->toArray();

        return view('accounting.reports.cash-flow', compact(
            'cashFlowData', 'totalOpening', 'totalReceipts', 'totalPayments', 'totalClosing',
            'receiptsBySource', 'paymentsBySource', 'fromDate', 'toDate'
        ));
    }

    // ─── Helper Methods ──────────────────────────────────────────────

    /**
     * Get total balance for an account type within a date range.
     */
    private function getTypeBalance(string $type, $fromDate, $toDate): float
    {
        $accounts = AccAccount::ofType($type)->get();
        $total = 0;

        foreach ($accounts as $account) {
            $total += $this->getAccountBalanceForPeriod($account, $fromDate, $toDate);
        }

        return $total;
    }

    /**
     * Get total balance for an account type up to a date.
     */
    private function getTypeBalanceUpTo(string $type, $asOfDate): float
    {
        $accounts = AccAccount::ofType($type)->get();
        $total = 0;

        foreach ($accounts as $account) {
            $result = $account->journalLines()
                ->whereHas('journalEntry', fn($q) => $q->where('is_posted', true)->where('date', '<=', $asOfDate))
                ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
                ->first();

            $debit = $result->d ?? 0;
            $credit = $result->c ?? 0;

            if ($account->opening_balance_type === 'debit') $debit += ($account->opening_balance ?? 0);
            elseif ($account->opening_balance_type === 'credit') $credit += ($account->opening_balance ?? 0);

            if ($account->isDebitNature()) {
                $total += $debit - $credit;
            } else {
                $total += $credit - $debit;
            }
        }

        return $total;
    }

    /**
     * Get accounts with computed balance for a given type and date.
     */
    private function getAccountsWithBalance(string $type, string $asOfDate)
    {
        return AccAccount::ofType($type)->orderBy('code')->get()->map(function ($account) use ($asOfDate) {
            $result = $account->journalLines()
                ->whereHas('journalEntry', fn($q) => $q->where('is_posted', true)->where('date', '<=', $asOfDate))
                ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
                ->first();

            $debit = $result->d ?? 0;
            $credit = $result->c ?? 0;

            if ($account->opening_balance_type === 'debit') $debit += ($account->opening_balance ?? 0);
            elseif ($account->opening_balance_type === 'credit') $credit += ($account->opening_balance ?? 0);

            if ($account->isDebitNature()) {
                $account->computed_balance = $debit - $credit;
            } else {
                $account->computed_balance = $credit - $debit;
            }

            return $account;
        })->filter(fn($a) => $a->computed_balance != 0);
    }

    /**
     * Get balance for a single account within a date range.
     */
    private function getAccountBalanceForPeriod(AccAccount $account, $fromDate, $toDate): float
    {
        $result = $account->journalLines()
            ->whereHas('journalEntry', function ($q) use ($fromDate, $toDate) {
                $q->where('is_posted', true);
                if ($fromDate) $q->where('date', '>=', $fromDate);
                if ($toDate) $q->where('date', '<=', $toDate);
            })
            ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')
            ->first();

        $debit = $result->d ?? 0;
        $credit = $result->c ?? 0;

        if ($account->isDebitNature()) {
            return $debit - $credit;
        }

        return $credit - $debit;
    }
}
