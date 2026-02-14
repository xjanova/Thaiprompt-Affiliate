<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortuneUserCredit;
use Illuminate\Http\Request;

/**
 * Fortune User Credit Controller
 *
 * จัดการเครดิตดูดวงฟรีรายคน
 * แอดมินสามารถเพิ่มเครดิต / รีเซ็ตสิทธิ์ฟรี / ให้ไม่จำกัดเป็นรายคนได้
 */
class FortuneUserCreditController extends Controller
{
    /**
     * แสดงรายการผู้ใช้ที่มีเครดิตพิเศษ
     */
    public function index(Request $request)
    {
        $query = FortuneUserCredit::query()
            ->orderBy('updated_at', 'desc');

        // ค้นหาตาม Facebook User ID หรือชื่อ
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('facebook_user_id', 'like', "%{$search}%")
                    ->orWhere('facebook_user_name', 'like', "%{$search}%");
            });
        }

        // กรองเฉพาะผู้ใช้ที่มีเครดิตเหลือ
        if ($request->boolean('has_credits')) {
            $query->hasCredits();
        }

        // กรองเฉพาะผู้ใช้ไม่จำกัด
        if ($request->boolean('unlimited')) {
            $query->unlimited();
        }

        $credits = $query->paginate(20)->withQueryString();

        // สถิติ
        $stats = [
            'total_users' => FortuneUserCredit::count(),
            'unlimited_users' => FortuneUserCredit::where('is_unlimited', true)->count(),
            'users_with_credits' => FortuneUserCredit::whereColumn('bonus_credits', '>', 'credits_used')->count(),
            'total_credits_given' => FortuneUserCredit::sum('bonus_credits'),
            'total_credits_used' => FortuneUserCredit::sum('credits_used'),
        ];

        return view('admin.fortune.credits.index', [
            'credits' => $credits,
            'stats' => $stats,
            'pageTitle' => 'จัดการเครดิตดูดวงฟรีรายคน',
        ]);
    }

    /**
     * เพิ่มเครดิตให้ผู้ใช้
     */
    public function addCredits(Request $request)
    {
        $validated = $request->validate([
            'facebook_user_id' => 'required|string|max:255',
            'facebook_user_name' => 'nullable|string|max:255',
            'amount' => 'required|integer|min:1|max:999',
            'note' => 'nullable|string|max:500',
        ]);

        $userCredit = FortuneUserCredit::getOrCreate(
            $validated['facebook_user_id'],
            'facebook',
            $validated['facebook_user_name'] ?? null
        );

        $userCredit->addCredits(
            $validated['amount'],
            auth()->id(),
            $validated['note'] ?? "เพิ่มเครดิต {$validated['amount']} ครั้ง"
        );

        return redirect()
            ->route('admin.fortune.credits.index')
            ->with('success', "เพิ่มเครดิต {$validated['amount']} ครั้ง ให้ {$validated['facebook_user_id']} สำเร็จ");
    }

    /**
     * รีเซ็ตสิทธิ์ฟรีวันนี้
     */
    public function resetDaily(Request $request, FortuneUserCredit $credit)
    {
        $credit->resetDaily(
            auth()->id(),
            $request->input('note', 'รีเซ็ตสิทธิ์ฟรีวันนี้โดยแอดมิน')
        );

        return redirect()
            ->route('admin.fortune.credits.index')
            ->with('success', "รีเซ็ตสิทธิ์ฟรีวันนี้ให้ {$credit->facebook_user_id} สำเร็จ");
    }

    /**
     * ตั้งค่าดูฟรีไม่จำกัด
     */
    public function setUnlimited(Request $request, FortuneUserCredit $credit)
    {
        $validated = $request->validate([
            'is_unlimited' => 'required|boolean',
            'unlimited_until' => 'nullable|date|after_or_equal:today',
            'note' => 'nullable|string|max:500',
        ]);

        $credit->setUnlimited(
            $validated['is_unlimited'],
            $validated['unlimited_until'] ?? null,
            auth()->id(),
            $validated['note']
        );

        $action = $validated['is_unlimited'] ? 'เปิด' : 'ปิด';

        return redirect()
            ->route('admin.fortune.credits.index')
            ->with('success', "{$action}ดูฟรีไม่จำกัดให้ {$credit->facebook_user_id} สำเร็จ");
    }

    /**
     * รีเซ็ตเครดิตทั้งหมด (กลับเป็น 0)
     */
    public function resetAll(Request $request, FortuneUserCredit $credit)
    {
        $credit->resetCredits(
            auth()->id(),
            $request->input('note', 'รีเซ็ตเครดิตทั้งหมดโดยแอดมิน')
        );

        return redirect()
            ->route('admin.fortune.credits.index')
            ->with('success', "รีเซ็ตเครดิตทั้งหมดของ {$credit->facebook_user_id} สำเร็จ");
    }

    /**
     * ลบเครดิตรายคน
     */
    public function destroy(FortuneUserCredit $credit)
    {
        $userId = $credit->facebook_user_id;
        $credit->delete();

        return redirect()
            ->route('admin.fortune.credits.index')
            ->with('success', "ลบเครดิตของ {$userId} สำเร็จ");
    }
}
