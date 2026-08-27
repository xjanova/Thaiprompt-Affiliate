@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'แม่หมอเสนอสินค้า')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     Lazada Hub — ตั้งค่าการเสนอสินค้าของบอทแม่หมอ
     เปิด/ปิดรายจุด + หน่วงเวลาก่อนส่ง (นาที)
     ════════════════════════════════════════════════════════════ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- Header --}}
    <div>
        <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
            <a href="{{ route('admin.lazada-hub.dashboard') }}" style="color:var(--accent2);text-decoration:none;">Lazada Hub</a> · แม่หมอเสนอสินค้า
        </div>
        <h1 style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;display:flex;align-items:center;gap:10px;">
            <i class="fas fa-basket-shopping" style="color:var(--accent1);"></i> แม่หมอเสนอสินค้า
        </h1>
        <p class="tp-muted" style="margin:4px 0 0;font-size:.9rem;">เลือกว่าใครจะได้การ์ดสินค้าบ้าง และรอกี่นาทีก่อนส่ง — มีผลทันทีทั้งเฟซบุ๊กและไลน์</p>
    </div>

    @if(session('success'))<div class="tp-card" style="border-left:4px solid #5aa07e;font-size:.88rem;color:var(--ink);"><i class="fas fa-circle-check" style="color:#5aa07e;"></i> {{ session('success') }}</div>@endif
    @if($errors->any())<div class="tp-card" style="border-left:4px solid #d9534f;font-size:.88rem;color:var(--ink);"><i class="fas fa-triangle-exclamation" style="color:#d9534f;"></i> {{ $errors->first() }}</div>@endif

    {{-- คลังของ — ตั้งค่าสวยแค่ไหนก็ไร้ผลถ้าไม่มีของ --}}
    <div class="tp-card" style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;{{ $pool['sendable'] < 10 ? 'border-left:4px solid #e0a52e;' : '' }}">
        <div>
            <div class="tp-muted" style="font-size:.72rem;font-weight:600;">ของสายมูที่ส่งได้จริง</div>
            <div class="tp-num" style="font-size:1.5rem;font-weight:800;color:var(--ink);">{{ number_format($pool['sendable']) }} <span class="tp-muted" style="font-size:.8rem;font-weight:600;">/ {{ number_format($pool['total']) }} ชิ้น</span></div>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            @forelse($pool['groups'] as $group => $n)
                <span class="tp-pill" style="background:var(--surf);color:var(--ink2);font-size:11px;">{{ $group }} · {{ $n }}</span>
            @empty
                <span class="tp-muted" style="font-size:.8rem;">ยังไม่มีของติดป้ายสายมู</span>
            @endforelse
        </div>
        @if($pool['sendable'] < 10)
            <div style="color:#e0a52e;font-size:.8rem;flex:1;min-width:220px;">
                <i class="fas fa-triangle-exclamation"></i> ของน้อยเกินไป — ลูกค้าจะเห็นของวนซ้ำ
                <a href="{{ route('admin.lazada-hub.catalog.import') }}" style="color:var(--accent2);">นำเข้าเพิ่ม</a>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.lazada-hub.mu-offer.update') }}" style="display:flex;flex-direction:column;gap:18px;">
        @csrf
        @method('PUT')

        {{-- สวิตช์ใหญ่ --}}
        <div class="tp-card" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;border-left:4px solid {{ $masterEnabled ? '#5aa07e' : 'var(--ink2)' }};">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="hidden" name="enabled" value="0">
                <input type="checkbox" name="enabled" value="1" @checked($masterEnabled) style="width:20px;height:20px;accent-color:#5aa07e;cursor:pointer;">
                <span style="font-weight:800;color:var(--ink);font-size:1rem;">เปิดระบบเสนอสินค้า</span>
            </label>
            <span class="tp-muted" style="font-size:.82rem;">ปิดตัวนี้ = ไม่ส่งการ์ดให้ใครเลย ไม่ว่าจะติ๊กจุดไหนไว้</span>
        </div>

        {{-- จุดยิง --}}
        <div class="tp-card" style="padding:0;overflow:hidden;">
            <div style="padding:14px 16px;">
                <div style="font-weight:700;color:var(--ink);"><i class="fas fa-bullseye" style="color:var(--accent1);"></i> ส่งให้ใครบ้าง</div>
                <p class="tp-muted" style="font-size:.8rem;margin:4px 0 0;">ติ๊ก = ส่ง · ช่องนาที = รอกี่นาทีหลังเหตุการณ์ถึงจะส่ง (0 = ส่งทันที)</p>
            </div>

            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.85rem;min-width:640px;">
                    <thead><tr style="background:var(--surf);color:var(--ink2);text-align:left;">
                        <th style="padding:9px 16px;width:44px;"></th>
                        <th style="padding:9px 12px;">เหตุการณ์</th>
                        <th style="padding:9px 12px;text-align:center;width:130px;">หน่วงก่อนส่ง</th>
                        <th style="padding:9px 16px;text-align:right;width:150px;">ส่งไปแล้ว (7 วัน)</th>
                    </tr></thead>
                    <tbody>
                    @foreach($triggers as $key => $meta)
                        @php
                            // จุดยิงที่ปิดรายจุดไม่ได้ (ลูกค้าถามหาของเอง) — ติ๊กค้างไว้เสมอ
                            $locked = ! empty($meta['always_on']);
                            $on = $locked || in_array($key, $enabledList, true);
                            $stat = $stats[$key] ?? ['cards' => 0, 'people' => 0];
                        @endphp
                        <tr style="border-top:1px solid color-mix(in srgb,var(--ink2) 14%,transparent);{{ $on ? '' : 'opacity:.55;' }}">
                            <td style="padding:10px 16px;">
                                @if($locked)
                                    {{-- ปิดไม่ได้ — ห้ามใส่ hidden คู่: ถ้าโพสต์ค่ากลับไปด้วย
                                         เงื่อนไข "ไม่ติ๊กอะไรเลย = ปิดสวิตช์ใหญ่" ในคอนโทรลเลอร์จะไม่มีวันเป็นจริง
                                         ⇒ ฝั่งคอนโทรลเลอร์เติม ALWAYS_ON_TRIGGERS ให้เองหลังเช็คว่าง --}}
                                    <input type="checkbox" checked disabled title="ปิดรายจุดไม่ได้ — ปิดได้ที่สวิตช์ใหญ่ทางเดียว"
                                           style="width:19px;height:19px;accent-color:#5aa07e;cursor:not-allowed;opacity:.75;">
                                @else
                                    <input type="checkbox" name="triggers[]" value="{{ $key }}" @checked($on)
                                           style="width:19px;height:19px;accent-color:#5aa07e;cursor:pointer;">
                                @endif
                            </td>
                            <td style="padding:10px 12px;">
                                <div style="font-weight:700;color:var(--ink);">
                                    <i class="fas {{ $meta['icon'] }}" style="color:var(--accent2);width:16px;"></i> {{ $meta['label'] }}
                                </div>
                                <div class="tp-muted" style="font-size:.75rem;margin-top:2px;">{{ $meta['hint'] }}</div>
                            </td>
                            <td style="padding:10px 12px;text-align:center;white-space:nowrap;">
                                @if($locked)
                                    {{-- ลูกค้าถามเอง = ต้องตอบเดี๋ยวนั้น หน่วงไม่ได้
                                         (input ที่ disabled ไม่โพสต์ค่า → คอนโทรลเลอร์บันทึกเป็น 0 ให้เอง) --}}
                                    <span class="tp-muted" style="font-size:.78rem;font-weight:600;">ทันที</span>
                                @else
                                    <input type="number" name="delays[{{ $key }}]" value="{{ $delays[$key] ?? 0 }}"
                                           min="0" max="1440" class="tp-input tp-num" style="width:74px;text-align:center;">
                                    <span class="tp-muted" style="font-size:.74rem;">นาที</span>
                                @endif
                            </td>
                            <td class="tp-num" style="padding:10px 16px;text-align:right;color:var(--ink2);font-size:.78rem;">
                                @if($stat['cards'] > 0)
                                    {{ number_format($stat['cards']) }} ใบ<br>
                                    <span style="font-size:.7rem;">{{ number_format($stat['people']) }} คน</span>
                                @else
                                    <span style="opacity:.6;">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if(($stats[\App\Models\FortuneProductOffer::TRIGGER_GESTURE]['cards'] ?? 0) > 0)
                <div class="tp-muted" style="padding:10px 16px;font-size:.75rem;border-top:1px solid color-mix(in srgb,var(--ink2) 14%,transparent);">
                    <i class="fas fa-clock-rotate-left"></i> ก่อนหน้านี้ทั้ง 3 ท่ารวมเป็นรายการเดียว — ประวัติเดิม
                    {{ number_format($stats[\App\Models\FortuneProductOffer::TRIGGER_GESTURE]['cards']) }} ใบ /
                    {{ number_format($stats[\App\Models\FortuneProductOffer::TRIGGER_GESTURE]['people']) }} คน จึงยังไม่แยกท่า
                </div>
            @endif
        </div>

        {{-- เพดาน --}}
        <div class="tp-card">
            <div style="font-weight:700;color:var(--ink);margin-bottom:4px;"><i class="fas fa-gauge-high" style="color:var(--accent1);"></i> เพดานความถี่</div>
            <p class="tp-muted" style="font-size:.8rem;margin:0 0 14px;">กันไม่ให้ลูกค้าคนเดียวโดนยิงรัว — ลูกค้าที่ถามหาของเองไม่ติดเพดานพวกนี้</p>

            <div style="display:flex;gap:18px;flex-wrap:wrap;align-items:flex-end;">
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">บอทเสนอเอง (ครั้ง/คน/วัน)</label>
                    <input type="number" name="daily_cap" value="{{ $dailyCap }}" min="0" max="20" class="tp-input tp-num" style="width:100px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">0 = ไม่จำกัด</div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">ท้ายบิลที่จ่ายแล้ว (ครั้ง/คน/วัน)</label>
                    <input type="number" name="paid_end_cap" value="{{ $paidEndCap }}" min="0" max="20" class="tp-input tp-num" style="width:100px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">โควตาแยก ไม่ให้ของฟรีมาแย่ง</div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">ลูกค้าบอกรำคาญ → เงียบ (วัน)</label>
                    <input type="number" name="mute_days" value="{{ $muteDays }}" min="1" max="365" class="tp-input tp-num" style="width:100px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">“ไม่เอา” เฉยๆ ยังส่งอยู่</div>
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.72rem;font-weight:600;margin-bottom:4px;">โหมดช้อปหลังเห็นการ์ด (ชม.)</label>
                    <input type="number" name="shop_mode_hours" value="{{ $shopModeHours }}" min="0" max="168" class="tp-input tp-num" style="width:100px;">
                    <div class="tp-muted" style="font-size:.7rem;margin-top:3px;">คนที่เพิ่งเห็นการ์ด พิมพ์ “ราคา/เอา/อันไหน” = ถามเรื่องของ · 0 = ปิด</div>
                </div>
            </div>

            <p class="tp-muted" style="font-size:.75rem;margin:14px 0 0;line-height:1.6;">
                <i class="fas fa-circle-info" style="color:var(--accent2);"></i>
                ในโหมดช้อป บอทจะเห็นชื่อ–ราคา–ลิงก์ของจริงที่เพิ่งเสนอไป จึงตอบราคาได้ตรงและวางลิงก์ให้เองได้
                นอกโหมดนี้ใช้เกณฑ์เข้ม (ต้องมีคำว่าซื้อ/สั่ง/หาของ ชัดๆ) เพื่อไม่ให้บอทเด้งไปขายของกลางวงดูดวง
            </p>
        </div>

        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <button class="tp-btn tp-btn-primary" style="height:42px;"><i class="fas fa-floppy-disk"></i> <span>บันทึกการตั้งค่า</span></button>
            <span class="tp-muted" style="font-size:.78rem;">ไม่ติ๊กเลยสักจุด = ระบบจะปิดสวิตช์ใหญ่ให้แทน (ไม่ใช่เปิดหมด)</span>
        </div>
    </form>

</div>
@endsection
