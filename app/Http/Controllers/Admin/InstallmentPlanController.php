<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstallmentPlan;
use App\Models\InstallmentPayment;
use Illuminate\Http\Request;

class InstallmentPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = InstallmentPlan::with(['user', 'order']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('plan_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $plans = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'total' => InstallmentPlan::count(),
            'active' => InstallmentPlan::where('status', 'active')->count(),
            'overdue' => InstallmentPlan::where('status', 'overdue')->count(),
            'completed' => InstallmentPlan::where('status', 'completed')->count(),
        ];

        return view('admin.installment-plans.index', compact('plans', 'stats'));
    }

    public function show(InstallmentPlan $installmentPlan)
    {
        $installmentPlan->load(['user', 'order', 'quotation', 'payments' => function($query) {
            $query->orderBy('installment_number');
        }]);

        // Check overdue
        $installmentPlan->checkOverdue();

        return view('admin.installment-plans.show', compact('installmentPlan'));
    }

    public function markPaymentAsPaid(Request $request, InstallmentPlan $installmentPlan, InstallmentPayment $payment)
    {
        if ($payment->installment_plan_id !== $installmentPlan->id) {
            abort(404);
        }

        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string|max:255',
            'payment_note' => 'nullable|string|max:1000',
        ]);

        $payment->markAsPaid(
            $validated['paid_amount'],
            $validated['payment_method'],
            $validated['payment_reference'] ?? null,
            $validated['payment_note'] ?? null
        );

        return back()->with('success', 'บันทึกการชำระเงินเรียบร้อยแล้ว');
    }

    public function cancel(Request $request, InstallmentPlan $installmentPlan)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $installmentPlan->cancel($validated['reason'] ?? null);

        return back()->with('success', 'ยกเลิกแผนผ่อนชำระเรียบร้อยแล้ว');
    }
}
