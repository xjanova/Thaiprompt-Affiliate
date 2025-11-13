<?php

namespace App\Http\Requests\TPIX;

use Illuminate\Foundation\Http\FormRequest;

class CreateTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:100|unique:tpix_tokens,name',
            'symbol' => 'required|string|min:2|max:20|uppercase|unique:tpix_tokens,symbol|regex:/^[A-Z0-9]+$/',
            'description' => 'required|string|min:50|max:5000',
            'total_supply' => 'required|numeric|min:1|max:1000000000000',
            'decimals' => 'required|integer|min:0|max:18',

            // Token Features
            'is_mintable' => 'boolean',
            'is_burnable' => 'boolean',
            'is_pausable' => 'boolean',
            'is_freezable' => 'boolean',
            'has_max_supply' => 'boolean',
            'max_supply' => 'required_if:has_max_supply,true|nullable|numeric|gt:total_supply',

            // Token Categories
            'category' => 'required|in:defi,gamefi,meme,utility,stablecoin,nft,dao,other',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:50',

            // Social & Links
            'website' => 'nullable|url|max:255',
            'whitepaper_url' => 'nullable|url|max:255',
            'github_url' => 'nullable|url|max:255',
            'twitter_handle' => 'nullable|string|max:50|regex:/^@?[A-Za-z0-9_]+$/',
            'telegram_group' => 'nullable|string|max:255',
            'discord_invite' => 'nullable|string|max:255',

            // Tokenomics
            'initial_price_tpix' => 'required|numeric|min:0.00000001',
            'allocation_team' => 'nullable|numeric|min:0|max:100',
            'allocation_marketing' => 'nullable|numeric|min:0|max:100',
            'allocation_liquidity' => 'nullable|numeric|min:0|max:100',
            'allocation_public_sale' => 'nullable|numeric|min:0|max:100',

            // Logo
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'กรุณาระบุชื่อ Token',
            'name.min' => 'ชื่อ Token ต้องมีอย่างน้อย 3 ตัวอักษร',
            'name.unique' => 'ชื่อ Token นี้ถูกใช้ไปแล้ว',
            'symbol.required' => 'กรุณาระบุสัญลักษณ์ Token',
            'symbol.unique' => 'สัญลักษณ์ Token นี้ถูกใช้ไปแล้ว',
            'symbol.regex' => 'สัญลักษณ์ต้องเป็นตัวอักษรพิมพ์ใหญ่และตัวเลขเท่านั้น',
            'description.min' => 'คำอธิบายต้องมีอย่างน้อย 50 ตัวอักษร',
            'total_supply.required' => 'กรุณาระบุจำนวน Token ทั้งหมด',
            'total_supply.min' => 'จำนวน Token ต้องมากกว่า 0',
            'max_supply.gt' => 'จำนวนสูงสุดต้องมากกว่าจำนวนเริ่มต้น',
            'website.url' => 'กรุณาระบุ URL ที่ถูกต้อง',
            'initial_price_tpix.required' => 'กรุณาระบุราคาเริ่มต้น',
            'logo.image' => 'ไฟล์ต้องเป็นรูปภาพ',
            'logo.max' => 'ขนาดไฟล์ต้องไม่เกิน 2MB',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate total allocation doesn't exceed 100%
            $totalAllocation =
                ($this->allocation_team ?? 0) +
                ($this->allocation_marketing ?? 0) +
                ($this->allocation_liquidity ?? 0) +
                ($this->allocation_public_sale ?? 0);

            if ($totalAllocation > 100) {
                $validator->errors()->add('allocation', 'การแบ่งสรร Token รวมกันต้องไม่เกิน 100%');
            }
        });
    }
}
