<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\Department;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    /**
     * Display a listing of positions
     */
    public function index(Request $request)
    {
        $query = Position::with('department');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('position_name', 'like', '%'.$search.'%')
                  ->orWhere('position_code', 'like', '%'.$search.'%')
                  ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        // Department filter
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->get('department_id'));
        }

        // Status filter
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->get('is_active'));
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $positions = $query->latest()->paginate($perPage)->withQueryString();

        $departments = Department::where('is_active', true)->get();

        return view('admin.hrm.positions.index', compact('positions', 'departments'));
    }

    /**
     * Show the form for creating a new position
     */
    public function create()
    {
        $departments = Department::where('is_active', true)->get();

        return view('admin.hrm.positions.create', compact('departments'));
    }

    /**
     * Store a newly created position
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'position_name' => 'required|string|max:255',
            'position_code' => 'required|string|unique:positions,position_code',
            'department_id' => 'required|exists:departments,id',
            'description' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'requirements' => 'nullable|string',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0',
            'level' => 'nullable|in:entry,junior,mid,senior,lead,manager,director,executive',
            'is_active' => 'boolean',
        ]);

        Position::create($validated);

        return redirect()->route('admin.hrm.positions.index')
                        ->with('success', __('Position created successfully'));
    }

    /**
     * Display the specified position
     */
    public function show(Position $position)
    {
        $position->load(['department', 'employees']);

        return view('admin.hrm.positions.show', compact('position'));
    }

    /**
     * Show the form for editing the specified position
     */
    public function edit(Position $position)
    {
        $departments = Department::where('is_active', true)->get();

        return view('admin.hrm.positions.edit', compact('position', 'departments'));
    }

    /**
     * Update the specified position
     */
    public function update(Request $request, Position $position)
    {
        $validated = $request->validate([
            'position_name' => 'required|string|max:255',
            'position_code' => 'required|string|unique:positions,position_code,'.$position->id,
            'department_id' => 'required|exists:departments,id',
            'description' => 'nullable|string',
            'responsibilities' => 'nullable|string',
            'requirements' => 'nullable|string',
            'min_salary' => 'nullable|numeric|min:0',
            'max_salary' => 'nullable|numeric|min:0',
            'level' => 'nullable|in:entry,junior,mid,senior,lead,manager,director,executive',
            'is_active' => 'boolean',
        ]);

        $position->update($validated);

        return redirect()->route('admin.hrm.positions.index')
                        ->with('success', __('Position updated successfully'));
    }

    /**
     * Remove the specified position
     */
    public function destroy(Position $position)
    {
        // Check if position has employees
        if ($position->employees()->count() > 0) {
            return redirect()->route('admin.hrm.positions.index')
                            ->with('error', __('Cannot delete position that has employees assigned to it'));
        }

        $position->delete();

        return redirect()->route('admin.hrm.positions.index')
                        ->with('success', __('Position deleted successfully'));
    }
}
