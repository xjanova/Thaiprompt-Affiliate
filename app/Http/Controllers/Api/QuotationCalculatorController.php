<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SoftwareProduct;
use App\Services\QuotationCalculatorService;
use Illuminate\Http\Request;

class QuotationCalculatorController extends Controller
{
    protected QuotationCalculatorService $calculatorService;

    public function __construct(QuotationCalculatorService $calculatorService)
    {
        $this->calculatorService = $calculatorService;
    }

    /**
     * Calculate price based on selections
     */
    public function calculate(Request $request, SoftwareProduct $product)
    {
        $validated = $request->validate([
            'selections' => 'required|array',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $calculation = $this->calculatorService->calculate(
            $product,
            $validated['selections'],
            $validated['tax_rate'] ?? 7,
            $validated['discount_percentage'] ?? 0
        );

        return response()->json([
            'success' => true,
            'data' => $calculation,
        ]);
    }

    /**
     * Calculate installment plan
     */
    public function calculateInstallment(Request $request)
    {
        $validated = $request->validate([
            'total_amount' => 'required|numeric|min:0',
            'down_payment_percentage' => 'required|numeric|min:0|max:100',
            'total_installments' => 'required|integer|min:1|max:60',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'frequency' => 'nullable|in:monthly,quarterly,yearly',
        ]);

        $calculation = $this->calculatorService->calculateInstallmentPlan(
            $validated['total_amount'],
            $validated['down_payment_percentage'],
            $validated['total_installments'],
            $validated['interest_rate'] ?? 0,
            $validated['frequency'] ?? 'monthly'
        );

        return response()->json([
            'success' => true,
            'data' => $calculation,
        ]);
    }
}
