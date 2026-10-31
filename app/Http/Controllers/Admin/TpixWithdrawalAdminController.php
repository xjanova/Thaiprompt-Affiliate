<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Crypto\TpixWithdrawalQueueService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

/**
 * คิวถอน TPIX ฝั่งแอดมิน: แอดมินโอนเหรียญเองนอกระบบ แล้วบันทึก tx hash หรือปฏิเสธพร้อมคืนยอด
 */
class TpixWithdrawalAdminController extends Controller
{
    public function __construct(private TpixWithdrawalQueueService $queue) {}

    /**
     * แสดงคิวที่รอดำเนินการและประวัติที่ปิดแล้ว
     */
    public function index(): View
    {
        // เก่าสุดขึ้นก่อน เพื่อให้ทำตามลำดับที่สมาชิกส่งคำขอ
        $pending = $this->queue->withdrawals()
            ->with('user')
            ->where('status', 'pending')
            ->oldest()
            ->paginate(30, ['*'], 'queue_page')
            ->withQueryString();

        $history = $this->queue->withdrawals()
            ->with(['user', 'reviewer'])
            ->where('status', '!=', 'pending')
            ->latest('reviewed_at')
            ->paginate(20, ['*'], 'history_page')
            ->withQueryString();

        $stats = [
            'pending_count' => $pending->total(),
            // SUM ของ DECIMAL ได้สตริงที่ตรงทุกหลัก ไม่ปัดแบบ float
            'pending_amount' => (string) ($this->queue->withdrawals()->where('status', 'pending')->sum('amount') ?: '0'),
            'confirmed_count' => $this->queue->withdrawals()->where('status', 'confirmed')->count(),
            'cancelled_count' => $this->queue->withdrawals()->where('status', 'cancelled')->count(),
        ];

        return view('admin.tpix.withdrawals', compact('pending', 'history', 'stats'));
    }

    /**
     * บันทึกว่าส่งเหรียญแล้ว พร้อม tx hash
     */
    public function confirm(Request $request, int $id): RedirectResponse
    {
        return $this->handle($request, $id, [
            'tx_hash' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{64}$/'],
        ], [
            'tx_hash.required' => 'กรุณากรอก tx hash',
            'tx_hash.regex' => 'tx hash ต้องขึ้นต้นด้วย 0x ตามด้วยตัวอักษรฐานสิบหก 64 ตัว',
        ], function (array $data) use ($request, $id) {
            $this->queue->confirmSent($id, $request->user(), $data['tx_hash']);

            return 'บันทึกการส่งเหรียญแล้ว';
        });
    }

    /**
     * ปฏิเสธคำขอและคืนยอดเข้ากระเป๋าสมาชิก
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        return $this->handle($request, $id, [
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'กรุณากรอกเหตุผลที่ปฏิเสธ',
            'reason.max' => 'เหตุผลยาวได้ไม่เกิน 500 ตัวอักษร',
        ], function (array $data) use ($request, $id) {
            $this->queue->reject($id, $request->user(), $data['reason']);

            return 'ปฏิเสธรายการและคืนยอดแล้ว';
        });
    }

    /**
     * ตรวจข้อมูล เรียก action แล้วแปลงผลเป็นข้อความภาษาไทยกลับไปที่หน้าคิว
     *
     * @param  array<string, array<int, string>>  $rules
     * @param  array<string, string>  $messages
     * @param  callable(array<string, mixed>): string  $action
     */
    private function handle(Request $request, int $id, array $rules, array $messages, callable $action): RedirectResponse
    {
        $validator = Validator::make($request->only(array_keys($rules)), $rules, $messages);

        if ($validator->fails()) {
            return $this->failed($request, $id, $validator->errors()->first());
        }

        try {
            return redirect()->route('admin.tpix.withdrawals.index')->with('success', $action($validator->validated()));
        } catch (DomainException $e) {
            return $this->failed($request, $id, $e->getMessage());
        } catch (Throwable $e) {
            Log::error('TPIX withdrawal queue action failed', [
                'transaction_id' => $id,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->failed($request, $id, 'ดำเนินการไม่สำเร็จ กรุณาลองอีกครั้ง');
        }
    }

    private function failed(Request $request, int $id, string $message): RedirectResponse
    {
        // จำแถวที่ผิดไว้ ค่าที่กรอกจะได้กลับไปที่แถวนั้นแถวเดียว
        return back()->withInput($request->only(['tx_hash', 'reason']))
            ->with('error', $message)
            ->with('tpix_withdrawal_row', $id);
    }
}
