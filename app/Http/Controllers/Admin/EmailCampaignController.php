<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\EmailProvider;
use App\Models\User;
use App\Models\Rank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * จัดการ Email Campaign
 *
 * Controller สำหรับจัดการแคมเปญการส่งอีเมลแบบ Bulk
 */
class EmailCampaignController extends Controller
{
    /**
     * แสดงรายการแคมเปญทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Query campaigns พร้อม relationships
        $query = EmailCampaign::with(['creator', 'template', 'provider'])
            ->latest();

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // กรองตามคำค้นหา
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $campaigns = $query->paginate(15);

        // นับจำนวนตามสถานะ
        $statusCounts = [
            'all' => EmailCampaign::count(),
            'draft' => EmailCampaign::where('status', 'draft')->count(),
            'scheduled' => EmailCampaign::where('status', 'scheduled')->count(),
            'sending' => EmailCampaign::where('status', 'sending')->count(),
            'sent' => EmailCampaign::where('status', 'sent')->count(),
            'cancelled' => EmailCampaign::where('status', 'cancelled')->count(),
        ];

        return view('admin.email.campaigns.index', compact('campaigns', 'statusCounts'));
    }

    /**
     * แสดงฟอร์มสร้างแคมเปญใหม่
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // ดึงข้อมูลสำหรับฟอร์ม
        $templates = EmailTemplate::where('is_active', true)
            ->orderBy('name')
            ->get();

        $providers = EmailProvider::where('is_active', true)
            ->orderBy('priority')
            ->get();

        $ranks = Rank::orderBy('level')->get();

        return view('admin.email.campaigns.create', compact('templates', 'providers', 'ranks'));
    }

    /**
     * บันทึกแคมเปญใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // ตรวจสอบข้อมูล
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_id' => 'nullable|exists:email_templates,id',
            'content_html' => 'required|string',
            'content_plain' => 'nullable|string',
            'target_type' => 'required|in:all_users,specific_users,by_rank,by_status,custom_query',
            'target_filters' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
            'provider_id' => 'nullable|exists:email_providers,id',
            'sending_rate' => 'integer|min:1|max:1000',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // สร้างแคมเปญ
            $campaign = EmailCampaign::create([
                'name' => $validated['name'],
                'subject' => $validated['subject'],
                'description' => $validated['description'] ?? null,
                'template_id' => $validated['template_id'] ?? null,
                'content_html' => $validated['content_html'],
                'content_plain' => $validated['content_plain'] ?? strip_tags($validated['content_html']),
                'target_type' => $validated['target_type'],
                'target_filters' => $validated['target_filters'] ?? null,
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'provider_id' => $validated['provider_id'] ?? null,
                'sending_rate' => $validated['sending_rate'] ?? 100,
                'track_opens' => $validated['track_opens'] ?? true,
                'track_clicks' => $validated['track_clicks'] ?? true,
                'created_by' => Auth::id(),
                'status' => $validated['scheduled_at'] ? 'scheduled' : 'draft',
            ]);

            // คำนวณจำนวนผู้รับ
            $recipientsCount = $this->calculateRecipientsCount($campaign);
            $campaign->update(['total_recipients' => $recipientsCount]);

            DB::commit();

            return redirect()
                ->route('admin.email.campaigns.show', $campaign)
                ->with('success', 'สร้างแคมเปญสำเร็จ! มีผู้รับทั้งหมด ' . number_format($recipientsCount) . ' คน');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * แสดงรายละเอียดแคมเปญ
     *
     * @param EmailCampaign $campaign
     * @return \Illuminate\View\View
     */
    public function show(EmailCampaign $campaign)
    {
        $campaign->load([
            'creator',
            'approver',
            'template',
            'provider',
            'recipients' => function ($query) {
                $query->latest()->limit(100);
            }
        ]);

        // สถิติแคมเปญ
        $stats = [
            'total' => $campaign->total_recipients,
            'sent' => $campaign->sent_count,
            'delivered' => $campaign->delivered_count,
            'opened' => $campaign->opened_count,
            'clicked' => $campaign->clicked_count,
            'bounced' => $campaign->bounced_count,
            'failed' => $campaign->failed_count,
            'open_rate' => $campaign->open_rate,
            'click_rate' => $campaign->click_rate,
            'bounce_rate' => $campaign->bounce_rate,
            'success_rate' => $campaign->success_rate,
        ];

        return view('admin.email.campaigns.show', compact('campaign', 'stats'));
    }

    /**
     * แสดงฟอร์มแก้ไขแคมเปญ
     *
     * @param EmailCampaign $campaign
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(EmailCampaign $campaign)
    {
        // เช็คว่าแก้ไขได้หรือไม่
        if (!$campaign->isEditable()) {
            return back()->with('error', 'ไม่สามารถแก้ไขแคมเปญที่กำลังส่งหรือส่งเสร็จแล้ว');
        }

        $templates = EmailTemplate::where('is_active', true)
            ->orderBy('name')
            ->get();

        $providers = EmailProvider::where('is_active', true)
            ->orderBy('priority')
            ->get();

        $ranks = Rank::orderBy('level')->get();

        return view('admin.email.campaigns.edit', compact('campaign', 'templates', 'providers', 'ranks'));
    }

    /**
     * อัพเดทแคมเปญ
     *
     * @param Request $request
     * @param EmailCampaign $campaign
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, EmailCampaign $campaign)
    {
        // เช็คว่าแก้ไขได้หรือไม่
        if (!$campaign->isEditable()) {
            return back()->with('error', 'ไม่สามารถแก้ไขแคมเปญที่กำลังส่งหรือส่งเสร็จแล้ว');
        }

        // ตรวจสอบข้อมูล
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_id' => 'nullable|exists:email_templates,id',
            'content_html' => 'required|string',
            'content_plain' => 'nullable|string',
            'target_type' => 'required|in:all_users,specific_users,by_rank,by_status,custom_query',
            'target_filters' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
            'provider_id' => 'nullable|exists:email_providers,id',
            'sending_rate' => 'integer|min:1|max:1000',
            'track_opens' => 'boolean',
            'track_clicks' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // อัพเดทแคมเปญ
            $campaign->update([
                'name' => $validated['name'],
                'subject' => $validated['subject'],
                'description' => $validated['description'] ?? null,
                'template_id' => $validated['template_id'] ?? null,
                'content_html' => $validated['content_html'],
                'content_plain' => $validated['content_plain'] ?? strip_tags($validated['content_html']),
                'target_type' => $validated['target_type'],
                'target_filters' => $validated['target_filters'] ?? null,
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'provider_id' => $validated['provider_id'] ?? null,
                'sending_rate' => $validated['sending_rate'] ?? 100,
                'track_opens' => $validated['track_opens'] ?? true,
                'track_clicks' => $validated['track_clicks'] ?? true,
                'status' => $validated['scheduled_at'] ? 'scheduled' : 'draft',
            ]);

            // คำนวณจำนวนผู้รับใหม่
            $recipientsCount = $this->calculateRecipientsCount($campaign);
            $campaign->update(['total_recipients' => $recipientsCount]);

            DB::commit();

            return redirect()
                ->route('admin.email.campaigns.show', $campaign)
                ->with('success', 'อัพเดทแคมเปญสำเร็จ!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ลบแคมเปญ
     *
     * @param EmailCampaign $campaign
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(EmailCampaign $campaign)
    {
        // เช็คว่าลบได้หรือไม่
        if (!$campaign->isDeletable()) {
            return back()->with('error', 'ไม่สามารถลบแคมเปญที่กำลังส่งได้');
        }

        try {
            $campaign->delete();

            return redirect()
                ->route('admin.email.campaigns.index')
                ->with('success', 'ลบแคมเปญสำเร็จ!');

        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * คำนวณจำนวนผู้รับตาม target_type
     *
     * @param EmailCampaign $campaign
     * @return int
     */
    private function calculateRecipientsCount(EmailCampaign $campaign): int
    {
        $query = User::query();

        switch ($campaign->target_type) {
            case 'all_users':
                // ทุกคนที่มี email
                $query->whereNotNull('email');
                break;

            case 'by_rank':
                // ตาม Rank
                if (!empty($campaign->target_filters['ranks'])) {
                    $query->whereIn('rank_id', $campaign->target_filters['ranks']);
                }
                break;

            case 'by_status':
                // ตามสถานะ
                if (!empty($campaign->target_filters['statuses'])) {
                    $query->whereIn('status', $campaign->target_filters['statuses']);
                }
                break;

            case 'specific_users':
                // เลือกเอง
                if (!empty($campaign->target_filters['user_ids'])) {
                    $query->whereIn('id', $campaign->target_filters['user_ids']);
                }
                break;

            case 'custom_query':
                // Custom Query (สำหรับอนาคต)
                break;
        }

        return $query->count();
    }

    /**
     * เริ่มส่งแคมเปญ
     *
     * @param EmailCampaign $campaign
     * @return \Illuminate\Http\RedirectResponse
     */
    public function start(EmailCampaign $campaign)
    {
        // เช็คสถานะ
        if (!in_array($campaign->status, ['draft', 'scheduled', 'paused'])) {
            return back()->with('error', 'ไม่สามารถเริ่มส่งแคมเปญนี้ได้');
        }

        try {
            DB::beginTransaction();

            // เปลี่ยนสถานะเป็น sending
            $campaign->update([
                'status' => 'sending',
                'started_at' => now(),
            ]);

            // TODO: Dispatch job เพื่อส่งอีเมล
            // dispatch(new SendEmailCampaignJob($campaign));

            DB::commit();

            return back()->with('success', 'เริ่มส่งแคมเปญแล้ว!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * หยุดส่งแคมเปญชั่วคราว
     *
     * @param EmailCampaign $campaign
     * @return \Illuminate\Http\RedirectResponse
     */
    public function pause(EmailCampaign $campaign)
    {
        if ($campaign->status !== 'sending') {
            return back()->with('error', 'แคมเปญนี้ไม่ได้กำลังส่งอยู่');
        }

        $campaign->update(['status' => 'paused']);

        return back()->with('success', 'หยุดส่งแคมเปญชั่วคราวแล้ว!');
    }

    /**
     * ยกเลิกแคมเปญ
     *
     * @param EmailCampaign $campaign
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(EmailCampaign $campaign)
    {
        if ($campaign->isCompleted()) {
            return back()->with('error', 'ไม่สามารถยกเลิกแคมเปญที่ส่งเสร็จแล้ว');
        }

        $campaign->update(['status' => 'cancelled']);

        return back()->with('success', 'ยกเลิกแคมเปญแล้ว!');
    }
}
