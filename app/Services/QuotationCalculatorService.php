<?php

namespace App\Services;

use App\Models\SoftwareProduct;
use App\Models\SoftwareQuotation;
use App\Models\SoftwareQuotationItem;
use App\Models\SoftwareQuotationSelectedOption;

class QuotationCalculatorService
{
    /**
     * คำนวณราคาใบเสนอราคาจาก selections
     *
     * @param SoftwareProduct $product
     * @param array $selections Format: ['option_id' => [value_id1, value_id2, ...]]
     * @param float $taxRate
     * @param float $discountPercentage
     * @return array
     */
    public function calculate(SoftwareProduct $product, array $selections, float $taxRate = 7, float $discountPercentage = 0): array
    {
        $basePrice = $product->base_price;
        $subtotal = $basePrice;
        $setupTotal = 0;
        $monthlyTotal = 0;
        $selectedOptions = [];

        foreach ($selections as $optionId => $valueIds) {
            $option = $product->options()->find($optionId);
            if (!$option) {
                continue;
            }

            // Convert single value to array
            if (!is_array($valueIds)) {
                $valueIds = [$valueIds];
            }

            foreach ($valueIds as $valueId) {
                $optionValue = $option->values()->find($valueId);
                if (!$optionValue) {
                    continue;
                }

                // Calculate price based on type
                $priceModifier = $optionValue->calculatePrice($basePrice);
                $subtotal += $priceModifier;
                $setupTotal += $optionValue->setup_fee;
                $monthlyTotal += $optionValue->monthly_fee;

                $selectedOptions[] = [
                    'option_id' => $option->id,
                    'option_name' => $option->display_name,
                    'value_id' => $optionValue->id,
                    'value_label' => $optionValue->display_label,
                    'price_modifier' => $priceModifier,
                    'setup_fee' => $optionValue->setup_fee,
                    'monthly_fee' => $optionValue->monthly_fee,
                ];
            }
        }

        // Apply discount
        $discountAmount = $subtotal * ($discountPercentage / 100);
        $amountAfterDiscount = $subtotal - $discountAmount;

        // Calculate tax
        $taxAmount = $amountAfterDiscount * ($taxRate / 100);

        // Total
        $totalAmount = $amountAfterDiscount + $taxAmount;

        return [
            'base_price' => $basePrice,
            'subtotal' => $subtotal,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'setup_total' => $setupTotal,
            'monthly_total' => $monthlyTotal,
            'selected_options' => $selectedOptions,
        ];
    }

    /**
     * สร้างใบเสนอราคาจากการเลือก
     */
    public function createQuotation(
        int $userId,
        SoftwareProduct $product,
        array $selections,
        array $customerInfo,
        float $taxRate = 7,
        float $discountPercentage = 0,
        ?string $notes = null
    ): SoftwareQuotation {
        $calculation = $this->calculate($product, $selections, $taxRate, $discountPercentage);

        // Create quotation
        $quotation = SoftwareQuotation::create([
            'user_id' => $userId,
            'software_product_id' => $product->id,
            'customer_name' => $customerInfo['name'],
            'customer_email' => $customerInfo['email'],
            'customer_phone' => $customerInfo['phone'] ?? null,
            'company_name' => $customerInfo['company_name'] ?? null,
            'company_address' => $customerInfo['company_address'] ?? null,
            'tax_id' => $customerInfo['tax_id'] ?? null,
            'notes' => $notes,
            'subtotal' => $calculation['subtotal'],
            'discount_amount' => $calculation['discount_amount'],
            'discount_percentage' => $discountPercentage,
            'tax_rate' => $taxRate,
            'tax_amount' => $calculation['tax_amount'],
            'total_amount' => $calculation['total_amount'],
            'setup_total' => $calculation['setup_total'],
            'monthly_total' => $calculation['monthly_total'],
            'status' => 'draft',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Create quotation item
        $item = SoftwareQuotationItem::create([
            'software_quotation_id' => $quotation->id,
            'software_product_id' => $product->id,
            'item_name' => $product->name,
            'item_description' => $product->short_description,
            'quantity' => 1,
            'unit_price' => $product->base_price,
            'setup_fee' => $calculation['setup_total'],
            'monthly_fee' => $calculation['monthly_total'],
            'subtotal' => $calculation['subtotal'],
            'selected_options' => $calculation['selected_options'],
        ]);

        // Create selected options
        foreach ($calculation['selected_options'] as $selectedOption) {
            SoftwareQuotationSelectedOption::create([
                'software_quotation_item_id' => $item->id,
                'software_product_option_id' => $selectedOption['option_id'],
                'software_product_option_value_id' => $selectedOption['value_id'],
                'option_name' => $selectedOption['option_name'],
                'option_value' => $selectedOption['value_label'],
                'option_display_label' => $selectedOption['value_label'],
                'price_modifier' => $selectedOption['price_modifier'],
                'setup_fee' => $selectedOption['setup_fee'],
                'monthly_fee' => $selectedOption['monthly_fee'],
                'quantity' => 1,
            ]);
        }

        // Increment quotation count
        $product->incrementQuotationCount();

        return $quotation;
    }

    /**
     * คำนวณแผนผ่อนชำระ
     */
    public function calculateInstallmentPlan(
        float $totalAmount,
        float $downPaymentPercentage,
        int $totalInstallments,
        float $interestRate = 0,
        string $frequency = 'monthly'
    ): array {
        $downPayment = $totalAmount * ($downPaymentPercentage / 100);
        $remainingAmount = $totalAmount - $downPayment;

        // Calculate with interest
        $principalPerPayment = $remainingAmount / $totalInstallments;
        $interestPerPayment = ($remainingAmount * ($interestRate / 100)) / $totalInstallments;
        $installmentAmount = $principalPerPayment + $interestPerPayment;
        $totalInterest = $interestPerPayment * $totalInstallments;

        return [
            'total_amount' => $totalAmount,
            'down_payment' => $downPayment,
            'down_payment_percentage' => $downPaymentPercentage,
            'remaining_amount' => $remainingAmount,
            'total_installments' => $totalInstallments,
            'installment_amount' => $installmentAmount,
            'principal_per_payment' => $principalPerPayment,
            'interest_per_payment' => $interestPerPayment,
            'interest_rate' => $interestRate,
            'total_interest' => $totalInterest,
            'total_with_interest' => $totalAmount + $totalInterest,
            'payment_frequency' => $frequency,
        ];
    }
}
