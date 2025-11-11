<?php

namespace App\Http\Controllers\Admin\BotAutomation;

use App\Http\Controllers\Controller;
use App\Models\BotAutomation\BotMarketplaceListing;
use App\Models\BotAutomation\BotMarketplaceReview;
use App\Models\BotAutomation\BotMarketplaceSubscription;
use Illuminate\Http\Request;

class BotMarketplaceController extends Controller
{
    /**
     * Display the marketplace dashboard
     */
    public function index()
    {
        $statistics = [
            'total_listings' => BotMarketplaceListing::count(),
            'active_listings' => BotMarketplaceListing::where('status', 'published')->count(),
            'total_subscribers' => BotMarketplaceSubscription::count(),
            'total_revenue' => BotMarketplaceSubscription::sum('price'),
            'pending_reviews' => BotMarketplaceReview::where('is_approved', false)->count(),
        ];

        $recentListings = BotMarketplaceListing::with(['user'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.bot-automation.marketplace.index', compact('statistics', 'recentListings'));
    }

    /**
     * Display all marketplace listings
     */
    public function listings(Request $request)
    {
        $listings = BotMarketplaceListing::query()
            ->with(['user'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->category, function ($query, $category) {
                $query->where('category', $category);
            })
            ->latest()
            ->paginate(20);

        return view('admin.bot-automation.marketplace.listings.index', compact('listings'));
    }

    /**
     * Show the form for creating a new listing
     */
    public function createListing()
    {
        return view('admin.bot-automation.marketplace.listings.create');
    }

    /**
     * Store a newly created listing
     */
    public function storeListing(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:automation,template,bot,integration',
            'price' => 'required|numeric|min:0',
            'pricing_type' => 'required|in:one_time,monthly,yearly',
            'features' => 'nullable|json',
            'preview_url' => 'nullable|url',
            'demo_url' => 'nullable|url',
            'documentation_url' => 'nullable|url',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'draft';

        $listing = BotMarketplaceListing::create($validated);

        return redirect()
            ->route('admin.bot-automation.marketplace.listings.edit', $listing)
            ->with('success', 'Listing created successfully');
    }

    /**
     * Show the form for editing the listing
     */
    public function editListing(BotMarketplaceListing $listing)
    {
        return view('admin.bot-automation.marketplace.listings.edit', compact('listing'));
    }

    /**
     * Update the listing
     */
    public function updateListing(Request $request, BotMarketplaceListing $listing)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:automation,template,bot,integration',
            'price' => 'required|numeric|min:0',
            'pricing_type' => 'required|in:one_time,monthly,yearly',
            'features' => 'nullable|json',
            'preview_url' => 'nullable|url',
            'demo_url' => 'nullable|url',
            'documentation_url' => 'nullable|url',
        ]);

        $listing->update($validated);

        return back()->with('success', 'Listing updated successfully');
    }

    /**
     * Delete the listing
     */
    public function destroyListing(BotMarketplaceListing $listing)
    {
        $listing->delete();

        return redirect()
            ->route('admin.bot-automation.marketplace.listings')
            ->with('success', 'Listing deleted successfully');
    }

    /**
     * Publish the listing
     */
    public function publish(BotMarketplaceListing $listing)
    {
        $listing->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return back()->with('success', 'Listing published successfully');
    }

    /**
     * Unpublish the listing
     */
    public function unpublish(BotMarketplaceListing $listing)
    {
        $listing->update([
            'status' => 'draft',
        ]);

        return back()->with('success', 'Listing unpublished successfully');
    }

    /**
     * Display all reviews
     */
    public function reviews(Request $request)
    {
        $reviews = BotMarketplaceReview::query()
            ->with(['listing', 'user'])
            ->when($request->status === 'pending', function ($query) {
                $query->where('is_approved', false);
            })
            ->when($request->status === 'approved', function ($query) {
                $query->where('is_approved', true);
            })
            ->latest()
            ->paginate(20);

        return view('admin.bot-automation.marketplace.reviews.index', compact('reviews'));
    }

    /**
     * Approve a review
     */
    public function approveReview(BotMarketplaceReview $review)
    {
        $review->update(['is_approved' => true]);

        return back()->with('success', 'Review approved successfully');
    }

    /**
     * Delete a review
     */
    public function destroyReview(BotMarketplaceReview $review)
    {
        $review->delete();

        return back()->with('success', 'Review deleted successfully');
    }

    /**
     * Display all subscriptions
     */
    public function subscriptions(Request $request)
    {
        $subscriptions = BotMarketplaceSubscription::query()
            ->with(['listing', 'user'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(20);

        return view('admin.bot-automation.marketplace.subscriptions.index', compact('subscriptions'));
    }

    /**
     * Display a single subscription
     */
    public function showSubscription(BotMarketplaceSubscription $subscription)
    {
        $subscription->load(['listing', 'user', 'transactions']);

        return view('admin.bot-automation.marketplace.subscriptions.show', compact('subscription'));
    }
}
