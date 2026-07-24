@extends('layouts.user-v4')

@section('title', 'ที่อยู่จัดส่ง')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px;">
            <div style="display:flex; align-items:center; gap:14px; min-width:0;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:24px; color:var(--deep1);">
                    <i class="fas fa-map-marked-alt"></i>
                </span>
                <div style="min-width:0;">
                    <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">ที่อยู่จัดส่ง · SHIPPING</div>
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:3px 0 0; color:var(--ink);">ที่อยู่จัดส่ง</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">จัดการที่อยู่สำหรับจัดส่งสินค้า</div>
                </div>
            </div>
            <a href="{{ route('shipping-addresses.create') }}"
               class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:11px 20px; border-radius:14px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise);">
                <i class="fas fa-plus"></i> เพิ่มที่อยู่ใหม่
            </a>
        </div>
    </div>

    {{-- ── รายการที่อยู่ ─────────────────────────────────────── --}}
    @forelse($addresses as $address)
        <div class="tp-card" style="padding:20px 22px;">
            <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:16px;">
                {{-- ข้อมูลที่อยู่ --}}
                <div style="flex:1; min-width:220px;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                        <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin:0;">{{ $address->recipient_name }}</h3>
                        @if($address->is_default)
                            <span class="tp-pill" style="background:color-mix(in srgb, var(--accent1) 22%, transparent); color:var(--deep1); font-size:11px; font-weight:700;">ค่าเริ่มต้น</span>
                        @endif
                    </div>
                    <div style="display:flex; flex-direction:column; gap:7px; font-size:13.5px; color:var(--ink2);">
                        <div style="display:flex; align-items:center; gap:9px;"><i class="fas fa-phone" style="width:16px; color:var(--accent1);"></i><span>{{ $address->phone_number }}</span></div>
                        <div style="display:flex; align-items:flex-start; gap:9px;"><i class="fas fa-location-dot" style="width:16px; margin-top:3px; color:var(--accent1);"></i><span style="color:var(--ink);">{{ $address->full_address }}</span></div>
                        @if($address->notes)
                            <div style="display:flex; align-items:flex-start; gap:9px; font-style:italic;"><i class="fas fa-note-sticky" style="width:16px; margin-top:3px; color:var(--accent1);"></i><span>{{ $address->notes }}</span></div>
                        @endif
                    </div>
                </div>

                {{-- ปุ่มจัดการ --}}
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    @if(!$address->is_default)
                        <form action="{{ route('shipping-addresses.set-default', $address->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="tp-btn" style="padding:9px 15px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13px;">
                                ตั้งเป็นค่าเริ่มต้น
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('shipping-addresses.edit', $address->id) }}"
                       class="tp-btn" style="padding:9px 15px; border-radius:11px; background:color-mix(in srgb, var(--accent1) 16%, transparent); color:var(--deep1); font-weight:600; font-size:13px;">
                        <i class="fas fa-pen"></i> แก้ไข
                    </a>
                    <form action="{{ route('shipping-addresses.destroy', $address->id) }}" method="POST" style="display:inline;"
                          onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบที่อยู่นี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tp-btn" style="padding:9px 15px; border-radius:11px; background:color-mix(in srgb, #d9534f 16%, transparent); color:#d9534f; font-weight:600; font-size:13px;">
                            <i class="fas fa-trash"></i> ลบ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        {{-- Empty state --}}
        <div class="tp-card" style="padding:56px 24px; text-align:center;">
            <div style="font-size:56px; margin-bottom:14px;">📭</div>
            <h3 style="font-size:18px; font-weight:800; color:var(--ink); margin:0 0 6px;">ยังไม่มีที่อยู่จัดส่ง</h3>
            <p style="font-size:14px; color:var(--ink2); margin:0 0 22px;">เพิ่มที่อยู่จัดส่งเพื่อใช้ในการสั่งซื้อสินค้า</p>
            <a href="{{ route('shipping-addresses.create') }}"
               class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:11px 22px; border-radius:14px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise);">
                <i class="fas fa-plus"></i> เพิ่มที่อยู่จัดส่ง
            </a>
        </div>
    @endforelse
</div>
@endsection
