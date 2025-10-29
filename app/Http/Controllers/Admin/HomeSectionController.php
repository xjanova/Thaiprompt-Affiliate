<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSection;
use Illuminate\Http\Request;

class HomeSectionController extends Controller
{
    /**
     * Display a listing of home sections
     */
    public function index()
    {
        $sections = HomeSection::orderBy('sort_order')->get();
        $types = HomeSection::getTypes();

        return view('admin.home-sections.index', compact('sections', 'types'));
    }

    /**
     * Show the form for creating a new section
     */
    public function create()
    {
        $types = HomeSection::getTypes();
        return view('admin.home-sections.create', compact('types'));
    }

    /**
     * Store a newly created section
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'type' => ['required', 'in:' . implode(',', array_keys(HomeSection::getTypes()))],
            'settings' => ['nullable', 'array'],
            'allowed_roles' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
            'schedule_start' => ['nullable', 'date'],
            'schedule_end' => ['nullable', 'date', 'after:schedule_start'],
        ]);

        $section = HomeSection::create($validated);

        return redirect()->route('admin.home-sections.index')
            ->with('success', 'สร้าง Section เรียบร้อยแล้ว');
    }

    /**
     * Show the form for editing the specified section
     */
    public function edit(HomeSection $homeSection)
    {
        $types = HomeSection::getTypes();
        return view('admin.home-sections.edit', compact('homeSection', 'types'));
    }

    /**
     * Update the specified section
     */
    public function update(Request $request, HomeSection $homeSection)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'type' => ['required', 'in:' . implode(',', array_keys(HomeSection::getTypes()))],
            'settings' => ['nullable', 'array'],
            'allowed_roles' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer'],
            'schedule_start' => ['nullable', 'date'],
            'schedule_end' => ['nullable', 'date', 'after:schedule_start'],
        ]);

        $homeSection->update($validated);

        return redirect()->route('admin.home-sections.index')
            ->with('success', 'อัปเดต Section เรียบร้อยแล้ว');
    }

    /**
     * Remove the specified section
     */
    public function destroy(HomeSection $homeSection)
    {
        $homeSection->delete();

        return redirect()->route('admin.home-sections.index')
            ->with('success', 'ลบ Section เรียบร้อยแล้ว');
    }

    /**
     * Reorder sections (for drag & drop)
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'sections' => ['required', 'array'],
            'sections.*.id' => ['required', 'exists:home_sections,id'],
            'sections.*.sort_order' => ['required', 'integer'],
        ]);

        foreach ($validated['sections'] as $sectionData) {
            HomeSection::where('id', $sectionData['id'])->update([
                'sort_order' => $sectionData['sort_order']
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Toggle section active status
     */
    public function toggle(HomeSection $homeSection)
    {
        $homeSection->is_active = !$homeSection->is_active;
        $homeSection->save();

        return response()->json([
            'success' => true,
            'is_active' => $homeSection->is_active
        ]);
    }
}
