<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileBanner;
use App\Services\AppBannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * แบนเนอร์แคมเปญแอป (admin.app-banners.*) — ธีม V4
 *
 * จัดการตาราง mobile_banners ตัวเดียวกับที่แอปเรียก:
 *   - ใหม่: GET /api/v1/banners?placement=  (มีข้อความ/ปุ่ม CTA/กลุ่มผู้ชม/ตารางเวลา)
 *   - เดิม: GET /api/v1/mobile/banners?position=  (แอปรุ่นเก่า — link/link_type ถูกตั้งให้ตรงกับ CTA อัตโนมัติ)
 * รูปอัปโหลดเก็บที่ public disk: storage/app-banners/ · แก้อะไรก็ล้าง cache API ทันที
 */
class AppCampaignBannerController extends Controller
{
    /** โฟลเดอร์รูปบน public disk */
    private const IMAGE_DIR = 'app-banners';

    public function __construct(private readonly AppBannerService $banners) {}

    /**
     * รายการแบนเนอร์ + พรีวิวในกรอบมือถือ
     */
    public function index(Request $request)
    {
        $placement = (string) $request->query('placement', 'all');
        if ($placement !== 'all' && ! array_key_exists($placement, MobileBanner::PLACEMENTS)) {
            $placement = 'all';
        }

        $all = MobileBanner::query()->orderBy('position')->orderBy('sort_order')->orderBy('id')->get();

        $list = $placement === 'all' ? $all : $all->where('position', $placement)->values();

        $counts = [];
        foreach (array_keys(MobileBanner::PLACEMENTS) as $key) {
            $counts[$key] = $all->where('position', $key)->count();
        }

        // พรีวิวในกรอบมือถือ = แบนเนอร์ที่ "กำลังแสดงจริง" ของแต่ละตำแหน่ง (รูปแบบเดียวกับ API)
        $preview = [];
        foreach (array_keys(MobileBanner::PLACEMENTS) as $key) {
            $preview[$key] = $all->where('position', $key)
                ->filter(fn (MobileBanner $b) => $b->scheduleState() === 'showing')
                ->map(fn (MobileBanner $b) => $b->toAppApi())
                ->values()
                ->all();
        }

        $views = (int) $all->sum('view_count');
        $clicks = (int) $all->sum('click_count');

        return view('admin.app-banners.index', [
            'banners' => $list,
            'placement' => $placement,
            'placements' => MobileBanner::PLACEMENTS,
            'audiences' => MobileBanner::AUDIENCES,
            'counts' => $counts,
            'preview' => $preview,
            'previewPlacement' => $placement === 'all' ? MobileBanner::POSITION_HOME : $placement,
            'stats' => [
                'total' => $all->count(),
                'showing' => $all->filter(fn (MobileBanner $b) => $b->scheduleState() === 'showing')->count(),
                'scheduled' => $all->filter(fn (MobileBanner $b) => $b->scheduleState() === 'scheduled')->count(),
                'views' => $views,
                'clicks' => $clicks,
                'ctr' => $views > 0 ? round($clicks / $views * 100, 2) : 0.0,
            ],
            'apiUrl' => url('/api/v1/banners'),
            'pageTitle' => 'แบนเนอร์แคมเปญแอป',
        ]);
    }

    /**
     * ฟอร์มสร้างแบนเนอร์
     */
    public function create(Request $request)
    {
        $banner = new MobileBanner([
            'position' => array_key_exists((string) $request->query('placement'), MobileBanner::PLACEMENTS)
                ? (string) $request->query('placement')
                : MobileBanner::POSITION_HOME,
            'audience' => 'all',
            'is_active' => true,
        ]);

        return $this->form($banner);
    }

    /**
     * บันทึกแบนเนอร์ใหม่
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = $this->validator($request, null);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        try {
            $banner = DB::transaction(function () use ($request, $data) {
                $banner = new MobileBanner;
                $this->fill($banner, $data, $request);
                $banner->image = $this->resolveImage($request, null);
                $banner->created_by = $request->user()?->id;
                $banner->updated_by = $request->user()?->id;

                if (! isset($data['sort_order']) || $data['sort_order'] === null) {
                    $banner->sort_order = (int) MobileBanner::where('position', $banner->position)->max('sort_order') + 1;
                }

                $banner->save();

                return $banner;
            });
        } catch (\Throwable $e) {
            Log::error('AppCampaignBanner: store failed', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'บันทึกแบนเนอร์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        $this->banners->bumpVersion();

        return redirect()
            ->route('admin.app-banners.index', ['placement' => $banner->position])
            ->with('success', 'สร้างแบนเนอร์ "'.$banner->title.'" แล้ว');
    }

    /**
     * ฟอร์มแก้ไขแบนเนอร์
     */
    public function edit(MobileBanner $banner)
    {
        return $this->form($banner);
    }

    /**
     * บันทึกการแก้ไข
     */
    public function update(Request $request, MobileBanner $banner): RedirectResponse
    {
        $validator = $this->validator($request, $banner);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $oldImage = $banner->image;

        try {
            DB::transaction(function () use ($request, $data, $banner) {
                $this->fill($banner, $data, $request);
                $banner->image = $this->resolveImage($request, $banner);
                $banner->updated_by = $request->user()?->id;
                $banner->save();
            });
        } catch (\Throwable $e) {
            Log::error('AppCampaignBanner: update failed', ['banner_id' => $banner->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'บันทึกแบนเนอร์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
        }

        if ($oldImage !== $banner->image) {
            $this->deleteUploadedImage($oldImage, $banner->id);
        }

        $this->banners->bumpVersion();

        return redirect()
            ->route('admin.app-banners.index', ['placement' => $banner->position])
            ->with('success', 'บันทึกแบนเนอร์ "'.$banner->title.'" แล้ว');
    }

    /**
     * ลบแบนเนอร์ (หน้าเว็บยืนยันก่อนส่ง)
     */
    public function destroy(Request $request, MobileBanner $banner): JsonResponse|RedirectResponse
    {
        $title = $banner->title;
        $placement = $banner->position;
        $image = $banner->image;

        try {
            $banner->delete();
        } catch (\Throwable $e) {
            Log::error('AppCampaignBanner: delete failed', ['banner_id' => $banner->id, 'error' => $e->getMessage()]);

            return $this->respond($request, false, 'ลบแบนเนอร์ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', null, 500);
        }

        $this->deleteUploadedImage($image, null);
        $this->banners->bumpVersion();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'ลบแบนเนอร์ "'.$title.'" แล้ว', 'data' => ['id' => (int) $banner->id]]);
        }

        return redirect()
            ->route('admin.app-banners.index', ['placement' => $placement])
            ->with('success', 'ลบแบนเนอร์ "'.$title.'" แล้ว');
    }

    /**
     * เปิด/ปิดแบนเนอร์
     */
    public function toggle(Request $request, MobileBanner $banner): JsonResponse|RedirectResponse
    {
        $banner->is_active = ! $banner->is_active;
        $banner->updated_by = $request->user()?->id;
        $banner->save();

        $this->banners->bumpVersion();

        return $this->respond($request, true, $banner->is_active ? 'เปิดแสดงแบนเนอร์แล้ว' : 'ปิดแบนเนอร์แล้ว', [
            'id' => (int) $banner->id,
            'is_active' => (bool) $banner->is_active,
            'state' => $banner->scheduleState(),
            'state_label' => $banner->scheduleStateLabel(),
        ]);
    }

    /**
     * เลื่อนลำดับขึ้น/ลงภายในตำแหน่งเดียวกัน
     */
    public function move(Request $request, MobileBanner $banner): JsonResponse|RedirectResponse
    {
        $direction = (string) $request->input('direction');
        if (! in_array($direction, ['up', 'down'], true)) {
            return $this->respond($request, false, 'ทิศทางไม่ถูกต้อง', null, 422, 'VALIDATION_ERROR');
        }

        DB::transaction(function () use ($banner, $direction) {
            $siblings = MobileBanner::where('position', $banner->position)
                ->orderBy('sort_order')->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->values();

            $index = $siblings->search(fn (MobileBanner $b) => $b->id === $banner->id);
            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($index === false || $target < 0 || $target >= $siblings->count()) {
                return;
            }

            $ordered = $siblings->all();
            [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

            foreach ($ordered as $position => $item) {
                if ((int) $item->sort_order !== $position + 1) {
                    MobileBanner::whereKey($item->id)->update(['sort_order' => $position + 1]);
                }
            }
        });

        $this->banners->bumpVersion();

        return $this->respond($request, true, 'จัดลำดับแล้ว', ['id' => (int) $banner->id]);
    }

    /**
     * จัดลำดับทั้งชุดของตำแหน่งหนึ่ง {placement, ids: [..]} (JSON)
     */
    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'placement' => ['required', Rule::in(array_keys(MobileBanner::PLACEMENTS))],
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'min:1'],
        ], [
            'placement.*' => 'ตำแหน่งไม่ถูกต้อง',
            'ids.*' => 'รายการแบนเนอร์ไม่ถูกต้อง',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
                'data' => null,
            ], 422);
        }

        $placement = (string) $request->input('placement');
        $ids = array_values(array_unique(array_map('intval', (array) $request->input('ids'))));

        DB::transaction(function () use ($placement, $ids) {
            $valid = MobileBanner::where('position', $placement)->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $order = 1;
            foreach ($ids as $id) {
                if (in_array($id, $valid, true)) {
                    MobileBanner::whereKey($id)->update(['sort_order' => $order++]);
                }
            }
        });

        $this->banners->bumpVersion();

        return response()->json(['success' => true, 'message' => 'บันทึกลำดับแล้ว', 'data' => ['ids' => $ids]]);
    }

    /**
     * เส้นทางเดิม admin.mobile-app.banners.* (หน้า V3) → หน้าใหม่
     */
    public function legacyIndex(): RedirectResponse
    {
        return redirect()->route('admin.app-banners.index');
    }

    public function legacyCreate(): RedirectResponse
    {
        return redirect()->route('admin.app-banners.create');
    }

    public function legacyEdit(MobileBanner $banner): RedirectResponse
    {
        return redirect()->route('admin.app-banners.edit', $banner);
    }

    // =====================================================================
    // ภายใน
    // =====================================================================

    private function form(MobileBanner $banner)
    {
        $isEdit = $banner->exists;

        return view('admin.app-banners.form', [
            'banner' => $banner,
            'isEdit' => $isEdit,
            'placements' => MobileBanner::PLACEMENTS,
            'audiences' => MobileBanner::AUDIENCES,
            'ctaTypes' => MobileBanner::CTA_TYPES,
            'appScreens' => MobileBanner::APP_SCREENS,
            'formAction' => $isEdit ? route('admin.app-banners.update', $banner) : route('admin.app-banners.store'),
            'imageUrl' => $banner->imageUrl(),
            'pageTitle' => $isEdit ? 'แก้ไขแบนเนอร์แคมเปญ' : 'สร้างแบนเนอร์แคมเปญ',
        ]);
    }

    private function validator(Request $request, ?MobileBanner $banner): \Illuminate\Validation\Validator
    {
        $hasImage = $banner !== null && trim((string) $banner->image) !== '';

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:100'],
            'subtitle' => ['nullable', 'string', 'max:150'],
            'image' => [$hasImage ? 'nullable' : 'required_without:image_path', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'image_path' => ['nullable', 'string', 'max:500', 'regex:#^(/(?!/)[^\s]*|https://[^\s]+)$#'],
            'placement' => ['required', Rule::in(array_keys(MobileBanner::PLACEMENTS))],
            'audience' => ['required', Rule::in(array_keys(MobileBanner::AUDIENCES))],
            'cta_type' => ['nullable', Rule::in(['', 'none', 'screen', 'url'])],
            'cta_label' => ['nullable', 'string', 'max:40'],
            'cta_value' => ['nullable', 'string', 'max:500'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'title.required' => 'กรุณาใส่หัวข้อแบนเนอร์',
            'title.max' => 'หัวข้อยาวได้ไม่เกิน 100 ตัวอักษร',
            'subtitle.max' => 'ข้อความรองยาวได้ไม่เกิน 150 ตัวอักษร',
            'image.required_without' => 'กรุณาอัปโหลดรูปแบนเนอร์ หรือใส่ที่อยู่รูป',
            'image.image' => 'ไฟล์ต้องเป็นรูปภาพ',
            'image.file' => 'อัปโหลดรูปไม่สำเร็จ',
            'image.mimes' => 'รองรับรูป JPG, PNG หรือ WEBP เท่านั้น',
            'image.max' => 'รูปต้องมีขนาดไม่เกิน 4 MB',
            'image_path.regex' => 'ที่อยู่รูปต้องขึ้นต้นด้วย / (ในเว็บนี้) หรือ https://',
            'image_path.max' => 'ที่อยู่รูปยาวเกินไป',
            'placement.required' => 'กรุณาเลือกตำแหน่งแสดง',
            'placement.in' => 'ตำแหน่งแสดงไม่ถูกต้อง',
            'audience.required' => 'กรุณาเลือกกลุ่มผู้ชม',
            'audience.in' => 'กลุ่มผู้ชมไม่ถูกต้อง',
            'cta_type.in' => 'ชนิดปุ่มไม่ถูกต้อง',
            'cta_label.max' => 'ข้อความบนปุ่มยาวได้ไม่เกิน 40 ตัวอักษร',
            'cta_value.max' => 'ปลายทางของปุ่มยาวเกินไป',
            'starts_at.date' => 'วันเริ่มแสดงไม่ถูกต้อง',
            'ends_at.date' => 'วันสิ้นสุดไม่ถูกต้อง',
            'sort_order.*' => 'ลำดับต้องเป็นจำนวนเต็ม 0–9999',
        ]);

        $validator->after(function ($v) use ($request) {
            $type = $this->ctaType($request->input('cta_type'));
            $value = trim((string) $request->input('cta_value'));

            if ($type !== null) {
                if (trim((string) $request->input('cta_label')) === '') {
                    $v->errors()->add('cta_label', 'กรุณาใส่ข้อความบนปุ่ม');
                }

                if ($value === '') {
                    $v->errors()->add('cta_value', $type === 'screen' ? 'กรุณาเลือกหน้าจอปลายทาง' : 'กรุณาใส่ลิงก์ปลายทาง');
                } elseif ($type === 'screen' && preg_match(MobileBanner::SCREEN_PATTERN, ltrim($value, '/')) !== 1) {
                    $v->errors()->add('cta_value', 'ชื่อหน้าจอใช้ได้เฉพาะตัวอักษรอังกฤษตัวเล็ก ตัวเลข ขีด และ / เช่น taladsod หรือ product/123');
                } elseif ($type === 'url' && preg_match('#^(/(?!/)[^\s]*|https://[^\s]+)$#', $value) !== 1) {
                    $v->errors()->add('cta_value', 'ลิงก์ต้องขึ้นต้นด้วย https:// หรือ / (หน้าในเว็บนี้)');
                }
            }

            $starts = $this->parseDate($request->input('starts_at'));
            $ends = $this->parseDate($request->input('ends_at'));
            if ($starts !== null && $ends !== null && $ends->lessThanOrEqualTo($starts)) {
                $v->errors()->add('ends_at', 'วันสิ้นสุดต้องอยู่หลังวันเริ่มแสดง');
            }
        });

        return $validator;
    }

    /**
     * ใส่ค่าจากฟอร์มลงโมเดล (ยังไม่ save)
     *
     * @param  array<string, mixed>  $data
     */
    private function fill(MobileBanner $banner, array $data, Request $request): void
    {
        $type = $this->ctaType($data['cta_type'] ?? null);

        $banner->title = trim((string) $data['title']);
        $banner->subtitle = ($s = trim((string) ($data['subtitle'] ?? ''))) !== '' ? $s : null;
        $banner->position = (string) $data['placement'];
        $banner->audience = (string) $data['audience'];
        $banner->cta_type = $type;
        $banner->cta_label = $type !== null ? trim((string) ($data['cta_label'] ?? '')) : null;
        $banner->cta_value = $type !== null
            ? ($type === 'screen' ? ltrim(trim((string) ($data['cta_value'] ?? '')), '/') : trim((string) ($data['cta_value'] ?? '')))
            : null;
        $banner->start_date = $this->parseDate($data['starts_at'] ?? null);
        $banner->end_date = $this->parseDate($data['ends_at'] ?? null);
        $banner->is_active = $request->boolean('is_active');

        if (isset($data['sort_order']) && $data['sort_order'] !== null && $data['sort_order'] !== '') {
            $banner->sort_order = (int) $data['sort_order'];
        }

        $banner->syncLegacyLink();
    }

    /**
     * รูปที่จะใช้: ไฟล์อัปโหลดใหม่ → ที่อยู่รูปที่พิมพ์ → รูปเดิม
     */
    private function resolveImage(Request $request, ?MobileBanner $banner): string
    {
        $file = $request->file('image');
        if ($file instanceof UploadedFile && $file->isValid()) {
            $path = $file->store(self::IMAGE_DIR, 'public');
            if ($path === false) {
                throw new \RuntimeException('store banner image failed');
            }

            return '/storage/'.$path;
        }

        $typed = trim((string) $request->input('image_path'));
        if ($typed !== '') {
            return $typed;
        }

        return (string) ($banner?->getOriginal('image') ?? $banner?->image ?? '');
    }

    /**
     * ลบไฟล์รูปที่อัปโหลดไว้ (เฉพาะใน storage/app-banners/ และไม่มีแบนเนอร์อื่นใช้อยู่)
     */
    private function deleteUploadedImage(?string $image, ?int $exceptId): void
    {
        $image = (string) $image;
        $prefix = '/storage/'.self::IMAGE_DIR.'/';

        if (! str_starts_with($image, $prefix)) {
            return;
        }

        $stillUsed = MobileBanner::where('image', $image)
            ->when($exceptId !== null, fn ($q) => $q->whereKeyNot($exceptId))
            ->exists();

        if ($stillUsed) {
            return;
        }

        try {
            Storage::disk('public')->delete(substr($image, strlen('/storage/')));
        } catch (\Throwable $e) {
            Log::warning('AppCampaignBanner: delete image failed', ['image' => $image, 'error' => $e->getMessage()]);
        }
    }

    private function ctaType(mixed $raw): ?string
    {
        $raw = (string) $raw;

        return in_array($raw, ['screen', 'url'], true) ? $raw : null;
    }

    private function parseDate(mixed $raw): ?Carbon
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    private function respond(Request $request, bool $ok, string $message, ?array $data = null, int $status = 200, ?string $code = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            $body = ['success' => $ok, 'message' => $message, 'data' => $data];
            if ($code !== null) {
                $body['code'] = $code;
            }

            return response()->json($body, $status);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }
}
