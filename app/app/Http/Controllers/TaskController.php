<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display all tasks with filters.
     */
    public function index(Request $request)
    {
        $query = Task::with('createdBy', 'client', 'assignedUsers');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->assigned_to) {
            $query->whereHas('assignedUsers', function ($q) {
                $q->where('user_id', request('assigned_to'));
            });
        }

        $tasks = $query->orderBy('due_date')->paginate(15);
        $clients = Client::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('tasks.index', compact('tasks', 'clients', 'users'));
    }

    /**
     * Show task creation form.
     */
    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        return view('tasks.create', compact('clients', 'users'));
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'status' => 'required|in:pending,in_progress,completed,overdue',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:0,1,2',
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'exists:users,id',
        ]);

        $validated['created_by'] = auth()->id();

        $task = Task::create($validated);

        if ($request->assigned_users) {
            $task->assignedUsers()->attach($request->assigned_users);
        }

        return redirect()->route('tasks.show', $task)->with('success', 'Task created successfully');
    }

    /**
     * Show task details.
     */
    public function show(Task $task)
    {
        $task->load('createdBy', 'client', 'assignedUsers');
        return view('tasks.show', compact('task'));
    }

    /**
     * Show edit form.
     */
    public function edit(Task $task)
    {
        $clients = Client::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $task->load('assignedUsers');
        return view('tasks.edit', compact('task', 'clients', 'users'));
    }

    /**
     * Update task.
     */
    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'status' => 'required|in:pending,in_progress,completed,overdue',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:0,1,2',
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'exists:users,id',
        ]);

        $task->update($validated);

        $task->assignedUsers()->sync($request->assigned_users ?? []);

        return redirect()->route('tasks.show', $task)->with('success', 'Task updated successfully');
    }

    /**
     * Delete task.
     */
    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully');
    }

    /**
     * Update task status (for quick status change).
     */
    public function updateStatus(Request $request, Task $task)
    {
        $task->update(['status' => $request->status]);
        return response()->json(['success' => true]);
    }
}
