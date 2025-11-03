<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiBotProfile;
use App\Models\AiProvider;
use App\Models\AiModel;
use App\Services\AI\AiServiceFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiBotController extends Controller
{
    /**
     * รายการ Bots ทั้งหมด
     */
    public function index()
    {
        $bots = AiBotProfile::with(['provider', 'model', 'owner'])
            ->where('owner_id', Auth::id())
            ->orWhere('is_public', true)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.ai-bots.index', compact('bots'));
    }

    /**
     * หน้าสร้าง Bot ใหม่
     */
    public function create()
    {
        $providers = AiProvider::where('is_active', true)->get();
        $models = AiModel::where('is_active', true)->get();

        return view('admin.ai-bots.create', compact('providers', 'models'));
    }

    /**
     * บันทึก Bot ใหม่
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'provider_id' => 'required|exists:ai_providers,id',
            'model_id' => 'required|exists:ai_models,id',
            'system_prompt' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:100000',
            'top_p' => 'nullable|numeric|min:0|max:1',
            'frequency_penalty' => 'nullable|numeric|min:-2|max:2',
            'presence_penalty' => 'nullable|numeric|min:-2|max:2',
            'enable_knowledge_base' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'is_rentable' => 'nullable|boolean',
            'rental_price_per_month' => 'nullable|numeric|min:0',
            'rental_price_per_message' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['owner_id'] = Auth::id();
        $validated['is_active'] = true;

        $bot = AiBotProfile::create($validated);

        return redirect()->route('admin.ai-bots.show', $bot->id)
            ->with('success', 'สร้าง Bot สำเร็จ!');
    }

    /**
     * ดูรายละเอียด Bot
     */
    public function show($id)
    {
        $bot = AiBotProfile::with(['provider', 'model', 'owner', 'conversations', 'usageLogs'])
            ->findOrFail($id);

        // ตรวจสอบสิทธิ์
        if ($bot->owner_id !== Auth::id() && !$bot->is_public) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึง Bot นี้');
        }

        // สถิติ
        $stats = [
            'total_conversations' => $bot->conversations()->count(),
            'total_messages' => $bot->conversations()->sum('total_messages'),
            'total_tokens' => $bot->usageLogs()->sum('total_tokens'),
            'total_cost' => $bot->usageLogs()->sum('cost'),
            'avg_response_time' => $bot->usageLogs()->avg('response_time_ms'),
            'success_rate' => $this->calculateSuccessRate($bot),
        ];

        return view('admin.ai-bots.show', compact('bot', 'stats'));
    }

    /**
     * หน้าแก้ไข Bot
     */
    public function edit($id)
    {
        $bot = AiBotProfile::findOrFail($id);

        // ตรวจสอบเจ้าของ
        if ($bot->owner_id !== Auth::id()) {
            abort(403, 'คุณไม่สามารถแก้ไข Bot นี้ได้');
        }

        $providers = AiProvider::where('is_active', true)->get();
        $models = AiModel::where('is_active', true)->get();

        return view('admin.ai-bots.edit', compact('bot', 'providers', 'models'));
    }

    /**
     * อัปเดต Bot
     */
    public function update(Request $request, $id)
    {
        $bot = AiBotProfile::findOrFail($id);

        if ($bot->owner_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'provider_id' => 'required|exists:ai_providers,id',
            'model_id' => 'required|exists:ai_models,id',
            'system_prompt' => 'nullable|string',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:100000',
            'top_p' => 'nullable|numeric|min:0|max:1',
            'frequency_penalty' => 'nullable|numeric|min:-2|max:2',
            'presence_penalty' => 'nullable|numeric|min:-2|max:2',
            'enable_knowledge_base' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'is_rentable' => 'nullable|boolean',
            'rental_price_per_month' => 'nullable|numeric|min:0',
            'rental_price_per_message' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $bot->update($validated);

        return redirect()->route('admin.ai-bots.show', $bot->id)
            ->with('success', 'อัปเดต Bot สำเร็จ!');
    }

    /**
     * ลบ Bot
     */
    public function destroy($id)
    {
        $bot = AiBotProfile::findOrFail($id);

        if ($bot->owner_id !== Auth::id()) {
            abort(403);
        }

        // ตรวจสอบว่ามีการใช้งานหรือไม่
        if ($bot->conversations()->where('status', 'active')->count() > 0) {
            return back()->with('error', 'ไม่สามารถลบ Bot ที่มีบทสนทนาที่กำลังใช้งานอยู่');
        }

        $bot->delete();

        return redirect()->route('admin.ai-bots.index')
            ->with('success', 'ลบ Bot สำเร็จ!');
    }

    /**
     * เปิด/ปิด Bot
     */
    public function toggle($id)
    {
        $bot = AiBotProfile::findOrFail($id);

        if ($bot->owner_id !== Auth::id()) {
            abort(403);
        }

        $bot->is_active = !$bot->is_active;
        $bot->save();

        return response()->json([
            'success' => true,
            'message' => $bot->is_active ? 'เปิดใช้งาน Bot แล้ว' : 'ปิดใช้งาน Bot แล้ว',
            'is_active' => $bot->is_active,
        ]);
    }

    /**
     * ทดสอบ Bot
     */
    public function test(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $bot = AiBotProfile::findOrFail($id);

        if ($bot->owner_id !== Auth::id() && !$bot->is_public) {
            abort(403);
        }

        try {
            $service = AiServiceFactory::createFromBot($bot);

            $messages = [];

            if ($bot->system_prompt) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $bot->system_prompt,
                ];
            }

            $messages[] = [
                'role' => 'user',
                'content' => $request->message,
            ];

            $response = $service->chat($messages, [
                'temperature' => $bot->temperature ?? 0.7,
                'max_tokens' => $bot->max_tokens ?? 500,
            ]);

            if ($response['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $response['content'],
                    'tokens_used' => $response['usage']['total_tokens'] ?? 0,
                    'response_time_ms' => $response['response_time_ms'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $response['error'] ?? 'Unknown error',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ดึง Models ตาม Provider
     */
    public function getModelsByProvider($providerId)
    {
        $models = AiModel::where('provider_id', $providerId)
            ->where('is_active', true)
            ->get(['id', 'display_name', 'model_identifier', 'context_window', 'pricing']);

        return response()->json([
            'success' => true,
            'data' => $models,
        ]);
    }

    /**
     * คำนวณ success rate
     */
    private function calculateSuccessRate(AiBotProfile $bot): float
    {
        $total = $bot->usageLogs()->count();

        if ($total === 0) {
            return 0;
        }

        $successful = $bot->usageLogs()->where('status', 'success')->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Duplicate Bot
     */
    public function duplicate($id)
    {
        $bot = AiBotProfile::findOrFail($id);

        if ($bot->owner_id !== Auth::id()) {
            abort(403);
        }

        $newBot = $bot->replicate();
        $newBot->name = $bot->name . ' (Copy)';
        $newBot->display_name = $bot->display_name . ' (Copy)';
        $newBot->is_public = false;
        $newBot->is_rentable = false;
        $newBot->save();

        return redirect()->route('admin.ai-bots.edit', $newBot->id)
            ->with('success', 'คัดลอก Bot สำเร็จ!');
    }
}
