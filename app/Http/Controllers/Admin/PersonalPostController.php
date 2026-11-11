<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PreparePersonalPostsJob;
use App\Models\PersonalPostDraft;
use App\Models\Setting;
use App\Services\PersonalPost\PersonalPostPrepService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * 📘 หน้าเตรียมโพสต์เฟซบุ๊กส่วนตัว
 *
 * ระบบเตรียมบทความ + ภาพไว้วันละ 4 โพสต์ — เจ้าของคัดลอก/แชร์แล้วกดโพสต์เองบนเฟซบุ๊ก
 * (Meta ไม่มี API โพสต์ลงโปรไฟล์ส่วนตัว ระบบจึงไม่โพสต์แทน)
 */
class PersonalPostController extends Controller
{
    /** งานสร้างที่ค้างเกินนี้ถือว่าตายแล้ว ให้กดใหม่ได้ (นาที) */
    protected const STALE_MINUTES = 10;

    /**
     * แสดงโพสต์ของวันที่เลือก
     */
    public function index(Request $request): View
    {
        $date = $this->resolveDate($request->query('date'));

        $drafts = PersonalPostDraft::whereDate('post_date', $date)->orderBy('slot')->get()->keyBy('slot');

        // สรุป 7 วันล่าสุด (สำหรับแถบเลือกวัน)
        $days = collect(range(0, 6))->map(function (int $i) use ($date) {
            $d = now()->startOfDay()->subDays($i);

            return [
                'date' => $d->toDateString(),
                'label' => $i === 0 ? 'วันนี้' : ($i === 1 ? 'เมื่อวาน' : $d->locale('th')->translatedFormat('j M')),
                'active' => $d->isSameDay($date),
            ];
        });
        $counts = PersonalPostDraft::whereDate('post_date', '>=', now()->subDays(6)->toDateString())
            ->selectRaw('post_date, status, COUNT(*) as c')
            ->groupBy('post_date', 'status')
            ->get()
            ->groupBy(fn ($r) => Carbon::parse($r->post_date)->toDateString());

        return view('admin.personal-posts.index', [
            'pageTitle' => 'โพสต์เฟซบุ๊กส่วนตัว',
            'date' => $date,
            'drafts' => $drafts,
            'days' => $days,
            'counts' => $counts,
            'preparing' => Cache::has(PreparePersonalPostsJob::lockKey($date->toDateString())),
            'settings' => [
                'enabled' => (bool) Setting::get(PersonalPostPrepService::SETTING_ENABLED, true),
                'profile_url' => (string) Setting::get(PersonalPostPrepService::SETTING_PROFILE_URL, PersonalPostPrepService::DEFAULT_PROFILE_URL),
                'text_provider' => (string) Setting::get(PersonalPostPrepService::SETTING_TEXT_PROVIDER, 'gemini'),
                'image_model' => (string) Setting::get(PersonalPostPrepService::SETTING_IMAGE_MODEL, 'gpt-image-2'),
            ],
            'textProviders' => PersonalPostPrepService::TEXT_PROVIDERS,
            'imageModels' => PersonalPostPrepService::OPENAI_IMAGE_MODELS,
        ]);
    }

    /**
     * สถานะล่าสุดของแต่ละช่อง (หน้าเว็บถามทุกไม่กี่วินาทีระหว่างกำลังสร้าง)
     */
    public function status(Request $request): JsonResponse
    {
        $date = $this->resolveDate($request->query('date'));
        $drafts = PersonalPostDraft::whereDate('post_date', $date)->orderBy('slot')->get();

        return response()->json([
            'preparing' => Cache::has(PreparePersonalPostsJob::lockKey($date->toDateString())),
            'drafts' => $drafts->map(fn (PersonalPostDraft $d) => [
                'id' => $d->id,
                'slot' => $d->slot,
                'status' => $d->status,
                'updated_at' => $d->updated_at?->timestamp,
            ])->values(),
        ]);
    }

    /**
     * บันทึกการตั้งค่า
     */
    public function saveSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'profile_url' => ['required', 'url', 'max:500', function ($attr, $value, $fail) {
                $host = strtolower((string) parse_url((string) $value, PHP_URL_HOST));
                if (! in_array($host, ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'web.facebook.com'], true)) {
                    $fail('ลิงก์โปรไฟล์ต้องเป็นลิงก์ของ facebook.com');
                }
            }],
            'text_provider' => ['required', Rule::in(array_keys(PersonalPostPrepService::TEXT_PROVIDERS))],
            'image_model' => ['required', Rule::in(array_keys(PersonalPostPrepService::OPENAI_IMAGE_MODELS))],
        ], [
            'profile_url.required' => 'กรุณาใส่ลิงก์โปรไฟล์เฟซบุ๊ก',
            'profile_url.url' => 'ลิงก์โปรไฟล์ไม่ถูกต้อง',
        ]);

        Setting::set(PersonalPostPrepService::SETTING_ENABLED, $request->boolean('enabled'), 'boolean', 'personal_posts');
        Setting::set(PersonalPostPrepService::SETTING_PROFILE_URL, $validated['profile_url'], 'string', 'personal_posts');
        Setting::set(PersonalPostPrepService::SETTING_TEXT_PROVIDER, $validated['text_provider'], 'string', 'personal_posts');
        Setting::set(PersonalPostPrepService::SETTING_IMAGE_MODEL, $validated['image_model'], 'string', 'personal_posts');

        return back()->with('success', 'บันทึกการตั้งค่าแล้ว');
    }

    /**
     * สั่งเตรียมโพสต์ทั้งวันเดี๋ยวนี้ (ทำต่อหลังตอบหน้าเว็บ)
     */
    public function prepare(Request $request): RedirectResponse
    {
        $date = $this->resolveDate($request->input('date'));
        $force = $request->boolean('force');

        // 🛡️ กันกดซ้ำ — ล็อก 20 นาที (job ปลดเองเมื่อเสร็จ)
        if (! Cache::add(PreparePersonalPostsJob::lockKey($date->toDateString()), now()->timestamp, now()->addMinutes(20))) {
            return back()->with('error', 'กำลังเตรียมโพสต์ของวันนี้อยู่ รอสักครู่แล้วหน้าเว็บจะอัปเดตเอง');
        }

        PreparePersonalPostsJob::dispatchAfterResponse('day', $date->toDateString(), null, $force);

        return redirect()
            ->route('admin.personal-posts.index', ['date' => $date->toDateString()])
            ->with('success', $force
                ? 'กำลังเลือกหัวข้อใหม่และสร้างใหม่ทุกช่อง ใช้เวลาประมาณ 2-5 นาที'
                : 'กำลังเตรียมโพสต์ช่องที่ยังไม่พร้อม ใช้เวลาประมาณ 2-5 นาที');
    }

    /**
     * สร้างบทความ/ภาพของช่องเดียวใหม่
     */
    public function regenerate(Request $request, PersonalPostDraft $draft): RedirectResponse
    {
        $mode = $request->validate([
            'mode' => ['required', Rule::in(['text', 'image', 'both'])],
        ])['mode'];

        if ($this->isBusy($draft)) {
            return back()->with('error', "ช่องที่ {$draft->slot} กำลังสร้างอยู่ รอสักครู่");
        }
        if ($mode === 'image' && trim((string) $draft->caption) === '') {
            $mode = 'both';
        }

        $draft->update(['status' => PersonalPostDraft::STATUS_GENERATING, 'error' => null]);
        PreparePersonalPostsJob::dispatchAfterResponse($mode, null, $draft->id);

        $what = ['text' => 'บทความ', 'image' => 'ภาพ', 'both' => 'บทความและภาพ'][$mode];

        return back()->with('success', "กำลังสร้าง{$what}ใหม่ให้ช่องที่ {$draft->slot}");
    }

    /**
     * แก้บทความเอง
     */
    public function updateCaption(Request $request, PersonalPostDraft $draft): RedirectResponse
    {
        $validated = $request->validate([
            'caption' => 'required|string|max:5000',
        ], [
            'caption.required' => 'บทความต้องไม่ว่าง',
            'caption.max' => 'บทความยาวเกิน 5,000 ตัวอักษร',
        ]);

        $draft->update([
            'caption' => trim($validated['caption']),
            // ล้มเพราะ AI แต่เจ้าของเขียนเองแล้ว = พร้อมโพสต์
            'status' => in_array($draft->status, [PersonalPostDraft::STATUS_FAILED, PersonalPostDraft::STATUS_DRAFT], true)
                ? PersonalPostDraft::STATUS_READY
                : $draft->status,
        ]);

        return back()->with('success', "บันทึกบทความช่องที่ {$draft->slot} แล้ว");
    }

    /**
     * ทำเครื่องหมายว่าโพสต์แล้ว (undo=1 = ยกเลิก)
     */
    public function markPosted(Request $request, PersonalPostDraft $draft): RedirectResponse|JsonResponse
    {
        if ($request->boolean('undo')) {
            $draft->update(['status' => PersonalPostDraft::STATUS_READY, 'posted_at' => null]);
        } elseif ($draft->status !== PersonalPostDraft::STATUS_POSTED) {
            // กดซ้ำไม่เปลี่ยนเวลาโพสต์เดิม
            $draft->update(['status' => PersonalPostDraft::STATUS_POSTED, 'posted_at' => now()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => $draft->status]);
        }

        return back()->with('success', $request->boolean('undo')
            ? "ยกเลิกสถานะโพสต์แล้วของช่องที่ {$draft->slot}"
            : "บันทึกว่าโพสต์ช่องที่ {$draft->slot} แล้ว 🎉");
    }

    /**
     * ข้ามช่องนี้ (undo=1 = เอากลับมา)
     */
    public function skip(Request $request, PersonalPostDraft $draft): RedirectResponse
    {
        if ($request->boolean('undo')) {
            $draft->update(['status' => trim((string) $draft->caption) !== '' ? PersonalPostDraft::STATUS_READY : PersonalPostDraft::STATUS_FAILED]);
        } else {
            $draft->update(['status' => PersonalPostDraft::STATUS_SKIPPED]);
        }

        return back()->with('success', $request->boolean('undo') ? "เอาช่องที่ {$draft->slot} กลับมาแล้ว" : "ข้ามช่องที่ {$draft->slot} แล้ว");
    }

    /**
     * ดาวน์โหลดภาพ (ชื่อไฟล์อ่านง่าย)
     */
    public function downloadImage(PersonalPostDraft $draft): BinaryFileResponse
    {
        $path = (string) $draft->image_path;
        // 🛡️ อนุญาตเฉพาะไฟล์ในโฟลเดอร์ของระบบนี้ (กัน path traversal จากค่าใน DB)
        abort_unless($path !== '' && str_starts_with($path, 'personal-posts/') && ! str_contains($path, '..'), 404);

        $absolute = storage_path('app/public/'.$path);
        abort_unless(is_file($absolute), 404);

        $name = sprintf('post-%s-%d.%s', Carbon::parse($draft->post_date)->format('Y-m-d'), $draft->slot, pathinfo($path, PATHINFO_EXTENSION) ?: 'png');

        return response()->download($absolute, $name);
    }

    /**
     * อ่านวันที่จาก query — ผิดรูปแบบ = วันนี้
     */
    protected function resolveDate(?string $value): Carbon
    {
        if ($value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
            } catch (Throwable) {
                // ตกไปใช้วันนี้
            }
        }

        return now()->startOfDay();
    }

    /**
     * ช่องนี้กำลังสร้างอยู่จริงไหม (ค้างเกิน 10 นาที = ถือว่าตาย ให้กดใหม่ได้)
     */
    protected function isBusy(PersonalPostDraft $draft): bool
    {
        return $draft->status === PersonalPostDraft::STATUS_GENERATING
            && $draft->updated_at
            && $draft->updated_at->gt(now()->subMinutes(self::STALE_MINUTES));
    }
}
