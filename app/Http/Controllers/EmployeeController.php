<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * Display all employees.
     */
    public function index()
    {
        $employees = User::with('roles')->orderBy('name')->paginate(15);
        return view('employees.index', compact('employees'));
    }

    /**
     * Show employee creation form.
     */
    public function create()
    {
        $roles = Role::all();
        return view('employees.create', compact('roles'));
    }

    /**
     * Store a newly created employee.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->roles()->attach($validated['roles']);

        return redirect()->route('employees.show', $user)->with('success', 'Employee created successfully');
    }

    /**
     * Show employee details.
     */
    public function show(User $employee)
    {
        $employee->load('roles', 'tasks', 'notifications');
        return view('employees.show', compact('employee'));
    }

    /**
     * Show edit form.
     */
    public function edit(User $employee)
    {
        $roles = Role::all();
        $employee->load('roles');
        return view('employees.edit', compact('employee', 'roles'));
    }

    /**
     * Update employee.
     */
    public function update(Request $request, User $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $employee->id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $employee->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($request->password) {
            $employee->update(['password' => Hash::make($validated['password'])]);
        }

        $employee->roles()->sync($validated['roles']);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated successfully');
    }

    /**
     * Delete employee.
     */
    public function destroy(User $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully');
    }
}
