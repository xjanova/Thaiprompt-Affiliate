<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\Request;

class JobPostingController extends Controller
{
    /**
     * Display a listing of job postings
     */
    public function index(Request $request)
    {
        $query = JobPosting::with(['department', 'position']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('job_title', 'like', '%'.$search.'%')
                  ->orWhere('job_code', 'like', '%'.$search.'%')
                  ->orWhere('job_description', 'like', '%'.$search.'%');
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Department filter
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->get('department_id'));
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $jobPostings = $query->latest()->paginate($perPage)->withQueryString();

        $departments = Department::where('is_active', true)->get();

        return view('admin.hrm.recruitment.jobs.index', compact('jobPostings', 'departments'));
    }

    /**
     * Show the form for creating a new job posting
     */
    public function create()
    {
        $departments = Department::where('is_active', true)->get();
        $positions = Position::where('is_active', true)->get();

        return view('admin.hrm.recruitment.jobs.create', compact('departments', 'positions'));
    }

    /**
     * Store a newly created job posting
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_title' => 'required|string|max:255',
            'job_code' => 'required|string|unique:job_postings,job_code',
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'employment_type' => 'required|in:full_time,part_time,contract,intern,freelance',
            'job_location' => 'required|string|max:255',
            'number_of_openings' => 'required|integer|min:1',
            'salary_range_min' => 'nullable|numeric|min:0',
            'salary_range_max' => 'nullable|numeric|min:0',
            'job_description' => 'required|string',
            'requirements' => 'required|string',
            'responsibilities' => 'required|string',
            'benefits' => 'nullable|string',
            'application_deadline' => 'nullable|date',
            'status' => 'required|in:draft,active,on_hold,closed,filled',
        ]);

        $validated['posted_by'] = auth()->id();
        $validated['posted_date'] = now();

        JobPosting::create($validated);

        return redirect()->route('admin.hrm.recruitment.jobs.index')
                        ->with('success', __('Job posting created successfully'));
    }

    /**
     * Display the specified job posting
     */
    public function show(JobPosting $job)
    {
        $job->load(['department', 'position', 'postedBy', 'applications']);

        return view('admin.hrm.recruitment.jobs.show', compact('job'));
    }

    /**
     * Show the form for editing the specified job posting
     */
    public function edit(JobPosting $job)
    {
        $departments = Department::where('is_active', true)->get();
        $positions = Position::where('is_active', true)->get();

        return view('admin.hrm.recruitment.jobs.edit', compact('job', 'departments', 'positions'));
    }

    /**
     * Update the specified job posting
     */
    public function update(Request $request, JobPosting $job)
    {
        $validated = $request->validate([
            'job_title' => 'required|string|max:255',
            'job_code' => 'required|string|unique:job_postings,job_code,'.$job->id,
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'nullable|exists:positions,id',
            'employment_type' => 'required|in:full_time,part_time,contract,intern,freelance',
            'job_location' => 'required|string|max:255',
            'number_of_openings' => 'required|integer|min:1',
            'salary_range_min' => 'nullable|numeric|min:0',
            'salary_range_max' => 'nullable|numeric|min:0',
            'job_description' => 'required|string',
            'requirements' => 'required|string',
            'responsibilities' => 'required|string',
            'benefits' => 'nullable|string',
            'application_deadline' => 'nullable|date',
            'status' => 'required|in:draft,active,on_hold,closed,filled',
        ]);

        $job->update($validated);

        return redirect()->route('admin.hrm.recruitment.jobs.index')
                        ->with('success', __('Job posting updated successfully'));
    }

    /**
     * Remove the specified job posting
     */
    public function destroy(JobPosting $job)
    {
        $job->delete();

        return redirect()->route('admin.hrm.recruitment.jobs.index')
                        ->with('success', __('Job posting deleted successfully'));
    }
}
