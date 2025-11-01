<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
    /**
     * Display a listing of the sliders.
     */
    public function index()
    {
        $sliders = Slider::ordered()->get();
        return view('admin.sliders.index', compact('sliders'));
    }

    /**
     * Show the form for creating a new slider.
     */
    public function create()
    {
        return view('admin.sliders.create');
    }

    /**
     * Store a newly created slider in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'media_type' => 'required|in:image,video',
            'image' => 'required_if:media_type,image|nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'video_url' => 'nullable|string',
            'video_file' => 'nullable|file|mimes:mp4,webm,ogg|max:51200', // 50MB max for video
            'video_type' => 'nullable|in:youtube,vimeo,upload,other',
            'link' => 'nullable|url',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            // Video settings
            'video_autoplay' => 'nullable|boolean',
            'video_muted' => 'nullable|boolean',
            'video_loop' => 'nullable|boolean',
            'video_controls' => 'nullable|boolean',
            // Text overlay
            'overlay_text' => 'nullable|string',
            'overlay_position' => 'nullable|string',
            'overlay_style' => 'nullable|string',
            'overlay_font_size' => 'nullable|string',
            'overlay_color' => 'nullable|string',
            'overlay_bg_color' => 'nullable|string',
            'overlay_animation' => 'nullable|string',
        ]);

        $data = $request->only(['title', 'description', 'link', 'order', 'is_active', 'media_type', 'video_url', 'video_type']);
        $data['is_active'] = $request->has('is_active');

        // Handle image upload
        if ($request->hasFile('image') && $request->media_type === 'image') {
            // Store in storage/app/public/sliders (persistent across deployments)
            $imagePath = $request->file('image')->store('sliders', 'public');
            $data['image'] = '/storage/' . $imagePath;
        }

        // Handle video file upload
        if ($request->hasFile('video_file') && $request->media_type === 'video') {
            $videoPath = $request->file('video_file')->store('sliders/videos', 'public');
            $data['video_file'] = '/storage/' . $videoPath;
            $data['video_type'] = 'upload';
        }

        // Handle video settings
        if ($request->media_type === 'video') {
            $data['video_settings'] = [
                'autoplay' => $request->has('video_autoplay'),
                'muted' => $request->has('video_muted'),
                'loop' => $request->has('video_loop'),
                'controls' => $request->has('video_controls'),
            ];
        }

        // Handle text overlay
        if ($request->filled('overlay_text')) {
            $data['text_overlay'] = [
                'text' => $request->overlay_text,
                'position' => $request->overlay_position ?? 'bottom-left',
                'style' => $request->overlay_style ?? 'elegant',
                'fontSize' => $request->overlay_font_size ?? 'text-4xl',
                'color' => $request->overlay_color ?? '#ffffff',
                'backgroundColor' => $request->overlay_bg_color ?? 'rgba(0, 0, 0, 0.5)',
                'animation' => $request->overlay_animation ?? 'fade-in',
            ];
        }

        // Set default order if not provided
        if (!isset($data['order'])) {
            $maxOrder = Slider::max('order') ?? 0;
            $data['order'] = $maxOrder + 1;
        }

        Slider::create($data);

        return redirect()->route('admin.sliders.index')->with('success', 'เพิ่มสไลด์สำเร็จ');
    }

    /**
     * Show the form for editing the specified slider.
     */
    public function edit(Slider $slider)
    {
        return view('admin.sliders.edit', compact('slider'));
    }

    /**
     * Update the specified slider in storage.
     */
    public function update(Request $request, Slider $slider)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'media_type' => 'required|in:image,video',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'video_url' => 'nullable|string',
            'video_file' => 'nullable|file|mimes:mp4,webm,ogg|max:51200',
            'video_type' => 'nullable|in:youtube,vimeo,upload,other',
            'link' => 'nullable|url',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            // Video settings
            'video_autoplay' => 'nullable|boolean',
            'video_muted' => 'nullable|boolean',
            'video_loop' => 'nullable|boolean',
            'video_controls' => 'nullable|boolean',
            // Text overlay
            'overlay_text' => 'nullable|string',
            'overlay_position' => 'nullable|string',
            'overlay_style' => 'nullable|string',
            'overlay_font_size' => 'nullable|string',
            'overlay_color' => 'nullable|string',
            'overlay_bg_color' => 'nullable|string',
            'overlay_animation' => 'nullable|string',
        ]);

        $data = $request->only(['title', 'description', 'link', 'order', 'media_type', 'video_url', 'video_type']);
        $data['is_active'] = $request->has('is_active');

        // If switching from video to image, clear video fields
        if ($request->media_type === 'image' && $slider->media_type === 'video') {
            // Delete old video file if exists
            if ($slider->video_file) {
                $oldPath = str_replace('/storage/', '', $slider->video_file);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $data['video_url'] = null;
            $data['video_file'] = null;
            $data['video_type'] = null;
            $data['video_settings'] = null;
        }

        // If switching from image to video, clear image field
        if ($request->media_type === 'video' && $slider->media_type === 'image') {
            // Delete old image if exists
            if ($slider->image) {
                $oldPath = str_replace('/storage/', '', $slider->image);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $data['image'] = null;
        }

        // Handle image upload
        if ($request->hasFile('image') && $request->media_type === 'image') {
            // Delete old image from storage
            if ($slider->image) {
                $oldPath = str_replace('/storage/', '', $slider->image);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Store new image in storage/app/public/sliders (persistent across deployments)
            $imagePath = $request->file('image')->store('sliders', 'public');
            $data['image'] = '/storage/' . $imagePath;
        }

        // Handle video file upload
        if ($request->hasFile('video_file') && $request->media_type === 'video') {
            // Delete old video file from storage
            if ($slider->video_file) {
                $oldPath = str_replace('/storage/', '', $slider->video_file);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $videoPath = $request->file('video_file')->store('sliders/videos', 'public');
            $data['video_file'] = '/storage/' . $videoPath;
            $data['video_type'] = 'upload';
        }

        // Handle video settings
        if ($request->media_type === 'video') {
            $data['video_settings'] = [
                'autoplay' => $request->has('video_autoplay'),
                'muted' => $request->has('video_muted'),
                'loop' => $request->has('video_loop'),
                'controls' => $request->has('video_controls'),
            ];
        }

        // Handle text overlay
        if ($request->filled('overlay_text')) {
            $data['text_overlay'] = [
                'text' => $request->overlay_text,
                'position' => $request->overlay_position ?? 'bottom-left',
                'style' => $request->overlay_style ?? 'elegant',
                'fontSize' => $request->overlay_font_size ?? 'text-4xl',
                'color' => $request->overlay_color ?? '#ffffff',
                'backgroundColor' => $request->overlay_bg_color ?? 'rgba(0, 0, 0, 0.5)',
                'animation' => $request->overlay_animation ?? 'fade-in',
            ];
        } else {
            $data['text_overlay'] = null;
        }

        $slider->update($data);

        return redirect()->route('admin.sliders.index')->with('success', 'อัพเดตสไลด์สำเร็จ');
    }

    /**
     * Remove the specified slider from storage.
     */
    public function destroy(Slider $slider)
    {
        // Delete image file from storage
        if ($slider->image) {
            $oldPath = str_replace('/storage/', '', $slider->image);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // Delete video file from storage
        if ($slider->video_file) {
            $oldPath = str_replace('/storage/', '', $slider->video_file);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $slider->delete();

        return redirect()->route('admin.sliders.index')->with('success', 'ลบสไลด์สำเร็จ');
    }

    /**
     * Reorder sliders
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'sliders' => 'required|array',
            'sliders.*.id' => 'required|exists:sliders,id',
            'sliders.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->input('sliders') as $sliderData) {
            Slider::where('id', $sliderData['id'])->update(['order' => $sliderData['order']]);
        }

        return response()->json(['success' => true, 'message' => 'เรียงลำดับสไลด์สำเร็จ']);
    }
}
