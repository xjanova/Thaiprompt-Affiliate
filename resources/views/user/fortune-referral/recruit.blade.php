{{-- resources/views/user/fortune-referral/recruit.blade.php --}}
@extends('layouts.user-v4')

@section('title', 'ชวนเพื่อนดูดวง')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:900px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:24px; text-align:center; background:linear-gradient(120deg, color-mix(in srgb, #d9a441 20%, transparent), transparent 70%);">
            <img src="{{ auth()->user()->profile_picture_url ?? 'https://ui-avatars.com/api/?name=U&background=f59e0b&color=fff&size=64' }}"
                 alt="avatar" style="width:64px; height:64px; border-radius:50%; box-shadow:var(--raise); margin:0 auto 12px;">
            <h1 style="font-size:clamp(20px,4vw,28px); font-weight:800; margin:0 0 4px;">ชวนเพื่อนมาดูดวง</h1>
            <div style="font-size:13px; color:var(--ink2);">แชร์ลิงก์เชิญเพื่อน → เพื่อนดูดวง → คุณรับคอมมิชชั่นทันที!</div>
        </div>
    </div>

    {{-- ── ลิงก์เชิญเพื่อน ───────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;" x-data="{ copied: false }">
        <div class="tp-section-h" style="margin-bottom:12px;">🔗 ลิงก์เชิญเพื่อน</div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <input type="text" readonly value="{{ $referralLink }}" id="referralLinkInput"
                   class="tp-input" style="flex:1; min-width:200px;">
            <button @click="
                navigator.clipboard.writeText('{{ $referralLink }}').then(() => {
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                });
            " class="tp-btn" style="white-space:nowrap;"
               :style="copied ? 'background:#5aa07e; border-color:#5aa07e; color:#fff;' : 'background:#d9a441; border-color:#d9a441; color:#fff;'">
                <span x-show="!copied">คัดลอก</span>
                <span x-show="copied" x-cloak>คัดลอกแล้ว!</span>
            </button>
        </div>
        <div style="display:flex; gap:10px; margin-top:14px; flex-wrap:wrap;">
            <a href="https://line.me/R/share?text={{ urlencode('มาดูดวงกัน! ' . $referralLink) }}"
               target="_blank" class="tp-btn tp-btn-sm" style="background:#5aa07e; border-color:#5aa07e; color:#fff;">แชร์ LINE</a>
            <button @click="
                navigator.clipboard.writeText('มาดูดวงกัน!\n{{ $referralLink }}').then(() => {
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                });
            " class="tp-btn tp-btn-sm">คัดลอกข้อความ</button>
        </div>
    </div>

    {{-- ── ตัวอย่างรายได้ ────────────────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">💵 ตัวอย่างรายได้จากการแนะนำ</div>

        {{-- 📦 (2026-07-15) โหมดค่าแนะนำแยกตามแพคเกจ --}}
        @php $packageRates = $packageRates ?? null; @endphp
        @if($packageRates)
        <div style="border-radius:14px; box-shadow:var(--inset-sm); padding:18px; margin-bottom:16px;">
            <p style="font-size:13px; color:var(--ink2); margin:0 0 14px;">
                ค่าแนะนำขึ้นกับแพคเกจที่เพื่อนเลือก — เพื่อนจ่ายจริงเมื่อไหร่ คุณได้เมื่อนั้น
            </p>
            <div style="display:flex; flex-direction:column; gap:12px;">
                @foreach($packageRates as $pkg)
                <div class="tp-card" style="padding:14px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                        <span style="font-weight:800; color:var(--ink);">{{ $pkg['label'] }}</span>
                        <span style="font-size:13px; font-weight:600; color:#7c5cbf;">{{ number_format($pkg['price'], 0) }} บาท</span>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:8px; font-size:13px;">
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--ink2);">🤝 สายตรง (เพื่อนที่คุณชวนเอง)</span>
                            <span style="font-weight:800; color:#5aa07e;">+{{ number_format($pkg['l1'], 0) }} บาท</span>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span style="color:var(--ink2);">👶 ชั้นหลาน (เพื่อนของเพื่อน)</span>
                            @if($pkg['l2_enabled'])
                            <span style="font-weight:800; color:#d9a441;">+{{ number_format($pkg['l2'], 0) }} บาท</span>
                            @else
                            <span style="font-weight:500; color:var(--ink2);">แพคเกจนี้ไม่มี</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <p style="font-size:11px; color:var(--ink2); margin:14px 0 0;">
                ค่าแนะนำมี 2 ชั้นเท่านั้น · ชวนได้ไม่จำกัดจำนวนคน ไม่มีเพดานรายได้
            </p>
        </div>
        @else
        <div style="border-radius:14px; box-shadow:var(--inset-sm); padding:18px; margin-bottom:16px;">
            <p style="font-size:13px; color:var(--ink2); margin:0 0 12px;">
                ราคาดูดวง: <span style="font-weight:800; color:#7c5cbf;">{{ number_format($readingPrice, 0) }} บาท</span>
            </p>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <div class="tp-card" style="padding:12px; display:flex; align-items:center; justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:18px;">🤝</span>
                        <div>
                            <div style="font-weight:600; color:var(--ink); font-size:13px;">Level 1 — เพื่อนดูดวง 1 ครั้ง</div>
                            <div style="font-size:11px; color:var(--ink2);">{{ $level1Type === 'fixed' ? 'จำนวนคงที่' : 'เปอร์เซ็นต์ ' . ($settings->fortune_level1_commission_amount ?? 10) . '%' }}</div>
                        </div>
                    </div>
                    <span style="font-weight:800; color:#5aa07e;">+{{ number_format($level1Amount, 2) }} บาท</span>
                </div>
                @if($level2Enabled)
                <div class="tp-card" style="padding:12px; display:flex; align-items:center; justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="font-size:18px;">👶</span>
                        <div>
                            <div style="font-weight:600; color:var(--ink); font-size:13px;">Level 2 — หลานดูดวง 1 ครั้ง</div>
                            <div style="font-size:11px; color:var(--ink2);">{{ $level2Type === 'fixed' ? 'จำนวนคงที่' : 'เปอร์เซ็นต์ ' . ($settings->fortune_level2_commission_amount ?? 5) . '%' }}</div>
                        </div>
                    </div>
                    <span style="font-weight:800; color:#d9a441;">+{{ number_format($level2Amount, 2) }} บาท</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- จำลองรายได้ --}}
        @php
            // โหมดแยกแพคเกจ: อ้างอิง Celtic เป็นตัวอย่าง (แพคเกจที่ค่าแนะนำสูงสุด)
            $simL1 = $packageRates ? $packageRates['celtic_cross']['l1'] : $level1Amount;
            $simL2Enabled = $packageRates ? $packageRates['celtic_cross']['l2_enabled'] : $level2Enabled;
            $simL2 = $packageRates ? $packageRates['celtic_cross']['l2'] : $level2Amount;
            $simLabel = $packageRates ? $packageRates['celtic_cross']['label'] : 'ดูดวง';
            $monthlyEstimate = ($simL1 * 10 * 3) + ($simL2Enabled ? $simL2 * 10 * 5 * 3 : 0);
        @endphp
        <div style="border-radius:14px; box-shadow:var(--inset-sm); padding:18px;">
            <div style="font-size:13px; font-weight:800; color:var(--ink); margin-bottom:12px;">
                จำลองรายได้
                @if($packageRates)
                <span style="font-weight:500; color:var(--ink2);">(ถ้าเพื่อนเลือก {{ $simLabel }})</span>
                @endif
            </div>
            <div style="display:flex; flex-direction:column; gap:8px; font-size:13px;">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--ink2);">ชวน 10 คน × ดูดวง 3 ครั้ง (สายตรง)</span>
                    <span style="font-weight:800; color:#5aa07e;">{{ number_format($simL1 * 10 * 3, 0) }} บาท</span>
                </div>
                @if($simL2Enabled)
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--ink2);">10 คน × ชวนอีกคนละ 5 หลาน × 3 ครั้ง (ชั้นหลาน)</span>
                    <span style="font-weight:800; color:#d9a441;">{{ number_format($simL2 * 10 * 5 * 3, 0) }} บาท</span>
                </div>
                @endif
                <div style="border-top:1px solid color-mix(in srgb, var(--ink2) 20%, transparent); padding-top:8px; display:flex; justify-content:space-between;">
                    <span style="font-weight:800; color:var(--ink);">รวมต่อเดือน (ประมาณ)</span>
                    <span class="tp-num" style="font-size:17px; font-weight:800; color:#7c5cbf;">{{ number_format($monthlyEstimate, 0) }} บาท</span>
                </div>
            </div>
            <p style="font-size:11px; color:var(--ink2); margin:12px 0 0;">
                เป็นเพียงตัวอย่างการคำนวณ ไม่ใช่การรับประกันรายได้ — รายได้จริงขึ้นกับจำนวนเพื่อนที่ใช้บริการจริง
            </p>
        </div>
    </div>

    {{-- ── เทคนิคการแชร์ ─────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
        <div class="tp-card" style="padding:18px;">
            <span style="font-size:24px; display:block; margin-bottom:8px;">🎯</span>
            <div style="font-weight:800; color:var(--ink); margin-bottom:4px;">แชร์ในกลุ่ม LINE</div>
            <div style="font-size:12px; color:var(--ink2);">ส่งลิงก์เชิญในกลุ่มเพื่อน กลุ่มดูดวง กลุ่มที่สนใจเรื่องโชคชะตา</div>
        </div>
        <div class="tp-card" style="padding:18px;">
            <span style="font-size:24px; display:block; margin-bottom:8px;">📱</span>
            <div style="font-weight:800; color:var(--ink); margin-bottom:4px;">โพสต์ใน Social Media</div>
            <div style="font-size:12px; color:var(--ink2);">แชร์ประสบการณ์ดูดวงพร้อมลิงก์เชิญใน Facebook, Instagram, TikTok</div>
        </div>
        <div class="tp-card" style="padding:18px;">
            <span style="font-size:24px; display:block; margin-bottom:8px;">💬</span>
            <div style="font-weight:800; color:var(--ink); margin-bottom:4px;">ส่งให้เพื่อนโดยตรง</div>
            <div style="font-size:12px; color:var(--ink2);">ส่งลิงก์ส่วนตัวให้เพื่อนที่สนใจดูดวง พร้อมบอกข้อดี</div>
        </div>
        <div class="tp-card" style="padding:18px;">
            <span style="font-size:24px; display:block; margin-bottom:8px;">🏆</span>
            <div style="font-weight:800; color:var(--ink); margin-bottom:4px;">ชวนเพื่อนชวนต่อ</div>
            <div style="font-size:12px; color:var(--ink2);">บอกเพื่อนว่าชวนต่อก็ได้เงิน — คุณได้จากหลานด้วย (Level 2)</div>
        </div>
    </div>

    {{-- ── CTA ──────────────────────────────────────────────── --}}
    <div style="text-align:center;">
        <a href="{{ route('user.fortune-referral.commissions') }}" class="tp-btn tp-btn-primary" style="background:#7c5cbf; border-color:#7c5cbf;">ดูคอมมิชชั่นของฉัน</a>
    </div>
</div>
@endsection
