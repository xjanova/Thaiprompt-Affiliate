<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningArticle;
use App\Models\LearningCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleManagementController extends Controller
{
    /**
     * Display a listing of articles
     */
    public function index(Request $request)
    {
        $query = LearningArticle::with(['category', 'creator'])
            ->orderBy('created_at', 'desc');

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->published();
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }

        // Filter by difficulty
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $articles = $query->paginate(20);
        $categories = LearningCategory::active()->ordered()->get();

        return view('admin.articles.index', compact('articles', 'categories'));
    }

    /**
     * Show the form for creating a new article
     */
    public function create()
    {
        $categories = LearningCategory::active()->ordered()->get();
        $articles = LearningArticle::published()->ordered()->get();

        return view('admin.articles.create', compact('categories', 'articles'));
    }

    /**
     * Store a newly created article
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:learning_categories,id',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:learning_articles,slug',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'thumbnail' => 'nullable|image|max:2048',
            'video_url' => 'nullable|url',
            'estimated_duration' => 'nullable|integer|min:0',
            'order' => 'nullable|integer|min:0',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'tags' => 'nullable|string',
            'prerequisites' => 'nullable|array',
            'prerequisites.*' => 'exists:learning_articles,id',
        ]);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')
                ->store('learning/thumbnails', 'public');
        }

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Parse tags
        if (!empty($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        }

        // Set published_at
        if ($validated['is_published'] ?? false) {
            $validated['published_at'] = now();
        }

        // Set creator
        $validated['created_by'] = Auth::id();

        $article = LearningArticle::create($validated);

        // Sync prerequisites
        if (!empty($request->prerequisites)) {
            $prerequisites = [];
            foreach ($request->prerequisites as $prereqId) {
                $prerequisites[$prereqId] = ['is_required' => true];
            }
            $article->prerequisites()->sync($prerequisites);
        }

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('success', 'บทความถูกสร้างเรียบร้อยแล้ว');
    }

    /**
     * Show the form for editing an article
     */
    public function edit(LearningArticle $article)
    {
        $article->load(['category', 'prerequisites', 'permissions']);
        $categories = LearningCategory::active()->ordered()->get();
        $allArticles = LearningArticle::published()
            ->where('id', '!=', $article->id)
            ->ordered()
            ->get();

        return view('admin.articles.edit', compact('article', 'categories', 'allArticles'));
    }

    /**
     * Update the specified article
     */
    public function update(Request $request, LearningArticle $article)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:learning_categories,id',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:learning_articles,slug,' . $article->id,
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'thumbnail' => 'nullable|image|max:2048',
            'video_url' => 'nullable|url',
            'estimated_duration' => 'nullable|integer|min:0',
            'order' => 'nullable|integer|min:0',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'tags' => 'nullable|string',
            'prerequisites' => 'nullable|array',
            'prerequisites.*' => 'exists:learning_articles,id',
        ]);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($article->thumbnail) {
                Storage::disk('public')->delete($article->thumbnail);
            }

            $validated['thumbnail'] = $request->file('thumbnail')
                ->store('learning/thumbnails', 'public');
        }

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Parse tags
        if (!empty($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
        } else {
            $validated['tags'] = [];
        }

        // Set published_at
        if (($validated['is_published'] ?? false) && !$article->published_at) {
            $validated['published_at'] = now();
        } elseif (!($validated['is_published'] ?? false)) {
            $validated['published_at'] = null;
        }

        // Set updater
        $validated['updated_by'] = Auth::id();

        $article->update($validated);

        // Sync prerequisites
        if ($request->has('prerequisites')) {
            $prerequisites = [];
            foreach ($request->prerequisites ?? [] as $prereqId) {
                $prerequisites[$prereqId] = ['is_required' => true];
            }
            $article->prerequisites()->sync($prerequisites);
        }

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('success', 'บทความถูกอัพเดตเรียบร้อยแล้ว');
    }

    /**
     * Remove the specified article
     */
    public function destroy(LearningArticle $article)
    {
        // Delete thumbnail
        if ($article->thumbnail) {
            Storage::disk('public')->delete($article->thumbnail);
        }

        $article->delete();

        return redirect()
            ->route('admin.articles.index')
            ->with('success', 'บทความถูกลบเรียบร้อยแล้ว');
    }

    /**
     * Manage article permissions
     */
    public function permissions(LearningArticle $article)
    {
        $article->load('permissions');

        return view('admin.articles.permissions', compact('article'));
    }

    /**
     * Update article permissions
     */
    public function updatePermissions(Request $request, LearningArticle $article)
    {
        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*.type' => 'required|in:role,user',
            'permissions.*.value' => 'required|string',
        ]);

        // Clear existing permissions
        $article->permissions()->delete();

        // Add new permissions
        if (!empty($validated['permissions'])) {
            foreach ($validated['permissions'] as $permission) {
                $article->permissions()->create([
                    'permission_type' => $permission['type'],
                    'permission_value' => $permission['value'],
                ]);
            }
        }

        return redirect()
            ->route('admin.articles.permissions', $article)
            ->with('success', 'สิทธิ์การเข้าถึงถูกอัพเดตเรียบร้อยแล้ว');
    }
}
