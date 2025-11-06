<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosAdvertisement;
use App\Models\VendorStore;
use App\Models\PosDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PosAdvertisementController extends Controller
{
    public function index(Request $request)
    {
        $query = PosAdvertisement::with('store')
            ->latest();

        // Filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('promotion_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $advertisements = $query->paginate(20);
        $stores = VendorStore::active()->get();

        return view('admin.pos.advertisements.index', compact('advertisements', 'stores'));
    }

    public function create()
    {
        $stores = VendorStore::active()->get();
        $devices = PosDevice::active()->get();

        return view('admin.pos.advertisements.create', compact('stores', 'devices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'nullable|exists:vendor_stores,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:image,video,html,promotion',
            'image' => 'nullable|image|max:5120', // 5MB
            'video' => 'nullable|mimes:mp4,webm|max:51200', // 50MB
            'html_content' => 'nullable|string',
            'promotion_code' => 'nullable|string|max:50',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'promotion_start' => 'nullable|date',
            'promotion_end' => 'nullable|date|after:promotion_start',
            'duration_seconds' => 'required|integer|min:1|max:300',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'show_on_idle_only' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'display_hours' => 'nullable|array',
            'display_days' => 'nullable|array',
            'target_devices' => 'nullable|array',
            'link_url' => 'nullable|url',
            'link_opens_new_tab' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('pos/advertisements', 'public');
        }

        // Handle video upload
        if ($request->hasFile('video')) {
            $validated['video_url'] = $request->file('video')->store('pos/advertisements', 'public');
        }

        $advertisement = PosAdvertisement::create($validated);

        return redirect()
            ->route('admin.pos.advertisements.show', $advertisement)
            ->with('success', 'Advertisement created successfully!');
    }

    public function show(PosAdvertisement $advertisement)
    {
        $advertisement->load('store');

        $stats = [
            'view_count' => $advertisement->view_count,
            'click_count' => $advertisement->click_count,
            'last_displayed' => $advertisement->last_displayed_at,
            'click_through_rate' => $advertisement->view_count > 0
                ? round(($advertisement->click_count / $advertisement->view_count) * 100, 2)
                : 0,
        ];

        return view('admin.pos.advertisements.show', compact('advertisement', 'stats'));
    }

    public function edit(PosAdvertisement $advertisement)
    {
        $stores = VendorStore::active()->get();
        $devices = PosDevice::active()->get();

        return view('admin.pos.advertisements.edit', compact('advertisement', 'stores', 'devices'));
    }

    public function update(Request $request, PosAdvertisement $advertisement)
    {
        $validated = $request->validate([
            'store_id' => 'nullable|exists:vendor_stores,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:image,video,html,promotion',
            'image' => 'nullable|image|max:5120',
            'video' => 'nullable|mimes:mp4,webm|max:51200',
            'html_content' => 'nullable|string',
            'promotion_code' => 'nullable|string|max:50',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'promotion_start' => 'nullable|date',
            'promotion_end' => 'nullable|date|after:promotion_start',
            'duration_seconds' => 'required|integer|min:1|max:300',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'show_on_idle_only' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'display_hours' => 'nullable|array',
            'display_days' => 'nullable|array',
            'target_devices' => 'nullable|array',
            'link_url' => 'nullable|url',
            'link_opens_new_tab' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($advertisement->image_url) {
                Storage::disk('public')->delete($advertisement->image_url);
            }
            $validated['image_url'] = $request->file('image')->store('pos/advertisements', 'public');
        }

        // Handle video upload
        if ($request->hasFile('video')) {
            // Delete old video
            if ($advertisement->video_url) {
                Storage::disk('public')->delete($advertisement->video_url);
            }
            $validated['video_url'] = $request->file('video')->store('pos/advertisements', 'public');
        }

        $advertisement->update($validated);

        return redirect()
            ->route('admin.pos.advertisements.show', $advertisement)
            ->with('success', 'Advertisement updated successfully!');
    }

    public function destroy(PosAdvertisement $advertisement)
    {
        // Delete associated media files
        if ($advertisement->image_url) {
            Storage::disk('public')->delete($advertisement->image_url);
        }

        if ($advertisement->video_url) {
            Storage::disk('public')->delete($advertisement->video_url);
        }

        $advertisement->delete();

        return redirect()
            ->route('admin.pos.advertisements.index')
            ->with('success', 'Advertisement deleted successfully!');
    }

    public function toggleStatus(PosAdvertisement $advertisement)
    {
        $advertisement->update([
            'is_active' => !$advertisement->is_active,
        ]);

        $status = $advertisement->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Advertisement {$status} successfully!");
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'advertisements' => 'required|array',
            'advertisements.*.id' => 'required|exists:pos_advertisements,id',
            'advertisements.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['advertisements'] as $item) {
            PosAdvertisement::where('id', $item['id'])
                ->update(['order' => $item['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Advertisement order updated successfully!',
        ]);
    }

    public function preview(PosAdvertisement $advertisement)
    {
        return view('admin.pos.advertisements.preview', compact('advertisement'));
    }

    public function duplicate(PosAdvertisement $advertisement)
    {
        $newAd = $advertisement->replicate();
        $newAd->title = $advertisement->title . ' (Copy)';
        $newAd->is_active = false;
        $newAd->view_count = 0;
        $newAd->click_count = 0;
        $newAd->last_displayed_at = null;
        $newAd->save();

        return redirect()
            ->route('admin.pos.advertisements.edit', $newAd)
            ->with('success', 'Advertisement duplicated successfully!');
    }

    public function analytics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $advertisements = PosAdvertisement::withCount([
            'posTransactions as conversion_count' => function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('transaction_date', [$dateFrom, $dateTo]);
            }
        ])->get();

        $totalViews = $advertisements->sum('view_count');
        $totalClicks = $advertisements->sum('click_count');
        $averageCTR = $totalViews > 0 ? round(($totalClicks / $totalViews) * 100, 2) : 0;

        return view('admin.pos.advertisements.analytics', compact(
            'advertisements',
            'totalViews',
            'totalClicks',
            'averageCTR',
            'dateFrom',
            'dateTo'
        ));
    }
}
