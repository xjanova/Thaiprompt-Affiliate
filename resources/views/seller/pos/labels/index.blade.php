@extends('layouts.seller-v4')

@section('title', 'พิมพ์ฉลากบาร์โค้ด')

@php
    $statusTone = ['completed' => 'ok', 'printing' => 'info', 'pending' => 'warn', 'failed' => 'bad', 'cancelled' => 'muted'];
    $posCategoryNames = ['product_label' => 'ฉลากสินค้า', 'shipping_label' => 'ใบปะหน้า', 'price_tag' => 'ป้ายราคา', 'barcode_only' => 'บาร์โค้ดอย่างเดียว'];
    $printerNames = ['thermal_roll' => 'เครื่องพิมพ์ความร้อน (ม้วน)', 'thermal_label' => 'เครื่องพิมพ์ความร้อน (สติกเกอร์)', 'inkjet' => 'อิงค์เจ็ท', 'laser' => 'เลเซอร์', 'any' => 'ทุกเครื่อง'];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="พิมพ์ฉลากบาร์โค้ด" icon="🏷️" crumb="ร้านค้า · POS"
                         subtitle="พิมพ์ฉลากราคาและบาร์โค้ดติดสินค้า เพื่อสแกนขายที่หน้าร้านได้รวดเร็ว">
        <a href="{{ route('seller.pos.labels.history') }}" class="tp-btn tp-btn-sm">📜 ประวัติการพิมพ์</a>
    </x-seller-kit.header>

    @include('seller.pos.partials.nav')

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
        <x-seller-kit.stat label="การพิมพ์ทั้งหมด" :value="number_format($stats['total_prints'])" icon="🖨️" tone="info" />
        <x-seller-kit.stat label="พิมพ์วันนี้" :value="number_format($stats['today_prints'])" icon="📅" tone="ok" />
        <x-seller-kit.stat label="พิมพ์เดือนนี้" :value="number_format($stats['this_month_prints'])" icon="🗓️" tone="violet" />
        <x-seller-kit.stat label="ฉลากทั้งหมด" :value="number_format((int) $stats['total_labels']).' ดวง'" icon="🏷️" tone="gold" />
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
        <a href="{{ route('seller.pos.labels.print-product') }}" class="tp-card tp-card-hover" style="text-decoration:none; color:var(--ink); display:flex; align-items:center; gap:16px; padding:22px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 22%, var(--card-bg)), var(--card-bg) 70%);">
            <span class="tp-tile" style="width:58px; height:58px; font-size:26px; border-radius:18px;" aria-hidden="true">🏷️</span>
            <span style="flex:1;">
                <span style="display:block; font-size:16px; font-weight:800;">พิมพ์ฉลากสินค้า</span>
                <span style="display:block; font-size:12.5px; color:var(--ink2); margin-top:3px;">ป้ายราคา สติกเกอร์ติดสินค้า และบาร์โค้ด</span>
            </span>
            <span style="font-size:20px; color:var(--deep1);" aria-hidden="true">→</span>
        </a>
        <a href="{{ route('seller.pos.labels.print-shipping') }}" class="tp-card tp-card-hover" style="text-decoration:none; color:var(--ink); display:flex; align-items:center; gap:16px; padding:22px;">
            <span class="tp-tile" style="width:58px; height:58px; font-size:26px; border-radius:18px; background:linear-gradient(135deg, var(--accent2), var(--deep2));" aria-hidden="true">📦</span>
            <span style="flex:1;">
                <span style="display:block; font-size:16px; font-weight:800;">ใบปะหน้าพัสดุ</span>
                <span style="display:block; font-size:12.5px; color:var(--ink2); margin-top:3px;">พิมพ์ที่อยู่ผู้รับจากออเดอร์ออนไลน์</span>
            </span>
            <span style="font-size:20px; color:var(--deep1);" aria-hidden="true">→</span>
        </a>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:16px;">
        {{-- การพิมพ์ล่าสุด --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 18px;">
                <div class="tp-section-h">🕘 การพิมพ์ล่าสุด</div>
                @if($recentPrints->count() > 0)
                    <a href="{{ route('seller.pos.labels.history') }}" style="font-size:12.5px; font-weight:700; color:var(--deep1); text-decoration:none;">ดูทั้งหมด →</a>
                @endif
            </div>
            @forelse($recentPrints as $print)
                <a href="{{ route('seller.pos.labels.show', $print) }}" style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent); text-decoration:none; color:var(--ink);">
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:700; font-size:13.5px;">{{ $print->print_type_name }}</div>
                        <div style="font-size:11.5px; color:var(--ink2);">{{ number_format($print->total_labels) }} ดวง · {{ $print->template->name ?? 'Template มาตรฐาน' }}</div>
                    </div>
                    <div style="text-align:right;">
                        <x-seller-kit.pill :tone="$statusTone[$print->status] ?? 'muted'">{{ $print->status_name }}</x-seller-kit.pill>
                        <div style="font-size:11px; color:var(--ink2); margin-top:4px;">{{ $print->created_at->diffForHumans() }}</div>
                    </div>
                </a>
            @empty
                <x-seller-kit.empty icon="🖨️" title="ยังไม่มีประวัติการพิมพ์" text="เริ่มพิมพ์ฉลากสินค้าชุดแรกได้เลย" />
            @endforelse
        </div>

        {{-- Template ยอดนิยม --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div class="tp-section-h" style="padding:16px 18px;">⭐ Template ที่ใช้บ่อย</div>
            @forelse($popularTemplates as $template)
                <div style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:700; font-size:13.5px;">{{ $template->name }}</div>
                        <div style="font-size:11.5px; color:var(--ink2);">
                            {{ $posCategoryNames[$template->pos_category] ?? ($template->pos_category ?: 'ทั่วไป') }}
                            · {{ $printerNames[$template->printer_type] ?? ($template->printer_type ?: 'ทุกเครื่อง') }}
                            · {{ rtrim(rtrim(number_format((float) $template->paper_width, 1), '0'), '.') }}×{{ rtrim(rtrim(number_format((float) $template->paper_height, 1), '0'), '.') }} มม.
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div class="tp-num" style="font-weight:800;">{{ number_format((int) $template->usage_count) }}</div>
                        <div style="font-size:10.5px; color:var(--ink2);">ครั้ง</div>
                    </div>
                </div>
            @empty
                <x-seller-kit.empty icon="🧩" title="ยังไม่มี Template" text="ผู้ดูแลระบบจะเพิ่ม Template มาตรฐานให้ใช้งาน" />
            @endforelse
            <div style="padding:12px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent); font-size:11.5px; color:var(--ink2);">
                💡 การออกแบบ Template เองจะเปิดให้ใช้เร็ว ๆ นี้ — ตอนนี้ใช้ Template มาตรฐานของระบบได้ทันที
            </div>
        </div>
    </div>
</div>
@endsection
