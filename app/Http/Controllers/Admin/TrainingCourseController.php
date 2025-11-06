<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingCourse;
use Illuminate\Http\Request;

class TrainingCourseController extends Controller
{
    /**
     * Display a listing of training courses
     */
    public function index(Request $request)
    {
        $query = TrainingCourse::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('course_name', 'like', '%'.$search.'%')
                  ->orWhere('course_code', 'like', '%'.$search.'%')
                  ->orWhere('course_description', 'like', '%'.$search.'%');
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('course_category', $request->get('category'));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $courses = $query->latest()->paginate($perPage)->withQueryString();

        return view('admin.hrm.training.courses.index', compact('courses'));
    }

    /**
     * Show the form for creating a new training course
     */
    public function create()
    {
        return view('admin.hrm.training.courses.create');
    }

    /**
     * Store a newly created training course
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_name' => 'required|string|max:255',
            'course_code' => 'required|string|unique:training_courses,course_code',
            'course_category' => 'required|in:technical,soft_skills,leadership,compliance,safety,product,sales,other',
            'course_description' => 'required|string',
            'course_objectives' => 'nullable|string',
            'course_duration_hours' => 'required|numeric|min:0',
            'training_method' => 'required|in:classroom,online,hybrid,on_the_job,workshop,seminar',
            'max_participants' => 'nullable|integer|min:1',
            'prerequisites' => 'nullable|string',
            'course_materials' => 'nullable|string',
            'certification_provided' => 'nullable|boolean',
            'certification_name' => 'nullable|string|max:255',
            'cost_per_participant' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive,archived',
        ]);

        $validated['created_by'] = auth()->id();

        TrainingCourse::create($validated);

        return redirect()->route('admin.hrm.training.courses.index')
                        ->with('success', __('Training course created successfully'));
    }

    /**
     * Display the specified training course
     */
    public function show(TrainingCourse $course)
    {
        $course->load(['enrollments.employee']);

        return view('admin.hrm.training.courses.show', compact('course'));
    }

    /**
     * Show the form for editing the specified training course
     */
    public function edit(TrainingCourse $course)
    {
        return view('admin.hrm.training.courses.edit', compact('course'));
    }

    /**
     * Update the specified training course
     */
    public function update(Request $request, TrainingCourse $course)
    {
        $validated = $request->validate([
            'course_name' => 'required|string|max:255',
            'course_code' => 'required|string|unique:training_courses,course_code,'.$course->id,
            'course_category' => 'required|in:technical,soft_skills,leadership,compliance,safety,product,sales,other',
            'course_description' => 'required|string',
            'course_objectives' => 'nullable|string',
            'course_duration_hours' => 'required|numeric|min:0',
            'training_method' => 'required|in:classroom,online,hybrid,on_the_job,workshop,seminar',
            'max_participants' => 'nullable|integer|min:1',
            'prerequisites' => 'nullable|string',
            'course_materials' => 'nullable|string',
            'certification_provided' => 'nullable|boolean',
            'certification_name' => 'nullable|string|max:255',
            'cost_per_participant' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive,archived',
        ]);

        $course->update($validated);

        return redirect()->route('admin.hrm.training.courses.index')
                        ->with('success', __('Training course updated successfully'));
    }

    /**
     * Remove the specified training course
     */
    public function destroy(TrainingCourse $course)
    {
        $course->delete();

        return redirect()->route('admin.hrm.training.courses.index')
                        ->with('success', __('Training course deleted successfully'));
    }
}
