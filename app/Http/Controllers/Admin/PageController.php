<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    /**
     * Display a listing of pages
     */
    public function index(Request $request)
    {
        $query = Page::query();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                  ->orWhere('slug', 'like', '%'.$search.'%');
            });
        }

        $pages = $query->orderBy('sort_order')->orderBy('created_at', 'desc')->paginate(20);
        $types = Page::getTypes();

        return view('admin.pages.index', compact('pages', 'types'));
    }

    /**
     * Show the form for creating a new page
     */
    public function create()
    {
        $types = Page::getTypes();
        return view('admin.pages.create', compact('types'));
    }

    /**
     * Store a newly created page
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:pages,slug'],
            'type' => ['required', 'in:' . implode(',', array_keys(Page::getTypes()))],
            'content' => ['required', 'string'],
            'is_published' => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $page = Page::create($validated);

        return redirect()->route('admin.pages.index')
            ->with('success', 'สร้างหน้าเรียบร้อยแล้ว');
    }

    /**
     * Display the specified page
     */
    public function show(Page $page)
    {
        return view('admin.pages.show', compact('page'));
    }

    /**
     * Show the form for editing the specified page
     */
    public function edit(Page $page)
    {
        $types = Page::getTypes();
        return view('admin.pages.edit', compact('page', 'types'));
    }

    /**
     * Update the specified page
     */
    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:pages,slug,' . $page->id],
            'type' => ['required', 'in:' . implode(',', array_keys(Page::getTypes()))],
            'content' => ['required', 'string'],
            'is_published' => ['boolean'],
            'sort_order' => ['integer'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $page->update($validated);

        return redirect()->route('admin.pages.index')
            ->with('success', 'อัปเดตหน้าเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified page
     */
    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('success', 'ลบหน้าเรียบร้อยแล้ว');
    }

    /**
     * Reorder pages
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'pages' => ['required', 'array'],
            'pages.*.id' => ['required', 'exists:pages,id'],
            'pages.*.sort_order' => ['required', 'integer'],
        ]);

        foreach ($validated['pages'] as $pageData) {
            Page::where('id', $pageData['id'])->update([
                'sort_order' => $pageData['sort_order']
            ]);
        }

        return response()->json(['success' => true]);
    }
}
