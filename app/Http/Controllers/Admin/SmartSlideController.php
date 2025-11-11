<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmartSlider;
use App\Models\SmartSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SmartSlideController extends Controller
{
    /**
     * Store a new slide
     */
    public function store(Request $request, SmartSlider $slider)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'background' => 'nullable|array',
            'link_url' => 'nullable|url',
            'link_target' => 'nullable|in:_self,_blank',
            'duration' => 'nullable|integer|min:1',
        ]);

        $validated['slider_id'] = $slider->id;
        $validated['order'] = $slider->slides()->count();

        $slide = SmartSlide::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'สไลด์ถูกสร้างแล้ว',
            'slide' => $slide->load('layers')
        ]);
    }

    /**
     * Update slide
     */
    public function update(Request $request, SmartSlide $slide)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'background' => 'nullable|array',
            'link_url' => 'nullable|url',
            'link_target' => 'nullable|in:_self,_blank',
            'duration' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $slide->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'สไลด์ถูกอัปเดตแล้ว',
            'slide' => $slide->fresh()->load('layers')
        ]);
    }

    /**
     * Delete slide
     */
    public function destroy(SmartSlide $slide)
    {
        $slide->delete();

        return response()->json([
            'success' => true,
            'message' => 'สไลด์ถูกลบแล้ว'
        ]);
    }

    /**
     * Duplicate slide
     */
    public function duplicate(SmartSlide $slide)
    {
        $newSlide = $slide->duplicateToSlider($slide->slider_id);

        return response()->json([
            'success' => true,
            'message' => 'สไลด์ถูกทำสำเนาแล้ว',
            'slide' => $newSlide->load('layers')
        ]);
    }

    /**
     * Reorder slides
     */
    public function reorder(Request $request, SmartSlider $slider)
    {
        $request->validate([
            'slides' => 'required|array',
            'slides.*.id' => 'required|exists:smart_slides,id',
            'slides.*.order' => 'required|integer',
        ]);

        foreach ($request->slides as $slideData) {
            SmartSlide::where('id', $slideData['id'])
                ->update(['order' => $slideData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'ลำดับสไลด์ถูกอัปเดตแล้ว'
        ]);
    }

    /**
     * Upload background image
     */
    public function uploadBackground(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $path = $request->file('image')->store('sliders/backgrounds', 'public');

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => asset('storage/' . $path)
        ]);
    }

    /**
     * Upload background video
     */
    public function uploadVideo(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,webm,ogg|max:51200', // 50MB
        ]);

        $path = $request->file('video')->store('sliders/videos', 'public');

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => asset('storage/' . $path)
        ]);
    }
}
