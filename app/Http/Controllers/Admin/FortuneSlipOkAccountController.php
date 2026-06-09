<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneTellingSetting;
use App\Models\SlipOkAccount;
use App\Services\Fortune\SlipOkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 🪪 จัดการ SlipOK Account Pool — หลายบัญชี SlipOK หมุนเวียนกัน quota ฟรีตัน
 */
class FortuneSlipOkAccountController extends Controller
{
    /**
     * แสดงรายการบัญชี + โหมด pool
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $settings = FortuneTellingSetting::getSettings();

        $accounts = SlipOkAccount::query()
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->each(fn (SlipOkAccount $a) => $a->resetMonthlyIfNeeded());

        return view('admin.fortune.slipok-accounts.index', [
            'pageTitle' => 'SlipOK Account Pool',
            'accounts' => $accounts,
            'settings' => $settings,
            'modes' => SlipOkAccount::MODES,
        ]);
    }

    /**
     * เพิ่มบัญชีใหม่
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:255',
            'branch_id' => 'required|string|max:255',
            'api_key' => 'required|string|max:1000',
            'priority' => 'nullable|integer|min:0|max:9999',
            'monthly_quota' => 'nullable|integer|min:1|max:1000000',
            'notes' => 'nullable|string|max:1000',
        ]);

        SlipOkAccount::create([
            'label' => $validated['label'] ?? null,
            'branch_id' => trim($validated['branch_id']),
            'api_key' => trim($validated['api_key']),
            'priority' => $validated['priority'] ?? 100,
            'monthly_quota' => $validated['monthly_quota'] ?? 100,
            'is_active' => true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('admin.fortune.slipok-accounts.index')
            ->with('success', 'เพิ่มบัญชี SlipOK สำเร็จ');
    }

    /**
     * แก้ไขบัญชี (api_key ว่าง = ไม่เขียนทับของเดิม — กัน secret หาย)
     */
    public function update(Request $request, SlipOkAccount $account): RedirectResponse
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:255',
            'branch_id' => 'required|string|max:255',
            'api_key' => 'nullable|string|max:1000',
            'priority' => 'nullable|integer|min:0|max:9999',
            'monthly_quota' => 'nullable|integer|min:1|max:1000000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $account->label = $validated['label'] ?? null;
        $account->branch_id = trim($validated['branch_id']);
        $account->priority = $validated['priority'] ?? $account->priority;
        $account->monthly_quota = $validated['monthly_quota'] ?? $account->monthly_quota;
        $account->notes = $validated['notes'] ?? null;

        // 🔐 (mirror SlipOK/Stripe secret guard) — api_key ว่าง = เก็บของเดิมไว้ ไม่ทับ
        $newKey = trim((string) ($validated['api_key'] ?? ''));
        if ($newKey !== '') {
            $account->api_key = $newKey;
        }

        $account->save();

        return redirect()
            ->route('admin.fortune.slipok-accounts.index')
            ->with('success', 'อัพเดทบัญชี SlipOK สำเร็จ');
    }

    /**
     * บันทึกโหมด pool + เปิด/ปิด pool + threshold
     */
    public function updateMode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slipok_pool_enabled' => 'nullable|boolean',
            'slipok_pool_mode' => 'required|in:near_empty,failover,balance',
            'slipok_pool_threshold' => 'nullable|integer|min:1|max:100000',
        ]);

        $settings = FortuneTellingSetting::getSettings();
        $settings->slipok_pool_enabled = (bool) ($validated['slipok_pool_enabled'] ?? false);
        $settings->slipok_pool_mode = $validated['slipok_pool_mode'];
        $settings->slipok_pool_threshold = $validated['slipok_pool_threshold'] ?? 10;
        $settings->save();

        FortuneTellingSetting::clearSettingsCache();

        return redirect()
            ->route('admin.fortune.slipok-accounts.index')
            ->with('success', 'บันทึกโหมด pool สำเร็จ');
    }

    /**
     * ลบบัญชี (soft delete)
     */
    public function destroy(SlipOkAccount $account): RedirectResponse
    {
        $account->delete();

        return redirect()
            ->route('admin.fortune.slipok-accounts.index')
            ->with('success', 'ลบบัญชี SlipOK สำเร็จ');
    }

    /**
     * เปิด/ปิดบัญชี
     */
    public function toggle(SlipOkAccount $account): RedirectResponse
    {
        $account->is_active = ! $account->is_active;
        // เปิดกลับ → ปลดสถานะพัก (เผื่อแอดมินรู้ว่าโควต้ากลับมาแล้ว)
        if ($account->is_active) {
            $account->exhausted_until = null;
            $account->consecutive_errors = 0;
        }
        $account->save();

        return redirect()
            ->route('admin.fortune.slipok-accounts.index')
            ->with('success', $account->is_active ? 'เปิดบัญชีแล้ว' : 'ปิดบัญชีแล้ว');
    }

    /**
     * ทดสอบเชื่อมต่อ + sync โควต้าจริงของบัญชีนี้
     */
    public function test(SlipOkAccount $account): JsonResponse
    {
        try {
            $result = (new SlipOkService(FortuneTellingSetting::getSettings()))
                ->checkQuotaForAccount($account);

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::warning('SlipOkAccount: test ล้มเหลว', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ทดสอบไม่สำเร็จ: '.$e->getMessage(),
            ], 500);
        }
    }
}
