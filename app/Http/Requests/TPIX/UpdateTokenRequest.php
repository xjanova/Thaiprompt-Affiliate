<?php

namespace App\Http\Requests\TPIX;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $tokenId = $this->route('id');

        return [
            'description' => 'sometimes|string|min:50|max:5000',
            'category' => 'sometimes|in:defi,gamefi,meme,utility,stablecoin,nft,dao,other',
            'tags' => 'sometimes|array|max:10',
            'tags.*' => 'string|max:50',

            // Social & Links
            'website' => 'nullable|url|max:255',
            'whitepaper_url' => 'nullable|url|max:255',
            'github_url' => 'nullable|url|max:255',
            'twitter_handle' => 'nullable|string|max:50|regex:/^@?[A-Za-z0-9_]+$/',
            'telegram_group' => 'nullable|string|max:255',
            'discord_invite' => 'nullable|string|max:255',

            // Logo
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',

            // Listing
            'is_listed' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'คำอธิบายต้องมีอย่างน้อย 50 ตัวอักษร',
            'website.url' => 'กรุณาระบุ URL ที่ถูกต้อง',
            'logo.image' => 'ไฟล์ต้องเป็นรูปภาพ',
            'logo.max' => 'ขนาดไฟล์ต้องไม่เกิน 2MB',
        ];
    }
}
