<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccAccount;
use App\Models\AccFiscalYear;
use App\Models\AccJournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalEntryController extends Controller
{
    /**
     * Display journal entries with date filter.
     */
    public function index(Request $request)
    {
        $query = AccJournalEntry::with(['fiscalYear', 'creator'])->latest('date');

        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }

        if ($request->filled('status')) {
            if ($request->status === 'posted') {
                $query->where('is_posted', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_posted', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                  ->orWhere('narration', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $entries = $query->paginate(25)->withQueryString();

        return view('accounting.journal-entries.index', compact('entries'));
    }

    /**
     * Show the form for creating a new journal entry.
     */
    public function create()
    {
        $accounts = AccAccount::active()->orderBy('code')->get();
        $nextNumber = AccJournalEntry::nextNumber();

        return view('accounting.journal-entries.create', compact('accounts', 'nextNumber'));
    }

    /**
     * Store a newly created journal entry with lines.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'                 => 'required|date',
            'reference'            => 'nullable|string|max:255',
            'narration'            => 'nullable|string',
            'lines'                => 'required|array|min:2',
            'lines.*.account_id'   => 'required|exists:acc_accounts,id',
            'lines.*.debit'        => 'nullable|numeric|min:0',
            'lines.*.credit'       => 'nullable|numeric|min:0',
            'lines.*.description'  => 'nullable|string|max:255',
            'auto_post'            => 'nullable|boolean',
        ]);

        // Validate that debits equal credits
        $totalDebit = collect($validated['lines'])->sum('debit');
        $totalCredit = collect($validated['lines'])->sum('credit');

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return back()->withInput()->withErrors(['lines' => 'Total debits must equal total credits. Debit: ' . number_format($totalDebit, 2) . ' Credit: ' . number_format($totalCredit, 2)]);
        }

        if ($totalDebit == 0) {
            return back()->withInput()->withErrors(['lines' => 'Journal entry must have a non-zero amount.']);
        }

        // Validate each line has either debit or credit (not both)
        foreach ($validated['lines'] as $index => $line) {
            $debit = $line['debit'] ?? 0;
            $credit = $line['credit'] ?? 0;
            if ($debit > 0 && $credit > 0) {
                return back()->withInput()->withErrors(["lines.{$index}" => 'A line cannot have both debit and credit.']);
            }
            if ($debit == 0 && $credit == 0) {
                return back()->withInput()->withErrors(["lines.{$index}" => 'A line must have either a debit or credit amount.']);
            }
        }

        DB::beginTransaction();

        try {
            $fy = AccFiscalYear::getForDate($validated['date']);

            if (!$fy) {
                return back()->withInput()->withErrors(['date' => 'No fiscal year found for the selected date.']);
            }

            if ($fy->is_closed) {
                return back()->withInput()->withErrors(['date' => 'The fiscal year for this date is closed.']);
            }

            $je = AccJournalEntry::create([
                'entry_number'  => AccJournalEntry::nextNumber(),
                'date'          => $validated['date'],
                'fiscal_year_id'=> $fy->id,
                'reference'     => $validated['reference'],
                'narration'     => $validated['narration'],
                'source_type'   => 'manual',
                'total_amount'  => $totalDebit,
                'is_posted'     => false,
                'created_by'    => auth()->id(),
            ]);

            $lines = collect($validated['lines'])->map(function ($line) {
                return [
                    'account_id'  => $line['account_id'],
                    'debit'       => $line['debit'] ?? 0,
                    'credit'      => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ];
            })->toArray();

            $je->lines()->createMany($lines);

            // Auto-post if requested
            if ($request->boolean('auto_post')) {
                $je->post();
            }

            DB::commit();

            return redirect()->route('accounting.journal-entries.show', $je)
                ->with('success', 'Journal entry created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified journal entry with lines.
     */
    public function show(AccJournalEntry $journalEntry)
    {
        $journalEntry->load(['lines.account', 'fiscalYear', 'creator']);

        return view('accounting.journal-entries.show', compact('journalEntry'));
    }

    /**
     * Show the form for editing a journal entry (only if not posted).
     */
    public function edit(AccJournalEntry $journalEntry)
    {
        if ($journalEntry->is_posted) {
            return back()->with('error', 'Posted journal entries cannot be edited.');
        }

        $journalEntry->load('lines');
        $accounts = AccAccount::active()->orderBy('code')->get();

        return view('accounting.journal-entries.edit', compact('journalEntry', 'accounts'));
    }

    /**
     * Update the specified journal entry.
     */
    public function update(Request $request, AccJournalEntry $journalEntry)
    {
        if ($journalEntry->is_posted) {
            return back()->with('error', 'Posted journal entries cannot be edited.');
        }

        $validated = $request->validate([
            'date'                 => 'required|date',
            'reference'            => 'nullable|string|max:255',
            'narration'            => 'nullable|string',
            'lines'                => 'required|array|min:2',
            'lines.*.account_id'   => 'required|exists:acc_accounts,id',
            'lines.*.debit'        => 'nullable|numeric|min:0',
            'lines.*.credit'       => 'nullable|numeric|min:0',
            'lines.*.description'  => 'nullable|string|max:255',
        ]);

        $totalDebit = collect($validated['lines'])->sum('debit');
        $totalCredit = collect($validated['lines'])->sum('credit');

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return back()->withInput()->withErrors(['lines' => 'Total debits must equal total credits.']);
        }

        DB::beginTransaction();

        try {
            $fy = AccFiscalYear::getForDate($validated['date']);

            if (!$fy) {
                return back()->withInput()->withErrors(['date' => 'No fiscal year found for the selected date.']);
            }

            if ($fy->is_closed) {
                return back()->withInput()->withErrors(['date' => 'The fiscal year for this date is closed.']);
            }

            $journalEntry->update([
                'date'          => $validated['date'],
                'fiscal_year_id'=> $fy->id,
                'reference'     => $validated['reference'],
                'narration'     => $validated['narration'],
                'total_amount'  => $totalDebit,
            ]);

            // Delete existing lines and recreate
            $journalEntry->lines()->delete();

            $lines = collect($validated['lines'])->map(function ($line) {
                return [
                    'account_id'  => $line['account_id'],
                    'debit'       => $line['debit'] ?? 0,
                    'credit'      => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ];
            })->toArray();

            $journalEntry->lines()->createMany($lines);

            DB::commit();

            return redirect()->route('accounting.journal-entries.show', $journalEntry)
                ->with('success', 'Journal entry updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update journal entry: ' . $e->getMessage());
        }
    }

    /**
     * Post the journal entry.
     */
    public function post(AccJournalEntry $journalEntry)
    {
        if ($journalEntry->is_posted) {
            return back()->with('error', 'Journal entry is already posted.');
        }

        if (!$journalEntry->isBalanced()) {
            return back()->with('error', 'Cannot post: journal entry is not balanced.');
        }

        $journalEntry->post();

        return back()->with('success', 'Journal entry posted successfully.');
    }

    /**
     * Create a reversing journal entry.
     */
    public function reverse(AccJournalEntry $journalEntry)
    {
        if (!$journalEntry->is_posted) {
            return back()->with('error', 'Only posted entries can be reversed.');
        }

        if ($journalEntry->is_reversed) {
            return back()->with('error', 'This journal entry has already been reversed.');
        }

        DB::beginTransaction();

        try {
            $fy = AccFiscalYear::getForDate(now());

            if (!$fy) {
                return back()->with('error', 'No fiscal year found for today.');
            }

            if ($fy->is_closed) {
                return back()->with('error', 'Current fiscal year is closed.');
            }

            $reversingJE = AccJournalEntry::create([
                'entry_number'  => AccJournalEntry::nextNumber(),
                'date'          => now()->toDateString(),
                'fiscal_year_id'=> $fy->id,
                'reference'     => 'Reversal of ' . $journalEntry->entry_number,
                'narration'     => 'Reversal: ' . ($journalEntry->narration ?? $journalEntry->entry_number),
                'source_type'   => 'reversal',
                'source_id'     => $journalEntry->id,
                'total_amount'  => $journalEntry->total_amount,
                'is_posted'     => false,
                'created_by'    => auth()->id(),
            ]);

            // Create reversed lines (swap debit and credit)
            $reversedLines = $journalEntry->lines->map(function ($line) {
                return [
                    'account_id'  => $line->account_id,
                    'debit'       => $line->credit,
                    'credit'      => $line->debit,
                    'description' => 'Reversal: ' . ($line->description ?? ''),
                ];
            })->toArray();

            $reversingJE->lines()->createMany($reversedLines);
            $reversingJE->post();

            // Mark the original as reversed
            $journalEntry->update([
                'is_reversed' => true,
                'reversed_by' => $reversingJE->id,
            ]);

            DB::commit();

            return redirect()->route('accounting.journal-entries.show', $reversingJE)
                ->with('success', 'Reversing journal entry created and posted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reverse: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified journal entry (only if not posted).
     */
    public function destroy(AccJournalEntry $journalEntry)
    {
        if ($journalEntry->is_posted) {
            return back()->with('error', 'Posted journal entries cannot be deleted. Reverse it instead.');
        }

        DB::beginTransaction();

        try {
            $journalEntry->lines()->delete();
            $journalEntry->delete();

            DB::commit();

            return redirect()->route('accounting.journal-entries.index')
                ->with('success', 'Journal entry deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete journal entry: ' . $e->getMessage());
        }
    }
}
