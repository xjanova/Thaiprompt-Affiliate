<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SoftwareProduct;

class SoftwareProductController extends Controller
{
    /**
     * Get product options with values
     */
    public function getOptions(SoftwareProduct $product)
    {
        $product->load(['activeOptions.activeValues']);

        $options = $product->activeOptions->map(function($option) {
            return [
                'id' => $option->id,
                'name' => $option->name,
                'display_name' => $option->display_name,
                'description' => $option->description,
                'input_type' => $option->input_type,
                'is_required' => $option->is_required,
                'allow_multiple' => $option->allow_multiple,
                'values' => $option->activeValues->map(function($value) use ($option) {
                    return [
                        'id' => $value->id,
                        'value' => $value->value,
                        'display_label' => $value->display_label,
                        'description' => $value->description,
                        'price_modifier' => $value->price_modifier,
                        'price_type' => $value->price_type,
                        'setup_fee' => $value->setup_fee,
                        'monthly_fee' => $value->monthly_fee,
                        'is_default' => $value->is_default,
                        'is_recommended' => $value->is_recommended,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'base_price' => $product->base_price,
                'options' => $options,
            ],
        ]);
    }
}
