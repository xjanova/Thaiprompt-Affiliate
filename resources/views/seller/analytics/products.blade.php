@extends('layouts.seller-v4')

@section('title', 'อันดับสินค้า')

@php
    $rows = collect($products ?? []);
    $top3 = $rows->take(3);
    $medals = ['🥇', '🥈', '🥉'];
    $scoreTone = fn ($s) => $s >= 70 ? 'ok' : ($s >= 40 ? 'warn' : 'bad');
    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink); white-space:nowrap;';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="อันดับสินค้าขายดี" icon="🏆" crumb="ร้านค้า · วิเคราะห์"
                         subtitle="จัดอันดับสินค้าใน 30 วันล่าสุด จากยอดขาย จำนวนชิ้น และจำนวนออเดอร์" />

    @include('seller.analytics.partials.nav')

    @if($rows->isNotEmpty())
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:16px;">
            @foreach($top3 as $i => $p)
                <div class="tp-card" style="display:flex; flex-direction:column; gap:8px; {{ $i === 0 ? 'background:linear-gradient(140deg, color-mix(in srgb, var(--accent1) 24%, var(--card-bg)), var(--card-bg) 70%);' : '' }}">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-size:34px;" aria-hidden="true">{{ $medals[$i] }}</span>
                        <x-seller-kit.pill :tone="$scoreTone((float) $p['performance'])">คะแนน {{ number_format((float) $p['performance'], 0) }}/100</x-seller-kit.pill>
                    </div>
                    <div style="font-weight:800; font-size:15px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $p['product_name'] }}</div>
                    <div class="tp-num" style="font-size:22px; font-weight:800; color:var(--deep1);">฿{{ number_format((float) $p['revenue'], 2) }}</div>
                    <div style="font-size:12px; color:var(--ink2);">{{ number_format((int) $p['orders']) }} ออเดอร์ · {{ number_format((float) $p['quantity_sold']) }} ชิ้น</div>
                </div>
            @endforeach
        </div>

        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div class="tp-section-h" style="padding:16px 18px;">📊 ตารางอันดับทั้งหมด</div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:680px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">อันดับ</th>
                            <th style="{{ $th }}">สินค้า</th>
                            <th style="{{ $th }} text-align:right;">ออเดอร์</th>
                            <th style="{{ $th }} text-align:right;">ขายได้ (ชิ้น)</th>
                            <th style="{{ $th }} text-align:right;">ยอดขาย</th>
                            <th style="{{ $th }} text-align:right;">ราคาเฉลี่ย</th>
                            <th style="{{ $th }} text-align:center;">คะแนน</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $i => $p)
                            <tr style="{{ $row }}">
                                <td style="{{ $td }} font-weight:800; {{ $i < 3 ? 'color:var(--deep1);' : '' }}" class="tp-num">{{ $i + 1 }}</td>
                                <td style="{{ $td }} font-weight:700; white-space:normal; min-width:180px;">{{ $p['product_name'] }}</td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ number_format((int) $p['orders']) }}</td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ number_format((float) $p['quantity_sold']) }}</td>
                                <td style="{{ $td }} text-align:right; font-weight:800;" class="tp-num">฿{{ number_format((float) $p['revenue'], 2) }}</td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">฿{{ number_format((float) $p['avg_price'], 2) }}</td>
                                <td style="{{ $td }} text-align:center;"><x-seller-kit.pill :tone="$scoreTone((float) $p['performance'])">{{ number_format((float) $p['performance'], 0) }}</x-seller-kit.pill></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:12px 18px; font-size:12px; color:var(--ink2); border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                วิธีคิดคะแนน: ยอดขาย 50% · จำนวนชิ้น 30% · จำนวนออเดอร์ 20% (เต็ม 100)
            </div>
        </div>
    @else
        <div class="tp-card">
            <x-seller-kit.empty icon="📦" title="ยังไม่มียอดขายใน 30 วันล่าสุด" text="เมื่อมีออเดอร์ สินค้าขายดีจะถูกจัดอันดับให้อัตโนมัติ">
                <a href="{{ route('seller.products.index') }}" class="tp-btn tp-btn-sm">📦 ไปหน้าสินค้า</a>
            </x-seller-kit.empty>
        </div>
    @endif
</div>
@endsection
