<?php

namespace App\Http\Controllers;

use App\Models\InstallmentPlan;
use App\Models\InstallmentPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstallmentController extends Controller
{
    /**
     * Display user's installment plans
     */
    public function index()
    {
        $plans = InstallmentPlan::where('user_id', Auth::id())
            ->with(['order', 'quotation'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('installments.index', compact('plans'));
    }

    /**
     * Display the specified installment plan
     */
    public function show(InstallmentPlan $plan)
    {
        // Check authorization
        if ($plan->user_id !== Auth::id() && !Auth::user()->is_admin) {
            abort(403);
        }

        $plan->load(['payments' => function($query) {
            $query->orderBy('installment_number');
        }, 'order', 'quotation']);

        // Check and update overdue status
        $plan->checkOverdue();

        return view('installments.show', compact('plan'));
    }

    /**
     * Pay installment payment
     */
    public function pay(Request $request, InstallmentPlan $plan, InstallmentPayment $payment)
    {
        // Check authorization
        if ($plan->user_id !== Auth::id()) {
            abort(403);
        }

        if ($payment->installment_plan_id !== $plan->id) {
            abort(404);
        }

        if ($payment->status === 'paid') {
            return back()->with('error', 'งวดนี้ชำระเงินแล้ว');
        }

        // Validate payment info
        $validated = $request->validate([
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string|max:255',
            'payment_note' => 'nullable|string|max:1000',
        ]);

        // Here you would integrate with payment gateway
        // For now, we'll mark as pending payment confirmation

        return redirect()
            ->route('payment.process', [
                'type' => 'installment',
                'installment_payment_id' => $payment->id,
                'amount' => $payment->total_due,
            ])
            ->with('info', 'กรุณาดำเนินการชำระเงิน');
    }
}
