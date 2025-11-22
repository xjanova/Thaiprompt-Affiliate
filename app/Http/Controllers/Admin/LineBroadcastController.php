<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendScheduledBroadcast;
use App\Models\LineBroadcastMessage;
use App\Models\LineFlexMessageTemplate;
use App\Models\User;
use App\Services\LineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LineBroadcastController extends Controller
{
    public function index()
    {
        $broadcasts = LineBroadcastMessage::with('flexTemplate', 'creator')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.line-bot.broadcast.index', compact('broadcasts'));
    }

    public function create()
    {
        $flexTemplates = LineFlexMessageTemplate::active()->get();
        return view('admin.line-bot.broadcast.create', compact('flexTemplates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'message_type' => 'required|in:text,flex,image,video',
            'content' => 'required_if:message_type,text|nullable|string',
            'flex_template_id' => 'required_if:message_type,flex|nullable|exists:line_flex_message_templates,id',
            'target_type' => 'required|in:all,users,sellers,custom',
            'target_users' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = $request->scheduled_at ? 'scheduled' : 'draft';

        // Calculate recipients
        $recipients = $this->getRecipients($validated['target_type'], $validated['target_users'] ?? []);
        $validated['total_recipients'] = count($recipients);

        $broadcast = LineBroadcastMessage::create($validated);

        return redirect()->route('admin.line-bot.broadcast.index')
            ->with('success', 'สร้างแคมเปญ Broadcast สำเร็จ');
    }

    public function show($id)
    {
        $broadcast = LineBroadcastMessage::with('flexTemplate', 'creator')->findOrFail($id);
        return view('admin.line-bot.broadcast.show', compact('broadcast'));
    }

    /**
     * ส่ง broadcast ทันที (dispatch job)
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function send($id)
    {
        $broadcast = LineBroadcastMessage::findOrFail($id);

        if (!in_array($broadcast->status, ['draft', 'scheduled'])) {
            return back()->with('error', 'ไม่สามารถส่งแคมเปญนี้ได้');
        }

        try {
            // Dispatch job ไปยัง queue
            SendScheduledBroadcast::dispatch($broadcast);

            return redirect()->route('admin.line-bot.broadcast.show', $id)
                ->with('success', 'ส่งคำสั่งไปยัง Queue แล้ว! ระบบจะเริ่มส่งข้อความในไม่ช้า');

        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ส่งซ้ำสำหรับ broadcast ที่ล้มเหลว
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function retry($id)
    {
        $broadcast = LineBroadcastMessage::findOrFail($id);

        if (!$broadcast->canRetry()) {
            return back()->with('error', 'ไม่สามารถส่งซ้ำได้ (เกินจำนวนครั้งสูงสุด หรือสถานะไม่ถูกต้อง)');
        }

        try {
            // Reset status และส่งใหม่
            $broadcast->update([
                'status' => 'scheduled',
                'scheduled_at' => now(), // ตั้งเวลาเป็นตอนนี้
            ]);

            // Dispatch job
            SendScheduledBroadcast::dispatch($broadcast);

            return redirect()->route('admin.line-bot.broadcast.show', $id)
                ->with('success', 'เริ่มการส่งซ้ำแล้ว!');

        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $broadcast = LineBroadcastMessage::findOrFail($id);

        if ($broadcast->status === 'sending') {
            return back()->with('error', 'ไม่สามารถลบแคมเปญที่กำลังส่งได้');
        }

        $broadcast->delete();

        return redirect()->route('admin.line-bot.broadcast.index')
            ->with('success', 'ลบแคมเปญสำเร็จ');
    }

    private function getRecipients($targetType, $targetUsers = [])
    {
        switch ($targetType) {
            case 'all':
                return User::whereNotNull('line_user_id')->pluck('line_user_id')->toArray();

            case 'users':
                return User::whereNotNull('line_user_id')
                    ->where('role', 'user')
                    ->pluck('line_user_id')->toArray();

            case 'sellers':
                return User::whereNotNull('line_user_id')
                    ->where('role', 'seller')
                    ->pluck('line_user_id')->toArray();

            case 'custom':
                return User::whereNotNull('line_user_id')
                    ->whereIn('id', $targetUsers)
                    ->pluck('line_user_id')->toArray();

            default:
                return [];
        }
    }
}
