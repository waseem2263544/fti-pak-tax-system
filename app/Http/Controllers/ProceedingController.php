<?php

namespace App\Http\Controllers;

use App\Models\Proceeding;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;

class ProceedingController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'department');

        $department = Proceeding::with('client', 'assignedTo')
            ->where('stage', 'department')->orderBy('hearing_date')->get();
        $commissioner = Proceeding::with('client', 'assignedTo')
            ->where('stage', 'commissioner_appeals')->orderBy('hearing_date')->get();
        $tribunal = Proceeding::with('client', 'assignedTo')
            ->where('stage', 'tribunal')->orderBy('hearing_date')->get();

        return view('proceedings.index', compact('department', 'commissioner', 'tribunal', 'tab'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        return view('proceedings.create', compact('clients', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'stage' => 'required|in:department,commissioner_appeals,tribunal',
            'case_number' => 'nullable|string|max:255',
            'tax_year' => 'nullable|string|max:255',
            'section' => 'nullable|string|max:255',
            'hearing_date' => 'nullable|date',
            'status' => 'required|in:pending,adjourned,decided,appealed',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        Proceeding::create($validated);

        // If created from an FBR notice, mark the notice as actioned
        if ($request->filled('fbr_notice_id')) {
            \App\Models\FbrNotice::where('id', $request->fbr_notice_id)->update(['status' => 'resolved']);
        }

        // Redirect back to FBR notices if came from there
        if ($request->filled('from_fbr')) {
            return redirect()->route('fbr-notices.index')->with('success', 'Proceeding created and notice marked as actioned');
        }

        return redirect()->route('proceedings.index', ['tab' => $validated['stage']])->with('success', 'Proceeding added successfully');
    }

    public function show(Proceeding $proceeding)
    {
        $proceeding->load('client', 'assignedTo');
        return view('proceedings.show', compact('proceeding'));
    }

    public function edit(Proceeding $proceeding)
    {
        $clients = Client::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        return view('proceedings.edit', compact('proceeding', 'clients', 'users'));
    }

    public function update(Request $request, Proceeding $proceeding)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'stage' => 'required|in:department,commissioner_appeals,tribunal',
            'case_number' => 'nullable|string|max:255',
            'tax_year' => 'nullable|string|max:255',
            'section' => 'nullable|string|max:255',
            'hearing_date' => 'nullable|date',
            'order_date' => 'nullable|date',
            'status' => 'required|in:pending,adjourned,decided,appealed',
            'outcome' => 'nullable|string',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $proceeding->update($validated);
        return redirect()->route('proceedings.show', $proceeding)->with('success', 'Proceeding updated successfully');
    }

    public function destroy(Proceeding $proceeding)
    {
        $stage = $proceeding->stage;
        $proceeding->delete();
        return redirect()->route('proceedings.index', ['tab' => $stage])->with('success', 'Proceeding deleted successfully');
    }
}
