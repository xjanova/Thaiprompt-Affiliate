<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\SmartSlider;
use App\Models\SmartSliderAnalytics;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    /**
     * Show slider by ID or alias
     */
    public function show($idOrAlias)
    {
        $slider = is_numeric($idOrAlias)
            ? SmartSlider::find($idOrAlias)
            : SmartSlider::findByAlias($idOrAlias);

        if (!$slider || !$slider->is_published) {
            abort(404, 'Slider not found');
        }

        // Track view
        SmartSliderAnalytics::track($slider->id, SmartSliderAnalytics::EVENT_VIEW);

        $slider->load(['slides' => function ($query) {
            $query->active()->ordered()->with('layers');
        }]);

        return view('frontend.slider.show', compact('slider'));
    }

    /**
     * Get slider data via API (for AJAX loading)
     */
    public function getData($idOrAlias)
    {
        $slider = is_numeric($idOrAlias)
            ? SmartSlider::find($idOrAlias)
            : SmartSlider::findByAlias($idOrAlias);

        if (!$slider || !$slider->is_published) {
            return response()->json(['error' => 'Slider not found'], 404);
        }

        // Track view
        SmartSliderAnalytics::track($slider->id, SmartSliderAnalytics::EVENT_VIEW);

        $slider->load(['slides' => function ($query) {
            $query->active()->ordered()->with('layers');
        }]);

        return response()->json([
            'slider' => $slider,
        ]);
    }

    /**
     * Track click event
     */
    public function trackClick(Request $request, SmartSlider $slider)
    {
        $request->validate([
            'slide_id' => 'nullable|exists:smart_slides,id',
        ]);

        SmartSliderAnalytics::track(
            $slider->id,
            SmartSliderAnalytics::EVENT_CLICK,
            $request->slide_id
        );

        return response()->json(['success' => true]);
    }

    /**
     * Track slide change event
     */
    public function trackSlideChange(Request $request, SmartSlider $slider)
    {
        $request->validate([
            'slide_id' => 'required|exists:smart_slides,id',
        ]);

        SmartSliderAnalytics::track(
            $slider->id,
            SmartSliderAnalytics::EVENT_SLIDE_CHANGE,
            $request->slide_id
        );

        return response()->json(['success' => true]);
    }
}
