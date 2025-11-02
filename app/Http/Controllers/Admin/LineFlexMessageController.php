<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineFlexMessageTemplate;
use App\Services\LineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LineFlexMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = LineFlexMessageTemplate::query();

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        $templates = $query->orderBy('created_at', 'desc')->paginate(12);

        return view('admin.line-bot.flex-messages.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.line-bot.flex-messages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'flex_content' => 'required|json',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['flex_content'] = json_decode($validated['flex_content'], true);

        LineFlexMessageTemplate::create($validated);

        return redirect()->route('admin.line-bot.flex.index')
            ->with('success', 'สร้างเทมเพลต Flex Message สำเร็จ');
    }

    public function edit($id)
    {
        $template = LineFlexMessageTemplate::findOrFail($id);

        return view('admin.line-bot.flex-messages.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = LineFlexMessageTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'flex_content' => 'required|json',
            'is_active' => 'boolean',
        ]);

        $validated['flex_content'] = json_decode($validated['flex_content'], true);

        $template->update($validated);

        return redirect()->route('admin.line-bot.flex.index')
            ->with('success', 'อัปเดตเทมเพลตสำเร็จ');
    }

    public function destroy($id)
    {
        $template = LineFlexMessageTemplate::findOrFail($id);

        if ($template->is_seed) {
            return back()->with('error', 'ไม่สามารถลบเทมเพลตตัวอย่างได้');
        }

        $template->delete();

        return redirect()->route('admin.line-bot.flex.index')
            ->with('success', 'ลบเทมเพลตสำเร็จ');
    }

    public function preview($id)
    {
        $template = LineFlexMessageTemplate::findOrFail($id);
        return response()->json($template->flex_content);
    }

    public function testSend(Request $request, $id)
    {
        $template = LineFlexMessageTemplate::findOrFail($id);

        $request->validate([
            'line_user_id' => 'required|string',
        ]);

        $lineService = new LineService();

        if (!$lineService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'LINE OA ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $flexMessage = [
            'type' => 'flex',
            'altText' => $template->name,
            'contents' => $template->flex_content,
        ];

        $success = $lineService->sendPushMessage(
            $request->line_user_id,
            $template->name,
            [$flexMessage]
        );

        $template->incrementUsage();

        return response()->json([
            'success' => $success,
            'message' => $success ? 'ส่งข้อความสำเร็จ' : 'ส่งข้อความล้มเหลว',
        ]);
    }
}
