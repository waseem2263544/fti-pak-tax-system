<?php

namespace App\Http\Controllers;

use App\Models\AutomatedTask;
use App\Models\Service;
use App\Models\Role;
use Illuminate\Http\Request;

class AutomatedTaskController extends Controller
{
    public function index()
    {
        $automations = AutomatedTask::with('service')->orderBy('name')->paginate(15);
        return view('automated-tasks.index', compact('automations'));
    }

    public function create()
    {
        $services = Service::orderBy('display_name')->get();
        $roles = Role::all();
        return view('automated-tasks.create', compact('services', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|in:deadline_based,date_based,recurring,event_based',
            'trigger_value' => 'nullable|string|max:255',
            'service_id' => 'nullable|exists:services,id',
            'task_template' => 'required|string|max:255',
            'priority' => 'required|in:0,1,2',
            'assign_to_roles' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        AutomatedTask::create($validated);
        return redirect()->route('automated-tasks.index')->with('success', 'Automation created successfully');
    }

    public function edit(AutomatedTask $automatedTask)
    {
        $services = Service::orderBy('display_name')->get();
        $roles = Role::all();
        return view('automated-tasks.edit', compact('automatedTask', 'services', 'roles'));
    }

    public function update(Request $request, AutomatedTask $automatedTask)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|in:deadline_based,date_based,recurring,event_based',
            'trigger_value' => 'nullable|string|max:255',
            'service_id' => 'nullable|exists:services,id',
            'task_template' => 'required|string|max:255',
            'priority' => 'required|in:0,1,2',
            'assign_to_roles' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $automatedTask->update($validated);
        return redirect()->route('automated-tasks.index')->with('success', 'Automation updated successfully');
    }

    public function destroy(AutomatedTask $automatedTask)
    {
        $automatedTask->delete();
        return redirect()->route('automated-tasks.index')->with('success', 'Automation deleted successfully');
    }

    public function toggle(AutomatedTask $automatedTask)
    {
        $automatedTask->update(['is_active' => !$automatedTask->is_active]);
        return response()->json(['success' => true, 'is_active' => $automatedTask->is_active]);
    }
}
