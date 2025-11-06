<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingEnrollment;
use App\Models\TrainingCourse;
use App\Models\Employee;
use Illuminate\Http\Request;

class TrainingEnrollmentController extends Controller
{
    /**
     * Display a listing of training enrollments
     */
    public function index(Request $request)
    {
        $query = TrainingEnrollment::with(['employee', 'trainingCourse']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('employee', function($q) use ($search) {
                $q->where('first_name', 'like', '%'.$search.'%')
                  ->orWhere('last_name', 'like', '%'.$search.'%');
            });
        }

        // Course filter
        if ($request->filled('training_course_id')) {
            $query->where('training_course_id', $request->get('training_course_id'));
        }

        // Status filter
        if ($request->filled('enrollment_status')) {
            $query->where('enrollment_status', $request->get('enrollment_status'));
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $enrollments = $query->latest()->paginate($perPage)->withQueryString();

        $courses = TrainingCourse::where('status', 'active')->get();
        $employees = Employee::where('employment_status', 'active')->get();

        return view('admin.hrm.training.enrollments.index', compact('enrollments', 'courses', 'employees'));
    }

    /**
     * Show the form for creating a new training enrollment
     */
    public function create()
    {
        $courses = TrainingCourse::where('status', 'active')->get();
        $employees = Employee::where('employment_status', 'active')->get();

        return view('admin.hrm.training.enrollments.create', compact('courses', 'employees'));
    }

    /**
     * Store a newly created training enrollment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'training_course_id' => 'required|exists:training_courses,id',
            'employee_id' => 'required|exists:employees,id',
            'training_start_date' => 'required|date',
            'training_end_date' => 'required|date|after:training_start_date',
            'enrollment_type' => 'required|in:mandatory,voluntary,recommended',
            'enrollment_status' => 'required|in:enrolled,in_progress,completed,failed,cancelled,no_show',
        ]);

        $validated['enrolled_by'] = auth()->id();
        $validated['enrollment_date'] = now();

        TrainingEnrollment::create($validated);

        return redirect()->route('admin.hrm.training.enrollments.index')
                        ->with('success', __('Training enrollment created successfully'));
    }

    /**
     * Display the specified training enrollment
     */
    public function show(TrainingEnrollment $enrollment)
    {
        $enrollment->load(['employee', 'trainingCourse', 'enrolledBy']);

        return view('admin.hrm.training.enrollments.show', compact('enrollment'));
    }

    /**
     * Show the form for editing the specified training enrollment
     */
    public function edit(TrainingEnrollment $enrollment)
    {
        $courses = TrainingCourse::where('status', 'active')->get();
        $employees = Employee::where('employment_status', 'active')->get();

        return view('admin.hrm.training.enrollments.edit', compact('enrollment', 'courses', 'employees'));
    }

    /**
     * Update the specified training enrollment
     */
    public function update(Request $request, TrainingEnrollment $enrollment)
    {
        $validated = $request->validate([
            'training_course_id' => 'required|exists:training_courses,id',
            'employee_id' => 'required|exists:employees,id',
            'training_start_date' => 'required|date',
            'training_end_date' => 'required|date|after:training_start_date',
            'enrollment_type' => 'required|in:mandatory,voluntary,recommended',
            'enrollment_status' => 'required|in:enrolled,in_progress,completed,failed,cancelled,no_show',
            'completion_date' => 'nullable|date',
            'attendance_percentage' => 'nullable|numeric|min:0|max:100',
            'final_score' => 'nullable|numeric|min:0|max:100',
            'feedback' => 'nullable|string',
            'trainer_comments' => 'nullable|string',
        ]);

        $enrollment->update($validated);

        return redirect()->route('admin.hrm.training.enrollments.index')
                        ->with('success', __('Training enrollment updated successfully'));
    }

    /**
     * Remove the specified training enrollment
     */
    public function destroy(TrainingEnrollment $enrollment)
    {
        $enrollment->delete();

        return redirect()->route('admin.hrm.training.enrollments.index')
                        ->with('success', __('Training enrollment deleted successfully'));
    }
}
