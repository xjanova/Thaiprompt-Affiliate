<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\Request;

class JobApplicationController extends Controller
{
    /**
     * Display a listing of job applications
     */
    public function index(Request $request)
    {
        $query = JobApplication::with(['jobPosting']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', '%'.$search.'%')
                  ->orWhere('last_name', 'like', '%'.$search.'%')
                  ->orWhere('email', 'like', '%'.$search.'%')
                  ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('application_status', $request->get('status'));
        }

        // Job posting filter
        if ($request->filled('job_posting_id')) {
            $query->where('job_posting_id', $request->get('job_posting_id'));
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $applications = $query->latest()->paginate($perPage)->withQueryString();

        $jobPostings = JobPosting::where('status', 'active')->get();

        return view('admin.hrm.recruitment.applications.index', compact('applications', 'jobPostings'));
    }

    /**
     * Show the form for creating a new job application
     */
    public function create()
    {
        $jobPostings = JobPosting::where('status', 'active')->get();

        return view('admin.hrm.recruitment.applications.create', compact('jobPostings'));
    }

    /**
     * Store a newly created job application
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_posting_id' => 'required|exists:job_postings,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'cover_letter' => 'nullable|string',
            'resume_file' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
            'expected_salary' => 'nullable|numeric|min:0',
            'available_start_date' => 'nullable|date',
        ]);

        if ($request->hasFile('resume_file')) {
            $validated['resume_path'] = $request->file('resume_file')->store('resumes', 'public');
        }

        $validated['application_status'] = 'new';
        $validated['application_date'] = now();

        JobApplication::create($validated);

        return redirect()->route('admin.hrm.recruitment.applications.index')
                        ->with('success', __('Job application created successfully'));
    }

    /**
     * Display the specified job application
     */
    public function show(JobApplication $application)
    {
        $application->load(['jobPosting', 'reviewedBy']);

        return view('admin.hrm.recruitment.applications.show', compact('application'));
    }

    /**
     * Show the form for editing the specified job application
     */
    public function edit(JobApplication $application)
    {
        $jobPostings = JobPosting::where('status', 'active')->get();

        return view('admin.hrm.recruitment.applications.edit', compact('application', 'jobPostings'));
    }

    /**
     * Update the specified job application
     */
    public function update(Request $request, JobApplication $application)
    {
        $validated = $request->validate([
            'application_status' => 'required|in:new,under_review,shortlisted,interview_scheduled,interviewed,offer_extended,hired,rejected,withdrawn',
            'interview_date' => 'nullable|date',
            'interview_notes' => 'nullable|string',
            'reviewer_comments' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
        ]);

        $validated['reviewed_by'] = auth()->id();
        $validated['reviewed_date'] = now();

        $application->update($validated);

        return redirect()->route('admin.hrm.recruitment.applications.index')
                        ->with('success', __('Job application updated successfully'));
    }

    /**
     * Remove the specified job application
     */
    public function destroy(JobApplication $application)
    {
        // Delete resume file if exists
        if ($application->resume_path) {
            \Storage::disk('public')->delete($application->resume_path);
        }

        $application->delete();

        return redirect()->route('admin.hrm.recruitment.applications.index')
                        ->with('success', __('Job application deleted successfully'));
    }
}
