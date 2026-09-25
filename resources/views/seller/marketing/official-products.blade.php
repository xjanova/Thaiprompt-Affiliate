@extends('layouts.seller-v4')

@section('title', 'สินค้าใน Official Shop')

@php
    $typeLabels = [
        \App\Models\OfficialShopProduct::TYPE_AI_FEATURED => ['🤖 AI แนะนำ', 'gold'],
        \App\Models\OfficialShopProduct::TYPE_NEW_PRODUCT => ['🆕 สินค้าใหม่', 'info'],
        \App\Models\OfficialShopProduct::TYPE_BEST_SELLER => ['🔥 ขายดี', 'ok'],
    ];
    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink);';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="สินค้าใน Official Shop" icon="🏅" crumb="ร้านค้า · การตลาด"
                         subtitle="สินค้าของร้านที่ถูกคัดขึ้นพื้นที่พิเศษของแพลตฟอร์ม (ทั้งที่แสดงอยู่และเคยแสดง)" />

    @include('seller.marketing.partials.nav')

    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($products->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:760px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">สินค้า</th>
                            <th style="{{ $th }}">ประเภท</th>
                            <th style="{{ $th }} text-align:right;">คะแนน AI</th>
                            <th style="{{ $th }}">คัดเลือกเมื่อ</th>
                            <th style="{{ $th }}">หมดอายุ</th>
                            <th style="{{ $th }} text-align:center;">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $op)
                            @php
                                [$tLabel, $tTone] = $typeLabels[$op->selection_type] ?? [$op->selection_type, 'muted'];
                                $showing = $op->is_active && ! $op->is_expired;
                            @endphp
                            <tr style="{{ $row }}">
                                <td style="{{ $td }}">
                                    <div style="font-weight:700;">{{ $op->product->name ?? 'สินค้าที่ถูกลบ' }}</div>
                                    @if($op->product && $op->product->category)<div style="font-size:11px; color:var(--ink2);">{{ $op->product->category->name }}</div>@endif
                                </td>
                                <td style="{{ $td }}"><x-seller-kit.pill :tone="$tTone">{{ $tLabel }}</x-seller-kit.pill></td>
                                <td style="{{ $td }} text-align:right; font-weight:800;" class="tp-num">{{ $op->ai_score !== null ? number_format((float) $op->ai_score, 1) : '—' }}</td>
                                <td style="{{ $td }} white-space:nowrap;" class="tp-num">{{ optional($op->selected_at)->format('d/m/Y') ?? '—' }}</td>
                                <td style="{{ $td }} white-space:nowrap;">{{ $op->expires_at ? $op->time_remaining : 'ไม่มีกำหนด' }}</td>
                                <td style="{{ $td }} text-align:center;">
                                    <x-seller-kit.pill :tone="$showing ? 'ok' : 'muted'">{{ $showing ? 'กำลังแสดง' : ($op->removed_at ? 'ถูกถอดแล้ว' : 'ไม่แสดง') }}</x-seller-kit.pill>
                                    @if($op->is_locked)<div style="font-size:10.5px; color:var(--ink2); margin-top:3px;">🔒 ล็อกการแก้ไข</div>@endif
                                    @if($op->removal_reason)<div style="font-size:10.5px; color:var(--ink2); margin-top:3px;">{{ \Illuminate\Support\Str::limit($op->removal_reason, 40) }}</div>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($products->hasPages())
                <div style="padding:14px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">{{ $products->links() }}</div>
            @endif
        @else
            <x-seller-kit.empty icon="🏅" title="ยังไม่มีสินค้าใน Official Shop" text="สินค้าที่รีวิวดีและขายดีจะถูก AI คัดเลือกอัตโนมัติ หรือใช้สิทธิ์โปรโมทสินค้าใหม่ได้เลย">
                <a href="{{ route('seller.marketing.select-product') }}" class="tp-btn tp-btn-primary tp-btn-sm">🚀 โปรโมทสินค้าใหม่</a>
            </x-seller-kit.empty>
        @endif
    </div>
</div>
@endsection
