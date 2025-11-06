<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PerformanceReview;
use App\Models\Employee;
use Illuminate\Http\Request;

class PerformanceReviewController extends Controller
{
    /**
     * Display a listing of performance reviews
     */
    public function index(Request $request)
    {
        $query = PerformanceReview::with(['employee', 'reviewer']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('employee', function($q) use ($search) {
                $q->where('first_name', 'like', '%'.$search.'%')
                  ->orWhere('last_name', 'like', '%'.$search.'%');
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Period filter
        if ($request->filled('review_period')) {
            $query->where('review_period', $request->get('review_period'));
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $reviews = $query->latest()->paginate($perPage)->withQueryString();

        return view('admin.hrm.performance.reviews.index', compact('reviews'));
    }

    /**
     * Show the form for creating a new performance review
     */
    public function create()
    {
        $employees = Employee::where('employment_status', 'active')->get();

        return view('admin.hrm.performance.reviews.create', compact('employees'));
    }

    /**
     * Store a newly created performance review
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'review_period' => 'required|string',
            'review_date' => 'required|date',
            'period_start_date' => 'required|date',
            'period_end_date' => 'required|date',
            'review_type' => 'required|string',
            'technical_skills_rating' => 'nullable|integer|min:1|max:5',
            'communication_rating' => 'nullable|integer|min:1|max:5',
            'teamwork_rating' => 'nullable|integer|min:1|max:5',
            'leadership_rating' => 'nullable|integer|min:1|max:5',
            'problem_solving_rating' => 'nullable|integer|min:1|max:5',
            'productivity_rating' => 'nullable|integer|min:1|max:5',
            'quality_of_work_rating' => 'nullable|integer|min:1|max:5',
            'punctuality_rating' => 'nullable|integer|min:1|max:5',
            'initiative_rating' => 'nullable|integer|min:1|max:5',
            'adaptability_rating' => 'nullable|integer|min:1|max:5',
            'strengths' => 'nullable|string',
            'areas_for_improvement' => 'nullable|string',
            'achievements' => 'nullable|string',
            'goals_for_next_period' => 'nullable|string',
            'training_needs' => 'nullable|string',
            'reviewer_comments' => 'nullable|string',
            'recommend_promotion' => 'nullable|boolean',
            'recommend_salary_increase' => 'nullable|boolean',
            'recommended_increase_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['reviewer_id'] = auth()->id();
        $validated['status'] = 'draft';

        $review = PerformanceReview::create($validated);
        $review->calculateOverallRating();
        $review->save();

        return redirect()->route('admin.hrm.performance.reviews.index')
                        ->with('success', __('Performance review created successfully'));
    }

    /**
     * Display the specified performance review
     */
    public function show(PerformanceReview $review)
    {
        $review->load(['employee', 'reviewer']);

        return view('admin.hrm.performance.reviews.show', compact('review'));
    }

    /**
     * Show the form for editing the specified performance review
     */
    public function edit(PerformanceReview $review)
    {
        $employees = Employee::where('employment_status', 'active')->get();

        return view('admin.hrm.performance.reviews.edit', compact('review', 'employees'));
    }

    /**
     * Update the specified performance review
     */
    public function update(Request $request, PerformanceReview $review)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'review_period' => 'required|string',
            'review_date' => 'required|date',
            'period_start_date' => 'required|date',
            'period_end_date' => 'required|date',
            'review_type' => 'required|string',
            'technical_skills_rating' => 'nullable|integer|min:1|max:5',
            'communication_rating' => 'nullable|integer|min:1|max:5',
            'teamwork_rating' => 'nullable|integer|min:1|max:5',
            'leadership_rating' => 'nullable|integer|min:1|max:5',
            'problem_solving_rating' => 'nullable|integer|min:1|max:5',
            'productivity_rating' => 'nullable|integer|min:1|max:5',
            'quality_of_work_rating' => 'nullable|integer|min:1|max:5',
            'punctuality_rating' => 'nullable|integer|min:1|max:5',
            'initiative_rating' => 'nullable|integer|min:1|max:5',
            'adaptability_rating' => 'nullable|integer|min:1|max:5',
            'strengths' => 'nullable|string',
            'areas_for_improvement' => 'nullable|string',
            'achievements' => 'nullable|string',
            'goals_for_next_period' => 'nullable|string',
            'training_needs' => 'nullable|string',
            'reviewer_comments' => 'nullable|string',
            'recommend_promotion' => 'nullable|boolean',
            'recommend_salary_increase' => 'nullable|boolean',
            'recommended_increase_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $review->update($validated);
        $review->calculateOverallRating();
        $review->save();

        return redirect()->route('admin.hrm.performance.reviews.index')
                        ->with('success', __('Performance review updated successfully'));
    }

    /**
     * Remove the specified performance review
     */
    public function destroy(PerformanceReview $review)
    {
        $review->delete();

        return redirect()->route('admin.hrm.performance.reviews.index')
                        ->with('success', __('Performance review deleted successfully'));
    }
}
