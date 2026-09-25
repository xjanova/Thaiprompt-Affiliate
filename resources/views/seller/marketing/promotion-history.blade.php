@extends('layouts.seller-v4')

@section('title', 'ประวัติโปรโมทสินค้า')

@php
    $statusTone = ['pending' => 'warn', 'active' => 'ok', 'expired' => 'muted', 'cancelled' => 'bad'];
    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink);';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="ประวัติโปรโมทสินค้าใหม่" icon="🕘" crumb="ร้านค้า · การตลาด">
        <a href="{{ route('seller.marketing.select-product') }}" class="tp-btn tp-btn-primary tp-btn-sm">🚀 โปรโมทสินค้า</a>
    </x-seller-kit.header>

    @include('seller.marketing.partials.nav')

    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($promotions->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:720px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">สินค้า</th>
                            <th style="{{ $th }}">ช่วงเวลา</th>
                            <th style="{{ $th }} text-align:right;">เข้าชม</th>
                            <th style="{{ $th }} text-align:right;">ขายได้</th>
                            <th style="{{ $th }} text-align:center;">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($promotions as $promo)
                            <tr style="{{ $row }}">
                                <td style="{{ $td }} font-weight:700;">{{ $promo->product->name ?? 'สินค้าที่ถูกลบ' }}</td>
                                <td style="{{ $td }} white-space:nowrap;" class="tp-num">
                                    {{ optional($promo->starts_at)->format('d/m/Y') ?? '—' }} – {{ optional($promo->ends_at)->format('d/m/Y') ?? '—' }}
                                    @if($promo->time_remaining)<div style="font-size:11px; color:var(--ink2);">{{ $promo->time_remaining }}</div>@endif
                                </td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ number_format((int) $promo->views_during_promo) }}</td>
                                <td style="{{ $td }} text-align:right; font-weight:800;" class="tp-num">{{ number_format((int) $promo->sales_during_promo) }}</td>
                                <td style="{{ $td }} text-align:center;"><x-seller-kit.pill :tone="$statusTone[$promo->status] ?? 'muted'">{{ $promo->status_label }}</x-seller-kit.pill></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($promotions->hasPages())
                <div style="padding:14px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">{{ $promotions->links() }}</div>
            @endif
        @else
            <x-seller-kit.empty icon="🕘" title="ยังไม่เคยโปรโมทสินค้า" text="ใช้สิทธิ์ดันสินค้าใหม่ขึ้นหน้า Official Shop ได้ฟรี">
                <a href="{{ route('seller.marketing.select-product') }}" class="tp-btn tp-btn-primary tp-btn-sm">🚀 เลือกสินค้าโปรโมท</a>
            </x-seller-kit.empty>
        @endif
    </div>
</div>
@endsection
