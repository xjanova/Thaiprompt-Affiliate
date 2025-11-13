<?php

namespace App\Http\Controllers;

use App\Services\PromptToWebService;
use App\Models\GeneratedPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PromptToWebController extends Controller
{
    protected $promptToWebService;

    public function __construct(PromptToWebService $promptToWebService)
    {
        $this->promptToWebService = $promptToWebService;
    }

    /**
     * Show the prompt to web interface
     */
    public function index()
    {
        $recentPages = null;

        if (Auth::check()) {
            $recentPages = GeneratedPage::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        return view('prompt-to-web.index', compact('recentPages'));
    }

    /**
     * Generate a web page from prompt
     */
    public function generate(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|min:10|max:2000',
            'provider' => 'nullable|string',
            'model' => 'nullable|string',
        ]);

        try {
            // Generate web page using AI
            $result = $this->promptToWebService->generateWebPage(
                $request->input('prompt'),
                $request->input('provider'),
                $request->input('model')
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'เกิดข้อผิดพลาดในการสร้างหน้าเว็บ',
                ], 500);
            }

            // Save to database
            $generatedPage = $this->savePage(
                $request->input('prompt'),
                $result
            );

            return response()->json([
                'success' => true,
                'message' => 'สร้างหน้าเว็บสำเร็จ',
                'data' => [
                    'id' => $generatedPage->id,
                    'slug' => $generatedPage->slug,
                    'title' => $result['title'],
                    'description' => $result['description'],
                    'html' => $result['html'],
                    'css' => $result['css'],
                    'js' => $result['js'],
                    'full_content' => $result['full_content'],
                    'preview_url' => route('prompt-to-web.preview', $generatedPage->slug),
                    'tokens_used' => $result['tokens_used'],
                    'cost' => $result['cost'],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Improve an existing page
     */
    public function improve(Request $request, $id)
    {
        $request->validate([
            'feedback' => 'required|string|min:10|max:1000',
            'provider' => 'nullable|string',
        ]);

        try {
            $page = GeneratedPage::findOrFail($id);

            // Check permission
            if (Auth::check() && $page->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์แก้ไขหน้าเว็บนี้',
                ], 403);
            }

            // Improve the page
            $result = $this->promptToWebService->improveWebPage(
                $page->html_content,
                $request->input('feedback'),
                $request->input('provider')
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'เกิดข้อผิดพลาดในการปรับปรุงหน้าเว็บ',
                ], 500);
            }

            // Update the page
            $page->update([
                'title' => $result['title'],
                'description' => $result['description'],
                'html_content' => $result['full_content'],
                'tokens_used' => $page->tokens_used + $result['tokens_used'],
                'cost' => $page->cost + $result['cost'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ปรับปรุงหน้าเว็บสำเร็จ',
                'data' => [
                    'id' => $page->id,
                    'slug' => $page->slug,
                    'title' => $result['title'],
                    'description' => $result['description'],
                    'html' => $result['html'],
                    'css' => $result['css'],
                    'js' => $result['js'],
                    'full_content' => $result['full_content'],
                    'preview_url' => route('prompt-to-web.preview', $page->slug),
                    'tokens_used' => $result['tokens_used'],
                    'cost' => $result['cost'],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview a generated page
     */
    public function preview($slug)
    {
        $page = GeneratedPage::where('slug', $slug)->firstOrFail();

        // Increment view count
        $page->increment('views');

        return view('prompt-to-web.preview', compact('page'));
    }

    /**
     * Show the raw HTML of a generated page
     */
    public function show($slug)
    {
        $page = GeneratedPage::where('slug', $slug)->firstOrFail();

        return response($page->html_content)
            ->header('Content-Type', 'text/html');
    }

    /**
     * List all generated pages for the current user
     */
    public function list(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเข้าสู่ระบบ',
            ], 401);
        }

        $pages = GeneratedPage::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    /**
     * Delete a generated page
     */
    public function delete($id)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาเข้าสู่ระบบ',
            ], 401);
        }

        try {
            $page = GeneratedPage::findOrFail($id);

            // Check permission
            if ($page->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์ลบหน้าเว็บนี้',
                ], 403);
            }

            $page->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบหน้าเว็บสำเร็จ',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save generated page to database
     */
    protected function savePage(string $prompt, array $result): GeneratedPage
    {
        $slug = Str::slug($result['title']) . '-' . Str::random(8);

        return GeneratedPage::create([
            'user_id' => Auth::id(),
            'title' => $result['title'],
            'description' => $result['description'],
            'slug' => $slug,
            'prompt' => $prompt,
            'html_content' => $result['full_content'],
            'provider' => $result['provider'] ?? null,
            'model' => $result['model'] ?? null,
            'tokens_used' => $result['tokens_used'] ?? 0,
            'cost' => $result['cost'] ?? 0,
            'views' => 0,
        ]);
    }
}
