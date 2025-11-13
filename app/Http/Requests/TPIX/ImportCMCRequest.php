<?php

namespace App\Http\Requests\TPIX;

use Illuminate\Foundation\Http\FormRequest;

class ImportCMCRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'cmc_id_or_slug' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'cmc_id_or_slug.required' => 'กรุณาระบุ CoinMarketCap ID หรือ Slug',
        ];
    }
}
