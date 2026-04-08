<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccContact;
use Illuminate\Http\Request;

class AccContactController extends Controller
{
    /**
     * Display all contacts (vendors).
     */
    public function index(Request $request)
    {
        $query = AccContact::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('ntn', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        } else {
            // Default to showing vendors
            $query->where('type', 'vendor');
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->boolean('status'));
        }

        $contacts = $query->orderBy('name')->paginate(25)->withQueryString();

        return view('accounting.contacts.index', compact('contacts'));
    }

    /**
     * Show the form for creating a new contact.
     */
    public function create()
    {
        return view('accounting.contacts.create');
    }

    /**
     * Store a newly created contact.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'type'            => 'required|in:vendor,supplier,other',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:50',
            'address'         => 'nullable|string',
            'ntn'             => 'nullable|string|max:50',
            'strn'            => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_active'       => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        AccContact::create($validated);

        return redirect()->route('accounting.contacts.index')
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Display the specified contact.
     */
    public function show(AccContact $contact)
    {
        $contact->load(['purchaseInvoices' => fn($q) => $q->latest('date')->limit(20)]);
        $contact->load(['payments' => fn($q) => $q->latest('date')->limit(20)]);

        // Calculate totals
        $totalBilled = $contact->purchaseInvoices()->sum('total');
        $totalPaid = $contact->purchaseInvoices()->sum('amount_paid');
        $totalOutstanding = $contact->purchaseInvoices()->sum('balance_due');

        return view('accounting.contacts.show', compact('contact', 'totalBilled', 'totalPaid', 'totalOutstanding'));
    }

    /**
     * Show the form for editing the specified contact.
     */
    public function edit(AccContact $contact)
    {
        return view('accounting.contacts.edit', compact('contact'));
    }

    /**
     * Update the specified contact.
     */
    public function update(Request $request, AccContact $contact)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'type'            => 'required|in:vendor,supplier,other',
            'email'           => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:50',
            'address'         => 'nullable|string',
            'ntn'             => 'nullable|string|max:50',
            'strn'            => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric|min:0',
            'is_active'       => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $contact->update($validated);

        return redirect()->route('accounting.contacts.index')
            ->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified contact (only if no invoices/payments).
     */
    public function destroy(AccContact $contact)
    {
        if ($contact->purchaseInvoices()->exists()) {
            return back()->with('error', 'Cannot delete contact with existing purchase invoices.');
        }

        if ($contact->payments()->exists()) {
            return back()->with('error', 'Cannot delete contact with existing payment vouchers.');
        }

        $contact->delete();

        return redirect()->route('accounting.contacts.index')
            ->with('success', 'Contact deleted successfully.');
    }
}
