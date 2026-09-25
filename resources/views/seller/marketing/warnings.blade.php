@extends('layouts.seller-v4')

@section('title', 'การแจ้งเตือน Official Shop')

@php
    $statusTone = ['pending' => 'warn', 'improved' => 'ok', 'expired' => 'bad', 'removed' => 'muted'];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="การแจ้งเตือน Official Shop" icon="⚠️" crumb="ร้านค้า · การตลาด"
                         subtitle="สินค้าที่คะแนนต่ำกว่าเกณฑ์จะได้รับแจ้งเตือนก่อนถูกถอดจาก Official Shop — ปรับปรุงให้ทันเวลาที่กำหนด" />

    @include('seller.marketing.partials.nav')

    @if($warnings->count() > 0)
        <div style="display:flex; flex-direction:column; gap:12px;">
            @foreach($warnings as $warning)
                @php
                    $gap = max(0, (float) $warning->required_score - (float) $warning->current_score);
                    $isPending = $warning->status === \App\Models\OfficialShopWarning::STATUS_PENDING;
                @endphp
                <div class="tp-card" style="display:flex; flex-wrap:wrap; gap:14px; align-items:flex-start; {{ $isPending ? 'border-left:4px solid var(--tp-warn, #e0a52e);' : '' }}">
                    <div style="flex:1 1 280px; min-width:0;">
                        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                            <span style="font-weight:800; font-size:14.5px;">{{ $warning->product->name ?? 'สินค้า' }}</span>
                            <x-seller-kit.pill :tone="$statusTone[$warning->status] ?? 'muted'">{{ $warning->status_label }}</x-seller-kit.pill>
                        </div>
                        @if($warning->warning_message)
                            <div style="font-size:13px; line-height:1.65; margin-top:6px;">{{ $warning->warning_message }}</div>
                        @endif
                        <div style="font-size:12px; color:var(--ink2); margin-top:6px;">
                            แจ้งเมื่อ {{ optional($warning->warned_at ?? $warning->created_at)->format('d/m/Y H:i') }}
                            @if($warning->deadline_at) · กำหนดปรับปรุงภายใน {{ $warning->deadline_at->format('d/m/Y H:i') }} ({{ $warning->time_remaining }})@endif
                        </div>
                        @if($warning->resolution_note)
                            <div style="font-size:12px; margin-top:6px;"><span style="color:var(--ink2);">หมายเหตุ:</span> {{ $warning->resolution_note }}</div>
                        @endif
                    </div>
                    <div class="tp-inset-sm" style="border-radius:14px; padding:12px 14px; min-width:200px;">
                        <div style="display:flex; justify-content:space-between; font-size:12px;"><span style="color:var(--ink2);">คะแนนตอนนี้</span><span class="tp-num" style="font-weight:800;">{{ number_format((float) $warning->current_score, 1) }}</span></div>
                        <div style="display:flex; justify-content:space-between; font-size:12px; margin-top:4px;"><span style="color:var(--ink2);">คะแนนที่ต้องการ</span><span class="tp-num" style="font-weight:800;">{{ number_format((float) $warning->required_score, 1) }}</span></div>
                        @if($isPending && $gap > 0)
                            <div style="font-size:12px; color:var(--tp-bad, #d9534f); margin-top:6px; font-weight:700;">ขาดอีก {{ number_format($gap, 1) }} คะแนน</div>
                        @endif
                        @if($warning->product)
                            <a href="{{ route('seller.products.edit', $warning->product->id) }}" class="tp-btn tp-btn-sm" style="width:100%; margin-top:8px;">✏️ ปรับปรุงสินค้า</a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        @if($warnings->hasPages())
            <div>{{ $warnings->links() }}</div>
        @endif
    @else
        <div class="tp-card">
            <x-seller-kit.empty icon="✅" title="ไม่มีการแจ้งเตือน" text="สินค้าใน Official Shop ของคุณผ่านเกณฑ์ทั้งหมด" />
        </div>
    @endif

    <div class="tp-card" style="background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 14%, transparent), transparent 70%);">
        <div class="tp-section-h">💡 วิธีเพิ่มคะแนน</div>
        <ul style="margin:8px 0 0; padding-left:18px; font-size:13px; line-height:1.8;">
            <li>ตอบรีวิวและจัดส่งให้ไว เพื่อให้ได้คะแนนรีวิวที่ดี</li>
            <li>ใส่รูปสินค้าให้ครบ ชัด และรายละเอียดตรงความจริง</li>
            <li>ใช้คูปองร้านกระตุ้นยอดขายช่วงที่ยอดตก</li>
        </ul>
    </div>
</div>
@endsection
