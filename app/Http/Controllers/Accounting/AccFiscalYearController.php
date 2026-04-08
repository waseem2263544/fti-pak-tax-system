<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccFiscalYear;
use App\Models\AccJournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccFiscalYearController extends Controller
{
    /**
     * Display all fiscal years.
     */
    public function index()
    {
        $fiscalYears = AccFiscalYear::orderByDesc('start_date')->get();

        // Add stats for each fiscal year
        $fiscalYears->each(function ($fy) {
            $fy->journal_count = $fy->journalEntries()->where('is_posted', true)->count();
            $fy->total_transactions = $fy->journalEntries()->count();
        });

        return view('accounting.fiscal-years.index', compact('fiscalYears'));
    }

    /**
     * Show the form for creating a new fiscal year.
     */
    public function create()
    {
        // Suggest next fiscal year dates based on the latest one
        $latest = AccFiscalYear::orderByDesc('end_date')->first();
        $suggestedStart = $latest ? $latest->end_date->addDay()->toDateString() : now()->startOfYear()->toDateString();
        $suggestedEnd = $latest ? $latest->end_date->addDay()->addYear()->subDay()->toDateString() : now()->endOfYear()->toDateString();
        $suggestedName = $latest
            ? 'FY ' . $latest->end_date->addDay()->format('Y') . '-' . $latest->end_date->addDay()->addYear()->subDay()->format('Y')
            : 'FY ' . now()->format('Y') . '-' . now()->addYear()->format('Y');

        return view('accounting.fiscal-years.create', compact('suggestedStart', 'suggestedEnd', 'suggestedName'));
    }

    /**
     * Store a newly created fiscal year.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
            'is_active'  => 'boolean',
        ]);

        // Check for overlapping fiscal years
        $overlap = AccFiscalYear::where(function ($q) use ($validated) {
            $q->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
              ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
              ->orWhere(function ($q2) use ($validated) {
                  $q2->where('start_date', '<=', $validated['start_date'])
                     ->where('end_date', '>=', $validated['end_date']);
              });
        })->exists();

        if ($overlap) {
            return back()->withInput()->withErrors(['start_date' => 'The fiscal year dates overlap with an existing fiscal year.']);
        }

        $isActive = $request->boolean('is_active', false);

        // If setting this as active, deactivate others
        if ($isActive) {
            AccFiscalYear::where('is_active', true)->update(['is_active' => false]);
        }

        AccFiscalYear::create([
            'name'       => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date'   => $validated['end_date'],
            'is_closed'  => false,
            'is_active'  => $isActive,
        ]);

        return redirect()->route('accounting.fiscal-years.index')
            ->with('success', 'Fiscal year created successfully.');
    }

    /**
     * Display the specified fiscal year.
     */
    public function show(AccFiscalYear $fiscalYear)
    {
        $journalEntries = $fiscalYear->journalEntries()
            ->with('creator')
            ->latest('date')
            ->paginate(25);

        $stats = [
            'total_entries'  => $fiscalYear->journalEntries()->count(),
            'posted_entries' => $fiscalYear->journalEntries()->where('is_posted', true)->count(),
            'draft_entries'  => $fiscalYear->journalEntries()->where('is_posted', false)->count(),
            'total_amount'   => $fiscalYear->journalEntries()->where('is_posted', true)->sum('total_amount'),
        ];

        return view('accounting.fiscal-years.show', compact('fiscalYear', 'journalEntries', 'stats'));
    }

    /**
     * Show the form for editing the specified fiscal year.
     */
    public function edit(AccFiscalYear $fiscalYear)
    {
        if ($fiscalYear->is_closed) {
            return back()->with('error', 'Closed fiscal years cannot be edited.');
        }

        return view('accounting.fiscal-years.edit', compact('fiscalYear'));
    }

    /**
     * Update the specified fiscal year.
     */
    public function update(Request $request, AccFiscalYear $fiscalYear)
    {
        if ($fiscalYear->is_closed) {
            return back()->with('error', 'Closed fiscal years cannot be edited.');
        }

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
            'is_active'  => 'boolean',
        ]);

        // Check for overlapping fiscal years (excluding current)
        $overlap = AccFiscalYear::where('id', '!=', $fiscalYear->id)->where(function ($q) use ($validated) {
            $q->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
              ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
              ->orWhere(function ($q2) use ($validated) {
                  $q2->where('start_date', '<=', $validated['start_date'])
                     ->where('end_date', '>=', $validated['end_date']);
              });
        })->exists();

        if ($overlap) {
            return back()->withInput()->withErrors(['start_date' => 'The fiscal year dates overlap with an existing fiscal year.']);
        }

        $isActive = $request->boolean('is_active', false);

        // If setting this as active, deactivate others
        if ($isActive && !$fiscalYear->is_active) {
            AccFiscalYear::where('is_active', true)->update(['is_active' => false]);
        }

        $fiscalYear->update([
            'name'       => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date'   => $validated['end_date'],
            'is_active'  => $isActive,
        ]);

        return redirect()->route('accounting.fiscal-years.index')
            ->with('success', 'Fiscal year updated successfully.');
    }

    /**
     * Close (lock) the fiscal year.
     */
    public function close(AccFiscalYear $fiscalYear)
    {
        if ($fiscalYear->is_closed) {
            return back()->with('error', 'This fiscal year is already closed.');
        }

        // Check for unposted journal entries
        $unpostedCount = $fiscalYear->journalEntries()->where('is_posted', false)->count();
        if ($unpostedCount > 0) {
            return back()->with('error', "Cannot close fiscal year: there are {$unpostedCount} unposted journal entries. Please post or delete them first.");
        }

        DB::beginTransaction();

        try {
            $fiscalYear->update([
                'is_closed' => true,
                'is_active' => false,
            ]);

            // Activate the next fiscal year if one exists
            $nextFY = AccFiscalYear::where('start_date', '>', $fiscalYear->end_date)
                ->where('is_closed', false)
                ->orderBy('start_date')
                ->first();

            if ($nextFY) {
                // Deactivate all first, then activate the next
                AccFiscalYear::where('is_active', true)->update(['is_active' => false]);
                $nextFY->update(['is_active' => true]);
            }

            DB::commit();

            return back()->with('success', 'Fiscal year closed successfully.' . ($nextFY ? ' ' . $nextFY->name . ' is now the active fiscal year.' : ''));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to close fiscal year: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified fiscal year (only if no journal entries).
     */
    public function destroy(AccFiscalYear $fiscalYear)
    {
        if ($fiscalYear->is_closed) {
            return back()->with('error', 'Closed fiscal years cannot be deleted.');
        }

        if ($fiscalYear->journalEntries()->exists()) {
            return back()->with('error', 'Cannot delete fiscal year with existing journal entries.');
        }

        $fiscalYear->delete();

        return redirect()->route('accounting.fiscal-years.index')
            ->with('success', 'Fiscal year deleted successfully.');
    }
}
