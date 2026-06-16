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

        // Monthly revenue & expense for the current fiscal year (keyed by calendar month number)
        $monthlySeries = function (string $type, string $col) use ($currentFY) {
            return AccJournalEntryLine::whereHas('account', fn($q) => $q->where('type', $type))
                ->whereHas('journalEntry', function ($q) use ($currentFY) {
                    $q->where('is_posted', true);
                    if ($currentFY) {
                        $q->whereBetween('date', [$currentFY->start_date, $currentFY->end_date]);
                    }
                })
                ->join('acc_journal_entries', 'acc_journal_entry_lines.journal_entry_id', '=', 'acc_journal_entries.id')
                ->selectRaw("MONTH(acc_journal_entries.date) as month, $col as total")
                ->groupByRaw('MONTH(acc_journal_entries.date)')
                ->pluck('total', 'month')
                ->toArray();
        };
        $monthlyRevenue = $monthlySeries('revenue', 'SUM(acc_journal_entry_lines.credit) - SUM(acc_journal_entry_lines.debit)');
        $monthlyExpense = $monthlySeries('expense', 'SUM(acc_journal_entry_lines.debit) - SUM(acc_journal_entry_lines.credit)');

        // Build an ordered 12-month chart series across the fiscal year
        $chartLabels = $chartRevenue = $chartExpense = [];
        if ($currentFY) {
            $cursor = Carbon::parse($currentFY->start_date)->startOfMonth();
            for ($i = 0; $i < 12; $i++) {
                $m = (int) $cursor->format('n');
                $chartLabels[] = $cursor->format('M Y');
                $chartRevenue[] = round((float) ($monthlyRevenue[$m] ?? 0), 2);
                $chartExpense[] = round((float) ($monthlyExpense[$m] ?? 0), 2);
                $cursor->addMonth();
            }
        }

        // Expense breakdown by account (top 8) for the current fiscal year
        $expenseBreakdown = AccJournalEntryLine::whereHas('account', fn($q) => $q->where('type', 'expense'))
            ->whereHas('journalEntry', function ($q) use ($currentFY) {
                $q->where('is_posted', true);
                if ($currentFY) {
                    $q->whereBetween('date', [$currentFY->start_date, $currentFY->end_date]);
                }
            })
            ->join('acc_accounts', 'acc_journal_entry_lines.account_id', '=', 'acc_accounts.id')
            ->selectRaw('acc_accounts.name as name, SUM(acc_journal_entry_lines.debit) - SUM(acc_journal_entry_lines.credit) as total')
            ->groupBy('acc_accounts.id', 'acc_accounts.name')
            ->havingRaw('SUM(acc_journal_entry_lines.debit) - SUM(acc_journal_entry_lines.credit) > 0')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        // Recent transactions
        $recentEntries = AccJournalEntry::with('creator', 'lines')
            ->latest('date')
            ->latest('id')
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

        // Bank/Cash balances (sub_type is seeded lowercase 'bank')
        $bankAccounts = AccAccount::active()
            ->whereRaw('LOWER(sub_type) IN (?, ?)', ['bank', 'cash'])
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                $account->computed_balance = $account->balance;
                return $account;
            });
        $stats['cash_total'] = $bankAccounts->sum('computed_balance');

        return view('accounting.dashboard', compact('stats', 'monthlyRevenue', 'recentEntries', 'bankAccounts', 'currentFY', 'chartLabels', 'chartRevenue', 'chartExpense', 'expenseBreakdown'));
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
        });

        $showAll = $request->boolean('show_all');
        if (!$showAll) {
            $accounts = $accounts->filter(fn($account) => $account->balance_debit > 0 || $account->balance_credit > 0);
        }

        $totalDebit = $accounts->sum('balance_debit');
        $totalCredit = $accounts->sum('balance_credit');

        if ($request->get('export') === 'csv') {
            $rows = $accounts->map(fn($a) => [$a->code, $a->name, $this->num($a->balance_debit), $this->num($a->balance_credit)])->values()->toArray();
            $rows[] = ['', 'TOTAL', $this->num($totalDebit), $this->num($totalCredit)];
            return $this->streamCsv("trial-balance-{$asOfDate}.csv", ['Code', 'Account', 'Debit', 'Credit'], $rows);
        }

        return view('accounting.reports.trial-balance', compact('accounts', 'totalDebit', 'totalCredit', 'asOfDate', 'showAll'));
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

        if ($request->get('export') === 'csv') {
            $rows = [['REVENUE', '']];
            foreach ($revenueAccounts as $a) $rows[] = [$a->code . ' ' . $a->name, $this->num($a->computed_balance)];
            $rows[] = ['Total Revenue', $this->num($totalRevenue)];
            $rows[] = ['', ''];
            $rows[] = ['EXPENSES', ''];
            foreach ($expenseAccounts as $a) $rows[] = [$a->code . ' ' . $a->name, $this->num($a->computed_balance)];
            $rows[] = ['Total Expenses', $this->num($totalExpenses)];
            $rows[] = ['', ''];
            $rows[] = ['NET ' . ($netIncome >= 0 ? 'PROFIT' : 'LOSS'), $this->num($netIncome)];
            return $this->streamCsv("income-statement-{$fromDate}-to-{$toDate}.csv", ['Account', 'Amount'], $rows);
        }

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

    /**
     * Accounting health check — verifies the books are internally consistent.
     */
    public function diagnostics()
    {
        // 1. Global posted debits vs credits (must be equal)
        $totals = DB::table('acc_journal_entry_lines')
            ->join('acc_journal_entries', 'acc_journal_entry_lines.journal_entry_id', '=', 'acc_journal_entries.id')
            ->where('acc_journal_entries.is_posted', true)
            ->selectRaw('COALESCE(SUM(debit),0) d, COALESCE(SUM(credit),0) c')->first();
        $globalDebit = (float) ($totals->d ?? 0);
        $globalCredit = (float) ($totals->c ?? 0);
        $globalBalanced = round($globalDebit - $globalCredit, 2) === 0.0;

        // 2. Individually unbalanced posted journal entries
        $unbalancedIds = DB::table('acc_journal_entry_lines')
            ->join('acc_journal_entries', 'acc_journal_entry_lines.journal_entry_id', '=', 'acc_journal_entries.id')
            ->where('acc_journal_entries.is_posted', true)
            ->groupBy('journal_entry_id')
            ->havingRaw('ROUND(SUM(debit),2) <> ROUND(SUM(credit),2)')
            ->pluck('journal_entry_id');
        $unbalancedEntries = AccJournalEntry::whereIn('id', $unbalancedIds)->get(['id', 'entry_number', 'date']);

        // 3. Control account configuration
        $roles = [
            'accounts_receivable' => 'Accounts Receivable', 'accounts_payable' => 'Accounts Payable',
            'cash' => 'Cash', 'bank' => 'Bank', 'sales' => 'Default Sales', 'purchase' => 'Default Purchase',
            'sales_tax' => 'Output Sales Tax', 'purchase_tax' => 'Input Sales Tax', 'sales_discount' => 'Sales Discounts',
        ];
        $controlAccounts = [];
        foreach ($roles as $role => $label) {
            $id = AccAccount::resolveId($role);
            $acct = $id ? AccAccount::find($id) : null;
            $controlAccounts[] = ['label' => $label, 'account' => $acct ? ($acct->code . ' · ' . $acct->name) : null];
        }

        // 4. Invoices missing a posted journal entry
        $invoicesNoJe = AccSalesInvoice::whereNull('journal_entry_id')->where('status', '!=', 'draft')->count();
        $billsNoJe = AccPurchaseInvoice::whereNull('journal_entry_id')->where('status', '!=', 'draft')->count();

        // 5. Active fiscal year present?
        $activeFy = AccFiscalYear::active();

        return view('accounting.reports.diagnostics', compact(
            'globalDebit', 'globalCredit', 'globalBalanced', 'unbalancedEntries',
            'controlAccounts', 'invoicesNoJe', 'billsNoJe', 'activeFy'
        ));
    }

    /**
     * Stream an array of rows as a CSV download.
     */
    private function streamCsv(string $filename, array $header, array $rows)
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function num($v): string
    {
        return number_format((float) $v, 2, '.', '');
    }

    /**
     * Customer Statement of Account — a client's invoices (debits) and receipts
     * (credits) over a period with opening/running/closing balance.
     */
    public function customerStatement(Request $request)
    {
        $clients = Client::orderBy('name')->get();
        $clientId = $request->input('client_id');
        $toDate = $request->input('to_date', now()->toDateString());
        $fromDate = $request->input('from_date');

        $client = null;
        $rows = [];
        $openingBalance = 0.0;
        $closingBalance = 0.0;
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        if ($clientId && ($client = Client::find($clientId))) {
            if ($fromDate) {
                $priorInv = (float) AccSalesInvoice::where('client_id', $clientId)->whereDate('date', '<', $fromDate)->sum('total');
                $priorRec = (float) AccVoucher::where('type', 'receipt')->where('client_id', $clientId)
                    ->where('status', '!=', 'cancelled')->whereDate('date', '<', $fromDate)->sum('amount');
                $openingBalance = $priorInv - $priorRec;
            }

            $events = [];
            $invQ = AccSalesInvoice::where('client_id', $clientId)->whereDate('date', '<=', $toDate);
            if ($fromDate) $invQ->whereDate('date', '>=', $fromDate);
            foreach ($invQ->orderBy('date')->get() as $inv) {
                $events[] = ['date' => $inv->date, 'type' => 'Invoice', 'ref' => $inv->invoice_number, 'debit' => (float) $inv->total, 'credit' => 0.0];
            }
            $recQ = AccVoucher::where('type', 'receipt')->where('client_id', $clientId)
                ->where('status', '!=', 'cancelled')->whereDate('date', '<=', $toDate);
            if ($fromDate) $recQ->whereDate('date', '>=', $fromDate);
            foreach ($recQ->orderBy('date')->get() as $rec) {
                $events[] = ['date' => $rec->date, 'type' => 'Receipt', 'ref' => $rec->voucher_number, 'debit' => 0.0, 'credit' => (float) $rec->amount];
            }

            usort($events, fn($a, $b) => $a['date']->timestamp <=> $b['date']->timestamp);

            $running = $openingBalance;
            foreach ($events as $e) {
                $running += $e['debit'] - $e['credit'];
                $e['balance'] = $running;
                $totalDebit += $e['debit'];
                $totalCredit += $e['credit'];
                $rows[] = $e;
            }
            $closingBalance = $running;
        }

        if ($client && $request->get('export') === 'csv') {
            $csv = [['Opening Balance', '', '', '', $this->num($openingBalance)]];
            foreach ($rows as $r) {
                $csv[] = [$r['date']->format('Y-m-d'), $r['type'], $r['ref'], $this->num($r['debit']), $this->num($r['credit']), $this->num($r['balance'])];
            }
            $csv[] = ['', '', 'Closing Balance', $this->num($totalDebit), $this->num($totalCredit), $this->num($closingBalance)];
            return $this->streamCsv('statement-' . \Illuminate\Support\Str::slug($client->name) . ".csv", ['Date', 'Type', 'Reference', 'Debit', 'Credit', 'Balance'], $csv);
        }

        return view('accounting.reports.customer-statement', compact(
            'clients', 'client', 'rows', 'openingBalance', 'closingBalance', 'totalDebit', 'totalCredit', 'fromDate', 'toDate'
        ));
    }

    /**
     * Sales Tax Report — output tax (on sales) vs input tax (on purchases) and net
     * tax payable/refundable for a period. Supports sales-tax return preparation.
     */
    public function taxReport(Request $request)
    {
        $fy = AccFiscalYear::active();
        $fromDate = $request->input('from_date', $fy ? $fy->start_date->toDateString() : now()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', $fy ? $fy->end_date->toDateString() : now()->toDateString());

        $sales = AccSalesInvoice::with('client', 'items')
            ->whereBetween('date', [$fromDate, $toDate])
            ->where('status', '!=', 'cancelled')
            ->orderBy('date')->get();
        $purchases = AccPurchaseInvoice::with('contact', 'items')
            ->whereBetween('date', [$fromDate, $toDate])
            ->where('status', '!=', 'cancelled')
            ->orderBy('date')->get();

        $taxableSales = (float) $sales->sum(fn($i) => (float) $i->items->sum('amount'));
        $outputTax = (float) $sales->sum(fn($i) => (float) $i->items->sum('tax_amount'));
        $taxablePurchases = (float) $purchases->sum(fn($i) => (float) $i->items->sum('amount'));
        $inputTax = (float) $purchases->sum(fn($i) => (float) $i->items->sum('tax_amount'));
        $netTax = $outputTax - $inputTax;

        if ($request->get('export') === 'csv') {
            $rows = [['OUTPUT TAX (SALES)', '', '', '', '']];
            foreach ($sales as $inv) $rows[] = [optional($inv->date)->format('Y-m-d'), $inv->invoice_number, $inv->client->name ?? '', $this->num($inv->items->sum('amount')), $this->num($inv->items->sum('tax_amount'))];
            $rows[] = ['', '', 'Total Output Tax', $this->num($taxableSales), $this->num($outputTax)];
            $rows[] = ['', '', '', '', ''];
            $rows[] = ['INPUT TAX (PURCHASES)', '', '', '', ''];
            foreach ($purchases as $b) $rows[] = [optional($b->date)->format('Y-m-d'), $b->bill_number, $b->contact->name ?? '', $this->num($b->items->sum('amount')), $this->num($b->items->sum('tax_amount'))];
            $rows[] = ['', '', 'Total Input Tax', $this->num($taxablePurchases), $this->num($inputTax)];
            $rows[] = ['', '', '', '', ''];
            $rows[] = ['', '', ($netTax >= 0 ? 'NET TAX PAYABLE' : 'NET TAX REFUNDABLE'), '', $this->num(abs($netTax))];
            return $this->streamCsv("sales-tax-report-{$fromDate}-to-{$toDate}.csv", ['Date', 'Document', 'Party', 'Taxable', 'Tax'], $rows);
        }

        return view('accounting.reports.tax-report', compact(
            'sales', 'purchases', 'taxableSales', 'outputTax', 'taxablePurchases', 'inputTax', 'netTax', 'fromDate', 'toDate'
        ));
    }
}
