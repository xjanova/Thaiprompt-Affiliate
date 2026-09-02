@extends('layouts.user-v4')

@section('title', 'ประวัติอัพเกรดดาว')

@section('content')
{{--
    ⭐ ประวัติอัพเกรดดาว (ธีม V4 นวลทองคำ)
    ของเดิมไม่มี @extends/@section เลย (มีแต่ @endsection ค้างท้ายไฟล์)
    → หน้าเรนเดอร์เป็นเศษ HTML ไม่มี head/CSS/เมนู เหมือนหน้า index
--}}
@php
    // path ของไอคอนดาว — ใช้ซ้ำหลายที่
    $starPath = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
@endphp

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Hero ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, #8a63c9 20%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:22px; display:flex; align-items:center; justify-content:center; background:#8a63c9;">
                        <i class="fas fa-clock-rotate-left" style="color:#fff;"></i>
                    </span>
                    <div>
                        <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ประวัติอัพเกรดดาว</h1>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">รายการอัพเกรดดาวทั้งหมดของคุณ</div>
                    </div>
                </div>
                <a href="{{ route('user.star-upgrade.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-arrow-left"></i> กลับไปหน้าอัพเกรด
                </a>
            </div>
        </div>
    </div>

    {{-- ===== KPI ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px;">
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#5689b8;">
                    <i class="fas fa-list-check"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['total_upgrades']) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">อัพเกรดทั้งหมด</div>
                </div>
            </div>
        </div>

        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-coins"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['total_coins_spent'], 0) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">Coins ที่ใช้ไป</div>
                </div>
            </div>
        </div>

        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#d6824a;">
                    <i class="fas fa-star"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ $stats['highest_star'] }} ดาว</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">ดาวสูงสุดที่เคยซื้อ</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ตาราง ===== --}}
    @if($history->count() > 0)
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="min-width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="padding:14px 18px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">วันที่</th>
                            <th style="padding:14px 18px; text-align:center; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">จาก</th>
                            <th style="padding:14px 8px;  text-align:center; font-size:11px; font-weight:700; color:var(--ink2);"></th>
                            <th style="padding:14px 18px; text-align:center; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">เป็น</th>
                            <th style="padding:14px 18px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">ราคา</th>
                            <th style="padding:14px 18px; text-align:center; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $item)
                            <tr style="box-shadow:var(--inset-sm); transition:background .15s;"
                                onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                                {{-- วันที่ --}}
                                <td style="padding:14px 18px; white-space:nowrap;">
                                    <div style="font-size:13.5px; font-weight:600; color:var(--ink);">{{ $item->created_at->format('d/m/Y') }}</div>
                                    <div style="font-size:11.5px; color:var(--ink2);">{{ $item->created_at->format('H:i:s') }}</div>
                                </td>

                                {{-- จาก --}}
                                <td style="padding:14px 18px; text-align:center; white-space:nowrap;">
                                    <div style="display:flex; justify-content:center; align-items:center; gap:2px;">
                                        @for($i = 0; $i < $item->from_stars; $i++)
                                            <svg viewBox="0 0 20 20" fill="color-mix(in srgb, var(--ink2) 65%, transparent)" style="width:17px; height:17px;"><path d="{{ $starPath }}"/></svg>
                                        @endfor
                                    </div>
                                    <div style="font-size:11.5px; color:var(--ink2); margin-top:4px;">{{ $item->from_stars }} ดาว</div>
                                </td>

                                {{-- ลูกศร --}}
                                <td style="padding:14px 8px; text-align:center;">
                                    <i class="fas fa-arrow-right" style="color:var(--ink2); opacity:.6;"></i>
                                </td>

                                {{-- เป็น --}}
                                <td style="padding:14px 18px; text-align:center; white-space:nowrap;">
                                    <div style="display:flex; justify-content:center; align-items:center; gap:2px;">
                                        @for($i = 0; $i < $item->to_stars; $i++)
                                            <svg viewBox="0 0 20 20" fill="#e0a52e" style="width:17px; height:17px;"><path d="{{ $starPath }}"/></svg>
                                        @endfor
                                    </div>
                                    <div style="font-size:11.5px; color:var(--ink2); margin-top:4px;">{{ $item->to_stars }} ดาว</div>
                                </td>

                                {{-- ราคา --}}
                                <td style="padding:14px 18px; text-align:right; white-space:nowrap;">
                                    <div class="tp-num" style="font-size:16px; font-weight:800; color:var(--deep1);">{{ number_format($item->coins_paid, 0) }}</div>
                                    <div style="font-size:11.5px; color:var(--ink2);">Coins</div>
                                </td>

                                {{-- สถานะ --}}
                                <td style="padding:14px 18px; text-align:center; white-space:nowrap;">
                                    @if($item->status === 'completed')
                                        <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c;">
                                            <i class="fas fa-check"></i> สำเร็จ
                                        </span>
                                    @else
                                        <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f;">
                                            <i class="fas fa-rotate-left"></i> คืนเงิน
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($history->hasPages())
            <div>{{ $history->appends(request()->query())->links() }}</div>
        @endif
    @else
        {{-- Empty state --}}
        <div class="tp-card" style="padding:56px 20px; text-align:center;">
            <div style="font-size:44px; margin-bottom:12px;">⭐</div>
            <div style="font-size:17px; font-weight:800; color:var(--ink); margin-bottom:6px;">ยังไม่มีประวัติการอัพเกรด</div>
            <p style="font-size:13px; color:var(--ink2); margin:0 0 20px;">เริ่มต้นอัพเกรดดาวเพื่อรับสิทธิประโยชน์พิเศษ</p>
            <a href="{{ route('user.star-upgrade.index') }}" class="tp-btn tp-btn-primary" style="font-weight:700;">
                <i class="fas fa-star"></i> ไปอัพเกรดดาว
            </a>
        </div>
    @endif
</div>
@endsection
