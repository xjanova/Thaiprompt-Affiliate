<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AICoreQuota;
use App\Models\AICoreTenant;
use App\Models\AICoreFeature;
use App\Services\AI\QuotaManager;
use Illuminate\Http\Request;

/**
 * AI Core Quota Controller
 *
 * จัดการ Quotas และการใช้งาน
 */
class AICoreQuotaController extends Controller
{
    /**
     * @var QuotaManager
     */
    protected $quotaManager;

    public function __construct(QuotaManager $quotaManager)
    {
        $this->quotaManager = $quotaManager;
    }

    /**
     * แสดงรายการ Quotas ทั้งหมด
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $quotas = AICoreQuota::with(['tenant', 'feature'])
            ->latest()
            ->paginate(50);

        return view('admin.ai-core.quotas.index', compact('quotas'));
    }

    /**
     * จัดการ Quota ของ Tenant และ Feature
     *
     * @param AICoreTenant $tenant
     * @param AICoreFeature $feature
     * @return \Illuminate\View\View
     */
    public function manage(AICoreTenant $tenant, AICoreFeature $feature)
    {
        $quota = $this->quotaManager->getCurrentQuota($tenant->id, $feature->id);

        $history = AICoreQuota::where('tenant_id', $tenant->id)
            ->where('feature_id', $feature->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.ai-core.quotas.manage', compact(
            'tenant',
            'feature',
            'quota',
            'history'
        ));
    }

    /**
     * เพิ่ม Bonus Quota
     *
     * @param Request $request
     * @param AICoreTenant $tenant
     * @param AICoreFeature $feature
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addBonus(Request $request, AICoreTenant $tenant, AICoreFeature $feature)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $success = $this->quotaManager->addBonusQuota(
            $tenant->id,
            $feature->id,
            $validated['amount'],
            $validated['reason'] ?? null
        );

        if ($success) {
            return back()->with('success', 'เพิ่ม Bonus Quota สำเร็จ: ' . number_format($validated['amount']) . ' units');
        }

        return back()->with('error', 'ไม่สามารถเพิ่ม Bonus Quota ได้');
    }

    /**
     * รีเซ็ต Quota
     *
     * @param AICoreTenant $tenant
     * @param AICoreFeature $feature
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(AICoreTenant $tenant, AICoreFeature $feature)
    {
        $quota = $this->quotaManager->getCurrentQuota($tenant->id, $feature->id);

        if (!$quota) {
            return back()->with('error', 'ไม่พบ Quota');
        }

        $success = $this->quotaManager->resetQuota($quota);

        if ($success) {
            return back()->with('success', 'รีเซ็ต Quota สำเร็จ');
        }

        return back()->with('error', 'ไม่สามารถรีเซ็ต Quota ได้');
    }

    /**
     * รีเซ็ต Quotas ทั้งหมดที่หมดอายุ
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetAllExpired()
    {
        $count = $this->quotaManager->resetExpiredQuotas();

        return back()->with('success', 'รีเซ็ต Quota ทั้งหมด: ' . $count . ' รายการ');
    }
}
