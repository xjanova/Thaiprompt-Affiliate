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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'link' => 'nullable|url',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->only(['title', 'description', 'link', 'order', 'is_active']);
        $data['is_active'] = $request->has('is_active');

        // Handle image upload
        if ($request->hasFile('image')) {
            // Store in storage/app/public/sliders (persistent across deployments)
            $imagePath = $request->file('image')->store('sliders', 'public');
            $data['image'] = '/storage/' . $imagePath;
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'link' => 'nullable|url',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->only(['title', 'description', 'link', 'order']);
        $data['is_active'] = $request->has('is_active');

        // Handle image upload
        if ($request->hasFile('image')) {
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
