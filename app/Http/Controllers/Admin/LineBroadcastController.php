<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    public function send($id)
    {
        $broadcast = LineBroadcastMessage::findOrFail($id);

        if (!in_array($broadcast->status, ['draft', 'scheduled'])) {
            return back()->with('error', 'ไม่สามารถส่งแคมเปญนี้ได้');
        }

        $recipients = $this->getRecipients($broadcast->target_type, $broadcast->target_users ?? []);

        $broadcast->update([
            'status' => 'sending',
            'total_recipients' => count($recipients),
        ]);

        $lineService = new LineService();
        $sentCount = 0;
        $failedCount = 0;

        foreach ($recipients as $lineUserId) {
            try {
                if ($broadcast->message_type === 'text') {
                    $success = $lineService->sendPushMessage($lineUserId, $broadcast->content);
                } elseif ($broadcast->message_type === 'flex' && $broadcast->flexTemplate) {
                    $flexMessage = [
                        'type' => 'flex',
                        'altText' => $broadcast->name,
                        'contents' => $broadcast->flexTemplate->flex_content,
                    ];
                    $success = $lineService->sendPushMessage($lineUserId, $broadcast->name, [$flexMessage]);
                }

                if ($success) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
            }
        }

        $broadcast->update([
            'status' => 'sent',
            'sent_at' => now(),
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
        ]);

        return redirect()->route('admin.line-bot.broadcast.show', $id)
            ->with('success', "ส่งข้อความสำเร็จ {$sentCount} คน ล้มเหลว {$failedCount} คน");
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
