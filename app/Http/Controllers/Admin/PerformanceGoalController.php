<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PerformanceGoal;
use App\Models\Employee;
use Illuminate\Http\Request;

class PerformanceGoalController extends Controller
{
    /**
     * Display a listing of performance goals
     */
    public function index(Request $request)
    {
        $query = PerformanceGoal::with(['employee', 'assignedBy']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('goal_title', 'like', '%'.$search.'%')
                  ->orWhere('goal_description', 'like', '%'.$search.'%')
                  ->orWhereHas('employee', function($eq) use ($search) {
                      $eq->where('first_name', 'like', '%'.$search.'%')
                         ->orWhere('last_name', 'like', '%'.$search.'%');
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Employee filter
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->get('employee_id'));
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $goals = $query->latest()->paginate($perPage)->withQueryString();

        $employees = Employee::where('employment_status', 'active')->get();

        return view('admin.hrm.performance.goals.index', compact('goals', 'employees'));
    }

    /**
     * Show the form for creating a new performance goal
     */
    public function create()
    {
        $employees = Employee::where('employment_status', 'active')->get();

        return view('admin.hrm.performance.goals.create', compact('employees'));
    }

    /**
     * Store a newly created performance goal
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'goal_title' => 'required|string|max:255',
            'goal_description' => 'required|string',
            'goal_type' => 'required|in:individual,team,departmental,organizational',
            'category' => 'required|in:productivity,quality,learning,behavior,project,other',
            'start_date' => 'required|date',
            'target_date' => 'required|date|after:start_date',
            'priority' => 'required|in:low,medium,high,critical',
            'measurement_criteria' => 'nullable|string',
            'target_value' => 'nullable|string',
            'weight_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['assigned_by'] = auth()->id();
        $validated['status'] = 'not_started';

        PerformanceGoal::create($validated);

        return redirect()->route('admin.hrm.performance.goals.index')
                        ->with('success', __('Performance goal created successfully'));
    }

    /**
     * Display the specified performance goal
     */
    public function show(PerformanceGoal $goal)
    {
        $goal->load(['employee', 'assignedBy']);

        return view('admin.hrm.performance.goals.show', compact('goal'));
    }

    /**
     * Show the form for editing the specified performance goal
     */
    public function edit(PerformanceGoal $goal)
    {
        $employees = Employee::where('employment_status', 'active')->get();

        return view('admin.hrm.performance.goals.edit', compact('goal', 'employees'));
    }

    /**
     * Update the specified performance goal
     */
    public function update(Request $request, PerformanceGoal $goal)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'goal_title' => 'required|string|max:255',
            'goal_description' => 'required|string',
            'goal_type' => 'required|in:individual,team,departmental,organizational',
            'category' => 'required|in:productivity,quality,learning,behavior,project,other',
            'start_date' => 'required|date',
            'target_date' => 'required|date|after:start_date',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:not_started,in_progress,achieved,partially_achieved,not_achieved,cancelled',
            'measurement_criteria' => 'nullable|string',
            'target_value' => 'nullable|string',
            'current_value' => 'nullable|string',
            'weight_percentage' => 'nullable|numeric|min:0|max:100',
            'progress_percentage' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $goal->update($validated);

        return redirect()->route('admin.hrm.performance.goals.index')
                        ->with('success', __('Performance goal updated successfully'));
    }

    /**
     * Remove the specified performance goal
     */
    public function destroy(PerformanceGoal $goal)
    {
        $goal->delete();

        return redirect()->route('admin.hrm.performance.goals.index')
                        ->with('success', __('Performance goal deleted successfully'));
    }
}
