<?php

namespace App\Http\Controllers;

use App\Models\Process;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class ProcessController extends Controller
{
    public function index(Request $request)
    {
        $query = Process::with('client', 'service', 'assignedTo');

        if ($request->stage) {
            $query->where('stage', $request->stage);
        }

        $processes = $query->orderBy('due_date')->paginate(15);

        $stats = [
            'intake' => Process::where('stage', 'intake')->count(),
            'in_progress' => Process::where('stage', 'in_progress')->count(),
            'review' => Process::where('stage', 'review')->count(),
            'completed' => Process::where('stage', 'completed')->count(),
        ];

        return view('processes.index', compact('processes', 'stats'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $services = Service::orderBy('display_name')->get();
        $users = User::orderBy('name')->get();
        return view('processes.create', compact('clients', 'services', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'service_id' => 'required|exists:services,id',
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'stage' => 'required|in:intake,in_progress,review,completed',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        Process::create($validated);
        return redirect()->route('processes.index')->with('success', 'Process created successfully');
    }

    public function show(Process $process)
    {
        $process->load('client', 'service', 'assignedTo');
        return view('processes.show', compact('process'));
    }

    public function edit(Process $process)
    {
        $clients = Client::orderBy('name')->get();
        $services = Service::orderBy('display_name')->get();
        $users = User::orderBy('name')->get();
        return view('processes.edit', compact('process', 'clients', 'services', 'users'));
    }

    public function update(Request $request, Process $process)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'service_id' => 'required|exists:services,id',
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'stage' => 'required|in:intake,in_progress,review,completed',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'completed_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $process->update($validated);
        return redirect()->route('processes.show', $process)->with('success', 'Process updated successfully');
    }

    public function destroy(Process $process)
    {
        $process->delete();
        return redirect()->route('processes.index')->with('success', 'Process deleted successfully');
    }

    public function updateStage(Request $request, Process $process)
    {
        $process->update(['stage' => $request->stage]);
        if ($request->stage === 'completed') {
            $process->update(['completed_date' => now()]);
        }
        return response()->json(['success' => true]);
    }
}
