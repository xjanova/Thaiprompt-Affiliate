@extends('layouts.seller-v4')

@section('title', 'อุปกรณ์ POS')

@php
    $subMap = ['active' => ['ใช้งาน', 'ok'], 'trial' => ['ทดลองใช้', 'info'], 'expired' => ['หมดอายุ', 'bad'], 'suspended' => ['ระงับ', 'bad'], 'cancelled' => ['ยกเลิก', 'muted']];
    $typeIcons = ['premium' => '💎', 'web' => '🌐', 'tablet' => '📲', 'desktop' => '🖥️'];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="อุปกรณ์ POS" icon="📱" crumb="ร้านค้า · POS"
                         subtitle="อุปกรณ์ขายหน้าร้านทั้งหมดของร้าน (รวมหน้าขายบนเว็บที่ระบบสร้างให้อัตโนมัติ)">
        <a href="{{ route('seller.pos.terminals') }}" class="tp-btn tp-btn-sm">🖥️ ลงทะเบียนเครื่อง POS</a>
    </x-seller-kit.header>

    @include('seller.pos.partials.nav')

    @if($devices->count() > 0)
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(270px,1fr)); gap:16px;">
            @foreach($devices as $device)
                @php
                    [$subLabel, $subTone] = $subMap[$device->subscription_status] ?? [$device->subscription_status ?: 'ไม่ระบุ', 'muted'];
                    $todayQuery = $device->transactions()->whereDate('transaction_date', today());
                    $todayCount = (clone $todayQuery)->count();
                    $todaySales = (float) (clone $todayQuery)->sum('total_amount');
                @endphp
                <div class="tp-card" style="padding:18px; display:flex; flex-direction:column; gap:12px;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="tp-tile" style="width:46px; height:46px; font-size:22px; border-radius:14px;" aria-hidden="true">{{ $typeIcons[$device->device_type] ?? '📱' }}</span>
                        <div style="min-width:0; flex:1;">
                            <div style="font-weight:800; font-size:14.5px; overflow-wrap:anywhere;">{{ $device->device_name }}</div>
                            <div class="tp-num" style="font-size:11px; color:var(--ink2);">{{ $device->device_code ?? '—' }}</div>
                        </div>
                        <x-seller-kit.pill :tone="$device->is_online ? 'ok' : 'muted'">{{ $device->is_online ? '● ออนไลน์' : '○ ออฟไลน์' }}</x-seller-kit.pill>
                    </div>

                    <div style="display:flex; justify-content:space-between; font-size:12.5px;">
                        <span style="color:var(--ink2);">สถานะแพ็กเกจ</span>
                        <x-seller-kit.pill :tone="$subTone">{{ $subLabel }}</x-seller-kit.pill>
                    </div>
                    @if($device->subscription_ends_at)
                        <div style="display:flex; justify-content:space-between; font-size:12.5px;">
                            <span style="color:var(--ink2);">หมดอายุ</span>
                            <span class="tp-num" style="font-weight:700;">{{ $device->subscription_ends_at->format('d/m/Y') }}</span>
                        </div>
                    @endif

                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                        @if($device->dual_screen_enabled)<span class="tp-pill tp-pill-soft">🖥️ จอลูกค้า</span>@endif
                        @if($device->offline_mode_enabled)<span class="tp-pill tp-pill-soft">📡 ใช้ออฟไลน์ได้</span>@endif
                        @if($device->receipt_printer_enabled)<span class="tp-pill tp-pill-soft">🖨️ เครื่องพิมพ์</span>@endif
                    </div>

                    <div class="tp-inset-sm" style="border-radius:14px; padding:10px 12px; display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                        <div>
                            <div style="font-size:11px; color:var(--ink2);">รายการวันนี้</div>
                            <div class="tp-num" style="font-weight:800; font-size:17px;">{{ number_format($todayCount) }}</div>
                        </div>
                        <div>
                            <div style="font-size:11px; color:var(--ink2);">ยอดขายวันนี้</div>
                            <div class="tp-num" style="font-weight:800; font-size:17px; color:var(--deep1);">฿{{ number_format($todaySales, 2) }}</div>
                        </div>
                    </div>

                    <a href="{{ route('seller.pos.devices.show', $device) }}" class="tp-btn tp-btn-sm" style="width:100%;">ดูรายละเอียด →</a>
                </div>
            @endforeach
        </div>

        @if($devices->hasPages())
            <div>{{ $devices->links() }}</div>
        @endif
    @else
        <div class="tp-card">
            <x-seller-kit.empty icon="📱" title="ยังไม่มีอุปกรณ์ POS" text="เปิดหน้าขายหน้าร้านครั้งแรกระบบจะสร้างอุปกรณ์ “Web POS” ให้อัตโนมัติ หรือเชื่อมโปรแกรม POS บนคอมพิวเตอร์ด้วย API Key">
                <a href="{{ route('seller.pos.terminal') }}" class="tp-btn tp-btn-primary tp-btn-sm">🛒 เปิดหน้าขาย</a>
                <a href="{{ route('seller.pos.terminals') }}" class="tp-btn tp-btn-sm">🖥️ สร้าง API Key</a>
            </x-seller-kit.empty>
        </div>
    @endif
</div>
@endsection
