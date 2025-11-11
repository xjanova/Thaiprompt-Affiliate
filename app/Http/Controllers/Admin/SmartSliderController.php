<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmartSlider;
use App\Models\SmartSliderTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SmartSliderController extends Controller
{
    /**
     * Display a listing of sliders
     */
    public function index()
    {
        $sliders = SmartSlider::withCount(['slides', 'analytics'])
            ->ordered()
            ->paginate(20);

        return view('admin.smart-sliders.index', compact('sliders'));
    }

    /**
     * Show the form for creating a new slider
     */
    public function create()
    {
        $templates = SmartSliderTemplate::active()->get();
        $selectedTemplate = ''; // Default to blank template

        return view('admin.smart-sliders.create', compact('templates', 'selectedTemplate'));
    }

    /**
     * Store a newly created slider
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:simple,block,carousel,showcase',
            'width' => 'required|integer|min:100',
            'height' => 'required|integer|min:100',
            'responsive_mode' => 'required|in:auto,fullwidth,boxed,fullpage',
            'template_id' => 'nullable|exists:smart_slider_templates,id',
        ]);

        // Create slider
        $slider = SmartSlider::create($validated);

        // Apply template if selected
        if ($request->template_id) {
            $template = SmartSliderTemplate::find($request->template_id);
            $template->applyToSlider($slider);
        }

        return redirect()
            ->route('admin.smart-sliders.edit', $slider)
            ->with('success', 'Slider สร้างสำเร็จ!');
    }

    /**
     * Show the form for editing the slider
     */
    public function edit(SmartSlider $smartSlider)
    {
        $smartSlider->load(['slides.layers']);

        return view('admin.smart-sliders.edit', compact('smartSlider'));
    }

    /**
     * Update the slider
     */
    public function update(Request $request, SmartSlider $smartSlider)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:simple,block,carousel,showcase',
            'width' => 'required|integer|min:100',
            'height' => 'required|integer|min:100',
            'responsive_mode' => 'required|in:auto,fullwidth,boxed,fullpage',
            'settings' => 'nullable|array',
            'controls' => 'nullable|array',
            'animations' => 'nullable|array',
            'is_published' => 'boolean',
        ]);

        $smartSlider->update($validated);

        return redirect()
            ->back()
            ->with('success', 'Slider อัปเดตสำเร็จ!');
    }

    /**
     * Delete the slider
     */
    public function destroy(SmartSlider $smartSlider)
    {
        $smartSlider->delete();

        return redirect()
            ->route('admin.smart-sliders.index')
            ->with('success', 'Slider ถูกลบแล้ว');
    }

    /**
     * Duplicate slider
     */
    public function duplicate(SmartSlider $smartSlider)
    {
        $newSlider = $smartSlider->duplicate();

        return redirect()
            ->route('admin.smart-sliders.edit', $newSlider)
            ->with('success', 'Slider ถูกทำสำเนาแล้ว');
    }

    /**
     * Toggle publish status
     */
    public function togglePublish(SmartSlider $smartSlider)
    {
        $smartSlider->update([
            'is_published' => !$smartSlider->is_published
        ]);

        $status = $smartSlider->is_published ? 'เผยแพร่' : 'ซ่อน';

        return response()->json([
            'success' => true,
            'message' => "Slider ถูก{$status}แล้ว",
            'is_published' => $smartSlider->is_published
        ]);
    }

    /**
     * Export slider
     */
    public function export(SmartSlider $smartSlider)
    {
        $slider = $smartSlider->load(['slides.allLayers']);

        $exportData = [
            'name' => $slider->name,
            'description' => $slider->description,
            'type' => $slider->type,
            'width' => $slider->width,
            'height' => $slider->height,
            'responsive_mode' => $slider->responsive_mode,
            'settings' => $slider->settings,
            'controls' => $slider->controls,
            'animations' => $slider->animations,
            'slides' => $slider->slides->map(function ($slide) {
                return [
                    'title' => $slide->title,
                    'description' => $slide->description,
                    'background' => $slide->background,
                    'link_url' => $slide->link_url,
                    'link_target' => $slide->link_target,
                    'duration' => $slide->duration,
                    'order' => $slide->order,
                    'layers' => $slide->allLayers->map(function ($layer) {
                        return [
                            'type' => $layer->type,
                            'content' => $layer->content,
                            'position' => $layer->position,
                            'style' => $layer->style,
                            'responsive' => $layer->responsive,
                            'animation' => $layer->animation,
                            'link_url' => $layer->link_url,
                            'link_target' => $layer->link_target,
                            'order' => $layer->order,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];

        $filename = Str::slug($slider->name) . '-' . date('Y-m-d') . '.json';

        return response()->json($exportData)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Import slider
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:json',
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());
        $data = json_decode($content, true);

        if (!$data) {
            return back()->with('error', 'ไฟล์ไม่ถูกต้อง');
        }

        // Create slider
        $sliderData = [
            'name' => $data['name'] . ' (Imported)',
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'simple',
            'width' => $data['width'] ?? 1200,
            'height' => $data['height'] ?? 600,
            'responsive_mode' => $data['responsive_mode'] ?? 'auto',
            'settings' => $data['settings'] ?? null,
            'controls' => $data['controls'] ?? null,
            'animations' => $data['animations'] ?? null,
            'is_published' => false,
        ];

        $slider = SmartSlider::create($sliderData);

        // Create slides
        if (isset($data['slides'])) {
            foreach ($data['slides'] as $slideData) {
                $slide = $slider->slides()->create([
                    'title' => $slideData['title'] ?? null,
                    'description' => $slideData['description'] ?? null,
                    'background' => $slideData['background'] ?? null,
                    'link_url' => $slideData['link_url'] ?? null,
                    'link_target' => $slideData['link_target'] ?? '_self',
                    'duration' => $slideData['duration'] ?? null,
                    'order' => $slideData['order'] ?? 0,
                ]);

                // Create layers
                if (isset($slideData['layers'])) {
                    foreach ($slideData['layers'] as $layerData) {
                        $slide->layers()->create([
                            'type' => $layerData['type'],
                            'content' => $layerData['content'] ?? null,
                            'position' => $layerData['position'] ?? null,
                            'style' => $layerData['style'] ?? null,
                            'responsive' => $layerData['responsive'] ?? null,
                            'animation' => $layerData['animation'] ?? null,
                            'link_url' => $layerData['link_url'] ?? null,
                            'link_target' => $layerData['link_target'] ?? '_self',
                            'order' => $layerData['order'] ?? 0,
                        ]);
                    }
                }
            }
        }

        return redirect()
            ->route('admin.smart-sliders.edit', $slider)
            ->with('success', 'Slider นำเข้าสำเร็จ!');
    }

    /**
     * Show slider analytics
     */
    public function analytics(SmartSlider $smartSlider)
    {
        $views = $smartSlider->analytics()
            ->where('event_type', 'view')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        $clicks = $smartSlider->analytics()
            ->where('event_type', 'click')
            ->count();

        $slidePerformance = $smartSlider->slides()
            ->withCount(['analytics as views' => function ($query) {
                $query->where('event_type', 'view');
            }])
            ->get();

        return view('admin.smart-sliders.analytics', compact(
            'smartSlider',
            'views',
            'clicks',
            'slidePerformance'
        ));
    }
}
