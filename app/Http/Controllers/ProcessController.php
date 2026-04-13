<?php

namespace App\Http\Controllers;

use App\Models\Process;
use App\Models\Task;
use App\Models\Notification;
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

        // Save template and metadata
        $validated['template'] = $request->input('template');

        $metadataFields = [
            'appellant_name', 'ntn_cnic', 'appellant_address', 'tax_year', 'section',
            'assessment_order_no', 'order_date', 'cira_order_no', 'cira_order_date',
            'cira_appeal_no', 'respondent_name', 'respondent_address', 'demand_amount',
            'amount_paid', 'balance_demand', 'grounds', 'prayer', 'stay_reasons',
        ];
        $metadata = [];
        foreach ($metadataFields as $field) {
            if ($request->filled($field)) {
                $metadata[$field] = $request->input($field);
            }
        }
        if (!empty($metadata)) {
            $validated['metadata'] = $metadata;
        }

        $process = Process::create($validated);
        $this->createTaskForAssignment($process);
        return redirect()->route('processes.show', $process)->with('success', 'Process created successfully');
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

        // Update metadata
        $metadataFields = [
            'appellant_name', 'ntn_cnic', 'appellant_address', 'tax_year', 'section',
            'assessment_order_no', 'order_date', 'cira_order_no', 'cira_order_date',
            'cira_appeal_no', 'respondent_name', 'respondent_address', 'demand_amount',
            'amount_paid', 'balance_demand', 'grounds', 'prayer', 'stay_reasons',
        ];
        $metadata = $process->metadata ?? [];
        foreach ($metadataFields as $field) {
            if ($request->has($field)) {
                $metadata[$field] = $request->input($field);
            }
        }
        $validated['metadata'] = $metadata;

        $oldAssignedTo = $process->assigned_to;
        $process->update($validated);

        if ($validated['assigned_to'] && $validated['assigned_to'] != $oldAssignedTo) {
            $this->createTaskForAssignment($process);
        }

        return redirect()->route('processes.show', $process)->with('success', 'Process updated successfully');
    }

    public function destroy(Process $process)
    {
        $process->delete();
        return redirect()->route('processes.index')->with('success', 'Process deleted successfully');
    }

    private function createTaskForAssignment($process)
    {
        if (!$process->assigned_to) return;

        $process->load('client', 'service');
        $task = Task::create([
            'title' => "[Process] {$process->title}",
            'description' => "Process assigned to you.\nService: {$process->service->display_name}\nClient: {$process->client->name}\nStage: " . ucfirst(str_replace('_', ' ', $process->stage)),
            'client_id' => $process->client_id,
            'created_by' => auth()->id(),
            'status' => 'pending',
            'due_date' => $process->due_date,
            'priority' => 1,
        ]);

        $task->assignedUsers()->attach($process->assigned_to);

        Notification::create([
            'user_id' => $process->assigned_to,
            'client_id' => $process->client_id,
            'title' => 'New Process Assigned',
            'message' => "{$process->title} - {$process->service->display_name}",
            'type' => 'task',
            'priority' => 'medium',
            'related_task_id' => $task->id,
        ]);
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
