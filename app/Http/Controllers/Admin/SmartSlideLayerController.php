<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmartSlide;
use App\Models\SmartSlideLayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SmartSlideLayerController extends Controller
{
    /**
     * Store a new layer
     */
    public function store(Request $request, SmartSlide $slide)
    {
        $validated = $request->validate([
            'type' => 'required|in:heading,text,image,button,video_youtube,video_vimeo,video_upload,html',
            'content' => 'nullable|string',
            'position' => 'nullable|array',
            'style' => 'nullable|array',
            'responsive' => 'nullable|array',
            'animation' => 'nullable|array',
            'link_url' => 'nullable|string',
            'link_target' => 'nullable|in:_self,_blank',
            'parent_id' => 'nullable|exists:smart_slide_layers,id',
        ]);

        $validated['slide_id'] = $slide->id;
        $validated['order'] = $slide->layers()->count();

        $layer = SmartSlideLayer::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Layer ถูกสร้างแล้ว',
            'layer' => $layer->fresh()
        ]);
    }

    /**
     * Update layer
     */
    public function update(Request $request, SmartSlideLayer $layer)
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:heading,text,image,button,video_youtube,video_vimeo,video_upload,html',
            'content' => 'nullable|string',
            'position' => 'nullable|array',
            'style' => 'nullable|array',
            'responsive' => 'nullable|array',
            'animation' => 'nullable|array',
            'link_url' => 'nullable|string',
            'link_target' => 'nullable|in:_self,_blank',
            'is_visible' => 'boolean',
        ]);

        $layer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Layer ถูกอัปเดตแล้ว',
            'layer' => $layer->fresh()
        ]);
    }

    /**
     * Delete layer
     */
    public function destroy(SmartSlideLayer $layer)
    {
        $layer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Layer ถูกลบแล้ว'
        ]);
    }

    /**
     * Duplicate layer
     */
    public function duplicate(SmartSlideLayer $layer)
    {
        $newLayer = $layer->duplicateToSlide($layer->slide_id, $layer->parent_id);

        return response()->json([
            'success' => true,
            'message' => 'Layer ถูกทำสำเนาแล้ว',
            'layer' => $newLayer
        ]);
    }

    /**
     * Reorder layers
     */
    public function reorder(Request $request, SmartSlide $slide)
    {
        $request->validate([
            'layers' => 'required|array',
            'layers.*.id' => 'required|exists:smart_slide_layers,id',
            'layers.*.order' => 'required|integer',
        ]);

        foreach ($request->layers as $layerData) {
            SmartSlideLayer::where('id', $layerData['id'])
                ->update(['order' => $layerData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'ลำดับ Layer ถูกอัปเดตแล้ว'
        ]);
    }

    /**
     * Upload layer image
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
        ]);

        $path = $request->file('image')->store('sliders/layers', 'public');

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => asset('storage/' . $path)
        ]);
    }

    /**
     * Update layer position (for drag & drop)
     */
    public function updatePosition(Request $request, SmartSlideLayer $layer)
    {
        $request->validate([
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'width' => 'nullable|string',
            'height' => 'nullable|string',
        ]);

        $position = $layer->position;
        $position['mode'] = 'absolute';
        $position['x'] = $request->x;
        $position['y'] = $request->y;

        if ($request->has('width')) {
            $position['width'] = $request->width;
        }
        if ($request->has('height')) {
            $position['height'] = $request->height;
        }

        $layer->update(['position' => $position]);

        return response()->json([
            'success' => true,
            'message' => 'ตำแหน่ง Layer ถูกอัปเดตแล้ว',
            'layer' => $layer->fresh()
        ]);
    }

    /**
     * Batch update layers
     */
    public function batchUpdate(Request $request, SmartSlide $slide)
    {
        $request->validate([
            'layers' => 'required|array',
            'layers.*.id' => 'required|exists:smart_slide_layers,id',
        ]);

        foreach ($request->layers as $layerData) {
            $layer = SmartSlideLayer::find($layerData['id']);

            if ($layer && $layer->slide_id === $slide->id) {
                $updateData = array_intersect_key($layerData, array_flip([
                    'content', 'position', 'style', 'responsive', 'animation',
                    'link_url', 'link_target', 'order', 'is_visible'
                ]));

                $layer->update($updateData);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Layers ถูกอัปเดตแล้ว',
            'slide' => $slide->fresh()->load('layers')
        ]);
    }
}
