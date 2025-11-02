<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineBotAiSetting;
use App\Models\LineBotKnowledgeBase;
use App\Services\LineBotAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LineBotAiController extends Controller
{
    public function index()
    {
        $aiSettings = LineBotAiSetting::with('knowledgeBases')->get();

        return view('admin.line-bot.ai.index', compact('aiSettings'));
    }

    public function create()
    {
        return view('admin.line-bot.ai.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|in:openai,deepseek,anthropic,gemini,custom',
            'api_key' => 'required|string',
            'api_endpoint' => 'nullable|string|url',
            'model' => 'required|string',
            'temperature' => 'required|numeric|min:0|max:2',
            'max_tokens' => 'required|integer|min:1',
            'system_prompt' => 'nullable|string',
            'enable_conversation_history' => 'boolean',
            'conversation_memory_limit' => 'integer|min:1',
            'is_active' => 'boolean',
            'enable_fallback' => 'boolean',
            'fallback_message' => 'nullable|string',
        ]);

        // Deactivate others if setting as active
        if ($request->is_active) {
            LineBotAiSetting::where('is_active', true)->update(['is_active' => false]);
        }

        $setting = LineBotAiSetting::create($validated);
        LineBotAiSetting::clearCache();

        return redirect()->route('admin.line-bot.ai.index')
            ->with('success', 'สร้างการตั้งค่า AI สำเร็จ');
    }

    public function edit($id)
    {
        $aiSetting = LineBotAiSetting::with('knowledgeBases')->findOrFail($id);
        return view('admin.line-bot.ai.edit', compact('aiSetting'));
    }

    public function update(Request $request, $id)
    {
        $setting = LineBotAiSetting::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|in:openai,deepseek,anthropic,gemini,custom',
            'api_key' => 'required|string',
            'api_endpoint' => 'nullable|string|url',
            'model' => 'required|string',
            'temperature' => 'required|numeric|min:0|max:2',
            'max_tokens' => 'required|integer|min:1',
            'system_prompt' => 'nullable|string',
            'enable_conversation_history' => 'boolean',
            'conversation_memory_limit' => 'integer|min:1',
            'is_active' => 'boolean',
            'enable_fallback' => 'boolean',
            'fallback_message' => 'nullable|string',
        ]);

        if ($request->is_active && !$setting->is_active) {
            LineBotAiSetting::where('is_active', true)->update(['is_active' => false]);
        }

        $setting->update($validated);
        LineBotAiSetting::clearCache();

        return redirect()->route('admin.line-bot.ai.index')
            ->with('success', 'อัปเดตการตั้งค่า AI สำเร็จ');
    }

    public function destroy($id)
    {
        $setting = LineBotAiSetting::findOrFail($id);
        $setting->delete();
        LineBotAiSetting::clearCache();

        return redirect()->route('admin.line-bot.ai.index')
            ->with('success', 'ลบการตั้งค่า AI สำเร็จ');
    }

    public function test(Request $request, $id)
    {
        $setting = LineBotAiSetting::findOrFail($id);
        $aiService = new LineBotAiService($setting);

        $testMessage = $request->input('message', 'สวัสดี! นี่คือข้อความทดสอบ');

        try {
            $response = $aiService->generateResponse($testMessage);

            return response()->json([
                'success' => true,
                'message' => $response['message'],
                'provider' => $response['provider'],
                'tokens_used' => $response['tokens_used'],
                'response_time' => round($response['response_time'], 2),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Knowledge Base Management
    public function knowledgeIndex($aiSettingId)
    {
        $aiSetting = LineBotAiSetting::with('knowledgeBases')->findOrFail($aiSettingId);
        $knowledgeBases = $aiSetting->knowledgeBases;
        return view('admin.line-bot.ai.knowledge-base', compact('aiSetting', 'knowledgeBases'));
    }

    public function knowledgeCreate($aiSettingId)
    {
        $setting = LineBotAiSetting::findOrFail($aiSettingId);
        return view('admin.line-bot.ai.knowledge.create', compact('setting'));
    }

    public function knowledgeStore(Request $request, $aiSettingId)
    {
        $setting = LineBotAiSetting::findOrFail($aiSettingId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:url,internal,file,text',
            'source' => 'nullable|string',
            'content' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,txt,doc,docx,csv|max:10240',
            'priority' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('knowledge-bases', 'public');
            $validated['file_path'] = $path;
            $validated['file_type'] = $request->file('file')->getClientOriginalExtension();
        }

        $setting->knowledgeBases()->create($validated);

        return redirect()->route('admin.line-bot.ai.knowledge.index', $aiSettingId)
            ->with('success', 'เพิ่มแหล่งข้อมูลสำเร็จ');
    }

    public function knowledgeDestroy($aiSettingId, $knowledgeId)
    {
        $knowledge = LineBotKnowledgeBase::where('ai_setting_id', $aiSettingId)
            ->findOrFail($knowledgeId);

        if ($knowledge->file_path) {
            Storage::disk('public')->delete($knowledge->file_path);
        }

        $knowledge->delete();

        return redirect()->route('admin.line-bot.ai.knowledge.index', $aiSettingId)
            ->with('success', 'ลบแหล่งข้อมูลสำเร็จ');
    }
}
