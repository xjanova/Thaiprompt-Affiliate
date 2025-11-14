<?php

namespace App\Http\Controllers\Admin\BotAutomation;

use App\Http\Controllers\Controller;
use App\Models\BotAutomation\BotContentTemplate;
use Illuminate\Http\Request;

class BotTemplateController extends Controller
{
    /**
     * Display a listing of templates
     */
    public function index(Request $request)
    {
        $templates = BotContentTemplate::query()
            ->when($request->category, function ($query, $category) {
                $query->where('category', $category);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20);

        $categories = BotContentTemplate::distinct()->pluck('category')->filter();

        return view('admin.bot-automation.templates.index', compact('templates', 'categories'));
    }

    /**
     * Show the form for creating a new template
     */
    public function create()
    {
        return view('admin.bot-automation.templates.create');
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:marketing,customer_service,sales,engagement,announcement',
            'content' => 'required|string',
            'media_url' => 'nullable|url',
            'media_type' => 'nullable|in:image,video,document',
            'platform_specific' => 'nullable|json',
            'variables' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        $validated['user_id'] = auth()->id();

        $template = BotContentTemplate::create($validated);

        return redirect()
            ->route('admin.bot-automation.templates.show', $template)
            ->with('success', 'Template created successfully');
    }

    /**
     * Display the specified template
     */
    public function show(BotContentTemplate $template)
    {
        return view('admin.bot-automation.templates.show', compact('template'));
    }

    /**
     * Show the form for editing the specified template
     */
    public function edit(BotContentTemplate $template)
    {
        return view('admin.bot-automation.templates.edit', compact('template'));
    }

    /**
     * Update the specified template
     */
    public function update(Request $request, BotContentTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:marketing,customer_service,sales,engagement,announcement',
            'content' => 'required|string',
            'media_url' => 'nullable|url',
            'media_type' => 'nullable|in:image,video,document',
            'platform_specific' => 'nullable|json',
            'variables' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return redirect()
            ->route('admin.bot-automation.templates.show', $template)
            ->with('success', 'Template updated successfully');
    }

    /**
     * Remove the specified template
     */
    public function destroy(BotContentTemplate $template)
    {
        $template->delete();

        return redirect()
            ->route('admin.bot-automation.templates.index')
            ->with('success', 'Template deleted successfully');
    }

    /**
     * Duplicate the specified template
     */
    public function duplicate(BotContentTemplate $template)
    {
        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->save();

        return redirect()
            ->route('admin.bot-automation.templates.edit', $newTemplate)
            ->with('success', 'Template duplicated successfully');
    }

    /**
     * Preview the template
     */
    public function preview(Request $request, BotContentTemplate $template)
    {
        // Replace variables with sample data
        $content = $template->content;
        $variables = json_decode($template->variables ?? '[]', true);

        foreach ($variables as $variable) {
            $content = str_replace("{{" . $variable . "}}", "[SAMPLE DATA]", $content);
        }

        return response()->json([
            'content' => $content,
            'media_url' => $template->media_url,
            'media_type' => $template->media_type,
        ]);
    }

    /**
     * ส่งออกเทมเพลตเป็นไฟล์ JSON
     *
     * @param BotContentTemplate $template
     * @return \Illuminate\Http\Response
     */
    public function export(BotContentTemplate $template)
    {
        $data = [
            'name' => $template->name,
            'description' => $template->description,
            'category' => $template->category,
            'content' => $template->content,
            'media_url' => $template->media_url,
            'media_type' => $template->media_type,
            'platform_specific' => json_decode($template->platform_specific, true),
            'variables' => json_decode($template->variables, true),
            'exported_at' => now()->toDateTimeString(),
            'version' => '1.0',
        ];

        $filename = 'template-' . \Str::slug($template->name) . '-' . now()->format('Y-m-d') . '.json';

        return response()
            ->json($data, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * ทดสอบเทมเพลตด้วยการส่งข้อความทดสอบ
     *
     * @param Request $request
     * @param BotContentTemplate $template
     * @return \Illuminate\Http\JsonResponse
     */
    public function test(Request $request, BotContentTemplate $template)
    {
        // ตรวจสอบข้อมูล
        $validated = $request->validate([
            'platform' => 'nullable|string|in:line,facebook,instagram,twitter',
            'test_data' => 'nullable|array',
        ]);

        // เตรียมเนื้อหาสำหรับทดสอบ
        $content = $template->content;
        $variables = json_decode($template->variables ?? '[]', true);
        $testData = $validated['test_data'] ?? [];

        // แทนที่ variables ด้วยข้อมูลทดสอบ
        foreach ($variables as $variable) {
            $value = $testData[$variable] ?? '[TEST: ' . $variable . ']';
            $content = str_replace("{{" . $variable . "}}", $value, $content);
        }

        // สร้าง preview ของข้อความ
        $preview = [
            'content' => $content,
            'media_url' => $template->media_url,
            'media_type' => $template->media_type,
            'platform' => $validated['platform'] ?? 'line',
            'category' => $template->category,
            'test_mode' => true,
            'timestamp' => now()->toDateTimeString(),
        ];

        // บันทึก log การทดสอบ (optional)
        \Log::info('Template test executed', [
            'template_id' => $template->id,
            'user_id' => auth()->id(),
            'platform' => $validated['platform'] ?? 'line',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ทดสอบเทมเพลตสำเร็จ',
            'preview' => $preview,
        ]);
    }
}
