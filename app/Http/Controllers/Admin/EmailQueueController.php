<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaignRecipient;
use App\Models\EmailCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * จัดการคิวการส่งอีเมล
 *
 * Controller สำหรับแสดงและจัดการคิวการส่งอีเมลในระบบ
 */
class EmailQueueController extends Controller
{
    /**
     * แสดงคิวการส่งอีเมล
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Query recipients พร้อม relationships
        $query = EmailCampaignRecipient::with(['campaign', 'user'])
            ->latest();

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // กรองตามแคมเปญ
        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        // กรองตามคำค้นหา (email)
        if ($request->filled('search')) {
            $query->where('email', 'like', '%' . $request->search . '%');
        }

        $queue = $query->paginate(50);

        // สถิติคิว
        $stats = [
            'pending' => EmailCampaignRecipient::where('status', 'pending')->count(),
            'sending' => EmailCampaignRecipient::where('status', 'sending')->count(),
            'sent' => EmailCampaignRecipient::whereIn('status', ['sent', 'delivered', 'opened', 'clicked'])->count(),
            'failed' => EmailCampaignRecipient::where('status', 'failed')->count(),
            'bounced' => EmailCampaignRecipient::where('status', 'bounced')->count(),
        ];

        // รายชื่อแคมเปญที่กำลังส่ง
        $activeCampaigns = EmailCampaign::whereIn('status', ['sending', 'scheduled'])
            ->orderBy('name')
            ->get(['id', 'name', 'status']);

        return view('admin.email.queue.index', compact('queue', 'stats', 'activeCampaigns'));
    }

    /**
     * ลองส่งใหม่สำหรับอีเมลที่ล้มเหลว
     *
     * @param EmailCampaignRecipient $recipient
     * @return \Illuminate\Http\RedirectResponse
     */
    public function retry(EmailCampaignRecipient $recipient)
    {
        // เช็คว่าเป็นอีเมลที่ล้มเหลวหรือไม่
        if (!$recipient->isFailed()) {
            return back()->with('error', 'สามารถลองส่งใหม่ได้เฉพาะอีเมลที่ล้มเหลวเท่านั้น');
        }

        try {
            // รีเซ็ตสถานะเป็น pending
            $recipient->update([
                'status' => 'pending',
                'error_message' => null,
            ]);

            // TODO: Dispatch job เพื่อส่งอีเมลใหม่
            // dispatch(new SendEmailJob($recipient));

            return back()->with('success', 'เพิ่มเข้าคิวการส่งใหม่แล้ว!');

        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ลองส่งใหม่สำหรับอีเมลที่ล้มเหลวทั้งหมดในแคมเปญ
     *
     * @param EmailCampaign $campaign
     * @return \Illuminate\Http\RedirectResponse
     */
    public function retryAll(EmailCampaign $campaign)
    {
        try {
            DB::beginTransaction();

            // รีเซ็ตสถานะอีเมลที่ล้มเหลวทั้งหมดเป็น pending
            $updated = $campaign->recipients()
                ->where('status', 'failed')
                ->update([
                    'status' => 'pending',
                    'error_message' => null,
                ]);

            DB::commit();

            return back()->with('success', "เพิ่มอีเมลที่ล้มเหลว {$updated} รายการเข้าคิวการส่งใหม่แล้ว!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ล้างคิวอีเมลที่ล้มเหลว
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearFailed()
    {
        try {
            // ลบอีเมลที่ล้มเหลวเท่านั้น
            $deleted = EmailCampaignRecipient::where('status', 'failed')->delete();

            return back()->with('success', "ล้างอีเมลที่ล้มเหลว {$deleted} รายการแล้ว!");

        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * แสดงรายละเอียดคิว
     *
     * @param EmailCampaignRecipient $recipient
     * @return \Illuminate\View\View
     */
    public function show(EmailCampaignRecipient $recipient)
    {
        $recipient->load(['campaign', 'user']);

        return view('admin.email.queue.show', compact('recipient'));
    }
}
