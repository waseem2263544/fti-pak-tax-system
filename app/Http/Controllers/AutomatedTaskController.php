<?php

namespace App\Http\Controllers;

use App\Models\AutomatedTask;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class AutomatedTaskController extends Controller
{
    public function index()
    {
        $automations = AutomatedTask::with('service', 'assignedUser')->orderBy('name')->paginate(15);
        return view('scheduled-tasks.index', compact('automations'));
    }

    public function create()
    {
        $services = Service::orderBy('display_name')->get();
        $users = User::orderBy('name')->get();
        return view('scheduled-tasks.create', compact('services', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|in:monthly,yearly,weekly,daily',
            'trigger_value' => 'nullable|string|max:255',
            'service_id' => 'required|exists:services,id',
            'task_template' => 'required|string|max:255',
            'priority' => 'required|in:0,1,2',
            'assign_to_user' => 'required|exists:users,id',
        ]);

        $validated['is_active'] = $request->has('is_active');

        AutomatedTask::create($validated);
        return redirect()->route('scheduled-tasks.index')->with('success', 'Scheduled task created successfully');
    }

    public function edit(AutomatedTask $automatedTask)
    {
        $services = Service::orderBy('display_name')->get();
        $users = User::orderBy('name')->get();
        return view('scheduled-tasks.edit', compact('automatedTask', 'services', 'users'));
    }

    public function update(Request $request, AutomatedTask $automatedTask)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|in:monthly,yearly,weekly,daily',
            'trigger_value' => 'nullable|string|max:255',
            'service_id' => 'required|exists:services,id',
            'task_template' => 'required|string|max:255',
            'priority' => 'required|in:0,1,2',
            'assign_to_user' => 'required|exists:users,id',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $automatedTask->update($validated);
        return redirect()->route('scheduled-tasks.index')->with('success', 'Scheduled task updated');
    }

    public function destroy(AutomatedTask $automatedTask)
    {
        $automatedTask->delete();
        return redirect()->route('scheduled-tasks.index')->with('success', 'Scheduled task deleted');
    }

    public function toggle(AutomatedTask $automatedTask)
    {
        $automatedTask->update(['is_active' => !$automatedTask->is_active]);
        return response()->json(['success' => true, 'is_active' => $automatedTask->is_active]);
    }

    public function runNow(AutomatedTask $automatedTask)
    {
        $result = app(\App\Console\Commands\RunScheduledTasks::class)->executeRule($automatedTask);
        return redirect()->route('scheduled-tasks.index')->with('success', "Ran \"{$automatedTask->name}\": {$result} tasks created.");
    }
}
