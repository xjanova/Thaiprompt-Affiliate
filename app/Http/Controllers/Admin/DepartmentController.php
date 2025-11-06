<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::with(['parent', 'manager', 'employees']);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                  ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        $departments = $query->latest()->paginate(20)->withQueryString();

        return view('admin.hrm.departments.index', compact('departments'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->get();
        $managers = User::whereIn('role', ['admin', 'manager'])->get();

        return view('admin.hrm.departments.create', compact('departments', 'managers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:departments,code',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'budget' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $department = Department::create($validated);

        return redirect()->route('admin.hrm.departments.index')
                       ->with('success', 'Department created successfully');
    }

    public function show(Department $department)
    {
        $department->load(['parent', 'children', 'manager', 'employees.position', 'positions']);

        return view('admin.hrm.departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        $departments = Department::where('is_active', true)
                                ->where('id', '!=', $department->id)
                                ->get();
        $managers = User::whereIn('role', ['admin', 'manager'])->get();

        return view('admin.hrm.departments.edit', compact('department', 'departments', 'managers'));
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'budget' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $department->update($validated);

        return redirect()->route('admin.hrm.departments.show', $department)
                       ->with('success', 'Department updated successfully');
    }

    public function destroy(Department $department)
    {
        // Check if department has employees
        if ($department->employees()->count() > 0) {
            return back()->with('error', 'Cannot delete department with active employees');
        }

        $department->delete();

        return redirect()->route('admin.hrm.departments.index')
                       ->with('success', 'Department deleted successfully');
    }
}
